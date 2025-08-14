<?php
require_once 'config.php';
if (!isset($_GET['category_id'])) exit('ไม่พบหมวดหมู่');
$category_id = intval($_GET['category_id']);
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$sql = "SELECT item_id, model_name, brand, item_number, serial_number, image, description, note, category_id, total_quantity, price_per_unit, total_price, location, purchase_date, budget_year, budget_amount, created_at, updated_at, available_quantity FROM items WHERE category_id = $category_id";
if ($brand !== '') {
    $sql .= " AND brand = '" . mysqli_real_escape_string($link, $brand) . "'";
}
$sql .= " ORDER BY brand, model_name";
$result = mysqli_query($link, $sql);
if (mysqli_num_rows($result) === 0) {
    echo '<div class="alert alert-info">ไม่มีครุภัณฑ์ในหมวดหมู่นี้';
    if ($brand !== '') echo ' สำหรับยี่ห้อ ' . htmlspecialchars($brand);
    echo '</div>';
    exit;
}
echo '<div class="table-responsive"><table class="table table-bordered table-sm align-middle">';
echo '<thead><tr><th>ชื่อรุ่น</th><th>ยี่ห้อ</th><th>หมายเลขครุภัณฑ์</th><th>Serial Number</th><th>จำนวน</th><th>ตำแหน่งที่เก็บ</th><th>รูป</th></tr></thead><tbody>';
while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['model_name'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['brand'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['item_number'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['serial_number'] ?? '-') . '</td>';
    echo '<td>' . (int)$row['total_quantity'] . '</td>';
    echo '<td>' . htmlspecialchars($row['location'] ?? '-') . '</td>';
        echo '<td>';
    if (!empty($row['image'])) {
        echo '<img src="' . htmlspecialchars($row['image']) . '" style="max-width:60px;max-height:60px;object-fit:cover;">';
        }
        echo '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>'; 