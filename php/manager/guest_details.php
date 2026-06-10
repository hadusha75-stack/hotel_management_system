<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
$conn = getDB();
$backUrl   = isset($_GET["from"]) && $_GET["from"]==="finance" ? "../../html/dashboards/finance.html" : "../../html/dashboards/manager.html";
$backLabel = isset($_GET["from"]) && $_GET["from"]==="finance" ? "Finance Dashboard"    : "Manager Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details — Sabawyan Hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Variables ── */
        :root {
            --gold:        #c9a84c;
            --gold-light:  #e8c97a;
            --gold-dark:   #a07830;
            --navy:        #0d1b2a;
            --navy-mid:    #1a2e45;
            --navy-light:  #243b55;
            --cream:       #faf7f2;
            --white:       #ffffff;
            --success:     #2ecc71;
            --danger:      #e74c3c;
            --text-dark:   #1a1a2e;
            --text-mid:    #4a4a6a;
            --text-light:  #8a8aaa;
            --shadow-sm:   0 4px 12px rgba(0,0,0,0.12);
            --shadow-md:   0 8px 28px rgba(0,0,0,0.18);
            --shadow-lg:   0 16px 48px rgba(0,0,0,0.28);
            --shadow-gold: 0 8px 24px rgba(201,168,76,0.25);
            --radius-sm:   6px;
            --radius-md:   12px;
            --radius-lg:   20px;
            --transition:  all 0.3s ease;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0d1b2a 0%, #1a2e45 50%, #243b55 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--text-dark);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: url('../../photo/hotelnamebg.jpg') center/cover no-repeat;
            opacity: 0.05;
            z-index: 0;
        }

        /* ── Top Bar ── */
        .topbar {
            position: relative;
            z-index: 10;
            background: rgba(13,27,42,0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(201,168,76,0.2);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-icon i { font-size: 18px; color: var(--navy); }
        .brand-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: #fff;
            line-height: 1.2;
        }
        .brand-text span {
            font-size: 12px;
            color: var(--gold-light);
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 20px;
            background: rgba(201,168,76,0.12);
            border: 1px solid rgba(201,168,76,0.35);
            border-radius: var(--radius-sm);
            color: var(--gold-light);
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
        }
        .back-btn:hover {
            background: rgba(201,168,76,0.22);
            border-color: var(--gold);
            color: var(--gold);
            transform: translateX(-2px);
        }

        /* ── Page Content ── */
        .page-content {
            position: relative;
            z-index: 1;
            padding: 40px 32px 60px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ── Page Header ── */
        .page-header {
            margin-bottom: 32px;
        }
        .page-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            color: #fff;
            margin-bottom: 6px;
        }
        .page-header p {
            font-size: 14px;
            color: rgba(255,255,255,0.45);
        }
        .gold-divider {
            width: 56px;
            height: 3px;
            background: linear-gradient(90deg, var(--gold-dark), var(--gold-light));
            border-radius: 2px;
            margin: 10px 0 0;
        }

        /* ── Stats Bar ── */
        .stats-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(201,168,76,0.18);
            border-radius: var(--radius-md);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 180px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .stat-icon.gold  { background: rgba(201,168,76,0.15); color: var(--gold); }
        .stat-icon.blue  { background: rgba(52,152,219,0.15); color: #3498db; }
        .stat-icon.green { background: rgba(46,204,113,0.15); color: var(--success); }
        .stat-icon.purple{ background: rgba(155,89,182,0.15); color: #9b59b6; }
        .stat-label { font-size: 12px; color: rgba(255,255,255,0.45); margin-bottom: 2px; }
        .stat-value { font-size: 22px; font-weight: 700; color: #fff; }

        /* ── Search / Filter Bar ── */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 220px;
        }
        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.35);
            font-size: 14px;
        }
        .search-input {
            width: 100%;
            padding: 11px 16px 11px 40px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: var(--radius-sm);
            color: #fff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }
        .search-input::placeholder { color: rgba(255,255,255,0.3); }
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(201,168,76,0.12);
        }
        .record-count {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            white-space: nowrap;
        }

        /* ── Table Container ── */
        .table-container {
            background: rgba(255,255,255,0.97);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border-top: 4px solid var(--gold);
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        /* ── Table Head ── */
        thead tr {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
        }
        thead th {
            padding: 14px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: var(--gold-light);
            white-space: nowrap;
            border-bottom: 2px solid rgba(201,168,76,0.3);
        }
        thead th i {
            margin-right: 6px;
            opacity: 0.75;
        }

        /* ── Table Body ── */
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: var(--transition);
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fdf9f0; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:nth-child(even):hover { background: #fdf9f0; }

        tbody td {
            padding: 13px 16px;
            color: var(--text-dark);
            vertical-align: middle;
            white-space: nowrap;
        }

        /* ── Special Cell Styles ── */
        .cell-name {
            font-weight: 600;
            color: var(--navy);
        }
        .cell-email {
            color: #3498db;
            font-size: 13px;
        }
        .cell-amount {
            font-weight: 700;
            color: #27ae60;
        }
        .cell-amount::before { content: '$'; font-size: 11px; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-male   { background: #e3f2fd; color: #1565c0; }
        .badge-female { background: #fce4ec; color: #880e4f; }
        .badge-other  { background: #f3e5f5; color: #6a1b9a; }

        .room-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            background: linear-gradient(135deg, rgba(13,27,42,0.08), rgba(13,27,42,0.04));
            border: 1px solid rgba(13,27,42,0.12);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--navy);
        }

        .date-cell {
            font-size: 12.5px;
            color: var(--text-mid);
        }
        .date-cell i { margin-right: 4px; color: var(--gold); font-size: 11px; }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 48px;
            color: rgba(201,168,76,0.4);
            margin-bottom: 16px;
            display: block;
        }
        .empty-state p {
            font-size: 16px;
            color: var(--text-mid);
            font-weight: 500;
        }
        .empty-state span {
            font-size: 13px;
            color: var(--text-light);
        }

        /* ── Table Footer ── */
        .table-footer {
            padding: 14px 20px;
            background: #f8f6f1;
            border-top: 1px solid #ede8dc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .table-footer span {
            font-size: 12.5px;
            color: var(--text-mid);
        }
        .footer-brand {
            font-family: 'Playfair Display', serif;
            font-size: 13px;
            color: var(--gold-dark);
            font-weight: 600;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .topbar { padding: 14px 16px; }
            .page-content { padding: 24px 16px 40px; }
            .page-header h2 { font-size: 22px; }
            .stats-bar { gap: 10px; }
            .stat-card { min-width: 140px; padding: 12px 16px; }
        }
    </style>
</head>
<body>

<!-- ── Top Bar ── -->
<header class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon"><i class="fas fa-hotel"></i></div>
        <div class="brand-text">
            <h1>Sabawyan Hotel</h1>
            <span>Management System</span>
        </div>
    </div>
    <div class="topbar-right">
        <a href="<?= $backUrl ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?= $backLabel ?>
        </a>
    </div>
</header>

<!-- ── Page Content ── -->
<main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-users" style="color:var(--gold-light);margin-right:10px;font-size:24px;"></i>Customer Details</h2>
        <p>View and manage all registered guest records</p>
        <div class="gold-divider"></div>
    </div>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
// Active guests (currently staying)
    $sql    = "SELECT * FROM customer";
    $result = $conn->query($sql);
    $total  = $result ? $result->rowCount() : 0;

    // Count currently checked-in guests (have checkin, no checkout yet)
    $checkedIn = 0;
    $rows      = [];
    if ($result && $total > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
            if (!empty($row['checkin']) && empty($row['checkout'])) $checkedIn++;
        }
    }

    // Total revenue = sum of all completed stays (deleted_customers = checked-out guests)
    $revResult    = $conn->query("SELECT COALESCE(SUM(totalamount), 0) AS rev FROM deleted_customers");
    $totalRevenue = $revResult ? floatval($revResult->fetch(PDO::FETCH_ASSOC)['rev']) : 0;

    // Pending revenue = sum of totalamount already calculated for active guests
    // (set when manager searches a room in update/checkout page)
    $pendingRevenue = 0;
    foreach ($rows as $r) {
        $pendingRevenue += floatval($r['totalamount'] ?? 0);
    }
    ?>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon gold"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-label">Active Guests</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="stat-label">Collected Revenue <small style="font-size:10px;opacity:0.6;display:block;font-weight:400;">from checked-out guests</small></div>
                <div class="stat-value">$<?= number_format($totalRevenue, 0) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="stat-label">Pending Revenue <small style="font-size:10px;opacity:0.6;display:block;font-weight:400;">from active guests (calculated)</small></div>
                <div class="stat-value" style="color:#e67e22;">$<?= number_format($pendingRevenue, 0) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-bed"></i></div>
            <div>
                <div class="stat-label">Currently Checked In</div>
                <div class="stat-value"><?= $checkedIn ?></div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Search by name, email, room…" oninput="filterTable()">
        </div>
        <span class="record-count" id="recordCount"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div class="table-scroll">
            <table id="customerTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i>Name</th>
                        <th><i class="fas fa-envelope"></i>Email</th>
                        <th><i class="fas fa-phone"></i>Mobile</th>
                        <th><i class="fas fa-globe"></i>Nationality</th>
                        <th><i class="fas fa-venus-mars"></i>Gender</th>
                        <th><i class="fas fa-id-card"></i>ID Proof</th>
                        <th><i class="fas fa-map-marker-alt"></i>Address</th>
                        <th><i class="fas fa-sign-in-alt"></i>Check-in</th>
                        <th><i class="fas fa-sign-out-alt"></i>Check-out</th>
                        <th><i class="fas fa-door-open"></i>Room No.</th>
                        <th><i class="fas fa-bed"></i>Bed Type</th>
                        <th><i class="fas fa-building"></i>Room Type</th>
                        <th><i class="fas fa-tag"></i>Price/Day</th>
                        <th><i class="fas fa-moon"></i>Days</th>
                        <th><i class="fas fa-coins"></i>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
if ($total > 0): ?>
                    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
foreach ($rows as $row): ?>
                    <tr>
                        <td class="cell-name"><?= htmlspecialchars($row['name'] ?? '') ?></td>
                        <td class="cell-email"><?= htmlspecialchars($row['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['mobilenumber'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['nationality'] ?? '') ?></td>
                        <td>
                            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
$g = strtolower($row['gender'] ?? '');
                            $cls = $g === 'male' ? 'badge-male' : ($g === 'female' ? 'badge-female' : 'badge-other');
                            $icon = $g === 'male' ? 'fa-mars' : ($g === 'female' ? 'fa-venus' : 'fa-genderless');
                            ?>
                            <span class="badge <?= $cls ?>"><i class="fas <?= $icon ?>"></i><?= htmlspecialchars($row['gender'] ?? '') ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['idproof'] ?? '') ?></td>
                        <td style="max-width:160px;white-space:normal;font-size:12.5px;"><?= htmlspecialchars($row['address'] ?? '') ?></td>
                        <td class="date-cell"><i class="fas fa-calendar"></i><?= htmlspecialchars($row['checkin'] ?? '') ?></td>
                        <td class="date-cell"><i class="fas fa-calendar"></i><?= htmlspecialchars($row['checkout'] ?? '') ?></td>
                        <td><span class="room-badge"><i class="fas fa-door-open"></i><?= htmlspecialchars($row['roomnumber'] ?? '') ?></span></td>
                        <td><?= htmlspecialchars($row['bedtype'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['roomtype'] ?? '') ?></td>
                        <td style="font-weight:600;color:#e67e22;">$<?= htmlspecialchars($row['priceperday'] ?? '') ?></td>
                        <td style="text-align:center;font-weight:600;"><?= htmlspecialchars($row['daystayed'] ?? '') ?></td>
                        <td class="cell-amount"><?= htmlspecialchars($row['totalamount'] ?? '') ?></td>
                    </tr>
                    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
endforeach; ?>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
else: ?>
                    <tr>
                        <td colspan="15">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No customer records found</p>
                                <span>Guest data will appear here once customers are checked in.</span>
                            </div>
                        </td>
                    </tr>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="table-footer">
            <span id="footerCount">Showing <strong><?= $total ?></strong> guest record<?= $total !== 1 ? 's' : '' ?></span>
            <span class="footer-brand"><i class="fas fa-hotel" style="margin-right:5px;"></i>Sabawyan Hotel</span>
        </div>
    </div>

</main>

<script>
function filterTable() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const rows  = document.querySelectorAll('#customerTable tbody tr');
    let visible = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const show = text.includes(query);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const total = rows.length;
    document.getElementById('recordCount').textContent =
        query ? `${visible} of ${total} records` : `${total} record${total !== 1 ? 's' : ''}`;
    document.getElementById('footerCount').innerHTML =
        `Showing <strong>${visible}</strong> guest record${visible !== 1 ? 's' : ''}`;
}
</script>

</body>
</html>