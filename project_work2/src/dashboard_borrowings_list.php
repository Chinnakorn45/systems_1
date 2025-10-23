<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('กรุณาเข้าสู่ระบบ'); }

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

// Format date to Thai Buddhist Era (พ.ศ.)
function thai_date($dateStr) {
    if (!$dateStr || $dateStr === '0000-00-00') return '-';
    $ts = strtotime($dateStr);
    if ($ts === false) return '-';
    $d = date('d', $ts);
    $m = date('m', $ts);
    $y = (int)date('Y', $ts) + 543;
    return htmlspecialchars(sprintf('%s/%s/%d', $d, $m, $y));
}

$where = "b.status IN ('borrowed','return_pending')";
if ($filter === 'overdue') {
    $where .= " AND b.due_date < CURDATE()";
}

$sql = "SELECT b.borrow_id, b.borrow_date, b.due_date, b.return_date, b.status, b.quantity_borrowed,
               i.item_number, i.model_name, i.brand,
               u.full_name, u.department
        FROM borrowings b
        LEFT JOIN items i ON b.item_id = i.item_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE $where
        ORDER BY CASE WHEN b.due_date IS NULL THEN 1 ELSE 0 END, b.due_date ASC, b.borrow_id DESC";

$result = mysqli_query($link, $sql);

function status_badge($status, $due, $return) {
    $today = date('Y-m-d');
    if ($status === 'return_pending') return '<span class="badge bg-warning text-dark">รอยืนยันการคืน</span>';
    if ($status === 'borrowed' && $due && $due < $today && !$return) return '<span class="badge bg-danger">เกินกำหนด</span>';
    if ($status === 'borrowed') return '<span class="badge bg-info text-dark">กำลังยืม</span>';
    if ($status === 'approved') return '<span class="badge bg-info text-dark">อนุมัติแล้ว</span>';
    if ($status === 'returned') return '<span class="badge bg-success">คืนแล้ว</span>';
    if ($status === 'cancelled') return '<span class="badge bg-dark">ถูกปฏิเสธ</span>';
    return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}

if (!$result || mysqli_num_rows($result) === 0) {
    echo '<div class="alert alert-info mb-0">ไม่พบรายการยืมที่ตรงเงื่อนไข</div>';
    exit;
}

echo '<div class="table-responsive">';
echo '<table class="table table-sm table-bordered align-middle">';
echo '<thead><tr>';
echo '<th>ผู้ยืม</th><th>หน่วยงาน</th><th>ครุภัณฑ์</th><th>หมายเลข</th><th class="text-center">จำนวน</th><th>ยืมวันที่</th><th>กำหนดคืน</th><th>สถานะ</th>';
echo '</tr></thead><tbody>';

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['full_name'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['department'] ?? '-') . '</td>';
    $itemName = trim(($row['brand'] ?? '') . ' ' . ($row['model_name'] ?? ''));
    echo '<td>' . htmlspecialchars($itemName !== '' ? $itemName : '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['item_number'] ?? '-') . '</td>';
    echo '<td class="text-center">' . (int)($row['quantity_borrowed'] ?? 0) . '</td>';
    echo '<td>' . thai_date($row['borrow_date'] ?? '') . '</td>';
    echo '<td>' . thai_date($row['due_date'] ?? '') . '</td>';
    echo '<td>' . status_badge($row['status'], $row['due_date'], $row['return_date']) . '</td>';
    echo '</tr>';
}

echo '</tbody></table></div>';
