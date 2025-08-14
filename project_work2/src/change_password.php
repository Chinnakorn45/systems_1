<?php
session_start();
require_once('config.php');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ตรวจสอบว่าต้องบังคับเปลี่ยนรหัสผ่านหรือไม่
$user_id = $_SESSION["user_id"];
$sql = "SELECT force_password_change, role FROM users WHERE user_id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ถ้าไม่ต้องบังคับเปลี่ยนรหัสผ่าน หรือเป็นแอดมิน ให้ไปหน้าหลัก
if (!$user['force_password_change'] || $user['role'] === 'admin') {
    if ($user['role'] === 'admin') {
        header('Location: dashboard.php');
    } elseif ($user['role'] === 'procurement') {
        header('Location: repairs_list.php');
    } else {
        header('Location: login.php');
    }
    exit;
}

$password_err = $confirm_password_err = $success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    // Validate password
    if (empty($password)) {
        $password_err = "กรุณากรอกรหัสผ่านใหม่";
    } elseif (
        strlen($password) < 10 ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password)
    ) {
        $password_err = "รหัสผ่านต้องมีอย่างน้อย 10 ตัว และประกอบด้วยตัวพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และอักขระพิเศษ";
    }
    
    // Validate confirm password
    if (empty($confirm_password)) {
        $confirm_password_err = "กรุณายืนยันรหัสผ่านใหม่";
    } elseif ($password !== $confirm_password) {
        $confirm_password_err = "รหัสผ่านไม่ตรงกัน";
    }
    
    // ถ้าไม่มี error
    if (empty($password_err) && empty($confirm_password_err)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // อัปเดตรหัสผ่านและรีเซ็ต force_password_change
        $sql = "UPDATE users SET password_hash = ?, force_password_change = 0 WHERE user_id = ?";
        if ($stmt = $link->prepare($sql)) {
            $stmt->bind_param("si", $password_hash, $user_id);
            if ($stmt->execute()) {
                $success = true;
                
                // Log password change event
                $log_stmt = $link->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time) VALUES (?, ?, 'เปลี่ยนรหัสผ่าน', 'เปลี่ยนรหัสผ่านสำเร็จ (บังคับเปลี่ยนครั้งแรก)', NOW())");
                $log_stmt->bind_param('is', $user_id, $_SESSION['full_name']);
                $log_stmt->execute();
                $log_stmt->close();
                
                // Redirect หลังจาก 2 วินาที
                header("refresh:2;url=" . ($_SESSION['role'] === 'admin' ? 'dashboard.php' : ($_SESSION['role'] === 'procurement' ? 'login.php' : 'login.php')));
            } else {
                $password_err = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่านใหม่ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f4f6fa;
            font-family: 'Prompt', 'Kanit', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .change-password-container {
            max-width: 400px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 32px 28px 24px 28px;
        }
        .change-password-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .change-password-header h2 {
            font-weight: 600;
            font-size: 1.6rem;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-change {
            width: 100%;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 10px 0;
        }
        .alert {
            font-size: 1em;
        }
    </style>
</head>
<body>
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: #f4f6fa;">
    <div class="change-password-container">
        <div class="change-password-header">
            <i class="fas fa-key fa-2x mb-2 text-primary"></i>
            <h2>เปลี่ยนรหัสผ่านใหม่</h2>
            <p class="text-danger fw-bold mb-1">การเข้าสู่ระบบครั้งแรก<br>กรุณาเปลี่ยนรหัสผ่านของคุณเพื่อความปลอดภัย</p>
            <p class="text-muted mb-0">กรุณาตั้งรหัสผ่านใหม่เพื่อเข้าใช้งานระบบ</p>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success">เปลี่ยนรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบใหม่</div>
        <?php endif; ?>
        <?php if ($password_err): ?>
            <div class="alert alert-danger"><?php echo $password_err; ?></div>
        <?php endif; ?>
        <?php if ($confirm_password_err): ?>
            <div class="alert alert-danger"><?php echo $confirm_password_err; ?></div>
        <?php endif; ?>
        <form action="" method="post" autocomplete="off">
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่านใหม่</label>
                <input type="password" class="form-control <?php echo $password_err ? 'is-invalid' : ''; ?>" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" class="form-control <?php echo $confirm_password_err ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-change mt-2">บันทึกรหัสผ่านใหม่</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ตรวจสอบความตรงกันของรหัสผ่านแบบ real-time
document.getElementById('confirm_password').addEventListener('input', function() {
    const pw = document.getElementById('password').value;
    if (this.value !== pw) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});
</script>
</body>
</html>