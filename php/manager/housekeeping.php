<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
$conn = getDB();
$backUrl   = isset($_GET["from"]) && $_GET["from"]==="finance" ? "../../html/dashboards/finance.html" : "../../html/dashboards/manager.html";
$backLabel = isset($_GET["from"]) && $_GET["from"]==="finance" ? "Finance Dashboard"    : "Manager Dashboard";

$success_msg = "";
$error_msg   = "";

if (isset($_POST['update_status'])) {
    $room_id    = $_POST['room_id'];
    $cleanDerty = $_POST['cleanDerty'];
    // Quote cleanDerty for PostgreSQL case-sensitivity
    $isPg = (DB_TYPE === 'pgsql');
    $col  = $isPg ? '"cleanDerty"' : 'cleanDerty';
    $stmt = $conn->prepare("UPDATE rooms SET $col=? WHERE roomnumber=?");
    $stmt->bind_param("ss", $cleanDerty, $room_id);
    if ($stmt->execute()) {
        $success_msg = "Room <strong>$room_id</strong> status updated to <strong>$cleanDerty</strong>.";
    } else {
        $error_msg = "Failed to update room status. Error: " . $stmt->error;
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM rooms ORDER BY roomnumber");
$rooms  = [];
$stats  = ['total'=>0,'clean'=>0,'dirty'=>0,'occupied'=>0,'booked'=>0];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
        $stats['total']++;
        $cl = strtolower($row['cleanDerty'] ?? '');
        if ($cl==='clean')        $stats['clean']++;
        elseif ($cl==='dirty')    $stats['dirty']++;
        elseif ($cl==='occupied') $stats['occupied']++;
        if (strtolower($row['status'] ?? '')==='booked') $stats['booked']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Status — Sabawyan Hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --gold:       #c9a84c; --gold-light: #e8c97a; --gold-dark: #a07830;
            --navy:       #0d1b2a; --navy-mid:   #1a2e45;
            --text-dark:  #1a1a2e; --text-mid:   #4a4a6a; --text-light: #8a8aaa;
            --shadow-lg:  0 16px 48px rgba(0,0,0,0.28);
            --radius-sm:  6px; --radius-md: 12px; --radius-lg: 20px;
            --transition: all 0.3s ease;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0d1b2a 0%, #1a2e45 50%, #243b55 100%);
            background-attachment: fixed; min-height: 100vh;
        }
        body::before {
            content: ''; position: fixed; inset: 0;
            background: url('../../photo/hotelnamebg.jpg') center/cover no-repeat;
            opacity: 0.05; z-index: 0;
        }
        .topbar {
            position: relative; z-index: 10;
            background: rgba(13,27,42,0.96); backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(201,168,76,0.2);
            padding: 0 32px; height: 66px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-brand { display: flex; align-items: center; gap: 12px; }
        .brand-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--gold-dark), var(--gold)); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .brand-icon i { font-size: 18px; color: var(--navy); }
        .brand-text h1 { font-family: 'Playfair Display', serif; font-size: 19px; color: #fff; line-height: 1.2; }
        .brand-text span { font-size: 11.5px; color: var(--gold-light); }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; background: rgba(201,168,76,0.1); border: 1px solid rgba(201,168,76,0.3); border-radius: var(--radius-sm); color: var(--gold-light); font-size: 13px; font-weight: 600; font-family: 'Inter', sans-serif; text-decoration: none; transition: var(--transition); }
        .back-btn:hover { background: rgba(201,168,76,0.2); border-color: var(--gold); color: var(--gold); transform: translateX(-2px); }
        .page { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 40px 24px 60px; }
        .page-header { margin-bottom: 28px; }
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 28px; color: #fff; margin-bottom: 5px; }
        .page-header p { font-size: 14px; color: rgba(255,255,255,0.4); }
        .teal-divider { width: 56px; height: 3px; background: linear-gradient(90deg, #0e6655, #1abc9c); border-radius: 2px; margin: 10px 0 0; }
        .alert { display: flex; align-items: center; gap: 12px; padding: 13px 18px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 500; margin-bottom: 20px; animation: fadeIn 0.4s ease; }
        .alert i { font-size: 15px; flex-shrink: 0; }
        .alert-success { background: rgba(46,204,113,0.12); border: 1px solid rgba(46,204,113,0.35); color: #a8f0c6; }
        .alert-error   { background: rgba(231,76,60,0.12);  border: 1px solid rgba(231,76,60,0.35);  color: #f5a0a0; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
        .stats-bar { display: flex; gap: 14px; margin-bottom: 28px; flex-wrap: wrap; }
        .stat-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-md); padding: 14px 20px; display: flex; align-items: center; gap: 12px; flex: 1; min-width: 140px; }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .si-total    { background: rgba(201,168,76,0.15); color: var(--gold); }
        .si-clean    { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .si-dirty    { background: rgba(231,76,60,0.15);  color: #e74c3c; }
        .si-occupied { background: rgba(243,156,18,0.15); color: #f39c12; }
        .si-booked   { background: rgba(52,152,219,0.15); color: #3498db; }
        .stat-label { font-size: 11px; color: rgba(255,255,255,0.4); margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 20px; font-weight: 700; color: #fff; }
        .table-card { background: rgba(255,255,255,0.97); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden; border-top: 4px solid #1abc9c; }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        thead tr { background: linear-gradient(135deg, #0e3d2f, #0e6655); }
        thead th { padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #7dffd4; white-space: nowrap; border-bottom: 2px solid rgba(26,188,156,0.3); }
        thead th i { margin-right: 6px; opacity: 0.7; }
        tbody tr { border-bottom: 1px solid #f0f0f0; transition: var(--transition); }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f0fdf8; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:nth-child(even):hover { background: #f0fdf8; }
        tbody td { padding: 12px 16px; color: var(--text-dark); vertical-align: middle; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 600; }
        .b-booked    { background: #e3f2fd; color: #1565c0; }
        .b-notbooked { background: #e8f5e9; color: #2e7d32; }
        .b-clean     { background: #e8f5e9; color: #2e7d32; }
        .b-dirty     { background: #fdecea; color: #c62828; }
        .b-occupied  { background: #fff8e1; color: #f57f17; }
        .update-form { display: flex; align-items: center; gap: 8px; }
        .update-form select { padding: 7px 12px; border: 1.5px solid #e0e0e0; border-radius: var(--radius-sm); font-size: 13px; font-family: 'Inter', sans-serif; background: #fafafa; color: var(--text-dark); transition: var(--transition); cursor: pointer; }
        .update-form select:focus { outline: none; border-color: #1abc9c; box-shadow: 0 0 0 3px rgba(26,188,156,0.12); }
        .btn-update { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; background: linear-gradient(135deg, #0e6655, #1abc9c); color: #fff; border: none; border-radius: var(--radius-sm); font-size: 12.5px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: var(--transition); white-space: nowrap; }
        .btn-update:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(26,188,156,0.35); }
        .table-footer { padding: 13px 20px; background: #f0fdf8; border-top: 1px solid #d4f5ea; display: flex; align-items: center; justify-content: space-between; }
        .table-footer span { font-size: 12.5px; color: var(--text-mid); }
        .footer-brand { font-family: 'Playfair Display', serif; font-size: 13px; color: #0e6655; font-weight: 600; }
        @media (max-width: 700px) {
            .topbar { padding: 0 16px; }
            .page { padding: 24px 12px 40px; }
            .stats-bar { gap: 10px; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon"><i class="fas fa-hotel"></i></div>
        <div class="brand-text">
            <h1>Sabawyan Hotel</h1>
            <span>Management System</span>
        </div>
    </div>
    <a href="<?= $backUrl ?>" class="back-btn">
        <i class="fas fa-arrow-left"></i> <?= $backLabel ?>
    </a>
</header>

<main class="page">

    <div class="page-header">
        <h2><i class="fas fa-list-check" style="color:#1abc9c;margin-right:10px;font-size:24px;"></i>Room Status Overview</h2>
        <p>Monitor and update room cleanliness and availability</p>
        <div class="teal-divider"></div>
    </div>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $success_msg ?></div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
endif; ?>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error_msg ?></div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
endif; ?>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-card"><div class="stat-icon si-total"><i class="fas fa-door-open"></i></div><div><div class="stat-label">Total Rooms</div><div class="stat-value"><?= $stats['total'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-clean"><i class="fas fa-check-circle"></i></div><div><div class="stat-label">Clean</div><div class="stat-value"><?= $stats['clean'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-dirty"><i class="fas fa-times-circle"></i></div><div><div class="stat-label">Dirty</div><div class="stat-value"><?= $stats['dirty'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-occupied"><i class="fas fa-user"></i></div><div><div class="stat-label">Occupied</div><div class="stat-value"><?= $stats['occupied'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-booked"><i class="fas fa-bed"></i></div><div><div class="stat-label">Booked</div><div class="stat-value"><?= $stats['booked'] ?></div></div></div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i>Room No.</th>
                        <th><i class="fas fa-snowflake"></i>Room Type</th>
                        <th><i class="fas fa-bed"></i>Bed Type</th>
                        <th><i class="fas fa-calendar-check"></i>Booking Status</th>
                        <th><i class="fas fa-broom"></i>Cleanliness</th>
                        <th><i class="fas fa-edit"></i>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
if (empty($rooms)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#888;">No rooms found.</td></tr>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
else: ?>
                    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
foreach ($rooms as $row):
                        $cl      = strtolower($row['cleanDerty'] ?? '');
                        $bk      = strtolower($row['status']    ?? '');
                        $clBadge = $cl === 'clean' ? 'b-clean' : ($cl === 'dirty' ? 'b-dirty' : 'b-occupied');
                        $bkBadge = $bk === 'booked' ? 'b-booked' : 'b-notbooked';
                        $clIcon  = $cl === 'clean' ? 'fa-check-circle' : ($cl === 'dirty' ? 'fa-times-circle' : 'fa-user');
                    ?>
                    <tr>
                        <td style="font-weight:700;color:var(--navy);"><?= htmlspecialchars($row['roomnumber']) ?></td>
                        <td><?= htmlspecialchars($row['roomtype'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['bedtype']  ?? '') ?></td>
                        <td><span class="badge <?= $bkBadge ?>"><i class="fas <?= $bk==='booked'?'fa-lock':'fa-lock-open' ?>"></i><?= htmlspecialchars($row['status'] ?? '') ?></span></td>
                        <td><span class="badge <?= $clBadge ?>"><i class="fas <?= $clIcon ?>"></i><?= htmlspecialchars($row['cleanDerty'] ?? '') ?></span></td>
                        <td>
                            <form method="POST" class="update-form">
                                <input type="hidden" name="room_id" value="<?= htmlspecialchars($row['roomnumber']) ?>">
                                <select name="cleanDerty">
                                    <option value="Clean"    <?= $row['cleanDerty']==='Clean'   ?'selected':'' ?>>Clean</option>
                                    <option value="Dirty"    <?= $row['cleanDerty']==='Dirty'   ?'selected':'' ?>>Dirty</option>
                                    <option value="Occupied" <?= $row['cleanDerty']==='Occupied'?'selected':'' ?>>Occupied</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-update">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </form>
                        </td>
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
endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span><?= $stats['total'] ?> room<?= $stats['total']!==1?'s':'' ?> total</span>
            <span class="footer-brand"><i class="fas fa-list-check" style="margin-right:5px;"></i>Room Status</span>
        </div>
    </div>

</main>
<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
?>
</body>
</html>