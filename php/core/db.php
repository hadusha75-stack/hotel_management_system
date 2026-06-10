<?php
require_once __DIR__ . '/config.php';

// ============================================================
// DATABASE ABSTRACTION LAYER
// Works with both MySQL (mysqli) and PostgreSQL (PDO)
// All PHP files use: $conn = getConn();
// Then use $conn exactly like mysqli
// ============================================================

function getConn() {
    static $conn = null;
    if ($conn !== null) return $conn;

    if (DB_TYPE === 'pgsql') {
        // PostgreSQL via PDO wrapped in mysqli-compatible class
        $conn = new PgConn();
    } else {
        // MySQL via native mysqli
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Keep getDB() for API files that call getDB()
function getDB() {
    return getConn();
}

// ============================================================
// PostgreSQL wrapper that mimics mysqli interface
// ============================================================
class PgConn {
    private PDO $pdo;
    public  int $errno    = 0;
    public  string $error = '';

    public function __construct() {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("PostgreSQL connection failed: " . $e->getMessage());
        }
    }

    // $conn->query("SELECT ...")
    public function query(string $sql): PgResult|bool {
        // Convert MySQL-specific syntax to PostgreSQL
        $sql = $this->convertSql($sql);
        try {
            $stmt = $this->pdo->query($sql);
            $this->errno = 0; $this->error = '';
            return new PgResult($stmt);
        } catch (PDOException $e) {
            $this->errno = (int)$e->getCode();
            $this->error = $e->getMessage();
            return false;
        }
    }

    // $conn->prepare("SELECT ... WHERE x=?")
    public function prepare(string $sql): PgStmt|bool {
        $sql = $this->convertSql($sql);
        try {
            $stmt = $this->pdo->prepare($sql);
            return new PgStmt($stmt, $this->pdo);
        } catch (PDOException $e) {
            $this->errno = (int)$e->getCode();
            $this->error = $e->getMessage();
            return false;
        }
    }

    // $conn->real_escape_string($val)
    public function real_escape_string(string $val): string {
        // PDO quote() adds surrounding quotes — we strip them
        return trim($this->pdo->quote($val), "'");
    }

    // $conn->close()
    public function close(): void { /* PDO closes on destruct */ }

    // Last insert id
    public function insert_id(): string {
        return $this->pdo->lastInsertId();
    }

    // Property access: $conn->insert_id
    public function __get(string $name): mixed {
        if ($name === 'insert_id') return $this->pdo->lastInsertId();
        if ($name === 'errno')     return $this->errno;
        if ($name === 'error')     return $this->error;
        return null;
    }

    // Rows affected by last INSERT/UPDATE/DELETE
    public int $affected_rows = 0;

    // Convert common MySQL syntax → PostgreSQL
    private function convertSql(string $sql): string {
        // NOW() works in both
        // CURDATE() → CURRENT_DATE
        $sql = str_ireplace('CURDATE()', 'CURRENT_DATE', $sql);
        // DATE_FORMAT → TO_CHAR
        $sql = preg_replace_callback(
            "/DATE_FORMAT\s*\(([^,]+),\s*'([^']+)'\)/i",
            function($m) {
                $col = trim($m[1]);
                $fmt = str_replace(['%Y','%m','%d','%H','%i','%s'], ['YYYY','MM','DD','HH24','MI','SS'], $m[2]);
                return "TO_CHAR($col, '$fmt')";
            },
            $sql
        );
        // DATE_SUB(x, INTERVAL n MONTH) → x - INTERVAL 'n months'
        $sql = preg_replace_callback(
            "/DATE_SUB\s*\(([^,]+),\s*INTERVAL\s+(\d+)\s+(\w+)\)/i",
            fn($m) => "({$m[1]} - INTERVAL '{$m[2]} {$m[3]}')",
            $sql
        );
        // DATEDIFF(a,b) → EXTRACT(EPOCH FROM (a::timestamp - b::timestamp))/86400
        $sql = preg_replace_callback(
            "/DATEDIFF\s*\(([^,]+),\s*([^)]+)\)/i",
            fn($m) => "EXTRACT(DAY FROM ({$m[1]}::date - {$m[2]}::date))",
            $sql
        );
        // status='not booked' — no change needed (same in PG)
        return $sql;
    }
}

// ── Result wrapper ────────────────────────────────────────────
class PgResult {
    private array $rows;
    private int   $pos = 0;
    public  int   $num_rows;

    public function __construct(PDOStatement $stmt) {
        $this->rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->num_rows = count($this->rows);
    }
    public function fetch_assoc(): ?array {
        return $this->rows[$this->pos++] ?? null;
    }
    // Alias for fetch_assoc
    public function fetch(): ?array {
        return $this->fetch_assoc();
    }
    public function fetch_row(): ?array {
        $row = $this->rows[$this->pos++] ?? null;
        return $row ? array_values($row) : null;
    }
    public function fetch_all(int $mode = MYSQLI_ASSOC): array {
        return $this->rows;
    }
    // mysqli compatibility alias
    public function rowCount(): int {
        return $this->num_rows;
    }
    public function close(): void {}
}

// ── Statement wrapper ─────────────────────────────────────────
class PgStmt {
    private PDOStatement $stmt;
    private PDO          $pdo;
    private array        $params = [];
    private array        $types  = [];
    private array        $refs   = [];
    public  string       $error  = '';
    public  int          $affected_rows = 0;

    public function __construct(PDOStatement $stmt, PDO $pdo) {
        $this->stmt = $stmt;
        $this->pdo  = $pdo;
    }

    // bind_param("ssi", $a, $b, $c)
    public function bind_param(string $types, &...$vars): bool {
        $this->types = str_split($types);
        $this->refs  = $vars;
        return true;
    }

    public function execute(): bool {
        $params = [];
        foreach ($this->refs as $i => $val) {
            $type = $this->types[$i] ?? 's';
            $params[] = match($type) {
                'i' => (int)$val,
                'd' => (float)$val,
                default => (string)($val ?? '')
            };
        }
        try {
            $this->stmt->execute($params);
            $this->affected_rows = $this->stmt->rowCount();
            $this->error = '';
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function get_result(): PgResult {
        // After execute(), re-fetch results
        return new PgResult($this->stmt);
    }

    public function rowCount(): int {
        return $this->affected_rows;
    }

    public function close(): void { /* nothing needed */ }

    public function insert_id(): string {
        return $this->pdo->lastInsertId();
    }
}

// No mysqli aliases needed — PgConn implements query/prepare/etc directly
// All code uses $conn->query() / $conn->prepare() which PgConn handles natively
