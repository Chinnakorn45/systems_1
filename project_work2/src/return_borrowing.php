<?php
require_once 'config.php';
require_once 'movement_logger.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
if (!isset($_GET['id'])) {
    header('Location: borrowings.php');
    exit;
}

$borrow_id = intval($_GET['id']);

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

// เปลี่ยนสถานะเป็นรอยืนยันการคืน
$update_borrow = mysqli_prepare($link, "UPDATE borrowings SET status='return_pending' WHERE borrow_id=?");
mysqli_stmt_bind_param($update_borrow, 'i', $borrow_id);
mysqli_stmt_execute($update_borrow);
mysqli_stmt_close($update_borrow);

// บันทึกการเคลื่อนไหว (รอยืนยันการคืน)
log_equipment_movement(
    $borrow_data['item_id'],
    'adjustment',
    null,
    null,
    $borrow_data['user_id'],
    null,
    $borrow_data['quantity_borrowed'],
    'รอยืนยันการคืนโดย ' . $borrow_data['full_name'],
    $borrow_id
);

header('Location: borrowings.php');
exit;