<?php
session_start();
require_once 'db.php';

/* ===== ถ้ามี session ให้เตรียมค่าล่วงหน้าไว้ ===== */
$user_id = $_SESSION['user_id'] ?? null;
$fullname = $_SESSION['full_name'] ?? null;

/* ===== หน้าแสดง SweetAlert2 เพื่อยืนยันก่อนออกจากระบบ ===== */
if (!isset($_GET['confirm'])):
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ยืนยันการออกจากระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    html,body{height:100%;margin:0;display:flex;align-items:center;justify-content:center;background:#f8f9fa;}
  </style>
</head>
<body>
<script>
Swal.fire({
  title: 'ออกจากระบบ?',
  text: 'คุณต้องการออกจากระบบแจ้งซ่อมครุภัณฑ์หรือไม่',
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
    // ถ้าผู้ใช้ยืนยัน → reload พร้อม ?confirm=1
    window.location.href = 'logout.php?confirm=1';
  } else {
    // ถ้ายกเลิก → กลับไปหน้าหลัก
    window.location.href = 'dashboard.php';
  }
});
</script>
</body>
</html>
<?php
exit;
endif;

/* ===== ดำเนินการออกจากระบบ (กรณียืนยันแล้ว) ===== */
try {
    if ($user_id && $fullname) {
        $stmt = $conn->prepare("
            INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time)
            VALUES (?, ?, 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', NOW())
        ");
        $stmt->bind_param('is', $user_id, $fullname);
        $stmt->execute();
        $stmt->close();
    }
} catch (Throwable $e) {
    // ข้าม error log ได้ เพื่อไม่ขัดขวาง logout
}

/* ===== เคลียร์ session & redirect ===== */
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

$redirectUrl = '/systems_1/sch/index.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ออกจากระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    html,body{height:100%;margin:0;display:flex;align-items:center;justify-content:center;background:#f8f9fa;}
  </style>
</head>
<body>
<script>
Swal.fire({
  title: 'ออกจากระบบสำเร็จ',
  html: 'ขอบคุณที่ใช้งานระบบแจ้งซ่อมครุภัณฑ์',
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
