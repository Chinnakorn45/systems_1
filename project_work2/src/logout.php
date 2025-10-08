<?php
// borrowing-system/src/logout.php
session_start();
require_once 'config.php';

/* ======== ถ้ายังไม่ได้ยืนยัน: แสดง SweetAlert2 ======== */
if (!isset($_GET['confirm'])):
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ยืนยันออกจากระบบ</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    html,body{height:100%;margin:0;display:flex;align-items:center;justify-content:center;background:#f8f9fa;}
  </style>
</head>
<body>
<script>
Swal.fire({
  title: 'ออกจากระบบ?',
  text: 'คุณต้องการออกจากระบบระบบบันทึกคลังครุภัณฑ์หรือไม่',
  icon: 'question',
  showCancelButton: true,
  confirmButtonText: 'ออกจากระบบ',
  cancelButtonText: 'ยกเลิก',
  confirmButtonColor: '#d33',
  cancelButtonColor: '#6c757d',
  reverseButtons: true,
  heightAuto: false
}).then((result) => {
  if (result.isConfirmed) {
    // ถ้ายืนยัน → ไปขั้นตอน logout จริง
    window.location.href = 'logout.php?confirm=1';
  } else {
    // ถ้ายกเลิก → กลับหน้าก่อนหน้า
    window.history.back();
  }
});
</script>
</body>
</html>
<?php
exit;
endif;

/* ======== ถ้ามีการยืนยันแล้ว: บันทึก Log ออกจากระบบ ======== */
if (isset($_SESSION['user_id']) && isset($_SESSION['full_name'])) {
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'];

    $log_stmt = mysqli_prepare(
        $link,
        "INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time)
         VALUES (?, ?, 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', NOW())"
    );
    mysqli_stmt_bind_param($log_stmt, "is", $user_id, $full_name);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}

/* ======== ลบข้อมูล Session และ Cookie ======== */
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

/* ======== แสดง SweetAlert แจ้งออกจากระบบสำเร็จ ======== */
$redirectUrl = '/systems_1/sch/index.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ออกจากระบบสำเร็จ</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    html,body{height:100%;margin:0;display:flex;align-items:center;justify-content:center;background:#f8f9fa;}
  </style>
</head>
<body>
<script>
Swal.fire({
  title: 'ออกจากระบบสำเร็จ',
  html: 'ขอบคุณที่ใช้งานระบบบันทึกคลังครุภัณฑ์',
  icon: 'success',
  timer: 1500,
  timerProgressBar: true,
  showConfirmButton: true,
  heightAuto: false
}).then(() => {
  window.location.href = <?= json_encode($redirectUrl, JSON_UNESCAPED_SLASHES); ?>;
});
</script>
</body>
</html>
