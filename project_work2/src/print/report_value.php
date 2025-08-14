<?php
require_once '../config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) { header("location: ../login.php"); exit; }
if ($_SESSION["role"] === 'staff') { header('Location: ../borrowings.php'); exit; }
$sql_category_value = "SELECT c.category_name, SUM(i.total_price) as category_value, COUNT(i.item_id) as item_count FROM categories c LEFT JOIN items i ON c.category_id = i.category_id WHERE i.total_price IS NOT NULL AND i.total_price > 0 GROUP BY c.category_id ORDER BY category_value DESC";
$result_category_value = mysqli_query($link, $sql_category_value);
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>พิมพ์รายงานมูลค่าครุภัณฑ์</title>
<style>
body, h2, table, th, td, div { font-family: 'Angsana New', 'TH SarabunPSK', 'Kanit', Arial, sans-serif !important; }
body { font-size: 16pt; color: #000; background: #fff; }
h2 { font-size: 22pt; font-weight: bold; text-align: center; margin-bottom: 32px; color: #000; }
table { border-collapse: collapse; width: 98%; margin: 0 auto 2rem auto; font-size: 16pt; }
th, td { border: 1px solid #000; padding: 10px 14px; text-align: center; color: #000; background: #fff; font-family: 'Angsana New', 'TH SarabunPSK', 'Kanit', Arial, sans-serif !important; }
th { font-weight: bold; font-size: 16pt; }
td { font-size: 16pt; }
tr:nth-child(even) td { background: #fff; }
@media print { body { background: #fff !important; } }
.num { font-family: Arial, Tahoma, sans-serif !important; font-size: 13pt !important; }
</style>
</head>
<body>
<h2>รายงานมูลค่าครุภัณฑ์ตามหมวดหมู่</h2>
<table>
<thead>
<tr>
<th>หมวดหมู่</th><th>จำนวนครุภัณฑ์</th><th>มูลค่ารวม (บาท)</th><th>มูลค่าเฉลี่ย (บาท)</th>
</tr>
</thead>
<tbody>
<?php while ($row = mysqli_fetch_assoc($result_category_value)): ?>
<tr>
<td><?php echo htmlspecialchars($row['category_name']); ?></td>
<td class="num"><?php echo number_format($row['item_count']); ?></td>
<td class="num"><?php echo number_format($row['category_value'], 2); ?></td>
<td class="num"><?php echo number_format($row['category_value'] / $row['item_count'], 2); ?></td>
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