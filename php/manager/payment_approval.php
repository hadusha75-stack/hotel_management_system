<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . "/../core/config.php";
require_once __DIR__ . "/../auth/guard_manager_finance.php";

$conn = getDB();
$backUrl   = isset($_GET["from"]) && $_GET["from"]==="finance" ? "../../html/dashboards/finance.html" : "../../html/dashboards/manager.html";
$backLabel = isset($_GET["from"]) && $_GET["from"]==="finance" ? "Finance Dashboard" : "Manager Dashboard";
$success_msg = ""; $error_msg = "";

// Approve payment
if (isset($_POST["approve"])) {
    $rn       = $conn->real_escape_string($_POST["roomnumber"]);
    $approver = $conn->real_escape_string($_SESSION["email"] ?? "");
    $conn->query("UPDATE customer SET payment_status='Paid', payment_approved_by='$approver', payment_approved_at=NOW() WHERE roomnumber='$rn'");
    $success_msg = "Payment for Room <strong>$rn</strong> marked as Paid.";
}
// Mark unpaid
if (isset($_POST["unpay"])) {
    $rn = $conn->real_escape_string($_POST["roomnumber"]);
    $conn->query("UPDATE customer SET payment_status='Unpaid', payment_approved_by=NULL, payment_approved_at=NULL WHERE roomnumber='$rn'");
    $success_msg = "Room <strong>$rn</strong> marked as Unpaid.";
}

