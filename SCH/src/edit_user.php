<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../db.php';
$popup = null;
if (!isset($_GET['id'])) { header('Location: users.php?error=ไม่พบข้อมูล'); exit; }
$id = intval($_GET['id']);
$res = $conn->query("SELECT * FROM users WHERE user_id=$id");
if (!$res || !$res->num_rows) { header('Location: users.php?error=ไม่พบข้อมูล'); exit; }
$user = $res->fetch_assoc();
// ดึง department_id ของแผนกย่อยจาก users
$dep_row = $conn->query("SELECT department_id, parent_id FROM departments WHERE department_name='".$conn->real_escape_string($user['department'])."' LIMIT 1");
$dep_info = $dep_row && $dep_row->num_rows ? $dep_row->fetch_assoc() : null;
$selected_main = $dep_info ? $dep_info['parent_id'] : '';
$selected_sub = $dep_info ? $dep_info['department_id'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $main_department = trim($_POST['main_department']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $role = $_POST['role'];
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, department=?, position=?, role=? WHERE user_id=?");
    $stmt->bind_param('sssssi', $full_name, $email, $department, $position, $role, $id);
    if ($stmt->execute()) {
        // Log edit user
        if (isset($_SESSION['user_id'])) {
            $detail = 'แก้ไขผู้ใช้: ' . $full_name . ' (' . $user['username'] . ')';
            $conn->query("INSERT INTO user_logs (user_id, username, event_type, event_detail) VALUES (".intval($_SESSION['user_id']).", '".$conn->real_escape_string($_SESSION['username'])."', 'edit_user', '".$conn->real_escape_string($detail)."')");
        }
        $popup = [
            'icon' => 'fa-smile',
            'color' => '#43a047',
            'msg' => 'แก้ไขข้อมูลสำเร็จ',
            'redirect' => 'users.php?success=แก้ไขข้อมูลสำเร็จ'
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
            <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>admin</option>
            <option value="staff" <?= $user['role']==='staff'?'selected':'' ?>>staff</option>
            <option value="procurement" <?= $user['role']==='procurement'?'selected':'' ?>>procurement</option>
        </select>
        <button type="submit">บันทึก</button>
        <a href="users.php" class="cancel-btn">ยกเลิก</a>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainSelect = document.getElementById('main_department');
        const subSelect = document.getElementById('department');
        // โหลดแผนกย่อยเมื่อเข้า edit
        function loadSubDepartments(selectedMain, selectedSub) {
            subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
            if (!selectedMain) return;
            fetch('get_departments_children.php?parent_id=' + selectedMain)
                .then(res => res.json())
                .then(data => {
                    data.forEach(dep => {
                        const opt = document.createElement('option');
                        opt.value = dep.department_name;
                        opt.textContent = dep.department_name;
                        if (dep.department_name === "<?= htmlspecialchars($user['department']) ?>") opt.selected = true;
                        subSelect.appendChild(opt);
                    });
                });
        }
        // โหลดแผนกย่อย default
        loadSubDepartments("<?= $selected_main ?>", "<?= htmlspecialchars($user['department']) ?>");
        mainSelect.addEventListener('change', function() {
            loadSubDepartments(this.value, '');
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?> 