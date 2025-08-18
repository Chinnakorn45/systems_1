<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'], $_SESSION['full_name'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['full_name'];
    // Log user logout event
    $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time) VALUES (?, ?, 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', NOW())");
    $log_stmt->bind_param('is', $user_id, $username);
    $log_stmt->execute();
    $log_stmt->close();
}
// Destroy session and redirect
session_unset();
session_destroy();
header('Location: /systems_1/sch/index.php');
exit;