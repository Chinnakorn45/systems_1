<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$category_id = intval($_POST['category_id'] ?? 0);

if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'รหัสหมวดหมู่ไม่ถูกต้อง']);
    exit;
}

try {
    // ตรวจสอบว่ามีครุภัณฑ์ที่ใช้หมวดหมู่นี้อยู่หรือไม่
    $check_sql = "SELECT COUNT(*) as count FROM items WHERE category_id = ?";
    $check_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $category_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบหมวดหมู่นี้ได้ เนื่องจากมีครุภัณฑ์ที่ใช้หมวดหมู่นี้อยู่']);
        exit;
    }
    
    // ลบหมวดหมู่
    $sql = "DELETE FROM categories WHERE category_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'ลบหมวดหมู่สำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบหมวดหมู่นี้']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบหมวดหมู่']);
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
