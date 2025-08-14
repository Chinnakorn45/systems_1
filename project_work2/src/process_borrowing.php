<?php
require_once 'config.php';
require_once 'movement_logger.php';
session_start();
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ['admin','procurement'])) {
    header("location: borrowings.php");
    exit;
}
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("location: borrowings.php");
    exit;
}
$borrow_id = intval($_GET['id']);
$action = $_GET['action'];

// ดึงข้อมูลการยืม
$borrow_sql = "SELECT b.*, i.item_number, u.full_name FROM borrowings b 
               LEFT JOIN items i ON b.item_id = i.item_id 
               LEFT JOIN users u ON b.user_id = u.user_id 
               WHERE b.borrow_id = ?";
$borrow_stmt = mysqli_prepare($link, $borrow_sql);
mysqli_stmt_bind_param($borrow_stmt, 'i', $borrow_id);
mysqli_stmt_execute($borrow_stmt);
$borrow_result = mysqli_stmt_get_result($borrow_stmt);
$borrow_data = mysqli_fetch_assoc($borrow_result);
mysqli_stmt_close($borrow_stmt);

if ($action === 'approve') {
    // อัปเดตสถานะการยืม
    $stmt = mysqli_prepare($link, "UPDATE borrowings SET status='borrowed' WHERE borrow_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $borrow_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // ลดจำนวนครุภัณฑ์ที่พร้อมให้ยืม
    $update_item_sql = "UPDATE items SET available_quantity = available_quantity - ? WHERE item_id = ?";
    $update_item_stmt = mysqli_prepare($link, $update_item_sql);
    mysqli_stmt_bind_param($update_item_stmt, 'ii', $borrow_data['quantity_borrowed'], $borrow_data['item_id']);
    mysqli_stmt_execute($update_item_stmt);
    mysqli_stmt_close($update_item_stmt);
    
    // บันทึกการเคลื่อนไหว
    log_borrow_movement(
        $borrow_data['item_id'],
        $borrow_data['user_id'],
        $borrow_data['quantity_borrowed'],
        $borrow_id,
        'อนุมัติการยืมโดย ' . $_SESSION['username']
    );
    
} elseif ($action === 'cancel') {
    // อัปเดตสถานะการยืม
    $stmt = mysqli_prepare($link, "UPDATE borrowings SET status='cancelled' WHERE borrow_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $borrow_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // บันทึกการเคลื่อนไหว (การยกเลิก)
    log_equipment_movement(
        $borrow_data['item_id'],
        'adjustment',
        null,
        null,
        null,
        null,
        $borrow_data['quantity_borrowed'],
        'ยกเลิกการยืมโดย ' . $_SESSION['username'],
        $borrow_id
    );
}
header("location: borrowings.php");
exit; 