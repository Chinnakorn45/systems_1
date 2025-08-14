<?php
require_once '../config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) { header("location: ../login.php"); exit; }
if ($_SESSION["role"] === 'staff') { header('Location: ../borrowings.php'); exit; }
$sql_borrowings = "SELECT b.*, i.model_name, i.item_number, u.full_name FROM borrowings b LEFT JOIN items i ON b.item_id = i.item_id LEFT JOIN users u ON b.user_id = u.user_id ORDER BY b.borrow_date DESC LIMIT 10";
$result_borrowings = mysqli_query($link, $sql_borrowings);
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>พิมพ์รายงานการยืม-คืน</title>
<style>
body, h2, table, th, td, div { font-family: 'Angsana New', 'TH SarabunPSK', 'Kanit', Arial, sans-serif !important; }
body { font-size: 16pt; color: #000; background: #fff; }
h2 { font-size: 22pt; font-weight: bold; text-align: center; margin-bottom: 32px; color: #000; }
table { border-collapse: collapse; width: 98%; margin: 0 auto 2rem auto; font-size: 16pt; }
th, td { border: 1px solid #000; padding: 10px 14px; text-align: center; color: #000; background: #fff; font-family: 'Angsana New', 'TH SarabunPSK', 'Kanit', Arial, sans-serif !important; white-space: nowrap; }
th { font-weight: bold; font-size: 16pt; }
td { font-size: 16pt; }
tr:nth-child(even) td { background: #fff; }
@media print { body { background: #fff !important; } }
.num { font-family: Arial, Tahoma, sans-serif !important; font-size: 13pt !important; }
.fullname-col, th.fullname-col { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.model-col, th.model-col {
  white-space: nowrap !important;
  text-align: left !important;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 13pt !important;
  max-width: 320px;
}
</style>
</head>
<body>
<h2>รายงานการยืม-คืน (10 รายการล่าสุด)</h2>
<table>
<thead>
<tr>
<th>วันที่ยืม</th><th>ผู้ยืม</th><th>ครุภัณฑ์</th><th>เลขครุภัณฑ์</th><th>จำนวน</th><th>วันที่คืน</th><th>สถานะ</th>
</tr>
</thead>
<tbody>
<?php while ($row = mysqli_fetch_assoc($result_borrowings)): ?>
<tr>
<td class="num"><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></td>
<td class="fullname-col"><?php echo htmlspecialchars($row['full_name']); ?></td>
<td class="model-col"><?php echo htmlspecialchars($row['model_name']); ?></td>
<td><?php echo htmlspecialchars($row['item_number']); ?></td>
<td class="num"><?php echo number_format($row['quantity_borrowed']); ?></td>
<td class="num"><?php echo $row['return_date'] ? date('d/m/Y', strtotime($row['return_date'])) : '-'; ?></td>
<td><?php
$status_labels = [
'pending' => 'รออนุมัติ',
'borrowed' => 'กำลังยืม',
'return_pending' => 'รอยืนยันการคืน',
'returned' => 'คืนแล้ว',
'cancelled' => 'ยกเลิก',
'overdue' => 'เกินกำหนด'
];
echo $status_labels[$row['status']] ?? $row['status'];
?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<div style="text-align:center; font-size:14pt; color:#000; margin-top:40px;">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี | © 2025
</div>
<script>window.onload = function(){ window.print(); };
window.onafterprint = function(){ window.close(); };</script>
</body>
</html> 