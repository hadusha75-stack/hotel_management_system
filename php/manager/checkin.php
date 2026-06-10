<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';

$conn = getDB();
$backUrl   = isset($_GET["from"]) && $_GET["from"]==="finance" ? "../../html/dashboards/finance.html" : "../../html/dashboards/manager.html";
$backLabel = isset($_GET["from"]) && $_GET["from"]==="finance" ? "Finance Dashboard" : "Manager Dashboard";
$fromParam = isset($_GET["from"]) ? "?from=".$_GET["from"] : "";

$availableRooms = [];
$roomsLoaded    = false;
$checkin_done   = false;
$success_msg    = "";
$error_msg      = "";
$field_errors   = [];

// ── STEP 1: Load available rooms (with individual prices) ─────
if (isset($_POST["loadrooms"])) {
    $selectedBed  = trim($_POST["bedtype"]  ?? "");
    $selectedRoom = trim($_POST["roomtype"] ?? "");
    $stmt = $conn->prepare("SELECT roomnumber, price, bedtype, roomtype FROM rooms WHERE bedtype=? AND roomtype=? AND status='not booked' ORDER BY roomnumber");
    $stmt->bind_param("ss", $selectedBed, $selectedRoom);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $availableRooms[] = $row;
    }
    $stmt->close();
    $roomsLoaded = true;
}

// ── STEP 2: Get price for selected room number ─────────────────
// Called via AJAX when room number changes
if (isset($_GET["get_price"])) {
    header("Content-Type: application/json");
    $rn   = $conn->real_escape_string($_GET["get_price"]);
    $row  = $conn->query("SELECT price FROM rooms WHERE roomnumber='$rn'")->fetch_assoc();
    echo json_encode(["price" => $row["price"] ?? 0]);
    exit;
}

