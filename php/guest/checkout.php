<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_guest.php';

$conn = getDB();
// Guest can ONLY check out their own booking (matched by session email)
$guestEmail = $_SESSION['email'] ?? '';
$guestName  = $_SESSION['name']  ?? 'Guest';

$booking    = null;
$error_msg  = '';
$success    = isset($_GET['success']) && $_GET['success'] === '1';

// ── SEARCH: load guest own booking ───────────────────────────
if (isset($_POST['search'])) {
    $checkoutDate = trim($_POST['checkout'] ?? '');

    // Find this guest's active booking by email
    $stmt = $conn->prepare("SELECT * FROM customer WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $guestEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        $error_msg = "You don't have an active booking to check out.";
    } else {
        $booking = $result->fetch_assoc();

        if ($checkoutDate) {
            // Save checkout date
            $conn->query("UPDATE customer SET checkout='$checkoutDate' WHERE email='" . $conn->real_escape_string($guestEmail) . "'");
            $booking['checkout'] = $checkoutDate;
        }

        // Calculate days and total
        if ($booking['checkin'] && $booking['checkout']) {
            $days  = max(1, (strtotime($booking['checkout']) - strtotime($booking['checkin'])) / 86400);
            $total = $days * floatval($booking['priceperday']);
            $conn->query("UPDATE customer SET daystayed='$days', totalamount='$total' WHERE email='" . $conn->real_escape_string($guestEmail) . "'");
            $booking['daystayed']   = $days;
            $booking['totalamount'] = $total;
        }

        // Store in session to survive redirect
        $_SESSION['pending_checkout'] = $booking;
        header("location: checkout.php?step=confirm");
        exit;
    }
}

// ── CONFIRM STEP: show booking loaded from session ────────────
if (isset($_GET['step']) && $_GET['step'] === 'confirm') {
    $booking = $_SESSION['pending_checkout'] ?? null;
    if (!$booking) {
        header("location: checkout.php");
        exit;
    }
}

