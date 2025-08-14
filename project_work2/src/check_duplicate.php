<?php
require_once 'config.php';
header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);
    
    if (empty($field) || empty($value)) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    // ตรวจสอบว่าฟิลด์ที่ส่งมาถูกต้องหรือไม่
    $allowed_fields = ['serial_number', 'item_number'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['error' => 'Invalid field']);
        exit;
    }
    
    try {
        // สร้าง SQL query สำหรับตรวจสอบข้อมูลซ้ำ
        $sql = "SELECT COUNT(*) AS cnt FROM items WHERE $field = ?";
        $params = [$value];
        $types = "s";
        
        // ถ้าเป็นการแก้ไข ให้ไม่รวมรายการปัจจุบัน
        if ($item_id > 0) {
            $sql .= " AND item_id != ?";
            $params[] = $item_id;
            $types .= "i";
        }
        
        $stmt = mysqli_prepare($link, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            $is_duplicate = $row['cnt'] > 0;
            $message = '';
            
            if ($is_duplicate) {
                if ($field === 'serial_number') {
                    $message = 'Serial Number นี้ถูกใช้ไปแล้วในระบบ';
                } elseif ($field === 'item_number') {
                    $message = 'เลขครุภัณฑ์นี้ถูกใช้ไปแล้วในระบบ';
                }
            }
            
            echo json_encode([
                'duplicate' => $is_duplicate,
                'message' => $message,
                'count' => $row['cnt']
            ]);
        } else {
            echo json_encode(['error' => 'Database error: ' . mysqli_error($link)]);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