// ── STEP 3: Submit check-in ────────────────────────────────────
if (isset($_POST["checkin_submit"])) {
    $name         = trim($_POST["name"]         ?? "");
    $email        = trim($_POST["email"]        ?? "");
    $mobilenumber = trim($_POST["mobilenumber"] ?? "");
    $nationality  = trim($_POST["nationality"]  ?? "");
    $gender       = trim($_POST["gender"]       ?? "");
    $address      = trim($_POST["address"]      ?? "");
    $idproof      = trim($_POST["idproof"]      ?? "");
    $roomnumber   = trim($_POST["roomnumber"]   ?? "");
    $checkin      = trim($_POST["checkin"]      ?? "");
    $availableRooms = json_decode($_POST["rooms_json"] ?? "[]", true);
    $roomsLoaded    = !empty($availableRooms);

    // Get room details from DB
    $roomRow = null;
    if ($roomnumber) {
        $rn = $conn->real_escape_string($roomnumber);
        $roomRow = $conn->query("SELECT price, bedtype, roomtype FROM rooms WHERE roomnumber='$rn'")->fetch_assoc();
    }
    $priceperday = $roomRow["price"]   ?? 0;
    $bedtype     = $roomRow["bedtype"] ?? "";
    $roomtype    = $roomRow["roomtype"]?? "";

    // ── Validation ──────────────────────────────────────────────
    // Name: two words, 3-10 chars each, letters only
    if (empty($name)) {
        $field_errors["name"] = "Full name is required.";
    } elseif (!preg_match('/^[a-zA-Z]+(\s+[a-zA-Z]+)+$/', $name)) {
        $field_errors["name"] = "Enter first and last name (e.g. Ashenafi Hadush).";
    } else {
        foreach (preg_split('/\s+/', $name) as $p) {
            if (strlen($p)<3) { $field_errors["name"]="Each name part must be at least 3 characters."; break; }
            if (strlen($p)>10){ $field_errors["name"]="Each name part must not exceed 10 characters."; break; }
        }
    }

    // Email unique
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors["email"] = "Enter a valid email address.";
    } else {
        $em = $conn->real_escape_string($email);
        $dup = $conn->query("SELECT id FROM customer WHERE email='$em' LIMIT 1")->fetch_assoc();
        if ($dup) $field_errors["email"] = "This email already has an active booking.";
    }

    // Phone: Ethiopian format, unique
    if (empty($mobilenumber)) {
        $field_errors["mobilenumber"] = "Mobile number is required.";
    } elseif (!preg_match('/^(\+?251[79]\d{8}|0[79]\d{8})$/', $mobilenumber)) {
        $field_errors["mobilenumber"] = "Enter a valid Ethiopian number: 09XXXXXXXX or +2519XXXXXXXX.";
    } else {
        $mob = $conn->real_escape_string($mobilenumber);
        $dup = $conn->query("SELECT id FROM customer WHERE mobilenumber='$mob' LIMIT 1")->fetch_assoc();
        if ($dup) $field_errors["mobilenumber"] = "This phone number already has an active booking.";
    }

    // Nationality
    if (empty($nationality) || !preg_match('/^[a-zA-Z\s]+$/', $nationality) || strlen($nationality)<3)
        $field_errors["nationality"] = "Nationality must be letters only, at least 3 characters.";

    // ID Proof: unique
    if (empty($idproof) || strlen($idproof)<4) {
        $field_errors["idproof"] = "ID proof must be at least 4 characters.";
    } else {
        $id = $conn->real_escape_string($idproof);
        $dup = $conn->query("SELECT id FROM customer WHERE idproof='$id' LIMIT 1")->fetch_assoc();
        if ($dup) $field_errors["idproof"] = "This ID proof already has an active booking.";
    }

    if (empty($address) || strlen($address)<5)
        $field_errors["address"] = "Address must be at least 5 characters.";

    // Check-in date: REQUIRED
    if (empty($checkin)) {
        $field_errors["checkin"] = "Check-in date is required.";
    } elseif (strtotime($checkin) < strtotime(date("Y-m-d"))) {
        $field_errors["checkin"] = "Check-in date cannot be in the past.";
    }

    if (empty($roomnumber))  $field_errors["roomnumber"] = "Please select a room.";
    if (empty($priceperday)) $field_errors["price"]      = "Room price not found.";

    if (empty($field_errors)) {
        $n = $conn->real_escape_string($name);
        $e = $conn->real_escape_string($email);
        $m = $conn->real_escape_string($mobilenumber);
        $nat = $conn->real_escape_string($nationality);
        $g = $conn->real_escape_string($gender);
        $addr = $conn->real_escape_string($address);
        $idp = $conn->real_escape_string($idproof);
        $bt = $conn->real_escape_string($bedtype);
        $rt = $conn->real_escape_string($roomtype);
        $rn = $conn->real_escape_string($roomnumber);
        $ci = $conn->real_escape_string($checkin);

        // If finance or manager checks in — auto-approve payment
        $role = $_SESSION['role'] ?? 'guest';
        $payStatus   = in_array($role, ['finance','manager']) ? 'Paid'   : 'Unpaid';
        $approvedBy  = in_array($role, ['finance','manager']) ? $conn->real_escape_string($_SESSION['email'] ?? '') : 'NULL';
        $approvedAt  = in_array($role, ['finance','manager']) ? "NOW()" : "NULL";

        $conn->query("INSERT INTO customer (name,email,mobilenumber,nationality,gender,address,idproof,bedtype,roomtype,roomnumber,checkin,priceperday,payment_status,payment_approved_by,payment_approved_at)
            VALUES ('$n','$e','$m','$nat','$g','$addr','$idp','$bt','$rt','$rn','$ci','$priceperday','$payStatus','$approvedBy',$approvedAt)");
        $conn->query("UPDATE rooms SET status='booked' WHERE roomnumber='$rn'");
        $checkin_done = true;
        $payLabel = $payStatus === 'Paid' ? '✅ Payment auto-approved' : '⏳ Awaiting payment approval';
        $success_msg = "Guest <strong>$name</strong> checked in to Room <strong>$roomnumber</strong> at ETB $priceperday/night. $payLabel.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check In — Sabawyan Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--gold:#c9a84c;--gold-light:#e8c97a;--gold-dark:#a07830;--navy:#0d1b2a;--navy-mid:#1a2e45;--blue:#2980b9;--blue-dark:#1a5276;--text-dark:#1a1a2e;--text-mid:#4a4a6a;--text-light:#8a8aaa;--shadow-lg:0 16px 48px rgba(0,0,0,0.28);--shadow-gold:0 8px 24px rgba(201,168,76,0.25);--radius-sm:6px;--radius-md:12px;--radius-lg:20px;--transition:all 0.3s ease;}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:Inter,sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a2e45 50%,#243b55 100%);background-attachment:fixed;min-height:100vh;}
        body::before{content:"";position:fixed;inset:0;background:url("../../photo/hotelnamebg.jpg") center/cover no-repeat;opacity:0.05;z-index:0;}
        .topbar{position:relative;z-index:10;background:rgba(13,27,42,0.96);backdrop-filter:blur(12px);border-bottom:1px solid rgba(201,168,76,0.2);padding:0 32px;height:66px;display:flex;align-items:center;justify-content:space-between;}
        .brand{display:flex;align-items:center;gap:12px;} .brand-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:10px;display:flex;align-items:center;justify-content:center;} .brand-icon i{font-size:18px;color:var(--navy);}
        .brand h1{font-family:"Playfair Display",serif;font-size:19px;color:#fff;line-height:1.2;} .brand span{font-size:11.5px;color:var(--gold-light);}
        .back-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.3);border-radius:var(--radius-sm);color:var(--gold-light);font-size:13px;font-weight:600;text-decoration:none;transition:var(--transition);}
        .back-btn:hover{background:rgba(201,168,76,0.2);color:var(--gold);transform:translateX(-2px);}
        .page{position:relative;z-index:1;max-width:900px;margin:0 auto;padding:40px 24px 60px;}
        .page-header{margin-bottom:28px;} .page-header h2{font-family:"Playfair Display",serif;font-size:28px;color:#fff;margin-bottom:5px;} .page-header p{font-size:14px;color:rgba(255,255,255,0.4);}
        .blue-divider{width:56px;height:3px;background:linear-gradient(90deg,var(--blue-dark),var(--blue));border-radius:2px;margin:10px 0 0;}
        .alert{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;}
        .alert-error{background:rgba(231,76,60,0.12);border:1px solid rgba(231,76,60,0.35);color:#f5a0a0;}
        .card{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid var(--blue);}
        .step-strip{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);padding:22px 32px;}
        .step-title{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.3);margin-bottom:14px;}
        .strip-fields{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;}
        .strip-fields .field{flex:1;min-width:140px;}
        .strip-fields label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--gold-light);margin-bottom:7px;}
        .strip-fields select{width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1.5px solid rgba(201,168,76,0.3);border-radius:var(--radius-sm);color:#fff;font-size:14px;font-family:Inter,sans-serif;transition:var(--transition);appearance:none;}
        .strip-fields select option{background:#1a2e45;color:#fff;}
        .strip-fields select:focus{outline:none;border-color:var(--gold);}
        .btn-load{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;background:linear-gradient(135deg,var(--blue-dark),var(--blue));color:#fff;border:none;border-radius:var(--radius-sm);font-size:13.5px;font-weight:600;font-family:Inter,sans-serif;cursor:pointer;transition:var(--transition);white-space:nowrap;align-self:flex-end;}
        .btn-load:hover{transform:translateY(-2px);}
        .rooms-bar{padding:13px 32px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid #eef2f8;}
        .rooms-bar.found{background:#eef6ff;} .rooms-bar.empty{background:#fff5f5;}
        .rb-label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;}
        .rooms-bar.found .rb-label{color:#1a5276;} .rooms-bar.empty .rb-label{color:#c0392b;}
        .room-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;background:rgba(41,128,185,0.1);border:1px solid rgba(41,128,185,0.25);border-radius:20px;font-size:12px;font-weight:600;color:#1a5276;}
        .form-body{padding:28px 32px;}
        .section-label{display:flex;align-items:center;gap:10px;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--blue-dark);margin-bottom:16px;margin-top:24px;}
        .section-label:first-child{margin-top:0;}
        .section-label::after{content:"";flex:1;height:1px;background:linear-gradient(90deg,rgba(41,128,185,0.2),transparent);}
        .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;} .col-span-3{grid-column:span 3;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group label{font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-mid);}
        .form-group label i{margin-right:5px;color:var(--blue-dark);font-size:11px;}
        .form-group input,.form-group select{padding:11px 13px;border:1.5px solid #e0e0e0;border-radius:var(--radius-sm);font-size:14px;font-family:Inter,sans-serif;background:#fafafa;color:var(--text-dark);transition:var(--transition);appearance:none;}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(41,128,185,0.12);}
        .form-group input.is-invalid{border-color:#e74c3c!important;background:#fff8f8!important;}
        .field-error{font-size:11.5px;color:#e74c3c;font-weight:500;display:flex;align-items:center;gap:4px;margin-top:2px;}
        .assign-box{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;background:linear-gradient(135deg,#eef6ff,#e4f0ff);border:1px solid rgba(41,128,185,0.18);border-radius:var(--radius-md);padding:20px;margin-top:4px;}
        .price-display{background:linear-gradient(135deg,#fdf9f0,#faf5e8);border:1px solid rgba(201,168,76,0.25);border-radius:var(--radius-sm);padding:11px 13px;font-size:18px;font-weight:700;color:var(--gold-dark);font-family:"Playfair Display",serif;}
        .form-footer{padding:20px 32px 28px;background:#f5f8ff;border-top:1px solid #dce8f5;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
        .form-footer .hint{font-size:12.5px;color:var(--text-light);}
        .btn-checkin{display:inline-flex;align-items:center;gap:10px;padding:13px 36px;background:linear-gradient(135deg,var(--gold-dark),var(--gold),var(--gold-light));color:var(--navy);border:none;border-radius:var(--radius-sm);font-size:15px;font-weight:700;font-family:Inter,sans-serif;cursor:pointer;transition:var(--transition);box-shadow:var(--shadow-gold);}
        .btn-checkin:hover{transform:translateY(-2px);}
        .btn-reset{display:inline-flex;align-items:center;gap:8px;padding:12px 22px;background:#f0f0f0;color:var(--text-mid);border:1.5px solid #ddd;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:Inter,sans-serif;cursor:pointer;}
        .success-box{text-align:center;padding:48px 32px;}
        .success-icon{width:72px;height:72px;background:linear-gradient(135deg,var(--blue-dark),var(--blue));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 8px 24px rgba(41,128,185,0.4);}
        .success-icon i{font-size:30px;color:#fff;}
        .success-box h3{font-family:"Playfair Display",serif;font-size:22px;color:var(--navy);margin-bottom:8px;}
        .success-box p{font-size:14px;color:var(--text-mid);margin-bottom:24px;}
        .btn-new{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,var(--blue-dark),var(--blue));color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:Inter,sans-serif;text-decoration:none;transition:var(--transition);margin-right:10px;}
        .btn-dash{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:600;font-family:Inter,sans-serif;text-decoration:none;}
        @media(max-width:700px){.topbar{padding:0 16px;}.page{padding:24px 12px 40px;}.step-strip{padding:18px 16px;}.form-body{padding:20px 16px;}.grid-3,.assign-box{grid-template-columns:1fr;}.col-span-3{grid-column:span 1;}.form-footer{flex-direction:column;align-items:stretch;}.btn-checkin{justify-content:center;}}
    </style>
</head>
<body>
<header class="topbar">
    <div class="brand"><div class="brand-icon"><i class="fas fa-hotel"></i></div><div><h1>Sabawyan Hotel</h1><span>Management System</span></div></div>
    <a href="<?= $backUrl ?>" class="back-btn"><i class="fas fa-arrow-left"></i> <?= $backLabel ?></a>
</header>
<main class="page">
    <div class="page-header">
        <h2><i class="fas fa-sign-in-alt" style="color:#29b6f6;margin-right:10px;font-size:24px;"></i>Guest Check In</h2>
        <p>Step 1: filter rooms &amp; load &nbsp;·&nbsp; Step 2: select room (price auto-fills) &nbsp;·&nbsp; Step 3: fill details</p>
        <div class="blue-divider"></div>
    </div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($error_msg): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?= $error_msg ?></span></div><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>

    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($checkin_done): ?>
    <div class="card"><div class="success-box">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h3>Check-In Successful!</h3><p><?= $success_msg ?></p>
        <a href="checkin.php<?= $fromParam ?>" class="btn-new"><i class="fas fa-plus"></i> New Check-In</a>
        <a href="<?= $backUrl ?>" class="btn-dash"><i class="fas fa-home"></i> <?= $backLabel ?></a>
    </div></div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: ?>

    <!-- STEP 1: Load Rooms -->
    <div class="card" style="margin-bottom:20px;">
        <form action="checkin.php<?= $fromParam ?>" method="POST">
            <div class="step-strip">
                <div class="step-title">Step 1 — Filter by bed &amp; room type to see available rooms</div>
                <div class="strip-fields">
                    <div class="field"><label><i class="fas fa-bed"></i> Bed Type</label>
                        <select name="bedtype">
                            <option value="Single">Single</option><option value="Double">Double</option><option value="Triple">Triple</option>
                        </select></div>
                    <div class="field"><label><i class="fas fa-snowflake"></i> Room Type</label>
                        <select name="roomtype">
                            <option value="AC">AC</option><option value="NonAC">Non-AC</option>
                        </select></div>
                    <button type="submit" name="loadrooms" class="btn-load"><i class="fas fa-search"></i> Load Rooms</button>
                </div>
            </div>
            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($roomsLoaded): ?>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (!empty($availableRooms)): ?>
                <div class="rooms-bar found">
                    <span class="rb-label"><i class="fas fa-door-open" style="margin-right:5px;"></i><?= count($availableRooms) ?> room<?= count($availableRooms)!==1?"s":"" ?> available</span>
                    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
foreach ($availableRooms as $r): ?>
                        <span class="room-pill"><i class="fas fa-door-open"></i> <?= htmlspecialchars($r["roomnumber"]) ?> — ETB <?= number_format($r["price"],0) ?>/night</span>
                    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endforeach; ?>
                </div>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: ?>
                <div class="rooms-bar empty"><span class="rb-label"><i class="fas fa-times-circle" style="margin-right:5px;"></i>No rooms available. Try a different type.</span></div>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
        </form>
    </div>

    <!-- STEP 2: Guest Details -->
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($roomsLoaded && !empty($availableRooms)): ?>
    <div class="card">
        <form id="guestForm" action="checkin.php<?= $fromParam ?>" method="POST">
            <input type="hidden" name="rooms_json" value="<?= htmlspecialchars(json_encode($availableRooms)) ?>">
            <div class="form-body">
                <div class="section-label"><i class="fas fa-user"></i> Personal Information</div>
                <div class="grid-3">
                    <div class="form-group"><label><i class="fas fa-user"></i>Full Name</label>
                        <input type="text" id="f_name" name="name" placeholder="e.g. Ashenafi Hadush" maxlength="30" class="<?= isset($field_errors['name'])?'is-invalid':'' ?>" oninput="liveVal(this)" required>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (isset($field_errors["name"])): ?><span class="field-error"><i class="fas fa-exclamation-circle"></i><?= $field_errors["name"] ?></span><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </div>
                    <div class="form-group"><label><i class="fas fa-envelope"></i>Email</label>
                        <input type="email" id="f_email" name="email" placeholder="guest@email.com" maxlength="60" class="<?= isset($field_errors['email'])?'is-invalid':'' ?>" oninput="liveVal(this)" required>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (isset($field_errors["email"])): ?><span class="field-error"><i class="fas fa-exclamation-circle"></i><?= $field_errors["email"] ?></span><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </div>
                    <div class="form-group"><label><i class="fas fa-phone"></i>Mobile</label>
                        <input type="text" id="f_mobile" name="mobilenumber" placeholder="09XXXXXXXX" maxlength="14" class="<?= isset($field_errors['mobilenumber'])?'is-invalid':'' ?>" oninput="liveVal(this)" required>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (isset($field_errors["mobilenumber"])): ?><span class="field-error"><i class="fas fa-exclamation-circle"></i><?= $field_errors["mobilenumber"] ?></span><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </div>
                    <div class="form-group"><label><i class="fas fa-globe"></i>Nationality</label>
                        <input type="text" id="f_nat" name="nationality" placeholder="e.g. Ethiopian" maxlength="30" class="<?= isset($field_errors['nationality'])?'is-invalid':'' ?>" oninput="liveVal(this)" required>
                    </div>
                    <div class="form-group"><label><i class="fas fa-venus-mars"></i>Gender</label>
                        <select name="gender"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                    </div>
                    <div class="form-group"><label><i class="fas fa-id-card"></i>ID Proof</label>
                        <input type="text" id="f_id" name="idproof" placeholder="Passport / National ID" maxlength="20" class="<?= isset($field_errors['idproof'])?'is-invalid':'' ?>" oninput="liveVal(this)" required>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (isset($field_errors["idproof"])): ?><span class="field-error"><i class="fas fa-exclamation-circle"></i><?= $field_errors["idproof"] ?></span><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </div>
                    <div class="form-group col-span-3"><label><i class="fas fa-map-marker-alt"></i>Address</label>
                        <input type="text" id="f_addr" name="address" placeholder="City / Street" maxlength="100" class="<?= isset($field_errors['address'])?'is-invalid':'' ?>" oninput="liveVal(this)" required>
                    </div>
                </div>

                <div class="section-label"><i class="fas fa-bed"></i> Room &amp; Check-In Date</div>
                <div class="assign-box">
                    <div class="form-group"><label><i class="fas fa-door-open"></i>Room Number</label>
                        <select name="roomnumber" id="roomSelect" onchange="updatePrice()" required>
                            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
foreach ($availableRooms as $r): ?>
                                <option value="<?= htmlspecialchars($r["roomnumber"]) ?>" data-price="<?= $r["price"] ?>">
                                    Room <?= htmlspecialchars($r["roomnumber"]) ?>
                                </option>
                            <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endforeach; ?>
                        </select>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (isset($field_errors["roomnumber"])): ?><span class="field-error"><i class="fas fa-exclamation-circle"></i><?= $field_errors["roomnumber"] ?></span><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </div>
                    <div class="form-group"><label><i class="fas fa-tag"></i>Price / Night (ETB)</label>
                        <div class="price-display" id="priceDisplay">—</div>
                        <input type="hidden" name="price_display" id="priceHidden" value="">
                    </div>
                    <div class="form-group"><label><i class="fas fa-bed"></i>Bed Type</label>
                        <input type="text" id="bedDisplay" readonly style="background:#f0f0f0;border-style:dashed;color:#4a4a6a;cursor:not-allowed;">
                    </div>
                    <div class="form-group"><label><i class="fas fa-snowflake"></i>Room Type</label>
                        <input type="text" id="rtDisplay" readonly style="background:#f0f0f0;border-style:dashed;color:#4a4a6a;cursor:not-allowed;">
                    </div>
                    <div class="form-group" style="grid-column:span 4;"><label><i class="fas fa-calendar-alt"></i>Check-In Date <span style="color:#e74c3c;">*</span></label>
                        <input type="date" id="f_checkin" name="checkin" min="<?= date('Y-m-d') ?>" class="<?= isset($field_errors['checkin'])?'is-invalid':'' ?>" required>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (isset($field_errors["checkin"])): ?><span class="field-error"><i class="fas fa-exclamation-circle"></i><?= $field_errors["checkin"] ?></span><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </div>
                </div>
            </div>
            <div class="form-footer">
                <span class="hint"><i class="fas fa-info-circle" style="margin-right:4px;"></i>Price auto-fills when you select a room. Check-in date is required.</span>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="reset" class="btn-reset" onclick="updatePrice()"><i class="fas fa-undo"></i> Reset</button>
                    <button type="submit" name="checkin_submit" class="btn-checkin"><i class="fas fa-sign-in-alt"></i> Confirm Check In</button>
                </div>
            </div>
        </form>
    </div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
</main>

<script>
const roomData = <?= json_encode(array_column($availableRooms, null, "roomnumber")) ?>;

function updatePrice() {
    const sel   = document.getElementById("roomSelect");
    if (!sel) return;
    const opt   = sel.options[sel.selectedIndex];
    const price = opt ? opt.dataset.price : "";
    const rn    = opt ? opt.value : "";
    const room  = roomData[rn] || {};

    document.getElementById("priceDisplay").textContent = price ? "ETB " + Number(price).toLocaleString() : "—";
    document.getElementById("priceHidden").value  = price;
    if (document.getElementById("bedDisplay")) document.getElementById("bedDisplay").value = room.bedtype || "—";
    if (document.getElementById("rtDisplay"))  document.getElementById("rtDisplay").value  = room.roomtype|| "—";
}

// Validation rules
const rules = {
    f_name:   { pattern:/^[a-zA-Z]+(\s+[a-zA-Z]+)+$/, msg:"Enter first and last name (e.g. Ashenafi Hadush)." },
    f_email:  { pattern:/^[^\s@]+@[^\s@]+\.[^\s@]+$/,  msg:"Enter a valid email address." },
    f_mobile: { pattern:/^(\+?251[79]\d{8}|0[79]\d{8})$/, msg:"Valid: 09XXXXXXXX, 07XXXXXXXX, or +2519XXXXXXXX." },
    f_nat:    { min:3, pattern:/^[a-zA-Z\s]+$/, msg:"Letters only, at least 3 characters." },
    f_id:     { min:4, msg:"At least 4 characters." },
    f_addr:   { min:5, msg:"At least 5 characters." }
};
function liveVal(el) {
    const r = rules[el.id]; if (!r) return;
    const v = el.value.trim();
    if (!v) { mark(el,false,"Required."); return; }
    if (r.pattern && !r.pattern.test(v)) { mark(el,false,r.msg); return; }
    if (r.min && v.length < r.min) { mark(el,false,r.msg); return; }
    if (el.id==="f_name") {
        for (const p of v.split(/\s+/)) {
            if (p.length<3) { mark(el,false,"Each part min 3 chars."); return; }
            if (p.length>10){ mark(el,false,"Each part max 10 chars."); return; }
        }
    }
    mark(el,true);
}
function mark(el,ok,msg) {
    el.classList.toggle("is-invalid",!ok);
    el.style.borderColor = ok ? "#27ae60" : "";
    el.style.background  = ok ? "#f8fff9" : "";
    let old = el.parentElement.querySelector(".js-err"); if (old) old.remove();
    if (!ok && msg) {
        const s = document.createElement("span"); s.className="field-error js-err";
        s.innerHTML="<i class='fas fa-exclamation-circle'></i>"+msg;
        el.parentElement.appendChild(s);
    }
}
document.getElementById("f_name")?.addEventListener("keypress",e=>{ if(/[0-9]/.test(e.key)) e.preventDefault(); });
document.getElementById("f_nat")?.addEventListener("keypress", e=>{ if(/[0-9]/.test(e.key)) e.preventDefault(); });
document.getElementById("f_mobile")?.addEventListener("keypress",e=>{
    if (e.key==="+" && document.getElementById("f_mobile").value.length===0) return;
    if (!/[0-9]/.test(e.key)) e.preventDefault();
});
// Validate check-in date on submit
document.getElementById("guestForm")?.addEventListener("submit",function(e){
    const ci = document.getElementById("f_checkin");
    if (ci && !ci.value) { e.preventDefault(); mark(ci,false,"Check-in date is required."); ci.scrollIntoView({behavior:"smooth",block:"center"}); return; }
    if (ci && new Date(ci.value) < new Date(new Date().toDateString())) { e.preventDefault(); mark(ci,false,"Check-in date cannot be in the past."); return; }
});
// Init price on load
window.addEventListener("DOMContentLoaded", updatePrice);
</script>
</body></html>
