<?php
require_once __DIR__ . '/../db.php';
if ($conn->connect_error) die('DB Error');
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
$result = $conn->query("SELECT department_name FROM departments WHERE parent_id = $parent_id ORDER BY department_name");
$children = [];
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
header('Content-Type: application/json');
echo json_encode($children);
$conn->close();