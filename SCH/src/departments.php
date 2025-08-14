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

// แจ้งเตือน
$alert = '';

// เพิ่มแผนก/ฝ่าย
$popup = null;
if (isset($_POST['add_department'])) {
    $name = trim($_POST['department_name']);
    $parent = $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO departments (department_name, parent_id) VALUES (?, ?)");
        $stmt->bind_param('si', $name, $parent);
        if ($stmt->execute()) {
            $popup = [
                'icon' => 'fa-smile',
                'color' => '#43a047',
                'msg' => 'เพิ่มแผนก/ฝ่ายสำเร็จ',
                'redirect' => 'departments.php?success=เพิ่มแผนก/ฝ่ายสำเร็จ'
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
            'msg' => 'กรุณากรอกชื่อแผนก/ฝ่าย',
            'redirect' => ''
        ];
    }
}

// แก้ไขแผนก/ฝ่าย
if (isset($_POST['edit_department'])) {
    $id = intval($_POST['edit_id']);
    $name = trim($_POST['edit_department_name']);
    $parent = $_POST['edit_parent_id'] !== '' ? intval($_POST['edit_parent_id']) : null;
    if ($name) {
        $stmt = $conn->prepare("UPDATE departments SET department_name=?, parent_id=? WHERE department_id=?");
        $stmt->bind_param('sii', $name, $parent, $id);
        if ($stmt->execute()) {
            $popup = [
                'icon' => 'fa-smile',
                'color' => '#43a047',
                'msg' => 'แก้ไขแผนก/ฝ่ายสำเร็จ',
                'redirect' => 'departments.php?success=แก้ไขแผนก/ฝ่ายสำเร็จ'
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
            'msg' => 'กรุณากรอกชื่อแผนก/ฝ่าย',
            'redirect' => ''
        ];
    }
}

// ลบแผนก/ฝ่าย
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // ตรวจสอบว่ามีลูกหรือไม่
    $chk = $conn->query("SELECT COUNT(*) AS cnt FROM departments WHERE parent_id=$id");
    $has_child = $chk->fetch_assoc()['cnt'] > 0;
    if ($has_child) {
        $popup = [
            'icon' => 'fa-frown',
            'color' => '#d32f2f',
            'msg' => 'ไม่สามารถลบแผนก/ฝ่ายที่มีแผนกย่อยได้',
            'redirect' => ''
        ];
    } else {
        $stmt = $conn->prepare("DELETE FROM departments WHERE department_id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $popup = [
                'icon' => 'fa-smile',
                'color' => '#43a047',
                'msg' => 'ลบแผนก/ฝ่ายสำเร็จ',
                'redirect' => 'departments.php?success=ลบแผนก/ฝ่ายสำเร็จ'
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
}

// ดึง department ทั้งหมดสำหรับ dropdown
$departments = [];
$res = $conn->query("SELECT department_id, department_name FROM departments");
while($row = $res->fetch_assoc()) $departments[$row['department_id']] = $row['department_name'];

// ดึงข้อมูลหลัก
$sql = "SELECT * FROM departments ORDER BY department_id";
$result = $conn->query($sql);

// สำหรับแก้ไข
$edit = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM departments WHERE department_id=$eid");
    $edit = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการแผนก/ฝ่าย</title>
    <link rel="stylesheet" href="user-crud.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php if (isset($popup) && $popup): ?>
    <div class="popup-modal">
        <div class="popup-content">
            <i class="fas <?= $popup['icon'] ?>" style="color:<?= $popup['color'] ?>;"></i>
            <div class="msg" style="color:<?= $popup['color'] ?>; font-size:20px;"> <?= $popup['msg'] ?> </div>
            <?php if ($popup['redirect']): ?>
            <div style="font-size:14px; color:#888;">กำลังโหลดข้อมูลใหม่...</div>
            <script>setTimeout(function(){ window.location.href = "<?= $popup['redirect'] ?>"; }, 1500);</script>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="department-card">
        <div class="department-header">
            <div>
                <span class="department-title"><i class="fas fa-sitemap"></i> จัดการหน่วยงาน/ฝ่าย</span>
            </div>
            <a href="users.php" class="user-manage-btn">&larr; กลับหน้าจัดการผู้ใช้งาน</a>
        </div>
        <?php if ($alert) echo $alert; ?>
        <form class="department-form" method="post">
            <?php if ($edit): ?>
                <input type="hidden" name="edit_id" value="<?= $edit['department_id'] ?>">
                <input type="text" name="edit_department_name" value="<?= htmlspecialchars($edit['department_name']) ?>" required placeholder="ชื่อหน่วยงาน/ฝ่าย">
                <select name="edit_parent_id">
                    <option value="">- ไม่มีหน่วยงานแม่ -</option>
                    <?php foreach($departments as $id=>$name): if ($id == $edit['department_id']) continue; ?>
                        <option value="<?= $id ?>" <?= $edit['parent_id']==$id?'selected':'' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="edit_department" class="dep-btn edit"><i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง</button>
                <a href="departments.php" class="dep-btn cancel-btn"><i class="fas fa-times"></i> ยกเลิก</a>
            <?php else: ?>
                <input type="text" name="department_name" required placeholder="ชื่อหน่วยงาน/ฝ่าย">
                <select name="parent_id">
                    <option value="">- ไม่มีหน่วยงานแม่ -</option>
                    <?php foreach($departments as $id=>$name): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_department" class="dep-btn add"><i class="fas fa-plus"></i> เพิ่มหน่วยงานใหม่</button>
            <?php endif; ?>
        </form>
        <table class="department-table">
            <thead>
                <tr>
                    <th>ชื่อหน่วยงาน/ฝ่าย</th>
                    <th>สังกัด</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['department_name']) ?></td>
                    <td><?= $row['parent_id'] ? htmlspecialchars($departments[$row['parent_id']] ?? '-') : '-' ?></td>
                    <td class="department-actions">
                        <a href="departments.php?edit=<?= $row['department_id'] ?>" class="dep-btn edit" title="แก้ไขข้อมูล"><i class="fas fa-pen"></i> แก้ไข</a>
                        <a href="departments.php?delete=<?= $row['department_id'] ?>" class="dep-btn delete" title="ลบข้อมูล" onclick="return confirm('ยืนยันการลบข้อมูลนี้หรือไม่?')"><i class="fas fa-trash"></i> ลบ</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>