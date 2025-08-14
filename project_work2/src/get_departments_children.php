<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;
if ($parent_id === null) {
    echo json_encode([]);
    exit;
}
$result = mysqli_query($link, "SELECT department_id, department_name FROM departments WHERE parent_id = $parent_id ORDER BY department_name");
$children = [];
while ($row = mysqli_fetch_assoc($result)) {
    $children[] = $row;
}
echo json_encode($children);