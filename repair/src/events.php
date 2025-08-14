<?php
header('Content-Type: application/json; charset=utf-8');
// error_reporting(0); // Uncomment for debugging if needed
require_once 'db.php';

$events = [];

$sql = "SELECT
    r.repair_id,
    r.created_at,
    r.status,
    r.issue_description,
    r.image,
    r.location_name,
    u.full_name AS reporter
FROM repairs r
LEFT JOIN users u ON r.reported_by = u.user_id";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([]);
    exit;
}

while ($row = $res->fetch_assoc()) {
    // แปลงสถานะเป็นภาษาไทย
    $status_map = [
        'pending' => 'รอดำเนินการ',
        'in_progress' => 'กำลังซ่อม',
        'done' => 'ซ่อมเสร็จ',
        'delivered' => 'รอส่งมอบ',
        'cancelled' => 'ยกเลิก',
    ];
    $status_th = isset($status_map[$row['status']]) ? $status_map[$row['status']] : $row['status'];
    $events[] = [
        'id' => $row['repair_id'],
        'title' => $row['reporter'],
        'start' => date('Y-m-d', strtotime($row['created_at'])),
        'reporter' => $row['reporter'],
        'status' => $status_th,
        'issue' => $row['issue_description'],
        'image' => $row['image'],
        'repair_id' => $row['repair_id'], // เลขที่อ้างอิง
        'location' => $row['location_name'] // สถานที่ตั้ง
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
exit;
