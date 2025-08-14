<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
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
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $main_department = trim($_POST['main_department']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $role = $_POST['role'];
    if ($username && $password && $full_name && $main_department && $department) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email, department, position, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssss', $username, $password_hash, $full_name, $email, $department, $position, $role);
        if ($stmt->execute()) {
            // Log add user
            if (isset($_SESSION['user_id'])) {
                $detail = 'เพิ่มผู้ใช้: ' . $full_name . ' (' . $username . ')';
                $conn->query("INSERT INTO user_logs (user_id, username, event_type, event_detail) VALUES (".intval($_SESSION['user_id']).", '".$conn->real_escape_string($_SESSION['username'])."', 'add_user', '".$conn->real_escape_string($detail)."')");
            }
            $popup = [
                'icon' => 'fa-smile',
                'color' => '#43a047',
                'msg' => 'เพิ่มพนักงานสำเร็จ',
                'redirect' => 'users.php?success=เพิ่มพนักงานสำเร็จ'
            ];
        } else {
            $popup = [
                'icon' => 'fa-frown',
                'color' => '#d32f2f',
                'msg' => 'เกิดข้อผิดพลาด: ' . $conn->error,
                'redirect' => ''
            ];
        }
        $stmt->close();
    } else {
        $popup = [
            'icon' => 'fa-frown',
            'color' => '#d32f2f',
            'msg' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
            'redirect' => ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มพนักงานใหม่</title>
    <link rel="stylesheet" href="user-crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php if ($popup): ?>
    <div class="popup-modal">
        <div class="popup-content">
            <i class="fas <?= $popup['icon'] ?>" style="color:<?= $popup['color'] ?>;"></i>
            <div class="msg" style="color:<?= $popup['color'] ?>; font-size:20px;"> <?= $popup['msg'] ?> </div>
            <?php if ($popup['redirect']): ?>
            <div style="font-size:14px; color:#888;">กำลังกลับไปหน้ารายชื่อพนักงาน...</div>
            <script>setTimeout(function(){ window.location.href = "<?= $popup['redirect'] ?>"; }, 1500);</script>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <form class="crud-form" method="post" <?= $popup ? 'style="filter:blur(2px);pointer-events:none;"' : '' ?>>
        <h3>เพิ่มพนักงานใหม่</h3>
        <label>Username *</label>
        <input type="text" name="username" required>
        <label>รหัสผ่าน *</label>
        <input type="password" name="password" required>
        <label>ชื่อ-นามสกุล *</label>
        <input type="text" name="full_name" required>
        <label>Email</label>
        <input type="email" name="email">
        <label>แผนกหลัก *</label>
        <select name="main_department" id="main_department" required>
            <option value="">-- เลือกแผนกหลัก --</option>
            <?php
            $main_result = $conn->query("SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
            while ($row = $main_result->fetch_assoc()): ?>
                <option value="<?= $row['department_id'] ?>"><?= htmlspecialchars($row['department_name']) ?></option>
            <?php endwhile; ?>
        </select>
        <label>แผนกย่อย *</label>
        <select name="department" id="department" required>
            <option value="">-- เลือกแผนกย่อย --</option>
        </select>
        <label>ตำแหน่ง</label>
        <input type="text" name="position">
        <label>บทบาท</label>
        <select name="role">
            <option value="admin">admin</option>
            <option value="staff" selected>staff</option>
            <option value="procurement">procurement</option>
        </select>
        <button type="submit">เพิ่ม</button>
        <a href="users.php" class="cancel-btn">ยกเลิก</a>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainSelect = document.getElementById('main_department');
        const subSelect = document.getElementById('department');
        mainSelect.addEventListener('change', function() {
            const parentId = this.value;
            subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
            if (!parentId) return;
            fetch('get_departments_children.php?parent_id=' + parentId)
                .then(res => res.json())
                .then(data => {
                    data.forEach(dep => {
                        const opt = document.createElement('option');
                        opt.value = dep.department_name;
                        opt.textContent = dep.department_name;
                        subSelect.appendChild(opt);
                    });
                });
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>
