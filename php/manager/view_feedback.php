<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
$conn = getDB();
$backUrl   = isset($_GET['from']) && $_GET['from']==='finance' ? '../../html/dashboards/finance.html'       : '../../html/dashboards/manager.html';
$backLabel = isset($_GET['from']) && $_GET['from']==='finance' ? 'Finance Dashboard' : 'Manager Dashboard';

$sql    = "SELECT * FROM feedback ORDER BY id DESC";
$result = $conn->query($sql);
$total  = $result ? $result->num_rows : 0;
$rows   = [];
$stats  = ['excellent'=>0,'good'=>0,'average'=>0,'poor'=>0];
if ($result && $total > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $e = strtolower($row['experience'] ?? '');
        if (str_contains($e,'excel'))   $stats['excellent']++;
        elseif (str_contains($e,'good')) $stats['good']++;
        elseif (str_contains($e,'aver')) $stats['average']++;
        else                             $stats['poor']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Feedback — Sabawyan Hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --gold:#c9a84c;--gold-light:#e8c97a;--gold-dark:#a07830;
            --navy:#0d1b2a;--navy-mid:#1a2e45;
            --text-dark:#1a1a2e;--text-mid:#4a4a6a;--text-light:#8a8aaa;
            --shadow-lg:0 16px 48px rgba(0,0,0,0.28);
            --radius-sm:6px;--radius-md:12px;--radius-lg:20px;--transition:all 0.3s ease;
        }
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
        .page-content{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:40px 24px 60px;}
        .page-header{margin-bottom:28px;}
        .page-header h2{font-family:'Playfair Display',serif;font-size:28px;color:#fff;margin-bottom:5px;}
        .page-header p{font-size:14px;color:rgba(255,255,255,0.4);}
        .gold-divider{width:56px;height:3px;background:linear-gradient(90deg,var(--gold-dark),var(--gold-light));border-radius:2px;margin:10px 0 0;}
        .stats-bar{display:flex;gap:14px;margin-bottom:28px;flex-wrap:wrap;}
        .stat-card{background:rgba(255,255,255,0.06);border:1px solid rgba(201,168,76,0.15);border-radius:var(--radius-md);padding:14px 20px;display:flex;align-items:center;gap:12px;flex:1;min-width:140px;}
        .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
        .si-gold{background:rgba(201,168,76,0.15);color:var(--gold);}
        .si-green{background:rgba(46,204,113,0.15);color:#2ecc71;}
        .si-blue{background:rgba(52,152,219,0.15);color:#3498db;}
        .si-yellow{background:rgba(241,196,15,0.15);color:#f1c40f;}
        .si-red{background:rgba(231,76,60,0.15);color:#e74c3c;}
        .stat-label{font-size:11px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px;}
        .stat-value{font-size:20px;font-weight:700;color:#fff;}
        .toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
        .search-wrap{position:relative;flex:1;min-width:220px;}
        .search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.35);font-size:14px;}
        .search-input{width:100%;padding:11px 16px 11px 40px;background:rgba(255,255,255,0.07);border:1px solid rgba(201,168,76,0.2);border-radius:var(--radius-sm);color:#fff;font-size:14px;font-family:'Inter',sans-serif;transition:var(--transition);}
        .search-input::placeholder{color:rgba(255,255,255,0.3);}
        .search-input:focus{outline:none;border-color:var(--gold);background:rgba(255,255,255,0.1);}
        .record-count{font-size:13px;color:rgba(255,255,255,0.4);white-space:nowrap;}
        .table-container{background:rgba(255,255,255,0.97);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;border-top:4px solid var(--gold);}
        .table-scroll{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;font-size:13.5px;}
        thead tr{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);}
        thead th{padding:14px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.7px;color:var(--gold-light);white-space:nowrap;border-bottom:2px solid rgba(201,168,76,0.3);}
        thead th i{margin-right:6px;opacity:0.75;}
        tbody tr{border-bottom:1px solid #f0f0f0;transition:var(--transition);}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#fdf9f0;}
        tbody tr:nth-child(even){background:#fafafa;}
        tbody tr:nth-child(even):hover{background:#fdf9f0;}
        tbody td{padding:13px 16px;color:var(--text-dark);vertical-align:middle;}
        .badge{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:600;}
        .b-excellent{background:#e8f5e9;color:#2e7d32;}
        .b-good{background:#e3f2fd;color:#1565c0;}
        .b-average{background:#fff8e1;color:#f57f17;}
        .b-poor{background:#fdecea;color:#c62828;}
        .msg-cell{max-width:280px;white-space:normal;font-size:13px;color:var(--text-mid);line-height:1.5;}
        .date-cell{font-size:12px;color:var(--text-light);white-space:nowrap;}
        .empty-state{text-align:center;padding:60px 20px;}
        .empty-state i{font-size:48px;color:rgba(201,168,76,0.3);margin-bottom:16px;display:block;}
        .empty-state p{font-size:16px;color:var(--text-mid);font-weight:500;}
        .table-footer{padding:14px 20px;background:#f8f6f1;border-top:1px solid #ede8dc;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
        .table-footer span{font-size:12.5px;color:var(--text-mid);}
        .footer-brand{font-family:'Playfair Display',serif;font-size:13px;color:var(--gold-dark);font-weight:600;}
        @media(max-width:768px){.topbar{padding:0 16px;}.page-content{padding:24px 16px 40px;}}
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
        <h2><i class="fas fa-star" style="color:var(--gold-light);margin-right:10px;font-size:24px;"></i>Guest Feedback</h2>
        <p>All guest reviews and experience ratings</p>
        <div class="gold-divider"></div>
    </div>

    <div class="stats-bar">
        <div class="stat-card"><div class="stat-icon si-gold"><i class="fas fa-comments"></i></div><div><div class="stat-label">Total Reviews</div><div class="stat-value"><?= $total ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-green"><i class="fas fa-smile-beam"></i></div><div><div class="stat-label">Excellent</div><div class="stat-value"><?= $stats['excellent'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-blue"><i class="fas fa-smile"></i></div><div><div class="stat-label">Good</div><div class="stat-value"><?= $stats['good'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-yellow"><i class="fas fa-meh"></i></div><div><div class="stat-label">Average</div><div class="stat-value"><?= $stats['average'] ?></div></div></div>
        <div class="stat-card"><div class="stat-icon si-red"><i class="fas fa-frown"></i></div><div><div class="stat-label">Poor</div><div class="stat-value"><?= $stats['poor'] ?></div></div></div>
    </div>

    <div class="toolbar">
        <div class="search-wrap"><i class="fas fa-search"></i><input type="text" class="search-input" id="searchInput" placeholder="Search by name, email, experience…" oninput="filterTable()"></div>
        <span class="record-count" id="recordCount"><?= $total ?> review<?= $total!==1?'s':'' ?></span>
    </div>

    <div class="table-container">
        <div class="table-scroll">
            <table id="feedbackTable">
                <thead><tr>
                    <th><i class="fas fa-hashtag"></i>ID</th>
                    <th><i class="fas fa-user"></i>Name</th>
                    <th><i class="fas fa-envelope"></i>Email</th>
                    <th><i class="fas fa-star"></i>Experience</th>
                    <th><i class="fas fa-comment"></i>Message</th>
                    <th><i class="fas fa-calendar"></i>Date</th>
                </tr></thead>
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
foreach ($rows as $row):
                        $exp = strtolower($row['experience'] ?? '');
                        $bc  = str_contains($exp,'excel') ? 'b-excellent' : (str_contains($exp,'good') ? 'b-good' : (str_contains($exp,'aver') ? 'b-average' : 'b-poor'));
                    ?>
                    <tr>
                        <td style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($row['id']) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($row['name']) ?></td>
                        <td style="color:#3498db;font-size:13px;"><?= htmlspecialchars($row['email']) ?></td>
                        <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($row['experience']) ?></span></td>
                        <td class="msg-cell"><?= htmlspecialchars($row['message']) ?></td>
                        <td class="date-cell"><i class="fas fa-clock" style="margin-right:4px;opacity:0.5;"></i><?= htmlspecialchars($row['created_at']) ?></td>
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
                    <tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><p>No feedback found yet.</p></div></td></tr>
                <?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager_finance.php';
endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span id="footerCount">Showing <strong><?= $total ?></strong> review<?= $total!==1?'s':'' ?></span>
            <span class="footer-brand"><i class="fas fa-hotel" style="margin-right:5px;"></i>Sabawyan Hotel</span>
        </div>
    </div>
</main>
<script>
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#feedbackTable tbody tr');
    let v = 0;
    rows.forEach(r => { const show = r.textContent.toLowerCase().includes(q); r.style.display = show?'':'none'; if(show) v++; });
    const t = rows.length;
    document.getElementById('recordCount').textContent = q ? v+' of '+t+' reviews' : t+' review'+(t!==1?'s':'');
    document.getElementById('footerCount').innerHTML = 'Showing <strong>'+v+'</strong> review'+(v!==1?'s':'');
}
</script>
</body>
</html>