// ── CHECKOUT: process ─────────────────────────────────────────
if (isset($_POST['check_out'])) {
    $booking = $_SESSION['pending_checkout'] ?? null;

    if (!$booking) {
        $error_msg = "Session expired. Please search again.";
    } else {
        $rn      = $conn->real_escape_string($booking['roomnumber']);
        $em      = $conn->real_escape_string($booking['email']);
        $name    = $conn->real_escape_string($booking['name']);
        $mobile  = $conn->real_escape_string($booking['mobilenumber']);
        $nat     = $conn->real_escape_string($booking['nationality']);
        $gender  = $conn->real_escape_string($booking['gender']);
        $idp     = $conn->real_escape_string($booking['idproof']);
        $addr    = $conn->real_escape_string($booking['address']);
        $ci      = $conn->real_escape_string($booking['checkin']);
        $co      = $conn->real_escape_string($booking['checkout']);
        $bed     = $conn->real_escape_string($booking['bedtype']);
        $rt      = $conn->real_escape_string($booking['roomtype']);
        $ppd     = floatval($booking['priceperday']);
        $days    = intval($booking['daystayed']);
        $total   = floatval($booking['totalamount']);

        // Archive to deleted_customers
        $conn->query("INSERT INTO deleted_customers
            (name,email,mobilenumber,nationality,gender,idproof,address,checkin,checkout,bedtype,roomtype,priceperday,roomnumber,daystayed,totalamount)
            VALUES ('$name','$em','$mobile','$nat','$gender','$idp','$addr','$ci','$co','$bed','$rt',$ppd,'$rn',$days,$total)");

        // Delete from active customer
        $conn->query("DELETE FROM customer WHERE email='$em'");

        // Free the room
        $conn->query("UPDATE rooms SET status='not booked', cleanDerty='Dirty' WHERE roomnumber='$rn'");

        // Clear session
        unset($_SESSION['pending_checkout']);

        // PRG redirect — prevents ERR_CACHE_MISS on refresh
        header("location: checkout.php?success=1&room=$rn&total=$total&nights=$days");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Out — Sabawyan Hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--gold:#c9a84c;--gold-light:#e8c97a;--gold-dark:#a07830;--navy:#0d1b2a;--navy-mid:#1a2e45;--orange:#e67e22;--orange-dark:#a04000;--text-dark:#1a1a2e;--text-mid:#4a4a6a;--text-light:#8a8aaa;--shadow-lg:0 16px 48px rgba(0,0,0,0.28);--shadow-gold:0 8px 24px rgba(201,168,76,0.25);--radius-sm:6px;--radius-md:12px;--radius-lg:20px;--transition:all 0.3s ease;}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a2e45 50%,#243b55 100%);background-attachment:fixed;min-height:100vh;}
        body::before{content:'';position:fixed;inset:0;background:url('../../photo/hotelnamebg.jpg') center/cover no-repeat;opacity:0.05;z-index:0;}
        .topbar{position:relative;z-index:10;background:rgba(13,27,42,0.96);backdrop-filter:blur(12px);border-bottom:1px solid rgba(201,168,76,0.2);padding:0 32px;height:66px;display:flex;align-items:center;justify-content:space-between;}
        .brand{display:flex;align-items:center;gap:12px;}
        .brand-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:10px;display:flex;align-items:center;justify-content:center;}
        .brand-icon i{font-size:18px;color:var(--navy);}
        .brand h1{font-family:'Playfair Display',serif;font-size:19px;color:#fff;line-height:1.2;}
        .brand span{font-size:11.5px;color:var(--gold-light);}
        .back-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.3);border-radius:var(--radius-sm);color:var(--gold-light);font-size:13px;font-weight:600;text-decoration:none;transition:var(--transition);}
        .back-btn:hover{background:rgba(201,168,76,0.2);color:var(--gold);transform:translateX(-2px);}
        .page{position:relative;z-index:1;max-width:720px;margin:0 auto;padding:40px 24px 60px;}
        .page-header{margin-bottom:28px;}
        .page-header h2{font-family:'Playfair Display',serif;font-size:28px;color:#fff;margin-bottom:5px;}
        .page-header p{font-size:14px;color:rgba(255,255,255,0.4);}
        .orange-divider{width:56px;height:3px;background:linear-gradient(90deg,var(--orange-dark),var(--orange));border-radius:2px;margin:10px 0 0;}
        .alert{display:flex;align-items:center;gap:12px;padding:13px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;}
        .alert-error{background:rgba(231,76,60,0.12);border:1px solid rgba(231,76,60,0.35);color:#f5a0a0;}
        .alert-info{background:rgba(52,152,219,0.12);border:1px solid rgba(52,152,219,0.3);color:#a8d8f0;}
        /* Step 1 card */
        .card{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid var(--orange);}
        .card-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));padding:20px 28px;}
        .card-header h3{font-family:'Playfair Display',serif;font-size:18px;color:#fff;margin-bottom:4px;}
        .card-header p{font-size:12.5px;color:rgba(255,255,255,0.45);}
        .card-body{padding:28px;}
        .form-row{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;}
        .form-group{display:flex;flex-direction:column;gap:6px;flex:1;min-width:180px;}
        .form-group label{font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-mid);}
        .form-group input{padding:11px 13px;border:1.5px solid #e0e0e0;border-radius:var(--radius-sm);font-size:14px;font-family:'Inter',sans-serif;background:#fafafa;color:var(--text-dark);transition:var(--transition);}
        .form-group input:focus{outline:none;border-color:var(--orange);background:#fff;box-shadow:0 0 0 3px rgba(230,126,34,0.12);}
        .form-group input[readonly]{background:#f0f0f0;color:var(--text-mid);cursor:not-allowed;border-style:dashed;}
        .btn-search{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;background:linear-gradient(135deg,#1a5276,#2980b9);color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:var(--transition);white-space:nowrap;align-self:flex-end;}
        .btn-search:hover{transform:translateY(-2px);}
        /* Booking detail */
        .detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:20px;}
        .detail-item .dl{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-light);margin-bottom:3px;}
        .detail-item .dv{font-size:14px;font-weight:600;color:var(--text-dark);}
        .billing-box{background:linear-gradient(135deg,#fff8f0,#fef3e2);border:1px solid rgba(230,126,34,0.2);border-radius:var(--radius-md);padding:18px;margin-top:20px;display:flex;gap:24px;flex-wrap:wrap;align-items:center;justify-content:space-between;}
        .bill-item .bl{font-size:11px;color:var(--text-light);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;}
        .bill-item .bv{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:var(--navy);}
        .bill-item .bv.green{color:#27ae60;}
        .bill-item .bv.orange{color:var(--orange-dark);}
        .card-footer{padding:18px 28px;background:#fdf8f3;border-top:1px solid #f0e6d8;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
        .hint{font-size:12.5px;color:var(--text-light);}
        .btn-checkout{display:inline-flex;align-items:center;gap:10px;padding:13px 36px;background:linear-gradient(135deg,var(--orange-dark),var(--orange));color:#fff;border:none;border-radius:var(--radius-sm);font-size:15px;font-weight:700;font-family:'Inter',sans-serif;cursor:pointer;transition:var(--transition);box-shadow:0 6px 20px rgba(230,126,34,0.35);}
        .btn-checkout:hover{transform:translateY(-2px);}
        /* Success */
        .success-card{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid #27ae60;}
        .success-box{text-align:center;padding:48px 32px;}
        .success-icon{width:72px;height:72px;background:linear-gradient(135deg,#1e8449,#27ae60);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 8px 24px rgba(39,174,96,0.4);}
        .success-icon i{font-size:30px;color:#fff;}
        .success-box h3{font-family:'Playfair Display',serif;font-size:22px;color:var(--navy);margin-bottom:8px;}
        .success-box p{font-size:14px;color:var(--text-mid);margin-bottom:8px;}
        .receipt{background:#f8f6f1;border-radius:var(--radius-md);padding:18px;margin:20px 0;display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:left;}
        .receipt-item .rl{font-size:11px;color:var(--text-light);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;}
        .receipt-item .rv{font-size:14px;font-weight:600;color:var(--text-dark);}
        .receipt-item .rv.big{font-size:20px;color:#27ae60;font-family:'Playfair Display',serif;}
        .btn-home{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:'Inter',sans-serif;text-decoration:none;transition:var(--transition);}
        .btn-home:hover{transform:translateY(-2px);}
        @media(max-width:640px){.topbar{padding:0 16px;}.detail-grid,.receipt{grid-template-columns:1fr 1fr;}.card-footer{flex-direction:column;align-items:stretch;}.btn-checkout{justify-content:center;}}
    </style>
</head>
<body>

<header class="topbar">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-hotel"></i></div>
        <div class="brand"><h1>Sabawyan Hotel</h1><span>Guest Portal</span></div>
    </div>
    <a href="../public/rooms.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
</header>

<main class="page">
    <div class="page-header">
        <h2><i class="fas fa-sign-out-alt" style="color:#e67e22;margin-right:10px;font-size:24px;"></i>Check Out</h2>
        <p>Welcome, <?= htmlspecialchars($guestName) ?></p>
        <div class="orange-divider"></div>
    </div>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error_msg ?></div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($success): ?>
    <!-- ── SUCCESS ── -->
    <div class="success-card">
        <div class="success-box">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h3>Checked Out Successfully!</h3>
            <p>Thank you for staying with us at Sabawyan Hotel.</p>
            <div class="receipt">
                <div class="receipt-item"><div class="rl">Room</div><div class="rv"><?= htmlspecialchars($_GET['room'] ?? '—') ?></div></div>
                <div class="receipt-item"><div class="rl">Nights</div><div class="rv"><?= intval($_GET['nights'] ?? 0) ?></div></div>
                <div class="receipt-item"><div class="rl">Total Charged</div><div class="rv big">$<?= number_format(floatval($_GET['total'] ?? 0), 2) ?></div></div>
            </div>
            <p style="font-size:13px;color:#888;margin-bottom:20px;">We hope to see you again soon!</p>
            <a href="../../index.html" class="btn-home"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    </div>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
elseif ($booking && isset($_GET['step'])): ?>
    <!-- ── CONFIRM STEP ── -->
    <div class="alert alert-info"><i class="fas fa-info-circle"></i>Please review your booking details and confirm checkout.</div>
    <div class="card">
        <div class="card-header">
            <h3>Room <?= htmlspecialchars($booking['roomnumber']) ?> — Your Booking</h3>
            <p>Confirm the details below before checking out</p>
        </div>
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item"><div class="dl">Name</div><div class="dv"><?= htmlspecialchars($booking['name']) ?></div></div>
                <div class="detail-item"><div class="dl">Email</div><div class="dv"><?= htmlspecialchars($booking['email']) ?></div></div>
                <div class="detail-item"><div class="dl">Mobile</div><div class="dv"><?= htmlspecialchars($booking['mobilenumber']) ?></div></div>
                <div class="detail-item"><div class="dl">Room Type</div><div class="dv"><?= htmlspecialchars($booking['roomtype']) ?></div></div>
                <div class="detail-item"><div class="dl">Bed Type</div><div class="dv"><?= htmlspecialchars($booking['bedtype']) ?></div></div>
                <div class="detail-item"><div class="dl">Room Number</div><div class="dv"><?= htmlspecialchars($booking['roomnumber']) ?></div></div>
                <div class="detail-item"><div class="dl">Check-In</div><div class="dv"><?= htmlspecialchars($booking['checkin']) ?></div></div>
                <div class="detail-item"><div class="dl">Check-Out</div><div class="dv"><?= htmlspecialchars($booking['checkout']) ?></div></div>
            </div>
            <div class="billing-box">
                <div class="bill-item"><div class="bl">Rate / Night</div><div class="bv orange">$<?= htmlspecialchars($booking['priceperday']) ?></div></div>
                <div class="bill-item"><div class="bl">Nights Stayed</div><div class="bv"><?= intval($booking['daystayed']) ?></div></div>
                <div class="bill-item"><div class="bl">Total Amount</div><div class="bv green">$<?= number_format(floatval($booking['totalamount']),2) ?></div></div>
            </div>
        </div>
        <div class="card-footer">
            <span class="hint"><i class="fas fa-info-circle" style="margin-right:4px;"></i>This action cannot be undone.</span>
            <form method="POST" action="checkout.php">
                <button type="submit" name="check_out" class="btn-checkout">
                    <i class="fas fa-sign-out-alt"></i> Confirm Check Out
                </button>
            </form>
        </div>
    </div>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: ?>
    <!-- ── STEP 1: Search ── -->
    <div class="card">
        <div class="card-header">
            <h3>Your Booking</h3>
            <p>Set your check-out date and load your booking details</p>
        </div>
        <div class="card-body">
            <form action="checkout.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt" style="margin-right:5px;"></i>Check-Out Date</label>
                        <input type="date" name="checkout" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <button name="search" type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Load My Booking
                    </button>
                </div>
            </form>
            <p style="margin-top:16px;font-size:13px;color:var(--text-light);"><i class="fas fa-shield-alt" style="margin-right:5px;color:var(--orange-dark);"></i>Only your own booking will be shown — logged in as <strong><?= htmlspecialchars($guestEmail) ?></strong></p>
        </div>
    </div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>

</main>
</body>
</html>
