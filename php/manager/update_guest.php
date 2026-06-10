<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
$conn = getDB();
$backUrl   = isset($_GET["from"]) && $_GET["from"]==="finance" ? "../../html/dashboards/finance.html" : "../../html/dashboards/manager.html";
$backLabel = isset($_GET["from"]) && $_GET["from"]==="finance" ? "Finance Dashboard"    : "Manager Dashboard";
$fromParam = isset($_GET["from"]) ? "?from=".$_GET["from"] : "";

$name = $email = $mobile = $nationality = $gender = "";
$idproof = $address = $checkin = $checkout = "";
$bedtype = $roomtype = $priceperday = "";
$days = $total = $roomnumber = "";
$checkout_value = "";
$success_msg = "";
$error_msg   = "";

if (isset($_POST["update"])) {
    $roomnumber  = $_POST["roomnumber"];
    $name        = $_POST["name"];
    $email       = $_POST["email"];
    $mobile      = $_POST["mobile"];
    $nationality = $_POST["nationality"];
    $gender      = $_POST["gender"];
    $idproof     = $_POST["idproof"];
    $address     = $_POST["address"];
    $checkin     = $_POST["checkin"];
    $checkout    = $_POST["checkout"];
    $bedtype     = $_POST["bedtype"];
    $roomtype    = $_POST["roomtype"];
    $priceperday = $_POST["priceperday"];
    $days        = $_POST["daystayed"];
    $total       = $_POST["total"];

    $sql = "UPDATE customer SET
        name='$name', email='$email', mobilenumber='$mobile',
        nationality='$nationality', gender='$gender', idproof='$idproof',
        address='$address', checkin='$checkin', checkout='$checkout',
        bedtype='$bedtype', roomtype='$roomtype', priceperday='$priceperday',
        daystayed='$days', totalamount='$total'
        WHERE roomnumber='$roomnumber'";

    if ($conn->query($sql)) {
        $success_msg = "Customer record updated successfully.";
    } else {
        $error_msg = "Update error: " . $conn->error;
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
    <title>Update Customer — Sabawyan Hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--gold:#c9a84c;--gold-light:#e8c97a;--gold-dark:#a07830;--navy:#0d1b2a;--navy-mid:#1a2e45;--info:#3498db;--info-dark:#2980b9;--success:#2ecc71;--success-dark:#27ae60;--danger:#e74c3c;--text-dark:#1a1a2e;--text-mid:#4a4a6a;--text-light:#8a8aaa;--shadow-md:0 8px 28px rgba(0,0,0,0.18);--shadow-lg:0 16px 48px rgba(0,0,0,0.28);--shadow-gold:0 8px 24px rgba(201,168,76,0.25);--radius-sm:6px;--radius-md:12px;--radius-lg:20px;--radius-xl:28px;--transition:all 0.3s ease;}
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
        .gold-divider{width:56px;height:3px;background:linear-gradient(90deg,var(--gold-dark),var(--gold-light));border-radius:2px;margin:10px 0 0;}
        .alert{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;animation:fadeIn 0.4s ease;}
        .alert i{font-size:16px;flex-shrink:0;margin-top:1px;}
        .alert-success{background:rgba(46,204,113,0.12);border:1px solid rgba(46,204,113,0.35);color:#a8f0c6;}
        .alert-error{background:rgba(231,76,60,0.12);border:1px solid rgba(231,76,60,0.35);color:#f5a0a0;}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-6px);}to{opacity:1;transform:translateY(0);}}
        .card{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid var(--gold);}
        .search-section{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);padding:24px 32px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;}
        .search-section .field{flex:1;min-width:180px;}
        .search-section label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--gold-light);margin-bottom:7px;}
        .search-section input{width:100%;padding:11px 16px;background:rgba(255,255,255,0.08);border:1.5px solid rgba(201,168,76,0.3);border-radius:var(--radius-sm);color:#fff;font-size:15px;font-family:'Inter',sans-serif;transition:var(--transition);}
        .search-section input::placeholder{color:rgba(255,255,255,0.3);}
        .search-section input:focus{outline:none;border-color:var(--gold);background:rgba(255,255,255,0.12);box-shadow:0 0 0 3px rgba(201,168,76,0.15);}
        .btn-search{display:inline-flex;align-items:center;gap:8px;padding:11px 26px;background:linear-gradient(135deg,var(--info-dark),var(--info));color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:var(--transition);box-shadow:0 4px 14px rgba(52,152,219,0.35);white-space:nowrap;}
        .btn-search:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(52,152,219,0.45);}
        .form-body{padding:32px;}
        .section-label{display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--gold-dark);margin-bottom:18px;margin-top:28px;}
        .section-label:first-child{margin-top:0;}
        .section-label::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(201,168,76,0.3),transparent);}
        .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
        .col-span-3{grid-column:span 3;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group label{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-mid);}
        .form-group label i{margin-right:5px;color:var(--gold-dark);font-size:11px;}
        .form-group input,.form-group select{padding:11px 14px;border:1.5px solid #e0e0e0;border-radius:var(--radius-sm);font-size:14px;font-family:'Inter',sans-serif;background:#fafafa;color:var(--text-dark);transition:var(--transition);}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:var(--gold);background:#fff;box-shadow:0 0 0 3px rgba(201,168,76,0.15);}
        .form-group input[readonly]{background:#f0f0f0;color:var(--text-mid);cursor:not-allowed;border-style:dashed;}
        .computed-row{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;background:linear-gradient(135deg,#fdf9f0,#faf5e8);border:1px solid rgba(201,168,76,0.2);border-radius:var(--radius-md);padding:20px;margin-top:8px;}
        .computed-item{text-align:center;}
        .computed-item .c-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-light);margin-bottom:6px;}
        .computed-item .c-value{font-size:22px;font-weight:700;color:var(--navy);font-family:'Playfair Display',serif;}
        .computed-item .c-value.green{color:#27ae60;}
        .computed-item .c-value.gold{color:var(--gold-dark);}
        .computed-item input{display:none;}
        .form-footer{padding:24px 32px;background:#f8f6f1;border-top:1px solid #ede8dc;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
        .form-footer span{font-size:12.5px;color:var(--text-light);}
        .btn-update{display:inline-flex;align-items:center;gap:10px;padding:13px 36px;background:linear-gradient(135deg,var(--gold-dark),var(--gold),var(--gold-light));color:var(--navy);border:none;border-radius:var(--radius-sm);font-size:15px;font-weight:700;font-family:'Inter',sans-serif;cursor:pointer;transition:var(--transition);box-shadow:var(--shadow-gold);}
        .btn-update:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(201,168,76,0.4);}
        @media(max-width:700px){.topbar{padding:0 16px;}.page-content{padding:24px 16px 40px;}.search-section{padding:18px 16px;}.form-body{padding:20px 16px;}.grid-3{grid-template-columns:1fr;}.col-span-3{grid-column:span 1;}.computed-row{grid-template-columns:1fr;}.form-footer{flex-direction:column;align-items:stretch;}.btn-update{justify-content:center;}}
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
        <h2><i class="fas fa-user-edit" style="color:var(--gold-light);margin-right:10px;font-size:24px;"></i>Update Customer</h2>
        <p>Search by room number, edit details, then save</p>
        <div class="gold-divider"></div>
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

    <div class="card">
        <form action="update_guest.php<?= $fromParam ?>" method="POST">

            <div class="search-section">
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

            <div class="form-body">
                <div class="section-label"><i class="fas fa-user"></i> Personal Information</div>
                <div class="grid-3">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i>Full Name</label>
                        <input type="text" name="name" placeholder="Guest name" value="<?= htmlspecialchars($name) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i>Email</label>
                        <input type="email" name="email" placeholder="email@example.com" value="<?= htmlspecialchars($email) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i>Mobile</label>
                        <input type="text" name="mobile" placeholder="Phone number" value="<?= htmlspecialchars($mobile) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-globe"></i>Nationality</label>
                        <input type="text" name="nationality" placeholder="Country" value="<?= htmlspecialchars($nationality) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-venus-mars"></i>Gender</label>
                        <select name="gender">
                            <option value="">— Select —</option>
                            <option value="Male"   <?= $gender==='Male'  ?'selected':'' ?>>Male</option>
                            <option value="Female" <?= $gender==='Female'?'selected':'' ?>>Female</option>
                            <option value="Other"  <?= $gender==='Other' ?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i>ID Proof</label>
                        <input type="text" name="idproof" placeholder="Passport / Aadhar…" value="<?= htmlspecialchars($idproof) ?>">
                    </div>
                    <div class="form-group col-span-3">
                        <label><i class="fas fa-map-marker-alt"></i>Address</label>
                        <input type="text" name="address" placeholder="Full address" value="<?= htmlspecialchars($address) ?>">
                    </div>
                </div>

                <div class="section-label"><i class="fas fa-bed"></i> Room &amp; Stay Details</div>
                <div class="grid-3">
                    <div class="form-group">
                        <label><i class="fas fa-sign-in-alt"></i>Check-In Date</label>
                        <input type="date" name="checkin" value="<?= htmlspecialchars($checkin) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-sign-out-alt"></i>Check-Out Date</label>
                        <input type="date" name="checkout" value="<?= htmlspecialchars($checkout) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-bed"></i>Bed Type</label>
                        <input type="text" name="bedtype" placeholder="Single / Double…" value="<?= htmlspecialchars($bedtype) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i>Room Type</label>
                        <input type="text" name="roomtype" placeholder="Deluxe / Suite…" value="<?= htmlspecialchars($roomtype) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i>Price / Day ($)</label>
                        <input type="number" name="priceperday" placeholder="0.00" value="<?= htmlspecialchars($priceperday) ?>">
                    </div>
                </div>

                <div class="section-label"><i class="fas fa-calculator"></i> Billing Summary</div>
                <div class="computed-row">
                    <div class="computed-item">
                        <div class="c-label"><i class="fas fa-moon"></i> Nights Stayed</div>
                        <div class="c-value gold"><?= $days !== '' ? intval($days) : '—' ?></div>
                        <input type="number" name="daystayed" value="<?= htmlspecialchars($days) ?>">
                    </div>
                    <div class="computed-item">
                        <div class="c-label"><i class="fas fa-tag"></i> Rate / Night</div>
                        <div class="c-value"><?= $priceperday !== '' ? '$'.htmlspecialchars($priceperday) : '—' ?></div>
                    </div>
                    <div class="computed-item">
                        <div class="c-label"><i class="fas fa-coins"></i> Total Amount</div>
                        <div class="c-value green"><?= $total !== '' ? '$'.number_format(floatval($total),2) : '—' ?></div>
                        <input type="number" name="total" value="<?= htmlspecialchars($total) ?>">
                    </div>
                </div>
            </div>

            <div class="form-footer">
                <span><i class="fas fa-info-circle" style="margin-right:5px;"></i>Search a room first, then edit and save.</span>
                <button type="submit" name="update" class="btn-update"><i class="fas fa-save"></i> Save Changes</button>
            </div>

        </form>
    </div>
</main>
</body>
</html>
