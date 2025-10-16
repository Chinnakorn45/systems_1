<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$dept_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$name    = isset($_GET['name']) ? trim($_GET['name']) : '';

// Resolve department by name if id not provided
if ($dept_id <= 0 && $name !== '') {
    if ($stmt = mysqli_prepare($link, "SELECT department_id FROM departments WHERE department_name = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $dept_id = (int)$row['department_id'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($dept_id <= 0) {
    echo json_encode(['ok' => true, 'type_name' => null, 'service_status' => null]);
    exit;
}

// Fetch service type via departments.type_service_id
$sql = "SELECT t.id AS type_id, t.type_name, t.service_status, t.description
        FROM departments d
        LEFT JOIN type_service_clinic t ON d.type_service_id = t.id
        WHERE d.department_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $dept_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        echo json_encode([
            'ok' => true,
            'type_id' => isset($row['type_id']) ? (int)$row['type_id'] : null,
            'type_name' => $row['type_name'] ?? null,
            'service_status' => $row['service_status'] ?? null,
            'description' => $row['description'] ?? null,
        ]);
    } else {
        echo json_encode(['ok' => true, 'type_name' => null, 'service_status' => null]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['ok' => false, 'message' => 'failed to prepare']);
}
?>

