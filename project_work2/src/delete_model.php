<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$model_id = intval($_POST['model_id'] ?? 0);

if ($model_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'รหัสรุ่นไม่ถูกต้อง']);
    exit;
}

try {
    // ตรวจสอบว่ามีครุภัณฑ์ที่ใช้รุ่นนี้อยู่หรือไม่
    $check_sql = "SELECT COUNT(*) as count FROM items WHERE model_name = (SELECT model_name FROM models WHERE model_id = ?)";
    $check_stmt = mysqli_prepare($link, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $model_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบรุ่นนี้ได้ เนื่องจากมีครุภัณฑ์ที่ใช้รุ่นนี้อยู่']);
        exit;
    }
    
    // ลบรุ่น
    $sql = "DELETE FROM models WHERE model_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $model_id);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'ลบรุ่นสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบรุ่นนี้']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบรุ่น']);
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
