<?php
session_start();
require_once 'db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ตรวจสอบว่าต้องบังคับเปลี่ยนรหัสผ่านหรือไม่
$user_id = $_SESSION["user_id"];
$sql = "SELECT force_password_change, role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
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
        header('Location: my_repairs.php');
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
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $password_hash, $user_id);
            if ($stmt->execute()) {
                $success = true;
                
                // Log password change event
                $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time) VALUES (?, ?, 'เปลี่ยนรหัสผ่าน', 'เปลี่ยนรหัสผ่านสำเร็จ (บังคับเปลี่ยนครั้งแรก)', NOW())");
                $log_stmt->bind_param('is', $user_id, $_SESSION['full_name']);
                $log_stmt->execute();
                $log_stmt->close();
                
                // Redirect หลังจาก 2 วินาที
                header("refresh:2;url=" . ($_SESSION['role'] === 'admin' ? 'dashboard.php' : ($_SESSION['role'] === 'procurement' ? 'repairs_list.php' : 'my_repairs.php')));
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
    <title>เปลี่ยนรหัสผ่าน - ระบบแจ้งซ่อมครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600&family=Kanit:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Prompt', 'Kanit', sans-serif;
            background: linear-gradient(135deg, #e3f0ff 0%, #f9fafe 100%);
        }
        .card {
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(34,58,94,0.08);
            border: none;
        }
        .btn-primary {
            background: #223a5e;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #1a2e4a;
        }
        .form-control:focus {
            border-color: #223a5e;
            box-shadow: 0 0 0 2px #e3f0ff;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
    </style>
</head>
<body>
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: #f4f6fa;">
    <div class="w-100" style="max-width:500px;">
        <div class="card shadow">
            <div class="card-header text-center" style="font-size:1.3rem; font-weight:bold; color:#223a5e;">
                <i class="fas fa-shield-alt me-2"></i>เปลี่ยนรหัสผ่าน
            </div>
            <div class="card-body">
                <div class="alert alert-warning text-center mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>การเข้าสู่ระบบครั้งแรก</strong><br>
                    กรุณาเปลี่ยนรหัสผ่านของคุณเพื่อความปลอดภัย
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle me-2"></i>
                        เปลี่ยนรหัสผ่านสำเร็จ! กำลังนำคุณไปยังหน้าหลัก...
                    </div>
                <?php else: ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>รหัสผ่านใหม่
                            </label>
                            <input type="password" class="form-control <?php echo !empty($password_err) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            <div class="form-text">
                                รหัสผ่านต้องมีอย่างน้อย 10 ตัว และประกอบด้วย:
                                <ul class="mb-0 mt-1">
                                    <li>ตัวพิมพ์เล็ก (a-z)</li>
                                    <li>ตัวพิมพ์ใหญ่ (A-Z)</li>
                                    <li>ตัวเลข (0-9)</li>
                                    <li>อักขระพิเศษ (!@#$%^&*)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i>ยืนยันรหัสผ่านใหม่
                            </label>
                            <input type="password" class="form-control <?php echo !empty($confirm_password_err) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>บันทึกรหัสผ่านใหม่
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ตรวจสอบความตรงกันของรหัสผ่านแบบ real-time
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('รหัสผ่านไม่ตรงกัน');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        if (this.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('รหัสผ่านไม่ตรงกัน');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
});
</script>
</body>
</html> 