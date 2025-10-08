<?php
header('Content-Type: application/json; charset=utf-8');
// error_reporting(0); // Uncomment for debugging if needed
require_once 'db.php';

$events = [];

$sql = "SELECT
    r.repair_id,
    r.created_at,
    COALESCE(latest.status, r.status) AS current_status,
    r.issue_description,
    r.image,
    r.location_name,
    u.full_name AS reporter
FROM repairs r
LEFT JOIN users u ON r.reported_by = u.user_id
LEFT JOIN (
    SELECT rl.repair_id, rl.status, rl.updated_at
    FROM repair_logs rl
    INNER JOIN (
        SELECT repair_id, MAX(updated_at) AS max_ts
        FROM repair_logs
        GROUP BY repair_id
    ) t ON rl.repair_id = t.repair_id AND rl.updated_at = t.max_ts
) latest ON latest.repair_id = r.repair_id";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([]);
    exit;
}

while ($row = $res->fetch_assoc()) {
    // ส่งค่า status เป็นรหัสสถานะ (อังกฤษ) ให้ปฏิทินไปแปลงเป็นภาษาไทยเอง
    $status_code = trim((string)($row['current_status'] ?? ''));
    $events[] = [
        'id' => $row['repair_id'],
        'title' => $row['reporter'],
        'start' => date('Y-m-d', strtotime($row['created_at'])),
        'reporter' => $row['reporter'],
        'status' => $status_code,
        'issue' => $row['issue_description'],
        'image' => $row['image'],
        'repair_id' => $row['repair_id'], // เลขที่อ้างอิง
        'location' => $row['location_name'] // สถานที่ตั้ง
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
exit;
