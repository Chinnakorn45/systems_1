<?php
require_once 'db.php';
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// รับ parent_id จาก GET parameter
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parent_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parent_id']);
    exit;
}

// ดึงแผนกย่อยตาม parent_id
$departments = [];
$sql = "SELECT department_id, department_name FROM departments WHERE parent_id = ? ORDER BY department_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $departments[] = [
        'department_id' => $row['department_id'],
        'department_name' => $row['department_name']
    ];
}

$stmt->close();

// ส่งข้อมูลกลับเป็น JSON
header('Content-Type: application/json');
echo json_encode($departments);
?> 