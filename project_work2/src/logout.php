<?php
// borrowing-system/src/logout.php

session_start();

if (!isset($_GET['confirm'])) {
    // แสดงหน้าคอนเฟิร์ม
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>ยืนยันออกจากระบบ</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '</head><body style="background:#f8f9fa;display:flex;align-items:center;justify-content:center;height:100vh;">';
    echo '<div class="card shadow p-4" style="max-width:350px;">';
    echo '<h4 class="mb-3 text-center">ยืนยันการออกจากระบบ</h4>';
    echo '<p class="mb-4 text-center">คุณต้องการออกจากระบบหรือไม่?</p>';
    echo '<div class="d-flex justify-content-between">';
    echo '<a href="javascript:history.back()" class="btn btn-secondary">ยกเลิก</a>';
    echo '<a href="logout.php?confirm=1" class="btn btn-danger">ยืนยันออกจากระบบ</a>';
    echo '</div>';
    echo '</div>';
    echo '</body></html>';
    exit;
}

// Log user logout event ก่อนลบ session
if (isset($_SESSION['user_id']) && isset($_SESSION['full_name'])) {
    require_once 'config.php';
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'];
    $log_stmt = mysqli_prepare($link, "INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time) VALUES (?, ?, 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', NOW())");
    mysqli_stmt_bind_param($log_stmt, "is", $user_id, $full_name);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}

// ลบข้อมูล session ทั้งหมด
$_SESSION = array();

// ถ้ามีการใช้ session cookie ให้ลบ cookie ด้วย
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย session
session_destroy();

// Redirect ไปหน้า login
header('Location: /systems_1/sch/index.php');
exit;
?>