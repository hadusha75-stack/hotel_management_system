<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_guest.php';

$conn = getDB();
// Guest can only see THEIR OWN record — match by email from session
$guestEmail = $_SESSION['email'] ?? '';
$sql    = "SELECT * FROM customer WHERE email=?";
$stmt   = $conn->prepare($sql);
$stmt->bind_param("s", $guestEmail);
$stmt->execute();
$result = $stmt->get_result();
$total  = $result->num_rows;
$rows   = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Details — Sabawyan Hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--gold:#c9a84c;--gold-light:#e8c97a;--gold-dark:#a07830;--navy:#0d1b2a;--navy-mid:#1a2e45;--text-dark:#1a1a2e;--text-mid:#4a4a6a;--text-light:#8a8aaa;--shadow-lg:0 16px 48px rgba(0,0,0,0.28);--radius-sm:6px;--radius-md:12px;--radius-lg:20px;--transition:all 0.3s ease;}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a2e45 50%,#243b55 100%);background-attachment:fixed;min-height:100vh;}
        body::before{content:'';position:fixed;inset:0;background:url('../../photo/hotelnamebg.jpg') center/cover no-repeat;opacity:0.05;z-index:0;}
        .topbar{position:relative;z-index:10;background:rgba(13,27,42,0.96);backdrop-filter:blur(12px);border-bottom:1px solid rgba(201,168,76,0.2);padding:0 32px;height:66px;display:flex;align-items:center;justify-content:space-between;}
        .topbar-brand{display:flex;align-items:center;gap:12px;}
        .brand-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:10px;display:flex;align-items:center;justify-content:center;}
        .brand-icon i{font-size:18px;color:var(--navy);}
        .brand-text h1{font-family:'Playfair Display',serif;font-size:19px;color:#fff;line-height:1.2;}
        .brand-text span{font-size:11.5px;color:var(--gold-light);}
        .back-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.3);border-radius:var(--radius-sm);color:var(--gold-light);font-size:13px;font-weight:600;font-family:'Inter',sans-serif;text-decoration:none;transition:var(--transition);}
        .back-btn:hover{background:rgba(201,168,76,0.2);border-color:var(--gold);color:var(--gold);transform:translateX(-2px);}
        .page-content{position:relative;z-index:1;max-width:900px;margin:0 auto;padding:40px 24px 60px;}
        .page-header{margin-bottom:28px;}
        .page-header h2{font-family:'Playfair Display',serif;font-size:28px;color:#fff;margin-bottom:5px;}
        .page-header p{font-size:14px;color:rgba(255,255,255,0.4);}
        .gold-divider{width:56px;height:3px;background:linear-gradient(90deg,var(--gold-dark),var(--gold-light));border-radius:2px;margin:10px 0 0;}
        /* Booking Card */
        .booking-card{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid var(--gold);margin-bottom:20px;}
        .booking-header{padding:20px 28px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
        .booking-room{font-family:'Playfair Display',serif;font-size:22px;color:#fff;}
        .booking-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}
        .b-active{background:rgba(46,204,113,0.2);color:#2ecc71;border:1px solid rgba(46,204,113,0.3);}
        .booking-body{padding:24px 28px;}
        .detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
        .detail-item{}
        .detail-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-light);margin-bottom:4px;}
        .detail-label i{margin-right:5px;color:var(--gold-dark);}
        .detail-value{font-size:14.5px;font-weight:600;color:var(--text-dark);}
        .billing-strip{background:linear-gradient(135deg,#fdf9f0,#faf5e8);border-top:1px solid rgba(201,168,76,0.15);padding:16px 28px;display:flex;align-items:center;gap:32px;flex-wrap:wrap;}
        .billing-item .bl{font-size:11px;color:var(--text-light);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;}
        .billing-item .bv{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--navy);}
        .billing-item .bv.green{color:#27ae60;}
        /* Empty */
        .empty-card{background:rgba(255,255,255,0.06);border:1px solid rgba(201,168,76,0.15);border-radius:var(--radius-lg);padding:60px 24px;text-align:center;}
        .empty-card i{font-size:48px;color:rgba(201,168,76,0.3);display:block;margin-bottom:16px;}
        .empty-card h3{font-family:'Playfair Display',serif;font-size:20px;color:#fff;margin-bottom:8px;}
        .empty-card p{font-size:14px;color:rgba(255,255,255,0.4);}
        .btn-checkin{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));color:var(--navy);border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;font-family:'Inter',sans-serif;text-decoration:none;cursor:pointer;transition:var(--transition);box-shadow:0 6px 20px rgba(201,168,76,0.3);margin-top:20px;}
        .btn-checkin:hover{transform:translateY(-2px);}
        @media(max-width:640px){.topbar{padding:0 16px;}.page-content{padding:24px 12px 40px;}.detail-grid{grid-template-columns:1fr 1fr;}.billing-strip{gap:16px;}}
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon"><i class="fas fa-hotel"></i></div>
        <div class="brand-text"><h1>Sabawyan Hotel</h1><span>Guest Portal</span></div>
    </div>
    <a href="../public/rooms.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
</header>

<main class="page-content">
    <div class="page-header">
        <h2><i class="fas fa-id-card" style="color:var(--gold-light);margin-right:10px;font-size:24px;"></i>My Booking</h2>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Guest') ?></p>
        <div class="gold-divider"></div>
    </div>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($total === 0): ?>
    <div class="empty-card">
        <i class="fas fa-bed"></i>
        <h3>No Active Booking</h3>
        <p>You don't have an active booking at the moment.</p>
        <a href="checkin.php" class="btn-checkin"><i class="fas fa-sign-in-alt"></i> Check In Now</a>
    </div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: foreach ($rows as $row): ?>
    <div class="booking-card">
        <div class="booking-header">
            <div>
                <div style="font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:4px;">Room</div>
                <div class="booking-room">Room <?= htmlspecialchars($row['roomnumber']) ?></div>
            </div>
            <span class="booking-badge b-active"><i class="fas fa-check-circle"></i> Active Stay</span>
        </div>
        <div class="booking-body">
            <div class="detail-grid">
                <div class="detail-item"><div class="detail-label"><i class="fas fa-user"></i>Name</div><div class="detail-value"><?= htmlspecialchars($row['name']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-envelope"></i>Email</div><div class="detail-value"><?= htmlspecialchars($row['email']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-phone"></i>Mobile</div><div class="detail-value"><?= htmlspecialchars($row['mobilenumber']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-globe"></i>Nationality</div><div class="detail-value"><?= htmlspecialchars($row['nationality']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-venus-mars"></i>Gender</div><div class="detail-value"><?= htmlspecialchars($row['gender']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-id-card"></i>ID Proof</div><div class="detail-value"><?= htmlspecialchars($row['idproof']) ?></div></div>
                <div class="detail-item" style="grid-column:span 3"><div class="detail-label"><i class="fas fa-map-marker-alt"></i>Address</div><div class="detail-value"><?= htmlspecialchars($row['address']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-bed"></i>Bed Type</div><div class="detail-value"><?= htmlspecialchars($row['bedtype']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-building"></i>Room Type</div><div class="detail-value"><?= htmlspecialchars($row['roomtype']) ?></div></div>
                <div class="detail-item"><div class="detail-label"><i class="fas fa-sign-in-alt"></i>Check-In</div><div class="detail-value"><?= htmlspecialchars($row['checkin']) ?></div></div>
            </div>
        </div>
        <div class="billing-strip">
            <div class="billing-item"><div class="bl">Rate / Night</div><div class="bv">$<?= htmlspecialchars($row['priceperday']) ?></div></div>
            <div class="billing-item"><div class="bl">Nights Stayed</div><div class="bv"><?= intval($row['daystayed']) ?></div></div>
            <div class="billing-item"><div class="bl">Total Amount</div><div class="bv green">$<?= number_format(floatval($row['totalamount']),2) ?></div></div>
        </div>
    </div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endforeach; endif; ?>
</main>
</body>
</html>