// Load all active guests with payment status
$filter = $_GET["filter"] ?? "all";
$where  = $filter === "unpaid" ? "WHERE c.payment_status='Unpaid'" : ($filter === "paid" ? "WHERE c.payment_status='Paid'" : "");
$result = $conn->query("SELECT * FROM customer c $where ORDER BY c.checkin DESC");
$rows = []; $paid = 0; $unpaid = 0; $total = 0;
while ($row = $result->fetch_assoc()) {
    $total++;
    if ($row["payment_status"] === "Paid") $paid++;
    else $unpaid++;
    $rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approval — Sabawyan Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--gold:#c9a84c;--gold-light:#e8c97a;--gold-dark:#a07830;--navy:#0d1b2a;--navy-mid:#1a2e45;--green:#27ae60;--text-dark:#1a1a2e;--text-mid:#4a4a6a;--shadow-lg:0 16px 48px rgba(0,0,0,0.28);--radius-sm:6px;--radius-md:12px;--radius-lg:20px;--transition:all 0.3s ease;}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:Inter,sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a2e45 50%,#243b55 100%);background-attachment:fixed;min-height:100vh;}
        body::before{content:"";position:fixed;inset:0;background:url("../../photo/hotelnamebg.jpg") center/cover no-repeat;opacity:0.05;z-index:0;}
        .topbar{position:relative;z-index:10;background:rgba(13,27,42,0.96);backdrop-filter:blur(12px);border-bottom:1px solid rgba(201,168,76,0.2);padding:0 32px;height:66px;display:flex;align-items:center;justify-content:space-between;}
        .brand{display:flex;align-items:center;gap:12px;}.brand-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:10px;display:flex;align-items:center;justify-content:center;}.brand-icon i{font-size:18px;color:var(--navy);}
        .brand h1{font-family:"Playfair Display",serif;font-size:19px;color:#fff;line-height:1.2;}.brand span{font-size:11.5px;color:var(--gold-light);}
        .back-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.3);border-radius:var(--radius-sm);color:var(--gold-light);font-size:13px;font-weight:600;text-decoration:none;transition:var(--transition);}
        .back-btn:hover{background:rgba(201,168,76,0.2);color:var(--gold);transform:translateX(-2px);}
        .page{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:40px 24px 60px;}
        .page-header{margin-bottom:28px;}.page-header h2{font-family:"Playfair Display",serif;font-size:28px;color:#fff;margin-bottom:5px;}.page-header p{font-size:14px;color:rgba(255,255,255,0.4);}
        .gold-divider{width:56px;height:3px;background:linear-gradient(90deg,var(--gold-dark),var(--gold-light));border-radius:2px;margin:10px 0 0;}
        .alert{display:flex;align-items:center;gap:12px;padding:13px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;}
        .alert-success{background:rgba(46,204,113,0.12);border:1px solid rgba(46,204,113,0.35);color:#a8f0c6;}
        .stats-bar{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap;}
        .stat-card{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:var(--radius-md);padding:14px 20px;display:flex;align-items:center;gap:12px;flex:1;min-width:130px;}
        .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;}
        .si-gold{background:rgba(201,168,76,0.15);color:var(--gold);}.si-green{background:rgba(39,174,96,0.15);color:#2ecc71;}.si-red{background:rgba(231,76,60,0.15);color:#e74c3c;}
        .stat-label{font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px;}.stat-value{font-size:20px;font-weight:700;color:#fff;}
        .filter-bar{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
        .filter-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:var(--radius-sm);color:rgba(255,255,255,0.6);font-size:13px;font-weight:600;text-decoration:none;transition:var(--transition);}
        .filter-btn:hover,.filter-btn.active{background:rgba(201,168,76,0.15);border-color:rgba(201,168,76,0.4);color:var(--gold-light);}
        .table-card{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid var(--gold);}
        .table-scroll{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;font-size:13.5px;}
        thead tr{background:linear-gradient(135deg,var(--navy),var(--navy-mid));}
        thead th{padding:13px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--gold-light);white-space:nowrap;}
        tbody tr{border-bottom:1px solid #f0f0f0;transition:var(--transition);}
        tbody tr:hover{background:#fdf9f0;}tbody tr:nth-child(even){background:#fafafa;}
        tbody td{padding:12px 14px;color:var(--text-dark);vertical-align:middle;}
        .badge{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;}
        .b-paid{background:#e8f5e9;color:#2e7d32;}.b-unpaid{background:#fdecea;color:#c62828;}
        .btn-approve{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:linear-gradient(135deg,#1e8449,#27ae60);color:#fff;border:none;border-radius:var(--radius-sm);font-size:12.5px;font-weight:600;cursor:pointer;transition:var(--transition);}
        .btn-approve:hover{transform:translateY(-1px);}
        .btn-unpay{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);color:#e74c3c;border-radius:var(--radius-sm);font-size:12px;font-weight:600;cursor:pointer;transition:var(--transition);}
        .empty-state{text-align:center;padding:50px;color:#888;}
        .table-footer{padding:13px 20px;background:#f8f6f1;border-top:1px solid #ede8dc;font-size:12.5px;color:var(--text-mid);}
        @media(max-width:768px){.topbar{padding:0 16px;}.page{padding:24px 12px 40px;}}
    </style>
</head>
<body>
<header class="topbar">
    <div class="brand"><div class="brand-icon"><i class="fas fa-hotel"></i></div><div><h1>Sabawyan Hotel</h1><span>Finance — Payment Approval</span></div></div>
    <a href="<?= $backUrl ?>" class="back-btn"><i class="fas fa-arrow-left"></i> <?= $backLabel ?></a>
</header>
<main class="page">
    <div class="page-header">
        <h2><i class="fas fa-money-check-alt" style="color:var(--gold-light);margin-right:10px;font-size:24px;"></i>Payment Approval</h2>
        <p>Review and approve guest payments for active bookings</p>
        <div class="gold-divider"></div>
    </div>
    <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($success_msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $success_msg ?></div><?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
    <div class="stats-bar">
        <div class="stat-card"><div class="stat-icon si-gold"><i class="fas fa-users"></i></div><div><div class="stat-label">Total Active</div><div class="stat-value"><?= $total ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-green"><i class="fas fa-check-circle"></i></div><div><div class="stat-label">Paid</div><div class="stat-value"><?= $paid ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-red"><i class="fas fa-times-circle"></i></div><div><div class="stat-label">Unpaid</div><div class="stat-value"><?= $unpaid ?></div></div></div>
    </div>
    <div class="filter-bar">
        <a href="?<?= isset($_GET["from"])?"from=".$_GET["from"]."&":"" ?>filter=all" class="filter-btn <?= $filter==="all"?"active":"" ?>"><i class="fas fa-list"></i> All</a>
        <a href="?<?= isset($_GET["from"])?"from=".$_GET["from"]."&":"" ?>filter=unpaid" class="filter-btn <?= $filter==="unpaid"?"active":"" ?>"><i class="fas fa-exclamation-circle"></i> Unpaid</a>
        <a href="?<?= isset($_GET["from"])?"from=".$_GET["from"]."&":"" ?>filter=paid" class="filter-btn <?= $filter==="paid"?"active":"" ?>"><i class="fas fa-check-circle"></i> Paid</a>
    </div>
    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead><tr>
                    <th><i class="fas fa-user"></i> Guest</th>
                    <th><i class="fas fa-door-open"></i> Room</th>
                    <th><i class="fas fa-bed"></i> Type</th>
                    <th><i class="fas fa-sign-in-alt"></i> Check-In</th>
                    <th><i class="fas fa-tag"></i> Rate/Night</th>
                    <th><i class="fas fa-coins"></i> Total</th>
                    <th><i class="fas fa-money-bill"></i> Status</th>
                    <th><i class="fas fa-user-check"></i> Approved By</th>
                    <th><i class="fas fa-cog"></i> Action</th>
                </tr></thead>
                <tbody>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if (empty($rows)): ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="fas fa-inbox" style="font-size:40px;margin-bottom:12px;display:block;"></i>No records found.</div></td></tr>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: foreach ($rows as $row): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($row["name"]) ?></td>
                    <td><strong><?= htmlspecialchars($row["roomnumber"]) ?></strong></td>
                    <td><?= htmlspecialchars($row["roomtype"]) ?> / <?= htmlspecialchars($row["bedtype"]) ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($row["checkin"]) ?></td>
                    <td>ETB <?= number_format(floatval($row["priceperday"]),0) ?></td>
                    <td style="font-weight:700;color:#27ae60;">ETB <?= number_format(floatval($row["totalamount"]),0) ?></td>
                    <td>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($row["payment_status"] === "Paid"): ?>
                            <span class="badge b-paid"><i class="fas fa-check-circle"></i> Paid</span>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: ?>
                            <span class="badge b-unpaid"><i class="fas fa-times-circle"></i> Unpaid</span>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </td>
                    <td style="font-size:12px;color:#888;"><?= htmlspecialchars($row["payment_approved_by"] ?? "—") ?></td>
                    <td>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
if ($row["payment_status"] !== "Paid"): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="roomnumber" value="<?= htmlspecialchars($row["roomnumber"]) ?>">
                            <button type="submit" name="approve" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                        </form>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="roomnumber" value="<?= htmlspecialchars($row["roomnumber"]) ?>">
                            <button type="submit" name="unpay" class="btn-unpay"><i class="fas fa-undo"></i> Revoke</button>
                        </form>
                        <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endif; ?>
                    </td>
                </tr>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer"><?= count($rows) ?> record<?= count($rows)!==1?"s":"" ?> · <?= $paid ?> paid · <?= $unpaid ?> unpaid</div>
    </div>
</main>
</body></html>
