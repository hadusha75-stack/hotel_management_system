<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_guest.php';
$conn = getDB();
$result = $conn->query("SELECT * FROM rooms ORDER BY roomnumber");
$rooms  = [];
$stats  = ['total'=>0,'available'=>0,'booked'=>0,'clean'=>0,'dirty'=>0];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
        $stats['total']++;
        $st = strtolower($row['status']    ?? '');
        $cl = strtolower($row['cleanDerty'] ?? '');
        if ($st === 'not booked') $stats['available']++;
        else                      $stats['booked']++;
        if ($cl === 'clean')      $stats['clean']++;
        elseif ($cl === 'dirty')  $stats['dirty']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rooms — Sabawyan Hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --gold:#c9a84c; --gold-light:#e8c97a; --gold-dark:#a07830;
            --navy:#0d1b2a; --navy-mid:#1a2e45;
            --text-dark:#1a1a2e; --text-mid:#4a4a6a; --text-light:#8a8aaa;
            --shadow-lg:0 16px 48px rgba(0,0,0,0.28);
            --radius-sm:6px; --radius-md:12px; --radius-lg:20px;
            --transition:all 0.3s ease;
        }
        *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#0d1b2a 0%,#1a2e45 50%,#243b55 100%);
            background-attachment:fixed; min-height:100vh;
        }
        body::before {
            content:''; position:fixed; inset:0;
            background:url('../../photo/hotelnamebg.jpg') center/cover no-repeat;
            opacity:0.05; z-index:0;
        }

        /* Topbar */
        .topbar {
            position:relative; z-index:10;
            background:rgba(13,27,42,0.96); backdrop-filter:blur(12px);
            border-bottom:1px solid rgba(201,168,76,0.2);
            padding:0 32px; height:66px;
            display:flex; align-items:center; justify-content:space-between;
        }
        .topbar-brand { display:flex; align-items:center; gap:12px; }
        .brand-icon {
            width:42px; height:42px;
            background:linear-gradient(135deg,var(--gold-dark),var(--gold));
            border-radius:10px; display:flex; align-items:center; justify-content:center;
        }
        .brand-icon i { font-size:18px; color:var(--navy); }
        .brand-text h1 { font-family:'Playfair Display',serif; font-size:19px; color:#fff; line-height:1.2; }
        .brand-text span { font-size:11.5px; color:var(--gold-light); }
        .back-btn {
            display:inline-flex; align-items:center; gap:8px; padding:8px 18px;
            background:rgba(201,168,76,0.1); border:1px solid rgba(201,168,76,0.3);
            border-radius:var(--radius-sm); color:var(--gold-light);
            font-size:13px; font-weight:600; font-family:'Inter',sans-serif;
            text-decoration:none; transition:var(--transition);
        }
        .back-btn:hover { background:rgba(201,168,76,0.2); border-color:var(--gold); color:var(--gold); transform:translateX(-2px); }

        /* Page */
        .page { position:relative; z-index:1; max-width:1000px; margin:0 auto; padding:40px 24px 60px; }
        .page-header { margin-bottom:28px; }
        .page-header h2 { font-family:'Playfair Display',serif; font-size:28px; color:#fff; margin-bottom:5px; }
        .page-header p { font-size:14px; color:rgba(255,255,255,0.4); }
        .gold-divider { width:56px; height:3px; background:linear-gradient(90deg,var(--gold-dark),var(--gold-light)); border-radius:2px; margin:10px 0 0; }

        /* Stats */
        .stats-bar { display:flex; gap:14px; margin-bottom:28px; flex-wrap:wrap; }
        .stat-card { background:rgba(255,255,255,0.06); border:1px solid rgba(201,168,76,0.15); border-radius:var(--radius-md); padding:14px 20px; display:flex; align-items:center; gap:12px; flex:1; min-width:130px; }
        .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
        .si-gold   { background:rgba(201,168,76,0.15); color:var(--gold); }
        .si-green  { background:rgba(46,204,113,0.15); color:#2ecc71; }
        .si-red    { background:rgba(231,76,60,0.15);  color:#e74c3c; }
        .si-blue   { background:rgba(52,152,219,0.15); color:#3498db; }
        .si-orange { background:rgba(243,156,18,0.15); color:#f39c12; }
        .stat-label { font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px; }
        .stat-value { font-size:20px; font-weight:700; color:#fff; }

        /* Filter bar */
        .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
        .search-wrap { position:relative; flex:1; min-width:200px; }
        .search-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,0.35); font-size:14px; }
        .search-input { width:100%; padding:11px 16px 11px 40px; background:rgba(255,255,255,0.07); border:1px solid rgba(201,168,76,0.2); border-radius:var(--radius-sm); color:#fff; font-size:14px; font-family:'Inter',sans-serif; transition:var(--transition); }
        .search-input::placeholder { color:rgba(255,255,255,0.3); }
        .search-input:focus { outline:none; border-color:var(--gold); background:rgba(255,255,255,0.1); }
        .filter-select { padding:11px 14px; background:rgba(255,255,255,0.07); border:1px solid rgba(201,168,76,0.2); border-radius:var(--radius-sm); color:#fff; font-size:13px; font-family:'Inter',sans-serif; cursor:pointer; transition:var(--transition); }
        .filter-select option { background:#1a2e45; }
        .filter-select:focus { outline:none; border-color:var(--gold); }
        .record-count { font-size:13px; color:rgba(255,255,255,0.4); white-space:nowrap; }

        /* Room Cards Grid */
        .rooms-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
        .room-card {
            background:rgba(255,255,255,0.97); border-radius:var(--radius-md);
            box-shadow:0 4px 16px rgba(0,0,0,0.18); overflow:hidden;
            transition:var(--transition); border-top:3px solid var(--gold);
        }
        .room-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.28); }
        .room-card.booked { border-top-color:#e74c3c; opacity:0.75; }
        .room-card-header {
            padding:16px 18px 12px;
            display:flex; align-items:center; justify-content:space-between;
        }
        .room-number { font-family:'Playfair Display',serif; font-size:22px; font-weight:700; color:var(--navy); }
        .room-status-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-available { background:#e8f5e9; color:#2e7d32; }
        .badge-booked    { background:#fdecea; color:#c62828; }
        .room-card-body { padding:0 18px 16px; }
        .room-detail { display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:13px; color:var(--text-mid); }
        .room-detail i { width:16px; text-align:center; color:var(--gold-dark); font-size:12px; }
        .room-detail span { font-weight:500; }
        .clean-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .clean-clean    { background:#e8f5e9; color:#2e7d32; }
        .clean-dirty    { background:#fdecea; color:#c62828; }
        .clean-occupied { background:#fff8e1; color:#f57f17; }
        .room-price { margin-top:10px; padding-top:10px; border-top:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between; }
        .price-label { font-size:11px; color:var(--text-light); text-transform:uppercase; letter-spacing:0.5px; }
        .price-value { font-family:'Playfair Display',serif; font-size:18px; font-weight:700; color:#27ae60; }

        /* Empty state */
        .empty-state { text-align:center; padding:60px 20px; grid-column:1/-1; }
        .empty-state i { font-size:48px; color:rgba(201,168,76,0.3); margin-bottom:16px; display:block; }
        .empty-state p { font-size:16px; color:rgba(255,255,255,0.5); font-weight:500; }

        @media(max-width:640px) {
            .topbar { padding:0 16px; }
            .page { padding:24px 12px 40px; }
            .rooms-grid { grid-template-columns:repeat(2,1fr); gap:12px; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon"><i class="fas fa-hotel"></i></div>
        <div class="brand-text">
            <h1>Sabawyan Hotel</h1>
            <span>Room Availability</span>
        </div>
    </div>
    <a href="../../html/public/auth.html" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</header>

<main class="page">

    <div class="page-header">
        <h2><i class="fas fa-door-open" style="color:var(--gold-light);margin-right:10px;font-size:24px;"></i>Room Availability</h2>
        <p>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Guest') ?> — browse and book a room below</p>
        <div class="gold-divider"></div>
    </div>

    <!-- Guest quick links -->
    <div style="display:flex;gap:12px;margin-bottom:28px;flex-wrap:wrap;">
        <a href="../guest/checkin.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#1a5276,#2980b9);color:#fff;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;transition:all 0.3s ease;"><i class="fas fa-sign-in-alt"></i> Check In</a>
        <a href="../guest/checkout.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#a04000,#e67e22);color:#fff;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;transition:all 0.3s ease;"><i class="fas fa-sign-out-alt"></i> Check Out</a>
        <a href="../guest/details.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#4a235a,#8e44ad);color:#fff;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;transition:all 0.3s ease;"><i class="fas fa-id-card"></i> My Booking</a>
        <a href="../auth/logout.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.6);border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;transition:all 0.3s ease;"><i class="fas fa-power-off"></i> Log Out</a>
    </div>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-card"><div class="stat-icon si-gold"><i class="fas fa-door-open"></i></div><div><div class="stat-label">Total Rooms</div><div class="stat-value"><?= $stats['total'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-green"><i class="fas fa-check-circle"></i></div><div><div class="stat-label">Available</div><div class="stat-value"><?= $stats['available'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-red"><i class="fas fa-lock"></i></div><div><div class="stat-label">Booked</div><div class="stat-value"><?= $stats['booked'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-blue"><i class="fas fa-broom"></i></div><div><div class="stat-label">Clean</div><div class="stat-value"><?= $stats['clean'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-orange"><i class="fas fa-times-circle"></i></div><div><div class="stat-label">Dirty</div><div class="stat-value"><?= $stats['dirty'] ?></div></div></div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Search room number…" oninput="filterRooms()">
        </div>
        <select class="filter-select" id="statusFilter" onchange="filterRooms()">
            <option value="">All Status</option>
            <option value="not booked">Available</option>
            <option value="booked">Booked</option>
        </select>
        <select class="filter-select" id="typeFilter" onchange="filterRooms()">
            <option value="">All Types</option>
            <option value="AC">AC</option>
            <option value="NonAC">Non-AC</option>
        </select>
        <select class="filter-select" id="bedFilter" onchange="filterRooms()">
            <option value="">All Beds</option>
            <option value="Single">Single</option>
            <option value="Double">Double</option>
            <option value="Triple">Triple</option>
        </select>
        <span class="record-count" id="recordCount"><?= $stats['total'] ?> room<?= $stats['total']!==1?'s':'' ?></span>
    </div>

    <!-- Room Cards -->
    <div class="rooms-grid" id="roomsGrid">
        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (empty($rooms)): ?>
            <div class="empty-state">
                <i class="fas fa-door-open"></i>
                <p>No rooms found in the system.</p>
            </div>
        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: ?>
            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
foreach ($rooms as $row):
                $st  = strtolower($row['status']     ?? '');
                $cl  = strtolower($row['cleanDerty']  ?? '');
                $isBooked = $st === 'booked';
                $clClass  = $cl === 'clean' ? 'clean-clean' : ($cl === 'dirty' ? 'clean-dirty' : 'clean-occupied');
                $clIcon   = $cl === 'clean' ? 'fa-check-circle' : ($cl === 'dirty' ? 'fa-times-circle' : 'fa-user');
            ?>
            <div class="room-card <?= $isBooked ? 'booked' : '' ?>"
                 data-status="<?= htmlspecialchars($row['status'] ?? '') ?>"
                 data-type="<?= htmlspecialchars($row['roomtype'] ?? '') ?>"
                 data-bed="<?= htmlspecialchars($row['bedtype'] ?? '') ?>"
                 data-room="<?= htmlspecialchars($row['roomnumber'] ?? '') ?>">
                <div class="room-card-header">
                    <span class="room-number"><?= htmlspecialchars($row['roomnumber']) ?></span>
                    <span class="room-status-badge <?= $isBooked ? 'badge-booked' : 'badge-available' ?>">
                        <i class="fas <?= $isBooked ? 'fa-lock' : 'fa-lock-open' ?>"></i>
                        <?= $isBooked ? 'Booked' : 'Available' ?>
                    </span>
                </div>
                <div class="room-card-body">
                    <div class="room-detail"><i class="fas fa-snowflake"></i><span><?= htmlspecialchars($row['roomtype'] ?? '—') ?></span></div>
                    <div class="room-detail"><i class="fas fa-bed"></i><span><?= htmlspecialchars($row['bedtype'] ?? '—') ?></span></div>
                    <div class="room-detail">
                        <i class="fas fa-broom"></i>
                        <span class="clean-badge <?= $clClass ?>"><i class="fas <?= $clIcon ?>"></i> <?= htmlspecialchars($row['cleanDerty'] ?? '—') ?></span>
                    </div>
                    <div class="room-price">
                        <span class="price-label">Per Night</span>
                        <span class="price-value">ETB <?= number_format(floatval($row['price'] ?? 0), 0) ?></span>
                    </div>
                </div>
            </div>
            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endforeach; ?>
        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
    </div>

</main>

<script>
function filterRooms() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
    const type   = document.getElementById('typeFilter').value.toLowerCase();
    const bed    = document.getElementById('bedFilter').value.toLowerCase();
    const cards  = document.querySelectorAll('.room-card');
    let visible  = 0;

    cards.forEach(card => {
        const roomNum  = (card.dataset.room   || '').toLowerCase();
        const roomSt   = (card.dataset.status || '').toLowerCase();
        const roomType = (card.dataset.type   || '').toLowerCase();
        const roomBed  = (card.dataset.bed    || '').toLowerCase();

        const show = (!search || roomNum.includes(search))
                  && (!status || roomSt   === status)
                  && (!type   || roomType === type)
                  && (!bed    || roomBed  === bed);

        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const total = cards.length;
    document.getElementById('recordCount').textContent =
        (search || status || type || bed)
        ? visible + ' of ' + total + ' rooms'
        : total + ' room' + (total !== 1 ? 's' : '');
}
</script>

</body>
</html>
