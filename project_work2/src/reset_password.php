<?php
// borrowing-system/src/reset_password.php
require_once 'config.php';

$success = false;
$error = '';
$new_password = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = trim($_POST["user_input"]);
    if (empty($input)) {
        $error = "กรุณากรอกชื่อผู้ใช้หรืออีเมล";
    } else {
        // ค้นหาผู้ใช้จาก username หรือ email
        $sql = "SELECT user_id, username, email FROM users WHERE username = ? OR email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $input, $input);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $user_id, $username, $email);
                    if (mysqli_stmt_fetch($stmt)) {
                        // สุ่มรหัสผ่านใหม่
                        $new_password = substr(bin2hex(random_bytes(4)), 0, 8);
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        // อัปเดตรหัสผ่านใหม่ในฐานข้อมูล
                        $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                        if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "si", $new_password_hash, $user_id);
                            if (mysqli_stmt_execute($update_stmt)) {
                                $success = true;
                            } else {
                                $error = "เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน";
                            }
                            mysqli_stmt_close($update_stmt);
                        }
                    }
                } else {
                    $error = "ไม่พบชื่อผู้ใช้หรืออีเมลนี้ในระบบ";
                }
            } else {
                $error = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน - ระบบยืม-คืนครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-header h2 {
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
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h2>รีเซ็ตรหัสผ่าน</h2>
            <p class="text-muted">กรอกชื่อผู้ใช้หรืออีเมลที่ลงทะเบียนไว้</p>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <strong>รีเซ็ตรหัสผ่านสำเร็จ!</strong><br>
                รหัสผ่านใหม่ของคุณคือ:<br>
                <span class="fw-bold text-primary" style="font-size:1.2em;letter-spacing:2px;">
                    <?php echo htmlspecialchars($new_password); ?>
                </span><br>
                <span class="text-danger">กรุณาเข้าสู่ระบบและเปลี่ยนรหัสผ่านทันทีเพื่อความปลอดภัย</span><br>
                <a href="login.php" class="btn btn-link mt-2">กลับไปหน้าเข้าสู่ระบบ</a>
            </div>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form action="reset_password.php" method="post">
                <div class="mb-3">
                    <label for="user_input" class="form-label">ชื่อผู้ใช้หรืออีเมล</label>
                    <input type="text" class="form-control" id="user_input" name="user_input" required>
                </div>
                <button type="submit" class="btn btn-primary btn-reset">รีเซ็ตรหัสผ่าน</button>
            </form>
            <div class="text-center mt-3">
                <a href="login.php" class="link-secondary">กลับไปหน้าเข้าสู่ระบบ</a>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 