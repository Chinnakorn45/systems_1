<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['officer','admin','procurement','staff'])) {
    header('Location: login.php'); exit;
}

$month = $_GET['month'] ?? '';
$year  = $_GET['year'] ?? '';
$cat   = $_GET['category'] ?? '';

$where = [];
if ($month !== '') $where[] = "MONTH(r.created_at)=".intval($month);
if ($year  !== '') $where[] = "YEAR(r.created_at)=".intval($year);
if ($cat   !== '') $where[] = "i.category_id='".$conn->real_escape_string($cat)."'";
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$sql = "SELECT IFNULL(c.category_name,'ไม่ระบุหมวดหมู่') AS category_name,
        COUNT(*) total,
        SUM(CASE WHEN r.status IN ('done','delivered','repair_completed') THEN 1 ELSE 0 END) done_count,
        SUM(CASE WHEN r.status IN ('pending','in_progress','received','evaluate_it','evaluate_repairable','evaluate_external','evaluate_disposal','external_repair','procurement_managing','procurement_returned','waiting_delivery','reported','') THEN 1 ELSE 0 END) not_done_count,
        SUM(CASE WHEN r.status='cancelled' THEN 1 ELSE 0 END) cancelled_count
        FROM repairs r
        LEFT JOIN items i ON r.item_id=i.item_id
        LEFT JOIN categories c ON i.category_id=c.category_id
        $where_sql
        GROUP BY c.category_name
        ORDER BY c.category_name ASC";
$rows = $conn->query($sql);

$total_all=$done_all=$not_done_all=$cancelled_all=0; $all_rows=[];
while($row=$rows->fetch_assoc()){
    $all_rows[]=$row;
    $total_all += (int)$row['total'];
    $done_all  += (int)$row['done_count'];
    $not_done_all += (int)$row['not_done_count'];
    $cancelled_all += (int)$row['cancelled_count'];
}

function labelText($m,$y,$c){
    $p=[]; if($m!=='')$p[]="เดือน $m"; if($y!=='')$p[]="ปี $y"; if($c!=='')$p[]="หมวด $c";
    return $p?(' ('.implode(' - ',$p).')'):'';
}
$export_name = 'Repair_Report'.labelText($month,$year,$cat).'.pdf';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Export PDF</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
<style>
body{ font-family:sans-serif; font-size:12px; color:#000; }
h2{ margin:0 0 10px; font-size:16px; }
table{ width:100%; border-collapse:collapse; margin-top:10px; }
table,th,td{ border:1px solid #000; }
th,td{ padding:4px 6px; text-align:center; }
tfoot td{ font-weight:bold; }
.header{ margin-bottom:10px; }
</style>
</head>
<body>
<div id="print-area">
  <div class="header">
    <h2>สรุปรายงานซ่อม<?= htmlspecialchars(labelText($month,$year,$cat)) ?></h2>
    <div>สร้างเมื่อ: <?= date('d/m/Y H:i') ?></div>
  </div>

  <table>
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
      <?php foreach($all_rows as $row):
        $percent = $row['total'] ? round($row['done_count']*100/$row['total'],1) : 0; ?>
      <tr>
        <td><?= htmlspecialchars($row['category_name']) ?></td>
        <td><?= $row['total'] ?></td>
        <td><?= $row['done_count'] ?></td>
        <td><?= $row['not_done_count'] ?></td>
        <td><?= $row['cancelled_count'] ?></td>
        <td><?= $percent ?>%</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td>รวมทั้งหมด</td>
        <td><?= $total_all ?></td>
        <td><?= $done_all ?></td>
        <td><?= $not_done_all ?></td>
        <td><?= $cancelled_all ?></td>
        <td><?= $total_all ? round($done_all*100/$total_all,1) : 0 ?>%</td>
      </tr>
    </tfoot>
  </table>
</div>

<script>
// export อัตโนมัติทันทีเมื่อโหลดหน้า
window.onload = function(){
  const element = document.getElementById('print-area');
  html2pdf().from(element).set({
    margin:10,
    filename: <?= json_encode($export_name) ?>,
    image:{ type:'jpeg', quality:0.98 },
    html2canvas:{ scale:2 },
    jsPDF:{ unit:'mm', format:'a4', orientation:'landscape' }
  }).save();
}
</script>
</body>
</html>
