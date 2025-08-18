<?php
session_start(); // Start the session if not already started

if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    require_once __DIR__ . '/db.php';
    if (!$conn->connect_error) {
        $full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
        $conn->query("INSERT INTO user_logs (user_id, username, event_type, event_detail) VALUES (" . intval($_SESSION['user_id']) . ", '" . $conn->real_escape_string($full_name) . "', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ')");
        $conn->close();
    }
}

session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header("Location: index.php"); // Redirect to your login page
exit();
?>