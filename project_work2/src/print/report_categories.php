<?php
require_once '../config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) { header("location: ../login.php"); exit; }
if ($_SESSION["role"] === 'staff') { header('Location: ../borrowings.php'); exit; }

// Charset/Collation for Thai output
mysqli_set_charset($link, 'utf8mb4');
mysqli_query($link, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($link, "SET collation_connection = 'utf8mb4_unicode_ci'");

// Align with the on-screen horizontal bars in reports.php
$sql = "
  SELECT c.category_name, COUNT(i.item_id) AS item_count
  FROM categories c
  LEFT JOIN items i ON i.category_id = c.category_id
  GROUP BY c.category_id
  HAVING item_count > 0
  ORDER BY item_count DESC
";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>พิมพ์รายงานหมวดหมู่ (จำนวนชิ้นต่อหมวด)</title>
<style>
body, h2, table, th, td, div { font-family: 'Angsana New', 'TH SarabunPSK', 'Kanit', Arial, sans-serif !important; }
body { font-size: 16pt; color: #000; background: #fff; }
h2 { font-size: 22pt; font-weight: bold; text-align: center; margin-bottom: 24px; color: #000; }
table { border-collapse: collapse; width: 80%; margin: 0 auto 2rem auto; font-size: 16pt; }
th, td { border: 1px solid #000; padding: 12px 16px; text-align: left; color: #000; background: #fff; }
th { font-weight: bold; font-size: 16pt; text-align: left; }
.num { text-align: right; font-family: Arial, Tahoma, sans-serif !important; font-size: 13pt !important; }
tr:nth-child(even) td { background: #fff; }
@media print { body { background: #fff !important; } }
</style>
</head>
<body>
<h2>รายงานหมวดหมู่ (จำนวนชิ้นต่อหมวด)</h2>
<table>
  <thead>
    <tr>
      <th>หมวดหมู่</th>
      <th class="num">จำนวนชิ้น</th>
    </tr>
  </thead>
  <tbody>
  <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
        <td class="num"><?php echo number_format((int)($row['item_count'] ?? 0)); ?></td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
      <tr><td colspan="2" style="text-align:center">ไม่มีข้อมูล</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<div style="text-align:center; font-size:14pt; color:#000; margin-top:40px;">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี | © 2025
  </div>
<script>
  window.onload = function(){ window.print(); };
  window.onafterprint = function(){ window.close(); };
</script>
</body>
</html>
<?php mysqli_free_result($result); ?>
