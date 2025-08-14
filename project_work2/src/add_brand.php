<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$brand_name = trim($_POST['brand_name'] ?? '');

if (empty($brand_name)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อยี่ห้อ']);
    exit;
}

try {
    // ตรวจสอบว่ามียี่ห้อนี้อยู่แล้วหรือไม่
    $check_sql = "SELECT COUNT(*) as count FROM brands WHERE brand_name = ?";
    $check_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $brand_name);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'ยี่ห้อนี้มีอยู่แล้วในระบบ']);
        exit;
    }
    
    // เพิ่มยี่ห้อใหม่
    $sql = "INSERT INTO brands (brand_name) VALUES (?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $brand_name);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'เพิ่มยี่ห้อสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มยี่ห้อ']);
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
