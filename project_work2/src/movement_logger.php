<?php
require_once 'config.php';

/**
 * บันทึกการเคลื่อนไหวของครุภัณฑ์
 * 
 * @param int $item_id ID ของครุภัณฑ์
 * @param string $movement_type ประเภทการเคลื่อนไหว (borrow, return, transfer, maintenance, disposal, purchase, adjustment)
 * @param string $from_location ตำแหน่งเดิม
 * @param string $to_location ตำแหน่งใหม่
 * @param int $from_user_id ID ผู้ใช้เดิม
 * @param int $to_user_id ID ผู้ใช้ใหม่
 * @param int $quantity จำนวนที่เคลื่อนไหว
 * @param string $notes หมายเหตุ
 * @param int $borrow_id ID การยืม (ถ้ามี)
 * @return bool ผลลัพธ์การบันทึก
 */
function log_equipment_movement($item_id, $movement_type, $from_location = null, $to_location = null,
                            $from_user_id = null, $to_user_id = null, $quantity = 1, $notes = '', $borrow_id = null) {
    global $link;
    
    if (!isset($_SESSION) && !headers_sent()) session_start();
    $created_by = $_SESSION['user_id'] ?? 1; // fallback to admin if no session
    
    $sql = "INSERT INTO equipment_movements (item_id, movement_type, from_location, to_location, 
            from_user_id, to_user_id, quantity, notes, created_by, borrow_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($link, $sql);
    if (!$stmt) {
        error_log("Failed to prepare movement log statement: " . mysqli_error($link));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'issssiisis', 
        $item_id, $movement_type, $from_location, $to_location, 
        $from_user_id, $to_user_id, $quantity, $notes, $created_by, $borrow_id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if (!$result) {
        error_log("Failed to log equipment movement: " . mysqli_error($link));
        return false;
    }
    
    return true;
}

/**
 * บันทึกการยืมครุภัณฑ์
 */
function log_borrow_movement($item_id, $user_id, $quantity, $borrow_id, $notes = '') {
    // ดึงข้อมูลครุภัณฑ์
    global $link;
    $item_sql = "SELECT location FROM items WHERE item_id = ?";
    $item_stmt = mysqli_prepare($link, $item_sql);
    mysqli_stmt_bind_param($item_stmt, 'i', $item_id);
    mysqli_stmt_execute($item_stmt);
    $item_result = mysqli_stmt_get_result($item_stmt);
    $item_data = mysqli_fetch_assoc($item_result);
    mysqli_stmt_close($item_stmt);
    
    return log_equipment_movement(
        $item_id, 
        'borrow', 
        $item_data['location'], // จากคลัง
        null, // ไปยังผู้ยืม
        null, // ไม่มีผู้ใช้เดิม
        $user_id, // ผู้ยืม
        $quantity,
        $notes,
        $borrow_id
    );
}

/**
 * บันทึกการคืนครุภัณฑ์
 */
function log_return_movement($item_id, $user_id, $quantity, $borrow_id, $notes = '') {
    // ดึงข้อมูลครุภัณฑ์
    global $link;
    $item_sql = "SELECT location FROM items WHERE item_id = ?";
    $item_stmt = mysqli_prepare($link, $item_sql);
    mysqli_stmt_bind_param($item_stmt, 'i', $item_id);
    mysqli_stmt_execute($item_stmt);
    $item_result = mysqli_stmt_get_result($item_stmt);
    $item_data = mysqli_fetch_assoc($item_result);
    mysqli_stmt_close($item_stmt);
    
    return log_equipment_movement(
        $item_id, 
        'return', 
        null, // จากผู้ยืม
        $item_data['location'], // กลับไปคลัง
        $user_id, // ผู้ยืม
        null, // ไม่มีผู้ใช้ใหม่
        $quantity,
        $notes,
        $borrow_id
    );
}

/**
 * บันทึกการโอนย้ายครุภัณฑ์
 */
function log_transfer_movement($item_id, $from_location, $to_location, $to_user_id = null, $quantity = 1, $notes = '') {
    return log_equipment_movement(
        $item_id, 
        'transfer', 
        $from_location,
        $to_location,
        null,
        $to_user_id,
        $quantity,
        $notes
    );
}

/**
 * บันทึกการซ่อมบำรุง
 */
function log_maintenance_movement($item_id, $from_location, $to_location = 'ศูนย์ซ่อมบำรุง', $quantity = 1, $notes = '') {
    return log_equipment_movement(
        $item_id, 
        'maintenance', 
        $from_location,
        $to_location,
        null,
        null,
        $quantity,
        $notes
    );
}

/**
 * บันทึกการจำหน่าย
 */
function log_disposal_movement($item_id, $from_location, $quantity = 1, $notes = '') {
    return log_equipment_movement(
        $item_id, 
        'disposal', 
        $from_location,
        'จำหน่ายแล้ว',
        null,
        null,
        $quantity,
        $notes
    );
}

/**
 * บันทึกการจัดซื้อ
 */
function log_purchase_movement($item_id, $to_location, $quantity = 1, $notes = '') {
    return log_equipment_movement(
        $item_id, 
        'purchase', 
        'จัดซื้อใหม่',
        $to_location,
        null,
        null,
        $quantity,
        $notes
    );
}

/**
 * บันทึกการปรับปรุง
 */
function log_adjustment_movement($item_id, $from_location, $to_location, $quantity = 1, $notes = '') {
    return log_equipment_movement(
        $item_id, 
        'adjustment', 
        $from_location,
        $to_location,
        null,
        null,
        $quantity,
        $notes
    );
} 