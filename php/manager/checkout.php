<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
$conn = getDB();
$backUrl   = isset($_GET["from"]) && $_GET["from"]==="finance" ? "../../html/dashboards/finance.html"  : "../../html/dashboards/manager.html";
$backLabel = isset($_GET["from"]) && $_GET["from"]==="finance" ? "Finance Dashboard"     : "Manager Dashboard";
$fromParam = isset($_GET["from"]) ? "?from=".$_GET["from"] : "";

$name = $email = $mobile = $nationality = $gender = "";
$idproof = $address = $checkin = $checkout = "";
$bedtype = $roomtype = $priceperday = "";
$days = $total = $roomnumber = "";
$checkout_value = "";
$success_msg = "";
$error_msg   = "";
$checked_out = false;

if (isset($_POST["check_out"])) {
    $roomnumber = $_POST["roomnumber"];
    $conn->query("UPDATE rooms SET status='not booked' WHERE roomnumber='$roomnumber'");
    $result = $conn->query("SELECT * FROM customer WHERE roomnumber='$roomnumber'");
    if ($result->rowCount() > 0) {
        $row         = $result->fetch(PDO::FETCH_ASSOC);
        $name        = $row["name"];
        $email       = $row["email"];
        $mobile      = $row["mobilenumber"];
        $nationality = $row["nationality"];
        $gender      = $row["gender"];
        $idproof     = $row["idproof"];
        $address     = $row["address"];
        $checkin     = $row["checkin"];
        $checkout    = $row["checkout"];
        $roomnumber  = $row["roomnumber"];
        $bedtype     = $row["bedtype"];
        $roomtype    = $row["roomtype"];
        $priceperday = $row["priceperday"];
        $days        = $row["daystayed"];
        $total       = $row["totalamount"];
        $ins = "INSERT INTO deleted_customers
            (name,email,mobilenumber,nationality,gender,idproof,address,checkin,checkout,bedtype,roomtype,priceperday,roomnumber,daystayed,totalamount)
            VALUES
            ('$name','$email','$mobile','$nationality','$gender','$idproof','$address','$checkin','$checkout','$bedtype','$roomtype','$priceperday','$roomnumber','$days','$total')";
        $conn->query($ins);
        $conn->query("DELETE FROM customer WHERE roomnumber='$roomnumber'");
        $success_msg = "Guest <strong>$name</strong> checked out from Room <strong>$roomnumber</strong> successfully.";
        $checked_out = true;
    } else {
        $error_msg = "No customer found for Room <strong>$roomnumber</strong>.";
    }
}

