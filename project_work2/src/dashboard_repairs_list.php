<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('กรุณาเข้าสู่ระบบ'); }

// แสดงรายการแจ้งซ่อมที่ยังไม่ยกเลิก (สอดคล้องกับการนับในการ์ด)
$fmt_date = function($dtStr) {
    if (!$dtStr || $dtStr === '0000-00-00 00:00:00') return '-';
    $ts = strtotime($dtStr);
    if ($ts === false) return '-';
    $d = date('d', $ts);
    $m = date('m', $ts);
    $y = (int)date('Y', $ts) + 543;
    $time = date('H:i', $ts);
    return htmlspecialchars(sprintf('%s/%s/%d %s', $d, $m, $y, $time));
};
$sql = "SELECT r.repair_id, r.status, r.created_at, r.updated_at, r.issue_description,
               r.asset_number, r.serial_number, r.location_name, r.brand, r.model_name,
               i.item_number AS item_number, i.model_name AS i_model, i.brand AS i_brand
        FROM repairs r
        LEFT JOIN items i ON r.item_id = i.item_id
        WHERE r.status NOT IN ('cancelled','delivered')
        ORDER BY r.updated_at DESC, r.repair_id DESC";

$result = mysqli_query($link, $sql);

function repair_badge($status) {
    switch ($status) {
        case 'pending': return '<span class="badge bg-secondary">รอดำเนินการ</span>';
        case 'in_progress': return '<span class="badge bg-warning text-dark">กำลังซ่อม</span>';
        case 'done': return '<span class="badge bg-success">ซ่อมเสร็จ</span>';
        case 'delivered': return '<span class="badge bg-info text-dark">ส่งมอบแล้ว</span>';
        case 'cancelled': return '<span class="badge bg-dark">ยกเลิก</span>';
        default: return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
    }
}

if (!$result || mysqli_num_rows($result) === 0) {
    echo '<div class="alert alert-info mb-0">ไม่พบรายการแจ้งซ่อม</div>';
    exit;
}

echo '<div class="table-responsive">';
echo '<table class="table table-sm table-bordered align-middle">';
echo '<thead><tr>';
echo '<th>ครุภัณฑ์</th><th>หมายเลข</th><th>Serial</th><th>สถานที่</th><th>อาการเสีย</th><th>อัปเดตล่าสุด</th><th>สถานะ</th>';
echo '</tr></thead><tbody>';

while ($row = mysqli_fetch_assoc($result)) {
    $name = trim(($row['brand'] ?: $row['i_brand'] ?: '') . ' ' . ($row['model_name'] ?: $row['i_model'] ?: ''));
    $itemNumber = $row['asset_number'] ?: ($row['item_number'] ?? '');
    echo '<tr>';
    echo '<td>' . htmlspecialchars($name !== '' ? $name : '-') . '</td>';
    echo '<td>' . htmlspecialchars($itemNumber !== '' ? $itemNumber : '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['serial_number'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['location_name'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['issue_description'] ?? '-') . '</td>';
    echo '<td>' . $fmt_date($row['updated_at'] ?: $row['created_at']) . '</td>';
    echo '<td>' . repair_badge($row['status']) . '</td>';
    echo '</tr>';
}

echo '</tbody></table></div>';
