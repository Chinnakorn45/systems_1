<?php
// Start session ASAP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php'; // ใช้ $link (mysqli)

$login_err = '';

// ถ้ามี session อยู่แล้ว
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: dashboard.php');
        exit;
    } else {
        // เคลียร์ session ของผู้ใช้ที่ไม่ใช่ admin
        session_unset();
        session_destroy();
        session_start();
        $login_err = 'ระบบนี้เข้าถึงได้เฉพาะผู้ดูแลระบบเท่านั้น';
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = trim($_POST['username'] ?? '');
    $input_password = trim($_POST['password'] ?? '');

    if ($input_username === '' || $input_password === '') {
        $login_err = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $sql = "SELECT user_id, username, password_hash, full_name, role, force_password_change
                FROM users
                WHERE username = ? LIMIT 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $input_username);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_bind_result(
                        $stmt, $user_id, $db_username, $password_hash,
                        $full_name, $role, $force_password_change
                    );
                    if (mysqli_stmt_fetch($stmt)) {
                        if (!password_verify($input_password, $password_hash)) {
                            $login_err = 'รหัสผ่านไม่ถูกต้อง';
                        } else {
                            // อนุญาตเฉพาะ admin
                            if ($role !== 'admin') {
                                $login_err = 'บัญชีของคุณไม่มีสิทธิ์เข้าระบบนี้ (เฉพาะผู้ดูแลระบบ)';
                                // Log การปฏิเสธ
                                if ($log_stmt = mysqli_prepare(
                                    $link,
                                    "INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time)
                                     VALUES (?, ?, 'ปฏิเสธการเข้าสู่ระบบ', 'สิทธิ์ไม่ใช่ผู้ดูแลระบบ', NOW())"
                                )) {
                                    mysqli_stmt_bind_param($log_stmt, "is", $user_id, $db_username);
                                    @mysqli_stmt_execute($log_stmt);
                                    mysqli_stmt_close($log_stmt);
                                }
                            } else {
                                // ล็อกอินสำเร็จ (admin)
                                session_regenerate_id(true);
                                $_SESSION['loggedin'] = true;
                                $_SESSION['user_id']  = (int)$user_id;
                                $_SESSION['username'] = $db_username;
                                $_SESSION['full_name']= $full_name;
                                $_SESSION['role']     = $role;

                                // Log สำเร็จ
                                if ($log_stmt = mysqli_prepare(
                                    $link,
                                    "INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time)
                                     VALUES (?, ?, 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบจัดการอุปกรณ์สำนักงาน สำเร็จ', NOW())"
                                )) {
                                    mysqli_stmt_bind_param($log_stmt, "is", $user_id, $db_username);
                                    @mysqli_stmt_execute($log_stmt);
                                    mysqli_stmt_close($log_stmt);
                                }

                                // บังคับเปลี่ยนรหัสผ่าน (ถ้าถูกตั้งค่าไว้) — admin ก็ยังตรวจได้
                                if ((int)$force_password_change === 1) {
                                    header('Location: change_password.php?force_change=1');
                                    exit;
                                }

                                header('Location: dashboard.php');
                                exit;
                            }
                        }
                    }
                } else {
                    $login_err = 'ไม่พบชื่อผู้ใช้นี้';
                }
            } else {
                $login_err = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
            }
            mysqli_stmt_close($stmt);
        } else {
            $login_err = 'ไม่สามารถเตรียมคำสั่งฐานข้อมูลได้';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ระบบจัดการอุปกรณ์สำนักงาน — เข้าสู่ระบบ</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #e8f5e9;
      min-height: 100vh; display:flex; align-items:center; justify-content:center;
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Prompt', 'Kanit', sans-serif;
    }
    .login-container {
      background:#fff; border-radius:15px; box-shadow:0 15px 35px rgba(0,0,0,.08);
      width:100%; max-width:420px; padding:32px 28px;
    }
    .login-header{ text-align:center; margin-bottom:20px; }
    .login-header h2{ margin:8px 0 2px; color:#222; font-weight:700; }
    .form-control{ border-radius:10px; padding:12px 14px; border:2px solid #e9ecef; }
    .form-control:focus{ border-color:#667eea; box-shadow:0 0 0 .2rem rgba(102,126,234,.25); }
    .btn-login{
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); border:none; border-radius:10px;
      padding:12px; font-weight:600; width:100%;
    }
    .btn-login:hover{ transform:translateY(-2px); box-shadow:0 5px 15px rgba(0,0,0,.18); }
    .alert{ border-radius:10px; }
    @keyframes flip { from{transform:rotateY(0)} to{transform:rotateY(360deg)} }
    .rotating-logo{ max-width:120px; display:block; margin:0 auto 12px; animation:flip 3s linear infinite; transform-style:preserve-3d; }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-header">
      <img src="img/logo1.png" alt="โลโก้" class="rotating-logo">
      <h2>ระบบจัดการอุปกรณ์สำนักงาน</h2>
      <p class="text-muted mb-0">กรุณาเข้าสู่ระบบ</p>
    </div>

    <?php if (!empty($login_err)): ?>
      <div class="alert alert-danger py-2" role="alert">
        <?= htmlspecialchars($login_err) ?>
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
      <button type="submit" class="btn btn-primary btn-login">เข้าสู่ระบบ</button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
