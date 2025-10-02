<?php
session_start();
require_once 'db.php'; // ใช้ $conn (mysqli)

// ================== Handle POST ==================
if (isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT user_id, username, password_hash, full_name, role, force_password_change
            FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {

            if (password_verify($password, $row['password_hash'])) {
                // ====== ตั้งค่า Session ======
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$row['user_id'];
                $_SESSION['role']      = $row['role'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['username']  = $row['username'];

                // ====== Log การเข้าสู่ระบบ ======
                $log_stmt = $conn->prepare("
                    INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time)
                    VALUES (?, ?, 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', NOW())
                ");
                if ($log_stmt) {
                    // บันทึกเป็น username จริง (เดิมผูก full_name)
                    $log_stmt->bind_param('is', $row['user_id'], $row['username']);
                    $log_stmt->execute();
                    $log_stmt->close();
                }

                // ====== บังคับเปลี่ยนรหัสผ่าน (ยกเว้น admin) ======
                if ($row['role'] !== 'admin' && (int)($row['force_password_change'] ?? 0) === 1) {
                    header('Location: change_password.php');
                    exit;
                }

                // ====== เปลี่ยนหน้าโดย role ======
                if ($row['role'] === 'admin') {
                    header('Location: dashboard.php');
                } elseif ($row['role'] === 'procurement') {
                    header('Location: repairs_list.php');
                } else {
                    header('Location: my_repairs.php');
                }
                exit;

            } else {
                $error = 'รหัสผ่านไม่ถูกต้อง';
            }

        } else {
            $error = 'ไม่พบผู้ใช้งานนี้';
        }
        $stmt->close();
    } else {
        $error = 'ไม่สามารถเตรียมคำสั่งฐานข้อมูลได้';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เข้าสู่ระบบ - ระบบแจ้งซ่อมครุภัณฑ์</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- ฟอนต์ (Prompt) -->
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- ไอคอน (ถ้าต้องใช้) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    /* ====== สไตล์รวมแบบโค้ดตัวอย่างที่สอง ====== */
    :root {
      --bg-soft: #e8f5e9;
      --card-radius: 15px;
      --card-shadow: 0 15px 35px rgba(0,0,0,.08);
      --input-border: #e9ecef;
      --focus-ring: rgba(102,126,234,.25);
      --brand-grad-start: #667eea;
      --brand-grad-end: #764ba2;
    }
    * { box-sizing: border-box; }
    body{
      background: var(--bg-soft);
      min-height: 100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Prompt', 'Kanit', sans-serif;
    }
    .login-container{
      background:#fff;
      border-radius: var(--card-radius);
      box-shadow: var(--card-shadow);
      width:100%;
      max-width: 440px;
      padding: 32px 28px;
    }
    .login-header{ text-align:center; margin-bottom: 18px; }
    .login-header h2{ margin:10px 0 4px; color:#222; font-weight:700; }
    .login-header p{ margin:0; color:#6c757d; }
    .form-label{ font-weight:600; }
    .form-control{
      border-radius: 10px;
      padding: 12px 14px;
      border: 2px solid var(--input-border);
    }
    .form-control:focus{
      border-color: var(--brand-grad-start);
      box-shadow: 0 0 0 .2rem var(--focus-ring);
      outline: none;
    }
    .btn-login{
      background: linear-gradient(135deg, var(--brand-grad-start) 0%, var(--brand-grad-end) 100%);
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 700;
      width: 100%;
      color: #fff;
      transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
    }
    .btn-login:hover{
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,.15);
      filter: brightness(1.02);
    }
    .alert{ border-radius: 10px; }

    /* โลโก้หมุนแบบตัวอย่าง */
    @keyframes flip { from{transform:rotateY(0)} to{transform:rotateY(360deg)} }
    .rotating-logo{
      max-width: 120px;
      display: block;
      margin: 0 auto 12px;
      animation: flip 3s linear infinite;
      transform-style: preserve-3d;
    }

    /* Toast (รองรับไว้ ถ้าอยากใช้แจ้งเตือนภายหลัง) */
    .toast-container { position: fixed; top: 1.2rem; right: 1.2rem; z-index: 1080; }
    .toast { border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-header">
      <!-- เปลี่ยนพาธโลโก้ให้ถูกกับโปรเจกต์ -->
      <img src="../img/logo1.png" alt="โลโก้" class="rotating-logo"
           onerror="this.style.display='none'">
      <h2>ระบบแจ้งซ่อมครุภัณฑ์</h2>
      <p>กรุณาเข้าสู่ระบบ</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2" role="alert">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label for="username" class="form-label">ชื่อผู้ใช้</label>
        <input type="text" class="form-control" id="username" name="username" required autofocus>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">รหัสผ่าน</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-login">เข้าสู่ระบบ</button>
    </form>
  </div>

  <!-- (ตัวเลือก) Toast สำเร็จ: ถ้าต้องการโชว์เมื่อ redirect กลับมาเพจนี้หลัง login (ปกติ header() จะพาออกเลย) -->
  <div class="toast-container p-3">
    <div id="loginToast" class="toast text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">เข้าสู่ระบบสำเร็จ!</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ถ้าต้องการให้โฟกัสช่อง username เสมอ (เผื่อบางเบราว์เซอร์ไม่เคารพ autofocus)
    window.addEventListener('DOMContentLoaded', () => {
      const u = document.getElementById('username');
      if (u) u.focus();
    });

    // ตัวอย่างการแสดง Toast (ยังไม่ถูกเรียกใช้เพราะเรา redirect ด้วย header())
    // const toastEl = document.getElementById('loginToast');
    // if (toastEl) new bootstrap.Toast(toastEl).show();
  </script>
</body>
</html>
