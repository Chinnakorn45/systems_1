<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['officer','admin','procurement','staff'])) {
    header('Location: login.php'); exit;
}

// filter
$where = [];
if (!empty($_GET['month'])) $where[] = "MONTH(r.created_at)=".intval($_GET['month']);
if (!empty($_GET['year'])) $where[] = "YEAR(r.created_at)=".intval($_GET['year']);
if (!empty($_GET['category'])) $where[] = "i.category_id='".$conn->real_escape_string($_GET['category'])."'";
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$sql = "SELECT IFNULL(c.category_name, 'ไม่ระบุหมวดหมู่') as category_name,
    COUNT(*) as total,
    SUM(CASE WHEN r.status IN ('done','delivered','repair_completed') THEN 1 ELSE 0 END) as done_count,
    SUM(CASE WHEN r.status IN ('pending','in_progress','received','evaluate_it','evaluate_repairable','evaluate_external','evaluate_disposal','external_repair','procurement_managing','procurement_returned','waiting_delivery','reported','') THEN 1 ELSE 0 END) as not_done_count,
    SUM(CASE WHEN r.status='cancelled' THEN 1 ELSE 0 END) as cancelled_count
FROM repairs r
LEFT JOIN items i ON r.item_id=i.item_id
LEFT JOIN categories c ON i.category_id=c.category_id
$where_sql
GROUP BY c.category_name";
$rows = $conn->query($sql);
$cats = $conn->query("SELECT * FROM categories");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สรุปรายงานซ่อม</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
h3 {
    margin-bottom: 3rem !important;
}
.report-visuals {
    display: flex;
    gap: 64px;
    align-items: flex-start;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    padding: 40px 40px 32px 40px;
    margin-top: 2.5rem;
    margin-bottom: 3rem;
}
.report-charts {
    display: flex;
    flex-direction: column;
    gap: 40px;
    min-width: 420px;
    max-width: 700px;
}
.report-table {
    flex: 1;
    min-width: 320px;
    margin-left: 40px;
}
@media (max-width: 991px) {
    .report-visuals {
    flex-direction: column;
    padding: 1.5rem;
    gap: 32px;
    }
    .report-charts, .report-table {
        min-width: 0;
        max-width: 100%;
        margin-left: 0;
    }
}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
    <h3>สรุปรายงานซ่อม</h3>
    <form class="row g-2 mb-3">
        <div class="col-auto">
            <select name="month" class="form-select">
                <option value="">-- เดือน --</option>
                <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= (($_GET['month']??'')==$m)?'selected':'' ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="year" class="form-select">
                <option value="">-- ปี --</option>
                <?php for ($y=date('Y');$y>=date('Y')-5;$y--): ?>
                    <option value="<?= $y ?>" <?= (($_GET['year']??'')==$y)?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="category" class="form-select">
                <option value="">-- หมวดหมู่ --</option>
                <?php while ($cat = $cats->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($cat['category_id']) ?>" <?= (($_GET['category']??'')==$cat['category_id'])?'selected':'' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">ดูรายงาน</button>
        </div>
        <div class="col-auto">
            <a href="export_reports.php?month=<?= $_GET['month']??'' ?>&year=<?= $_GET['year']??'' ?>&category=<?= $_GET['category']??'' ?>" class="btn btn-success">Export Excel</a>
            <a href="export_reports_pdf.php?month=<?= $_GET['month']??'' ?>&year=<?= $_GET['year']??'' ?>&category=<?= $_GET['category']??'' ?>" class="btn btn-danger">Export PDF</a>
        </div>
    </form>
    <div class="report-visuals">
        <div>
            <canvas id="reportChart" width="620" height="370"></canvas>
        </div>
        <div>
            <canvas id="barChart" width="650" height="370"></canvas>
        </div>
    </div>
    <?php
    // วนลูปเก็บข้อมูลทั้งหมดและคำนวณรวม
    $total_all = $done_all = $not_done_all = $cancelled_all = 0;
    $all_rows = [];
    while ($row = $rows->fetch_assoc()) {
        $all_rows[] = $row;
        $total_all += $row['total'];
        $done_all += $row['done_count'];
        $not_done_all += $row['not_done_count'];
        $cancelled_all += $row['cancelled_count'];
    }
    // เตรียมข้อมูล Pie Chart สถานะ
    $pie_labels = ['ซ่อมเสร็จ', 'ยังไม่เสร็จ', 'ยกเลิก'];
    $pie_data = [
        $total_all ? round($done_all*100/$total_all,1) : 0,
        $total_all ? round($not_done_all*100/$total_all,1) : 0,
        $total_all ? round($cancelled_all*100/$total_all,1) : 0
    ];
    ?>
    <?php
    // เตรียมข้อมูล Bar Chart (Grouped Bar)
    $bar_labels = [];
    $bar_done = [];
    $bar_not_done = [];
    $bar_cancelled = [];
    $sql_bar = "
  SELECT DATE(r.created_at) as report_date,
    SUM(CASE WHEN r.status IN ('done','delivered','repair_completed') THEN 1 ELSE 0 END) as done_count,
    SUM(CASE WHEN r.status IN ('pending','in_progress','received','evaluate_it','evaluate_repairable','evaluate_external','evaluate_disposal','external_repair','procurement_managing','procurement_returned','waiting_delivery','reported','') THEN 1 ELSE 0 END) as not_done_count,
    SUM(CASE WHEN r.status='cancelled' THEN 1 ELSE 0 END) as cancelled_count
  FROM repairs r
  $where_sql
  GROUP BY report_date
  ORDER BY report_date ASC
  ";
  $bar_rows = $conn->query($sql_bar);
  while ($row = $bar_rows->fetch_assoc()) {
      $bar_labels[] = date('d/m', strtotime($row['report_date']));
      $bar_done[] = (int)$row['done_count'];
      $bar_not_done[] = (int)$row['not_done_count'];
      $bar_cancelled[] = (int)$row['cancelled_count'];
  }
    ?>
    <script>
    const pieLabels = <?= json_encode($pie_labels) ?>;
    const pieData = <?= json_encode($pie_data) ?>;
    const total = <?= (int)$total_all ?>;
    const ctx = document.getElementById('reportChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: ['#28a745', '#17a2b8', '#ffc107']
            }]
        },
        options: {
            responsive: false,
            cutout: '70%',
            plugins: {
                legend: { display: true, position: 'bottom' },
                datalabels: {
                    color: '#222',
                    font: { weight: 'bold', size: 18 },
                    formatter: (value) => value + '%'
                }
            }
        },
        plugins: [ChartDataLabels, {
            id: 'centerText',
            afterDraw: chart => {
                const {ctx, chartArea: {width, height}} = chart;
                ctx.save();
                ctx.font = 'bold 32px Sarabun, Prompt, Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#222';
                ctx.fillText(total, width/2, height/2 - 10);
                ctx.font = '16px Sarabun, Prompt, Arial';
                ctx.fillText('รายการ', width/2, height/2 + 18);
                ctx.restore();
            }
        }]
    });

    // Mockup ข้อมูล Bar Chart (ควรดึงจากฐานข้อมูลจริงในอนาคต)
    const barLabels = <?= json_encode($bar_labels) ?>;
    const barDone = <?= json_encode($bar_done) ?>;
    const barNotDone = <?= json_encode($bar_not_done) ?>;
    const barCancelled = <?= json_encode($bar_cancelled) ?>;
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [
                {
                    label: 'ซ่อมเสร็จ',
                    data: barDone,
                    backgroundColor: '#28a745'
                },
                {
                    label: 'ยังไม่เสร็จ',
                    data: barNotDone,
                    backgroundColor: '#17a2b8'
                },
                {
                    label: 'ยกเลิก',
                    data: barCancelled,
                    backgroundColor: '#ffc107'
                }
            ]
        },
        options: {
            responsive: false,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                x: { stacked: false },
                y: { beginAtZero: true }
            }
        }
    });
    </script>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>หมวดหมู่</th>
                <th>จำนวนแจ้งซ่อม</th>
                <th>ซ่อมเสร็จ</th>
                <th>ยังไม่เสร็จ</th>
                <th>ยกเลิก</th>
                <th>% สำเร็จ</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($all_rows as $row):
            $percent = $row['total'] ? round($row['done_count']*100/$row['total'],1) : 0;
        ?>
            <tr>
                <td><?= htmlspecialchars($row['category_name']) ?></td>
                <td><?= $row['total'] ?></td>
                <td><?= $row['done_count'] ?></td>
                <td><?= $row['not_done_count'] ?></td>
                <td><?= $row['cancelled_count'] ?></td>
                <td><?= $percent ?>%</td>
            </tr>
        <?php endforeach; ?>
        <tr class="table-secondary fw-bold">
            <td>รวมทั้งหมด</td>
            <td><?= $total_all ?></td>
            <td><?= $done_all ?></td>
            <td><?= $not_done_all ?></td>
            <td><?= $cancelled_all ?></td>
            <td><?= $total_all ? round($done_all*100/$total_all,1) : 0 ?>%</td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html> 