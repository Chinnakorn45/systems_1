<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$brand_id = intval($_POST['brand_id'] ?? 0);

if ($brand_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'รหัสยี่ห้อไม่ถูกต้อง']);
    exit;
}

try {
    // ตรวจสอบว่ามีรุ่นที่ใช้ยี่ห้อนี้อยู่หรือไม่
    $check_sql = "SELECT COUNT(*) as count FROM models WHERE brand_id = ?";
    $check_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $brand_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบยี่ห้อนี้ได้ เนื่องจากมีรุ่นที่ใช้ยี่ห้อนี้อยู่']);
        exit;
    }
    
    // ลบยี่ห้อ
    $sql = "DELETE FROM brands WHERE brand_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $brand_id);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'ลบยี่ห้อสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบยี่ห้อนี้']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบยี่ห้อ']);
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
