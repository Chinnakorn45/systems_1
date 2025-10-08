<?php
require_once '../config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) { header("location: ../login.php"); exit; }
if ($_SESSION["role"] === 'staff') { header('Location: ../borrowings.php'); exit; }
$sql_stats = "SELECT COUNT(*) as total_items, COALESCE(SUM(total_quantity),0) as total_quantity FROM items";
$result_stats = mysqli_query($link, $sql_stats);
$equipment_stats = mysqli_fetch_assoc($result_stats);
$sql_borrowed = "SELECT COALESCE(SUM(quantity_borrowed), 0) as borrowed_quantity FROM borrowings WHERE status IN ('borrowed', 'return_pending')";
$result_borrowed = mysqli_query($link, $sql_borrowed);
$borrowed_data = mysqli_fetch_assoc($result_borrowed);
// Normalize integers and guard negatives to align with on-screen summary
$total_qty    = (int)($equipment_stats['total_quantity'] ?? 0);
$borrowed_qty = (int)($borrowed_data['borrowed_quantity'] ?? 0);
$available    = max(0, $total_qty - $borrowed_qty);
$equipment_stats['borrowed_quantity']  = $borrowed_qty;
$equipment_stats['available_quantity'] = $available;
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>พิมพ์รายงานสรุปสถานะครุภัณฑ์</title>
<style>
body, h2, table, th, td, div { font-family: 'Angsana New', 'TH SarabunPSK', 'Kanit', Arial, sans-serif !important; }
body { font-size: 16pt; color: #000; background: #fff; }
h2 { font-size: 22pt; font-weight: bold; text-align: center; margin-bottom: 32px; color: #000; }
table { border-collapse: collapse; width: 60%; margin: 0 auto 2rem auto; font-size: 16pt; }
th, td { border: 1px solid #000; padding: 14px 18px; text-align: center; color: #000; background: #fff; font-family: 'Angsana New', 'TH SarabunPSK', 'Kanit', Arial, sans-serif !important; }
th { font-weight: bold; font-size: 16pt; }
td { font-size: 16pt; }
tr:nth-child(even) td { background: #fff; }
@media print { body { background: #fff !important; } }
.num { font-family: Arial, Tahoma, sans-serif !important; font-size: 13pt !important; }
</style>
</head>
<body>
<h2>รายงานสรุปสถานะครุภัณฑ์</h2>
<table>
<thead>
<tr><th>รายการ</th><th>จำนวน</th></tr>
</thead>
<tbody>
<tr><td>ครุภัณฑ์ทั้งหมด</td><td class="num"><?php echo number_format($equipment_stats['total_quantity']); ?></td></tr>
<tr><td>จำนวนที่ว่าง</td><td class="num"><?php echo number_format($equipment_stats['available_quantity']); ?></td></tr>
<tr><td>จำนวนที่ยืมอยู่</td><td class="num"><?php echo number_format($equipment_stats['borrowed_quantity']); ?></td></tr>
</tbody>
</table>
<div style="text-align:center; font-size:14pt; color:#000; margin-top:40px;">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี | © 2025
</div>
<script>window.onload = function(){ window.print(); };
window.onafterprint = function(){ window.close(); };
</script>
</body>
</html> 
