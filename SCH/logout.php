<?php
session_start(); // Start the session if not already started

/* ================= DB CONFIG (ปรับตามเครื่องคุณได้) ================ */
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'borrowing_db';

/* ============== STEP 1: ถ้ายังไม่กดยืนยัน ให้ขึ้นป็อปอัป ============== */
if (!isset($_GET['confirm'])):
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ยืนยันการออกจากระบบ (Admin Control)</title>
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
    text: 'คุณต้องการออกจากระบบ admin control หรือไม่',
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
      // ไปขั้นตอนออกจากระบบจริง
      window.location.href = location.pathname + '?confirm=1';
    } else {
      // กลับหน้าก่อนหน้า
      history.back();
    }
  });
</script>
</body>
</html>
<?php
exit;
endif;

/* ============== STEP 2: ยืนยันแล้ว → บันทึก log (ถ้า login อยู่) ============== */
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // เชื่อมต่อ DB (ถ้าต่อไม่ได้จะข้ามการ log แต่ยัง logout ได้)
    $conn = @new mysqli($host, $user, $pass, $db);

    if (!$conn->connect_error) {
        $user_id   = (int)$_SESSION['user_id'];
        // คุณใช้ full_name เป็นค่าที่เก็บลงช่อง username ในตาราง user_logs เดิม
        $full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';

        // ใช้ prepared statement ปลอดภัยกว่า
        if ($stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail) VALUES (?, ?, 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ')")) {
            $stmt->bind_param('is', $user_id, $full_name);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
}

/* ============== STEP 3: ลบเซสชันและคุกกี้ แล้วแจ้งผลสวย ๆ ============== */
// ล้าง session และทำลาย cookie ของ session ด้วย
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

$redirectUrl = 'index.php'; // ปลายทางหลังออกจากระบบ
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ออกจากระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <noscript><meta http-equiv="refresh" content="1;url=<?= htmlspecialchars($redirectUrl, ENT_QUOTES) ?>"></noscript>
  <style>
    html,body{height:100%;margin:0;display:flex;align-items:center;justify-content:center;background:#f8f9fa;}
  </style>
</head>
<body>
<script>
  Swal.fire({
    title: 'ออกจากระบบสำเร็จ',
    html: 'ขอบคุณที่ใช้งานระบบ admin control',
    icon: 'success',
    timer: 1500,
    timerProgressBar: true,
    showConfirmButton: true,
    heightAuto: false
  }).then(() => {
    window.location.href = <?= json_encode($redirectUrl, JSON_UNESCAPED_SLASHES) ?>;
  });
</script>
</body>
</html>
