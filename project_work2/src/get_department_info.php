<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$name = isset($_GET['name']) ? trim($_GET['name']) : '';
if ($name === '') {
    echo json_encode(null);
    exit;
}

$sql = "SELECT department_id, parent_id, department_name FROM departments WHERE department_name = ? LIMIT 1";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        echo json_encode([
            'department_id'   => (int)$row['department_id'],
            'parent_id'       => isset($row['parent_id']) ? (int)$row['parent_id'] : null,
            'department_name' => $row['department_name'],
        ]);
    } else {
        echo json_encode(null);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(null);
}
?>

