<?php
session_start(); // Start the session if not already started
// เชื่อมต่อฐานข้อมูลโดยตรงในไฟล์นี้
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fullname = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
    // Log user logout event (ใช้ชื่อเต็มเท่านั้น)
    $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail" . (columnExists($conn, 'user_logs', 'event_time') ? ", event_time" : "") . ") VALUES (?, ?, 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ'" . (columnExists($conn, 'user_logs', 'event_time') ? ", NOW()" : "") . ")");
    $log_stmt->bind_param('is', $user_id, $fullname);
    $log_stmt->execute();
    $log_stmt->close();
}
// Destroy session and redirect
session_unset();
session_destroy();
header('Location: ../index.php');
exit;

// Helper function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".$column."'");
    return $result && $result->num_rows > 0;
}
?>