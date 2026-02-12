<?php
/* =========================================================
   ai_vision.php — Farm Intelligence (Verified DB)
   ========================================================= */
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// CONFIGURAÇÃO
$baseDir = __DIR__;
$scriptPath = $baseDir . DIRECTORY_SEPARATOR . 'previsao_ia.py';
$jsonPath   = $baseDir . DIRECTORY_SEPARATOR . 'ai_analysis.json';
$errorPath  = $baseDir . DIRECTORY_SEPARATOR . 'ai_error.log';

$statusMsg = "";
$data = null;

// Try
if (isset($_POST['run_ai'])) {
    if(file_exists($errorPath)) unlink($errorPath);

    // Conection

    $cmd = "python \"$scriptPath\" 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($errorPath)) {
        $statusMsg = "Python Error: " . file_get_contents($errorPath);
    } else {
        $statusMsg = "Analysis Updated!";
    }
}

// ready
if (file_exists($jsonPath)) {
    $content = file_get_contents($jsonPath);
    $data = json_decode($content, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Farm AI Vision</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* THEME: Glass Dark */
    body {
        margin: 0; font-family: "Segoe UI", sans-serif;
        background: url('assets/img/dowloag.png') no-repeat center center fixed;
        background-size: cover; color: #fff;
    }
    body::before { content: ""; position: fixed; inset: 0; background: rgba(10, 15, 25, 0.9); z-index: -1; }

    .main { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
    
    .glass {
        background: rgba(255,255,255,0.03); backdrop-filter: blur(15px);
        border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 25px;
        margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .btn {
        background: #6366f1; color: white; border: none; padding: 10px 20px; 
        border-radius: 8px; cursor: pointer; font-weight: 600;
    }
    .btn:hover { background: #4f46e5; }

    .grid { display: grid; grid-template-columns: 350px 1fr; gap: 25px; }
    
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { text-align: left; color: #94a3b8; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    td { padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); }

    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .danger { background: rgba(239,68,68,0.2); color: #f87171; }
    .warning { background: rgba(245,158,11,0.2); color: #fbbf24; }
    .success { background: rgba(74,222,128,0.2); color: #4ade80; }
    .neutral { background: rgba(255,255,255,0.1); color: #cbd5e1; }
</style>
</head>
<body>

<div class="main">
    <div class="header">
        <div>
            <h1 style="margin:0">AI Financial Analysis</h1>
            <small style="color:#94a3b8">Data Driven • Last Run: <?= $data['updated'] ?? 'Never' ?></small>
        </div>
        <div>
            <a href="dashboard.php" style="color:#cbd5e1; text-decoration:none; margin-right:15px;">Dashboard</a>
            <form method="post" style="display:inline;">
                <button type="submit" name="run_ai" class="btn"><i class="fa-solid fa-rotate"></i> Run Analysis</button>
            </form>
        </div>
    </div>

    <?php if($statusMsg): ?>
        <div style="background:#333; color:#fbbf24; padding:15px; border-radius:8px; margin-bottom:20px; font-family:monospace;">
            <?= $statusMsg ?>
        </div>
    <?php endif; ?>

    <?php if(!$data): ?>
        <div class="glass" style="text-align:center; padding:50px;">
            <h3>No Analysis Found</h3>
            <p>Please click "Run Analysis" to generate the report.</p>
        </div>
    <?php else: ?>

    <div class="grid">
        <div>
            <div class="glass">
                <h3 style="margin-top:0; color:#a78bfa"><i class="fa-solid fa-dna"></i> Genetic Ranking</h3>
                <table style="margin-bottom:0;">
                    <?php foreach($data['breeds'] as $b): ?>
                    <tr>
                        <td><?= $b['name'] ?></td>
                        <td style="text-align:right; color:#4ade80; font-weight:bold">$<?= $b['avg_profit'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="glass" style="border-left: 4px solid #ef4444;">
                <h3 style="margin-top:0; color:#f87171"><i class="fa-solid fa-triangle-exclamation"></i> Critical Alerts</h3>
                <?php if(empty($data['alerts'])): ?>
                    <p style="color:#94a3b8">No critical issues detected.</p>
                <?php else: ?>
                    <?php foreach($data['alerts'] as $a): ?>
                    <div style="padding:10px; border-bottom:1px solid rgba(255,255,255,0.05);">
                        <strong style="color:#fff">Tag <?= $a['tag'] ?></strong>
                        <span class="badge <?= $a['css'] ?>" style="float:right"><?= $a['status'] ?></span>
                        <div style="color:#cbd5e1; font-size:12px; margin-top:4px;"><?= $a['msg'] ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="glass">
                <h3 style="margin-top:0">Weight vs Profitability</h3>
                <div style="height:250px"><canvas id="aiChart"></canvas></div>
            </div>

            <div class="glass">
                <h3 style="margin-top:0; color:#fbbf24"><i class="fa-solid fa-trophy"></i> Top 10 Profitable Animals</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Breed</th>
                            <th>Weight</th>
                            <th>Net Profit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['animals'] as $row): ?>
                        <tr>
                            <td>#<?= $row['tag'] ?></td>
                            <td><?= $row['breed'] ?></td>
                            <td><?= $row['weight'] ?> kg</td>
                            <td style="color:#4ade80; font-weight:bold">$<?= $row['profit'] ?></td>
                            <td><span class="badge <?= $row['css'] ?>"><?= $row['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
<?php if($data): ?>
    const ctx = document.getElementById('aiChart').getContext('2d');
    const chartData = [
        <?php foreach($data['animals'] as $a): ?>
        { x: <?= $a['weight'] ?>, y: <?= $a['profit'] ?> },
        <?php endforeach; ?>
    ];

    new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Animals',
                data: chartData,
                backgroundColor: 'rgba(99, 102, 241, 0.6)',
                borderColor: '#6366f1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { title: {display:true, text:'Weight (kg)'}, grid:{color:'rgba(255,255,255,0.05)'} },
                y: { title: {display:true, text:'Profit ($)'}, grid:{color:'rgba(255,255,255,0.05)'} }
            },
            plugins: { legend: {display:false} }
        }
    });
<?php endif; ?>
</script>

</body>
</html>