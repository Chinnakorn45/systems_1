<?php
session_start();
// เชื่อมต่อฐานข้อมูลโดยตรงในไฟล์นี้
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
$popup = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if ($username && $password) {
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role FROM users WHERE username=?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['role'] !== 'admin') {
                $log_fullname = !empty($row['full_name']) ? $row['full_name'] : '';
                $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail" . (columnExists($conn, 'user_logs', 'event_time') ? ", event_time" : "") . ") VALUES (NULL, ?, 'login_fail', 'ไม่ใช่ admin'" . (columnExists($conn, 'user_logs', 'event_time') ? ", NOW()" : "") . ")");
                $log_stmt->bind_param('s', $log_fullname);
                $log_stmt->execute();
                $log_stmt->close();
                $popup = [
                    'icon' => 'fa-frown',
                    'color' => '#d32f2f',
                    'msg' => 'อนุญาตเฉพาะผู้ดูแลระบบ (admin) เท่านั้น',
                    'redirect' => ''
                ];
            } elseif (password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                $log_fullname = !empty($row['full_name']) ? $row['full_name'] : '';
                $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail" . (columnExists($conn, 'user_logs', 'event_time') ? ", event_time" : "") . ") VALUES (?, ?, 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ'" . (columnExists($conn, 'user_logs', 'event_time') ? ", NOW()" : "") . ")");
                $log_stmt->bind_param('is', $row['user_id'], $log_fullname);
                $log_stmt->execute();
                $log_stmt->close();
                $popup = [
                    'icon' => 'fa-smile',
                    'color' => '#43a047',
                    'msg' => 'เข้าสู่ระบบสำเร็จ',
                    'redirect' => 'users.php'
                ];
            } else {
                $log_fullname = !empty($row['full_name']) ? $row['full_name'] : '';
                $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail" . (columnExists($conn, 'user_logs', 'event_time') ? ", event_time" : "") . ") VALUES (?, ?, 'login_fail', 'รหัสผ่านไม่ถูกต้อง'" . (columnExists($conn, 'user_logs', 'event_time') ? ", NOW()" : "") . ")");
                $log_stmt->bind_param('is', $row['user_id'], $log_fullname);
                $log_stmt->execute();
                $log_stmt->close();
                $popup = [
                    'icon' => 'fa-frown',
                    'color' => '#d32f2f',
                    'msg' => 'รหัสผ่านไม่ถูกต้อง',
                    'redirect' => ''
                ];
            }
        } else {
            $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail" . (columnExists($conn, 'user_logs', 'event_time') ? ", event_time" : "") . ") VALUES (NULL, ?, 'login_fail', 'ไม่พบชื่อผู้ใช้นี้'" . (columnExists($conn, 'user_logs', 'event_time') ? ", NOW()" : "") . ")");
            $log_stmt->bind_param('s', $username); // ไม่มีชื่อเต็ม กรณี user ไม่พบ
            $log_stmt->execute();
            $log_stmt->close();
            $popup = [
                'icon' => 'fa-frown',
                'color' => '#d32f2f',
                'msg' => 'ไม่พบชื่อผู้ใช้นี้',
                'redirect' => ''
            ];
        }
        $stmt->close();
    } else {
        $popup = [
            'icon' => 'fa-frown',
            'color' => '#d32f2f',
            'msg' => 'กรุณากรอกข้อมูลให้ครบ',
            'redirect' => ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="user-crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    body { background: #e8f5e9; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(34,51,107,0.08); padding: 36px 32px 28px 32px; width: 100%; max-width: 370px; border: 1px solid #e0e4ea; }
    .login-card h2 { color: #22336b; margin-bottom: 18px; text-align: center; }
    .login-card label { margin-top: 8px; display: block; margin-bottom: 8px; }
    .login-card input[type=text], .login-card input[type=password] { width: 100%; padding: 9px 12px; border-radius: 4px; border: 1px solid #b0b8c9; font-size: 15px; margin-bottom: 14px; }
    .login-card button { width: 100%; background: #1976d2; color: #fff; border: none; border-radius: 4px; font-weight: 600; font-size: 16px; padding: 10px 0; margin-top: 10px; cursor: pointer; transition: background 0.18s; }
    .login-card button:hover { background: #1251a3; }
    </style>
</head>
<body>
    <?php if ($popup): ?>
    <div class="popup-modal" id="popupModal">
        <div class="popup-content">
            <i class="fas <?= $popup['icon'] ?>" style="color:<?= $popup['color'] ?>;"></i>
            <div class="msg" style="color:<?= $popup['color'] ?>; font-size:20px;"> <?= $popup['msg'] ?> </div>
            <?php if ($popup['redirect']): ?>
            <div style="font-size:14px; color:#888;">กำลังเข้าสู่ระบบ...</div>
            <script>setTimeout(function(){ window.location.href = "<?= $popup['redirect'] ?>"; }, 1200);</script>
            <?php elseif ($popup['msg'] === 'ไม่พบชื่อผู้ใช้นี้'): ?>
            <script>
                setTimeout(function(){
                    document.getElementById('popupModal').style.display = 'none';
                    var form = document.querySelector('.login-card');
                    if(form) { form.style.filter = ''; form.style.pointerEvents = ''; }
                }, 800);
            </script>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <form class="login-card" method="post"<?= ($popup && $popup['msg'] !== 'ไม่พบชื่อผู้ใช้นี้') ? ' style="filter:blur(2px);pointer-events:none;"' : '' ?>>
        <h2><i class="fas fa-user-lock"></i> เข้าสู่ระบบ</h2>
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" required autofocus>
        <label>รหัสผ่าน</label>
        <input type="password" name="password" required>
        <button type="submit">เข้าสู่ระบบ</button>

        <!-- ✅ บัญชีทดลอง -->
        <div class="mt-4">
            <div class="alert alert-light border text-sm" role="alert">
                <strong>บัญชีสำหรับทดสอบระบบ:</strong><br>
                🔑 <b>แอดมิน:</b> <code>admin1 / 123456</code><br>
            </div>
        </div>
        
    </form>
</body>
</html>

<?php $conn->close(); ?>

<?php
// เพิ่มฟังก์ชันตรวจสอบคอลัมน์ในตาราง
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `".$table."` LIKE '".$column."'");
    return $result && $result->num_rows > 0;
}
?>