if (isset($_POST["search"])) {
    $roomnumber     = $_POST["roomnumber"];
    $checkout_value = $_POST["checkout"];
    $conn->query("UPDATE customer SET checkout='$checkout_value' WHERE roomnumber='$roomnumber'");
    $result = $conn->query("SELECT * FROM customer WHERE roomnumber='$roomnumber'");
    if ($result->rowCount() > 0) {
        $row         = $result->fetch(PDO::FETCH_ASSOC);
        $name        = $row["name"];
        $email       = $row["email"];
        $mobile      = $row["mobilenumber"];
        $nationality = $row["nationality"];
        $gender      = $row["gender"];
        $idproof     = $row["idproof"];
        $address     = $row["address"];
        $checkin     = $row["checkin"];
        $checkout    = $row["checkout"];
        $bedtype     = $row["bedtype"];
        $roomtype    = $row["roomtype"];
        $priceperday = $row["priceperday"];
        $days        = ($checkin && $checkout_value)
                       ? (strtotime($checkout_value) - strtotime($checkin)) / (60*60*24)
                       : 0;
        $total       = $days * $priceperday;
        $conn->query("UPDATE customer SET totalamount='$total', daystayed='$days' WHERE roomnumber='$roomnumber'");
    } else {
        $error_msg = "No customer found for Room <strong>$roomnumber</strong>.";
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
        :root{--gold:#c9a84c;--gold-light:#e8c97a;--gold-dark:#a07830;--navy:#0d1b2a;--navy-mid:#1a2e45;--orange:#e67e22;--orange-dark:#a04000;--success:#2ecc71;--danger:#e74c3c;--text-dark:#1a1a2e;--text-mid:#4a4a6a;--text-light:#8a8aaa;--shadow-md:0 8px 28px rgba(0,0,0,0.18);--shadow-lg:0 16px 48px rgba(0,0,0,0.28);--shadow-gold:0 8px 24px rgba(201,168,76,0.25);--radius-sm:6px;--radius-md:12px;--radius-lg:20px;--transition:all 0.3s ease;}
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
        .page-content{position:relative;z-index:1;max-width:860px;margin:0 auto;padding:40px 24px 60px;}
        .page-header{margin-bottom:28px;}
        .page-header h2{font-family:'Playfair Display',serif;font-size:28px;color:#fff;margin-bottom:5px;}
        .page-header p{font-size:14px;color:rgba(255,255,255,0.4);}
        .orange-divider{width:56px;height:3px;background:linear-gradient(90deg,var(--orange-dark),var(--orange));border-radius:2px;margin:10px 0 0;}
        .alert{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;animation:fadeIn 0.4s ease;}
        .alert i{font-size:16px;flex-shrink:0;margin-top:1px;}
        .alert-error{background:rgba(231,76,60,0.12);border:1px solid rgba(231,76,60,0.35);color:#f5a0a0;}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-6px);}to{opacity:1;transform:translateY(0);}}
        .card{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid var(--orange);}
        .search-strip{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);padding:22px 32px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;}
        .search-strip .field{flex:1;min-width:160px;}
        .search-strip label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--gold-light);margin-bottom:7px;}
        .search-strip input{width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1.5px solid rgba(201,168,76,0.3);border-radius:var(--radius-sm);color:#fff;font-size:15px;font-family:'Inter',sans-serif;transition:var(--transition);}
        .search-strip input::placeholder{color:rgba(255,255,255,0.3);}
        .search-strip input:focus{outline:none;border-color:var(--gold);background:rgba(255,255,255,0.12);box-shadow:0 0 0 3px rgba(201,168,76,0.15);}
        .btn-search{display:inline-flex;align-items:center;gap:8px;padding:11px 26px;background:linear-gradient(135deg,#1a5276,#2980b9);color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:var(--transition);box-shadow:0 4px 14px rgba(52,152,219,0.35);white-space:nowrap;}
        .btn-search:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(52,152,219,0.45);}
        .form-body{padding:30px 32px;}
        .section-label{display:flex;align-items:center;gap:10px;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--orange-dark);margin-bottom:16px;margin-top:26px;}
        .section-label:first-child{margin-top:0;}
        .section-label::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(230,126,34,0.25),transparent);}
        .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
        .col-span-3{grid-column:span 3;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group label{font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-mid);}
        .form-group label i{margin-right:5px;color:var(--orange-dark);font-size:11px;}
        .form-group input{padding:11px 13px;border:1.5px solid #e0e0e0;border-radius:var(--radius-sm);font-size:14px;font-family:'Inter',sans-serif;background:#fafafa;color:var(--text-dark);transition:var(--transition);}
        .form-group input[readonly]{background:#f4f4f4;color:var(--text-mid);cursor:not-allowed;border-style:dashed;}
        .billing-box{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;background:linear-gradient(135deg,#fff8f0,#fef3e2);border:1px solid rgba(230,126,34,0.2);border-radius:var(--radius-md);padding:20px;margin-top:6px;}
        .bill-item{text-align:center;}
        .bill-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-light);margin-bottom:6px;}
        .bill-value{font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:var(--navy);}
        .bill-value.orange{color:var(--orange-dark);}
        .bill-value.green{color:#27ae60;}
        .form-footer{padding:20px 32px 28px;background:#fdf8f3;border-top:1px solid #f0e6d8;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
        .form-footer .hint{font-size:12.5px;color:var(--text-light);}
        .btn-checkout{display:inline-flex;align-items:center;gap:10px;padding:13px 36px;background:linear-gradient(135deg,var(--orange-dark),var(--orange));color:#fff;border:none;border-radius:var(--radius-sm);font-size:15px;font-weight:700;font-family:'Inter',sans-serif;cursor:pointer;transition:var(--transition);box-shadow:0 6px 20px rgba(230,126,34,0.35);}
        .btn-checkout:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(230,126,34,0.45);}
        .btn-checkout:disabled{opacity:0.45;cursor:not-allowed;transform:none;}
        .checkout-success{text-align:center;padding:48px 32px;}
        .success-icon{width:72px;height:72px;background:linear-gradient(135deg,#27ae60,#2ecc71);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 8px 24px rgba(46,204,113,0.35);animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);}
        @keyframes popIn{from{transform:scale(0);}to{transform:scale(1);}}
        .success-icon i{font-size:30px;color:#fff;}
        .checkin-success h3,.checkout-success h3{font-family:'Playfair Display',serif;font-size:22px;color:var(--navy);margin-bottom:8px;}
        .checkout-success p{font-size:14px;color:var(--text-mid);margin-bottom:24px;}
        .receipt-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;background:#f8f6f1;border-radius:var(--radius-md);padding:18px;margin-bottom:24px;text-align:left;}
        .receipt-item .r-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-light);margin-bottom:3px;}
        .receipt-item .r-value{font-size:14px;font-weight:600;color:var(--text-dark);}
        .receipt-item .r-value.big{font-size:20px;color:#27ae60;font-family:'Playfair Display',serif;}
        .btn-back-dash{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:'Inter',sans-serif;text-decoration:none;transition:var(--transition);box-shadow:var(--shadow-md);}
        .btn-back-dash:hover{transform:translateY(-2px);}
        @media(max-width:700px){.topbar{padding:0 16px;}.page-content{padding:24px 12px 40px;}.search-strip{padding:18px 16px;}.form-body{padding:20px 16px;}.grid-3,.billing-box,.receipt-grid{grid-template-columns:1fr;}.col-span-3{grid-column:span 1;}.form-footer{flex-direction:column;align-items:stretch;}.btn-checkout{justify-content:center;}}
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon"><i class="fas fa-hotel"></i></div>
        <div class="brand-text"><h1>Sabawyan Hotel</h1><span>Management System</span></div>
    </div>
    <a href="<?= $backUrl ?>" class="back-btn"><i class="fas fa-arrow-left"></i> <?= $backLabel ?></a>
</header>

<main class="page-content">
    <div class="page-header">
        <h2><i class="fas fa-sign-out-alt" style="color:#e67e22;margin-right:10px;font-size:24px;"></i>Guest Check Out</h2>
        <p>Search by room number, confirm details, then process checkout</p>
        <div class="orange-divider"></div>
    </div>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?= $error_msg ?></span></div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
endif; ?>

    <div class="card">
        <form action="checkout.php<?= $fromParam ?>" method="POST">
            <div class="search-strip">
                <div class="field">
                    <label><i class="fas fa-door-open"></i> Room Number</label>
                    <input type="number" name="roomnumber" placeholder="e.g. 101" value="<?= htmlspecialchars($roomnumber) ?>">
                </div>
                <div class="field">
                    <label><i class="fas fa-calendar-alt"></i> Check-Out Date</label>
                    <input type="date" name="checkout" value="<?= htmlspecialchars($checkout_value) ?>">
                </div>
                <button name="search" type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
            </div>

            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
if ($checked_out): ?>
            <div class="checkout-success">
                <div class="success-icon"><i class="fas fa-check"></i></div>
                <h3>Checkout Complete!</h3>
                <p>Guest has been checked out and the room is now available.</p>
                <div class="receipt-grid">
                    <div class="receipt-item"><div class="r-label">Guest Name</div><div class="r-value"><?= htmlspecialchars($name) ?></div></div>
                    <div class="receipt-item"><div class="r-label">Room No.</div><div class="r-value"><?= htmlspecialchars($roomnumber) ?></div></div>
                    <div class="receipt-item"><div class="r-label">Room Type</div><div class="r-value"><?= htmlspecialchars($roomtype) ?></div></div>
                    <div class="receipt-item"><div class="r-label">Check-In</div><div class="r-value"><?= htmlspecialchars($checkin) ?></div></div>
                    <div class="receipt-item"><div class="r-label">Check-Out</div><div class="r-value"><?= htmlspecialchars($checkout) ?></div></div>
                    <div class="receipt-item"><div class="r-label">Nights Stayed</div><div class="r-value"><?= intval($days) ?></div></div>
                    <div class="receipt-item" style="grid-column:span 3">
                        <div class="r-label">Total Amount Charged</div>
                        <div class="r-value big">$<?= number_format(floatval($total),2) ?></div>
                    </div>
                </div>
                <a href="<?= $backUrl ?>" class="btn-back-dash"><i class="fas fa-home"></i> <?= $backLabel ?></a>
            </div>

            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
else: ?>
            <div class="form-body">
                <div class="section-label"><i class="fas fa-user"></i> Personal Information</div>
                <div class="grid-3">
                    <div class="form-group"><label><i class="fas fa-user"></i>Full Name</label><input type="text" value="<?= htmlspecialchars($name) ?>" readonly placeholder="—"></div>
                    <div class="form-group"><label><i class="fas fa-envelope"></i>Email</label><input type="email" value="<?= htmlspecialchars($email) ?>" readonly placeholder="—"></div>
                    <div class="form-group"><label><i class="fas fa-phone"></i>Mobile</label><input type="text" value="<?= htmlspecialchars($mobile) ?>" readonly placeholder="—"></div>
                    <div class="form-group"><label><i class="fas fa-globe"></i>Nationality</label><input type="text" value="<?= htmlspecialchars($nationality) ?>" readonly placeholder="—"></div>
                    <div class="form-group"><label><i class="fas fa-venus-mars"></i>Gender</label><input type="text" value="<?= htmlspecialchars($gender) ?>" readonly placeholder="—"></div>
                    <div class="form-group"><label><i class="fas fa-id-card"></i>ID Proof</label><input type="text" value="<?= htmlspecialchars($idproof) ?>" readonly placeholder="—"></div>
                    <div class="form-group col-span-3"><label><i class="fas fa-map-marker-alt"></i>Address</label><input type="text" value="<?= htmlspecialchars($address) ?>" readonly placeholder="—"></div>
                </div>
                <div class="section-label"><i class="fas fa-bed"></i> Room & Stay</div>
                <div class="grid-3">
                    <div class="form-group"><label><i class="fas fa-sign-in-alt"></i>Check-In Date</label><input type="date" value="<?= htmlspecialchars($checkin) ?>" readonly></div>
                    <div class="form-group"><label><i class="fas fa-sign-out-alt"></i>Check-Out Date</label><input type="date" value="<?= htmlspecialchars($checkout ?: $checkout_value) ?>" readonly></div>
                    <div class="form-group"><label><i class="fas fa-door-open"></i>Room Number</label><input type="text" value="<?= htmlspecialchars($roomnumber) ?>" readonly placeholder="—"></div>
                    <div class="form-group"><label><i class="fas fa-bed"></i>Bed Type</label><input type="text" value="<?= htmlspecialchars($bedtype) ?>" readonly placeholder="—"></div>
                    <div class="form-group"><label><i class="fas fa-building"></i>Room Type</label><input type="text" value="<?= htmlspecialchars($roomtype) ?>" readonly placeholder="—"></div>
                </div>
                <div class="section-label"><i class="fas fa-receipt"></i> Billing Summary</div>
                <div class="billing-box">
                    <div class="bill-item"><div class="bill-label"><i class="fas fa-moon"></i> Nights Stayed</div><div class="bill-value orange"><?= $days!==''?intval($days):'—' ?></div></div>
                    <div class="bill-item"><div class="bill-label"><i class="fas fa-tag"></i> Rate / Night</div><div class="bill-value"><?= $priceperday!==''?'$'.htmlspecialchars($priceperday):'—' ?></div></div>
                    <div class="bill-item"><div class="bill-label"><i class="fas fa-coins"></i> Total Amount</div><div class="bill-value green"><?= $total!==''?'$'.number_format(floatval($total),2):'—' ?></div></div>
                </div>
            </div>
            <div class="form-footer">
                <span class="hint"><i class="fas fa-info-circle" style="margin-right:4px;"></i>Search a room first, then confirm and check out.</span>
                <button type="submit" name="check_out" class="btn-checkout" <?= !$name?'disabled':'' ?>>
                    <i class="fas fa-sign-out-alt"></i> Confirm Check Out
                </button>
            </div>
            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
endif; ?>
        </form>
    </div>
</main>
</body>
</html>
