<?php
session_start();
require_once 'db.php'; // เชื่อมต่อฐานข้อมูล

if (isset($_POST['username'], $_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['username'] = $row['username'];
            
            // Log user login event
            $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail, event_time) VALUES (?, ?, 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', NOW())");
            $log_stmt->bind_param('is', $row['user_id'], $row['full_name']);
            $log_stmt->execute();
            $log_stmt->close();
            
            // ตรวจสอบว่าต้องบังคับเปลี่ยนรหัสผ่านหรือไม่ (ยกเว้นแอดมิน)
            if ($row['role'] !== 'admin' && isset($row['force_password_change']) && $row['force_password_change'] == 1) {
                // บังคับเปลี่ยนรหัสผ่าน
                header('Location: change_password.php');
                exit;
            }
            
            // ถ้าไม่ต้องเปลี่ยนรหัสผ่าน ให้ไปหน้าตาม role
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
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - ระบบแจ้งซ่อมครุภัณฑ์</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Google Fonts Modern -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    body {
      font-family: 'Prompt', sans-serif;
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
    @keyframes coin-spin {
      0%   { transform: rotateY(0deg);}
      100% { transform: rotateY(360deg);}
    }
    .coin-spin {
      display: inline-block;
      animation: coin-spin 2.5s linear infinite;
      perspective: 400px;
    }
    /* Toast Modern */
    .toast-container {
      position: fixed;
      top: 1.5rem;
      right: 1.5rem;
      z-index: 9999;
    }
    .toast {
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(34,58,94,0.10);
      font-family: 'Prompt', sans-serif;
    }
    </style>
</head>
<body>
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: #f4f6fa;">
    <div class="w-100" style="max-width:500px;">
        <div class="card shadow">
            <div class="card-header text-center" style="font-size:1.3rem; font-weight:bold; color:#223a5e;">ระบบแจ้งซ่อมครุภัณฑ์</div>
            <div class="card-body">
                <div class="alert alert-info text-center mb-3" style="font-size:0.95rem;">ใช้ username  และ password เดียวกับระบบบันทึกคลังครุภัณฑ์</div>
                <div class="text-center mb-3">
                    <i class="fas fa-key fa-3x coin-spin" style="color:#223a5e;"></i>
                </div>
                <h5 class="text-center mb-4" style="color:#223a5e;">เข้าสู่ระบบ</h5>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center"><?= $error ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Toast แจ้งเตือน -->
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div id="loginToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        เข้าสู่ระบบสำเร็จ!
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toast แสดงเมื่อ login สำเร็จ (ฝั่ง client)
<?php if (isset($_SESSION['user_id'])): ?>
  var toastEl = document.getElementById('loginToast');
  var toast = new bootstrap.Toast(toastEl);
  toast.show();
  setTimeout(function(){ window.location.href = "<?php echo ($_SESSION['role']==='admin'?'dashboard.php':($_SESSION['role']==='procurement'?'repairs_list.php':'my_repairs.php')); ?>"; }, 1200);
<?php endif; ?>
</script>
</body>
</html>