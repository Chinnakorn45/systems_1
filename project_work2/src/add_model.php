<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$model_name = trim($_POST['model_name'] ?? '');
$brand_id = intval($_POST['brand_id'] ?? 0);

if (empty($model_name)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อรุ่น']);
    exit;
}

if ($brand_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเลือกยี่ห้อ']);
    exit;
}

try {
    // ตรวจสอบว่ามีรุ่นนี้อยู่แล้วหรือไม่
    $check_sql = "SELECT COUNT(*) as count FROM models WHERE model_name = ? AND brand_id = ?";
    $check_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "si", $model_name, $brand_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'รุ่นนี้มีอยู่แล้วในระบบ']);
        exit;
    }
    
    // เพิ่มรุ่นใหม่
    $sql = "INSERT INTO models (model_name, brand_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "si", $model_name, $brand_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'เพิ่มรุ่นสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มรุ่น']);
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
