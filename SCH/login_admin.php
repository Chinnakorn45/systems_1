<?php
// Start session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../project_work2/src/config.php';

// ===== Shim: รองรับทั้ง $link และ $conn =====
if (!isset($link) && isset($conn) && $conn instanceof mysqli) {
    $link = $conn;
}

// ตรวจสอบการส่งฟอร์มล็อกอิน
$login_err = $login_err ?? '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    if (empty($username) || empty($password)) {
        $login_err = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    } else {
        $sql = "SELECT user_id, username, password_hash, full_name, role, force_password_change FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $user_id, $username, $password_hash, $full_name, $role, $force_password_change);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $password_hash)) {
                            
                            // ✅ ตรวจสอบ role ว่าเป็น admin เท่านั้น
                            if ($role !== 'admin') {
                                $login_err = "บัญชีนี้ไม่มีสิทธิ์เข้าระบบ (อนุญาตเฉพาะผู้ดูแลระบบ)";
                            } else {
                                // ====== ตั้งค่า Session ======
                                $_SESSION["loggedin"] = true;
                                $_SESSION["user_id"] = $user_id;
                                $_SESSION["username"] = $username;
                                $_SESSION["full_name"] = $full_name;
                                $_SESSION["role"] = $role;

                                // ====== Log การเข้าสู่ระบบ ======
                                $log_stmt = mysqli_prepare($link, "INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time) VALUES (?, ?, 'เข้าสู่ระบบ', 'เข้าสู่ระบบ Admin Control สำเร็จ', NOW())");
                                mysqli_stmt_bind_param($log_stmt, "is", $user_id, $full_name);
                                mysqli_stmt_execute($log_stmt);
                                mysqli_stmt_close($log_stmt);

                                header("location: admin_dashboard.php");
                                exit;
                            }
                        } else {
                            $login_err = "รหัสผ่านไม่ถูกต้อง";
                        }
                    }
                } else {
                    $login_err = "ไม่พบชื่อผู้ใช้นี้";
                }
            } else {
                $login_err = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
            mysqli_stmt_close($stmt);
        } else {
            $login_err = "เกิดข้อผิดพลาดในการเตรียมคำสั่งฐานข้อมูล";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบ Admin Control - เข้าสู่ระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #e8f5e9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        @keyframes flip {
            from { transform: rotateY(0deg); }
            to { transform: rotateY(360deg); }
        }
        .rotating-logo {
            max-width: 120px;
            display: block;
            margin: 0 auto 16px auto;
            animation: flip 3s linear infinite;
            transform-style: preserve-3d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="img/logo1.png" alt="โลโก้โรงพยาบาล" class="rotating-logo" onerror="this.style.display='none'">
            <h2>ระบบ Admin Control</h2>
            <p class="text-muted">กรุณาเข้าสู่ระบบ</p>
        </div>
        
        <?php if (!empty($login_err)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($login_err, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
