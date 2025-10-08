<?php
session_start(); // Start the session if not already started

/* ================== เชื่อมต่อฐานข้อมูล ================== */
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  // ถ้าต่อ DB ไม่ได้ ให้ยังสามารถออกจากระบบได้ (แค่ไม่บันทึก log)
  $conn = null;
}

/* ===== Helper: ตรวจว่ามีคอลัมน์ในตารางหรือไม่ ===== */
function columnExists($conn, $table, $column) {
    if (!$conn) return false;
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && $result->num_rows > 0;
}

/* ===== หากยังไม่กดยืนยัน แสดงป็อปอัป SweetAlert2 ===== */
if (!isset($_GET['confirm'])):
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <title>ยืนยันการออกจากระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    html,body{height:100%;margin:0;display:flex;align-items:center;justify-content:center;background:#f8f9fa;}
  </style>
</head>
<body>
<script>
  Swal.fire({
    title: 'ออกจากระบบ?',
    text: 'คุณต้องการออกจากระบบฐานข้อมูลพนักงานหรือไม่',
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

/* ================== ขั้นตอนออกจากระบบจริง (ยืนยันแล้ว) ================== */
/* ----- บันทึก Log (ถ้าเชื่อมต่อ DB และมี session) ----- */
if (isset($_SESSION['user_id'])) {
    $user_id  = (int)$_SESSION['user_id'];
    $fullname = isset($_SESSION['full_name']) ? (string)$_SESSION['full_name'] : '';

    if ($conn) {
        $hasEventTime = columnExists($conn, 'user_logs', 'event_time');
        // สร้าง SQL ตามว่ามีคอลัมน์ event_time หรือไม่
        $sql = "INSERT INTO user_logs (user_id, username, event_type, event_detail"
             . ($hasEventTime ? ", event_time" : "")
             . ") VALUES (?, ?, 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ'"
             . ($hasEventTime ? ", NOW()" : "")
             . ")";
        if ($log_stmt = $conn->prepare($sql)) {
            $log_stmt->bind_param('is', $user_id, $fullname);
            $log_stmt->execute();
            $log_stmt->close();
        }
    }
}

/* ----- ล้าง Session และ Cookie อย่างครบถ้วน ----- */
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

/* ----- แสดงผลสำเร็จด้วย SweetAlert แล้วค่อย Redirect ----- */
$redirectUrl = '../index.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <title>ออกจากระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
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
    html: 'ขอบคุณที่ใช้งานระบบ',
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
