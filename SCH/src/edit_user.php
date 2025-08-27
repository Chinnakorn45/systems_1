<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// (แนะนำ) เปิดเฉพาะแอดมิน
// if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: users.php?error=ไม่มีสิทธิ์'); exit; }

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

$popup = null;
$pwd_err = '';

if (!isset($_GET['id'])) { header('Location: users.php?error=ไม่พบข้อมูล'); exit; }
$id = intval($_GET['id']);

$res = $conn->query("SELECT * FROM users WHERE user_id=$id");
if (!$res || !$res->num_rows) { header('Location: users.php?error=ไม่พบข้อมูล'); exit; }
$user = $res->fetch_assoc();

// ดึง department_id ของแผนกย่อยจาก users
$dep_row = $conn->query("SELECT department_id, parent_id FROM departments WHERE department_name='".$conn->real_escape_string($user['department'])."' LIMIT 1");
$dep_info = $dep_row && $dep_row->num_rows ? $dep_row->fetch_assoc() : null;
$selected_main = $dep_info ? $dep_info['parent_id'] : '';
$selected_sub  = $dep_info ? $dep_info['department_id'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name       = trim($_POST['full_name']);
    $email           = trim($_POST['email']);
    $main_department = trim($_POST['main_department']); // ใช้สำหรับโหลด sub เท่านั้น
    $department      = trim($_POST['department']);      // เก็บชื่อแผนกย่อยลง users.department (ตามโครงสร้างเดิม)
    $position        = trim($_POST['position']);
    $role            = $_POST['role'];

    // รหัสผ่านใหม่ (ถ้ามี)
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // ตรวจความถูกต้องรหัสผ่านใหม่ (ถ้าผู้ใช้กรอก)
    $update_password = false;
    $hashed = null;
    if ($new_password !== '' || $confirm_password !== '') {
        if (strlen($new_password) < 10) {
            $pwd_err = 'รหัสผ่านใหม่ต้องยาวอย่างน้อย 10 ตัวอักษร';
        } elseif ($new_password !== $confirm_password) {
            $pwd_err = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = true;
        }
    }

    if ($pwd_err === '') {
        if ($update_password) {
            // อัปเดตรหัสผ่านด้วย
            $sql = "UPDATE users
                    SET full_name=?, email=?, department=?, position=?, role=?, password_hash=?
                    WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssi', $full_name, $email, $department, $position, $role, $hashed, $id);
        } else {
            // ไม่แตะต้องรหัสผ่าน
            $sql = "UPDATE users
                    SET full_name=?, email=?, department=?, position=?, role=?
                    WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssi', $full_name, $email, $department, $position, $role, $id);
        }

        if ($stmt && $stmt->execute()) {
            // Log edit user (ใช้ prepared ปลอดภัยกว่า)
            if (isset($_SESSION['user_id'])) {
                $detail = 'แก้ไขผู้ใช้: ' . $full_name . ' (' . $user['username'] . ')';
                if ($update_password) $detail .= ' + แก้ไขรหัสผ่าน';
                if ($log = $conn->prepare("INSERT INTO user_logs (user_id, username, event_type, event_detail) VALUES (?, ?, 'edit_user', ?)")) {
                    $uid = (int)$_SESSION['user_id'];
                    $uname = $_SESSION['username'] ?? '';
                    $log->bind_param('iss', $uid, $uname, $detail);
                    $log->execute();
                    $log->close();
                }
            }

            $popup = [
                'icon' => 'fa-smile',
                'color' => '#43a047',
                'msg' => 'แก้ไขข้อมูลสำเร็จ' . ($update_password ? ' และอัปเดตรหัสผ่านเรียบร้อย' : ''),
                'redirect' => 'users.php?success=แก้ไขข้อมูลสำเร็จ'
            ];
        } else {
            $popup = [
                'icon' => 'fa-frown',
                'color' => '#d32f2f',
                'msg' => 'เกิดข้อผิดพลาด: ' . ($stmt ? $stmt->error : $conn->error),
                'redirect' => ''
            ];
        }
        if ($stmt) $stmt->close();
    } else {
        // มีข้อผิดพลาดรหัสผ่าน → แสดง popup สีแดง
        $popup = [
            'icon' => 'fa-frown',
            'color' => '#d32f2f',
            'msg' => $pwd_err,
            'redirect' => ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลพนักงาน</title>
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
        <h3>แก้ไขข้อมูลพนักงาน</h3>

        <label>ชื่อ-นามสกุล *</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">

        <label>แผนกหลัก *</label>
        <select name="main_department" id="main_department" required>
            <option value="">-- เลือกแผนกหลัก --</option>
            <?php
            $main_result = $conn->query("SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
            while ($row = $main_result->fetch_assoc()): ?>
                <option value="<?= $row['department_id'] ?>" <?= $selected_main == $row['department_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['department_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>แผนกย่อย *</label>
        <select name="department" id="department" required>
            <option value="">-- เลือกแผนกย่อย --</option>
        </select>

        <label>ตำแหน่ง</label>
        <input type="text" name="position" value="<?= htmlspecialchars($user['position']) ?>">

        <label>บทบาท</label>
        <select name="role">
            <option value="admin"       <?= $user['role']==='admin'?'selected':'' ?>>admin</option>
            <option value="staff"       <?= $user['role']==='staff'?'selected':'' ?>>staff</option>
            <option value="procurement" <?= $user['role']==='procurement'?'selected':'' ?>>procurement</option>
        </select>

        <hr style="margin:16px 0; opacity:.2;">
        <h4 style="margin:0 0 8px">เปลี่ยนรหัสผ่าน</h4>
        <div class="hint" style="color:#777; font-size:13px; margin-bottom:8px;">
            (ไม่บังคับ) กรอกเมื่อคุณต้องการตั้งรหัสผ่านใหม่ — อย่างน้อย 10 ตัวอักษร
        </div>
        <label>รหัสผ่านใหม่</label>
        <input type="password" name="new_password" minlength="10" placeholder="ปล่อยว่างหากไม่ต้องการเปลี่ยน">

        <label>ยืนยันรหัสผ่านใหม่</label>
        <input type="password" name="confirm_password" minlength="10" placeholder="พิมพ์รหัสผ่านใหม่อีกครั้ง">

        <button type="submit">บันทึก</button>
        <a href="users.php" class="cancel-btn">ยกเลิก</a>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainSelect = document.getElementById('main_department');
        const subSelect  = document.getElementById('department');

        function loadSubDepartments(selectedMain, selectedSubName) {
            subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
            if (!selectedMain) return;
            fetch('get_departments_children.php?parent_id=' + encodeURIComponent(selectedMain))
                .then(res => res.json())
                .then(data => {
                    data.forEach(dep => {
                        const opt = document.createElement('option');
                        opt.value = dep.department_name;   // เก็บชื่อแผนกลง users.department
                        opt.textContent = dep.department_name;
                        if (dep.department_name === selectedSubName) opt.selected = true;
                        subSelect.appendChild(opt);
                    });
                })
                .catch(()=>{ /* ignore */ });
        }

        // โหลดแผนกย่อย default
        loadSubDepartments("<?= $selected_main ?>", "<?= htmlspecialchars($user['department'], ENT_QUOTES) ?>");

        mainSelect.addEventListener('change', function() {
            loadSubDepartments(this.value, '');
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>
