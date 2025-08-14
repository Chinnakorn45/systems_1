<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$category_name = trim($_POST['category_name'] ?? '');

if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อหมวดหมู่']);
    exit;
}

try {
    // ตรวจสอบว่ามีหมวดหมู่นี้อยู่แล้วหรือไม่
    $check_sql = "SELECT COUNT(*) as count FROM categories WHERE category_name = ?";
    $check_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $category_name);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'หมวดหมู่นี้มีอยู่แล้วในระบบ']);
        exit;
    }
    
    // เพิ่มหมวดหมู่ใหม่
    $sql = "INSERT INTO categories (category_name) VALUES (?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $category_name);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'เพิ่มหมวดหมู่สำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่']);
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
