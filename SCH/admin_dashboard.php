<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
function event_type_th($type) {
    switch($type) {
        case 'login_success': return 'เข้าสู่ระบบ';
        case 'login_fail': return 'เข้าสู่ระบบล้มเหลว';
        case 'logout': return 'ออกจากระบบ';
        case 'add_user': return 'เพิ่มผู้ใช้';
        case 'edit_user': return 'แก้ไขผู้ใช้';
        case 'delete_user': return 'ลบผู้ใช้';
        default: return $type;
    }
}
// Filter logic
$where = [];
$params = [];
$types = '';
if (!empty($_GET['username'])) {
    $where[] = "username LIKE ?";
    $params[] = '%' . $_GET['username'] . '%';
    $types .= 's';
}
if (!empty($_GET['event_type'])) {
    $where[] = "event_type = ?";
    $params[] = $_GET['event_type'];
    $types .= 's';
}
if (!empty($_GET['date_from'])) {
    $where[] = "DATE(event_time) >= ?";
    $params[] = $_GET['date_from'];
    $types .= 's';
}
if (!empty($_GET['date_to'])) {
    $where[] = "DATE(event_time) <= ?";
    $params[] = $_GET['date_to'];
    $types .= 's';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
// Pagination setup
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;
// สร้าง query string ใหม่โดยลบ key 'page' ออกก่อน
$get_params = $_GET;
unset($get_params['page']);
$query_str = http_build_query($get_params);
$query_str = $query_str ? '&'.$query_str : '';
// Get total log count (with filter)
$count_sql = "SELECT COUNT(*) FROM user_logs $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_logs);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total_logs / $per_page);
// Get logs for current page (with filter)
$log_sql = "SELECT * FROM user_logs $where_sql ORDER BY event_time DESC LIMIT ? OFFSET ?";
$log_stmt = $conn->prepare($log_sql);
if ($params) {
    $params[] = $per_page;
    $params[] = $offset;
    $log_stmt->bind_param($types . 'ii', ...$params);
} else {
    $log_stmt->bind_param('ii', $per_page, $offset);
}
$log_stmt->execute();
$logs = $log_stmt->get_result();
// Load settings
$sys_name = 'ระบบงานพัสดุ-ครุภัณฑ์';
$logo_path = '';
$hospital_name_en = '';
$hospital_name_th = '';
$system_intro = '';
$system_title = 'ระบบงานพัสดุ-ครุภัณฑ์';
$set = $conn->query("SELECT * FROM system_settings WHERE setting_key IN ('system_name','system_logo','hospital_name_en','hospital_name_th','system_intro','system_title')");
while($row = $set->fetch_assoc()) {
    if ($row['setting_key'] === 'system_name') $sys_name = $row['setting_value'];
    if ($row['setting_key'] === 'system_logo') $logo_path = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_en') $hospital_name_en = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_th') $hospital_name_th = $row['setting_value'];
    if ($row['setting_key'] === 'system_intro') $system_intro = $row['setting_value'];
    if ($row['setting_key'] === 'system_title') $system_title = $row['setting_value'];
}
// Settings logic
$popup_msg = '';
$popup_color = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sys_name = trim($_POST['system_name'] ?? '');
    $hospital_name_en = trim($_POST['hospital_name_en'] ?? '');
    $hospital_name_th = trim($_POST['hospital_name_th'] ?? '');
    $system_intro = trim($_POST['system_intro'] ?? '');
    $system_title = trim($_POST['system_title'] ?? '');
    $logo_path = null;
    $success = true;
    if (isset($_FILES['system_logo']) && $_FILES['system_logo']['tmp_name']) {
        $ext = strtolower(pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed)) {
            $logo_path = 'uploads/system_logo.' . $ext;
            if (!move_uploaded_file($_FILES['system_logo']['tmp_name'], $logo_path)) {
                $success = false;
                $popup_msg = 'เกิดข้อผิดพลาดในการอัปโหลดโลโก้';
                $popup_color = '#d32f2f';
            } else {
                $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_logo', '".$conn->real_escape_string($logo_path)."')");
            }
        } else {
            $success = false;
            $popup_msg = 'อนุญาตเฉพาะไฟล์ jpg, jpeg, png, gif เท่านั้น';
            $popup_color = '#d32f2f';
        }
    }
    if ($success) {
        $q1 = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_name', '".$conn->real_escape_string($sys_name)."')");
        $q2 = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('hospital_name_en', '".$conn->real_escape_string($hospital_name_en)."')");
        $q3 = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('hospital_name_th', '".$conn->real_escape_string($hospital_name_th)."')");
        $q4 = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_intro', '".$conn->real_escape_string($system_intro)."')");
        $q5 = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_title', '".$conn->real_escape_string($system_title)."')");
        if ($q1 && $q2 && $q3 && $q4 && $q5) {
            $popup_msg = 'บันทึกการตั้งค่าสำเร็จ';
            $popup_color = '#43a047';
        } else {
            $popup_msg = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
            $popup_color = '#d32f2f';
        }
    }
    echo '<div class="popup-modal" style="display:flex;"><div class="popup-content"><i class="fas fa-'.($popup_color==='#43a047'?'smile':'frown').'" style="color:'.$popup_color.';"></i><div class="msg" style="color:'.$popup_color.'; font-size:20px;">'.$popup_msg.'</div></div></div><script>setTimeout(function(){document.querySelectorAll(\'.popup-modal\')[0].style.display=\'none\';},1800);</script>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="user-crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    body { background: #f8fafc; }
    .admin-dashboard-container {
        max-width: 1100px;
        margin: 40px auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 32px rgba(191,161,74,0.10);
        padding: 36px 36px 28px 36px;
    }
    .admin-dashboard-title {
        font-size: 2.1rem;
        color: #1a3e6d;
        font-weight: 700;
        margin-bottom: 18px;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .admin-dashboard-nav {
        margin-bottom: 28px;
        display: flex;
        gap: 18px;
    }
    .admin-dashboard-nav a {
        color: #1a3e6d;
        background: #f7e7b4;
        border-radius: 8px;
        padding: 8px 18px;
        text-decoration: none;
        font-weight: 600;
        transition: background 0.18s;
    }
    .admin-dashboard-nav a.active, .admin-dashboard-nav a:hover {
        background: #bfa14a;
        color: #fff;
    }
    .admin-section {
        margin-bottom: 38px;
    }
    .admin-section-title {
        font-size: 1.18rem;
        color: #bfa14a;
        font-weight: 700;
        margin-bottom: 12px;
        letter-spacing: 0.2px;
    }
    .settings-form label {
        display: block;
        margin-top: 12px;
        font-weight: 600;
        color: #1a3e6d;
    }
    .settings-form input[type="text"], .settings-form input[type="file"] {
        width: 100%;
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #d8e0e7;
        margin-top: 4px;
    }
    .settings-form button {
        margin-top: 18px;
        background: #bfa14a;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 10px 28px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.18s;
    }
    .settings-form button:hover {
        background: #a88c2c;
    }
    .log-page-btn {
        display: inline-block;
        background: #f7e7b4;
        color: #1a3e6d;
        border-radius: 6px;
        padding: 6px 16px;
        margin: 0 6px;
        text-decoration: none;
        font-weight: 600;
        border: 1px solid #e7e2d1;
        transition: background 0.18s;
    }
    .log-page-btn:hover {
        background: #bfa14a;
        color: #fff;
    }
    </style>
</head>
<body>
    <div class="admin-dashboard-container">
        <div class="admin-dashboard-title">
            <i class="fas fa-tools"></i> Admin Dashboard
        </div>
        <div class="admin-dashboard-nav">
            <a href="#logs" class="active"><i class="fas fa-clipboard-list"></i> Log การใช้งาน</a>
            <a href="#settings"><i class="fas fa-cogs"></i> ตั้งค่าระบบ</a>
            <a href="logout.php" style="margin-left:auto; background:#e57373; color:#fff;"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
        <div class="admin-section" id="logs">
            <div class="admin-section-title"><i class="fas fa-history"></i> ประวัติการใช้งานระบบ</div>
            <form method="get" class="log-filter-form">
                <div>
                    <label>ผู้ใช้งาน</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
                </div>
                <div>
                    <label>เหตุการณ์</label>
                    <select name="event_type">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="login_success" <?= (($_GET['event_type'] ?? '') === 'login_success') ? 'selected' : '' ?>>เข้าสู่ระบบ</option>
                        <option value="login_fail" <?= (($_GET['event_type'] ?? '') === 'login_fail') ? 'selected' : '' ?>>เข้าสู่ระบบล้มเหลว</option>
                        <option value="logout" <?= (($_GET['event_type'] ?? '') === 'logout') ? 'selected' : '' ?>>ออกจากระบบ</option>
                        <option value="add_user" <?= (($_GET['event_type'] ?? '') === 'add_user') ? 'selected' : '' ?>>เพิ่มผู้ใช้</option>
                        <option value="edit_user" <?= (($_GET['event_type'] ?? '') === 'edit_user') ? 'selected' : '' ?>>แก้ไขผู้ใช้</option>
                        <option value="delete_user" <?= (($_GET['event_type'] ?? '') === 'delete_user') ? 'selected' : '' ?>>ลบผู้ใช้</option>
                    </select>
                </div>
                <div>
                    <label>จากวันที่</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                <div>
                    <label>ถึงวันที่</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>
                <button type="submit">กรอง</button>
            </form>
            <table class="log-table">
                <thead>
                    <tr>
                        <th>เวลา</th>
                        <th>ผู้ใช้งาน</th>
                        <th>เหตุการณ์</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($logs && $logs->num_rows): ?>
                    <?php while($log = $logs->fetch_assoc()): ?>
                    <tr>
                        <td><?php 
                            $dt = strtotime($log['event_time']);
                            $th_year = date('Y', $dt) + 543;
                            $th_date = date('d/m/', $dt) . $th_year . date(' H:i:s', $dt);
                            echo htmlspecialchars($th_date);
                        ?></td>
                        <td><?= htmlspecialchars($log['username']) ?></td>
                        <td><?= event_type_th($log['event_type']) ?></td>
                        <td><?= htmlspecialchars($log['event_detail']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#aaa;">ยังไม่มีข้อมูล log</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <div style="text-align:center; margin-top:10px;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?><?= $query_str ?>#logs" class="log-page-btn">ก่อนหน้า</a>
                <?php endif; ?>
                หน้า <?= $page ?> / <?= $total_pages ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?><?= $query_str ?>#logs" class="log-page-btn">ถัดไป</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="admin-section" id="settings">
            <div class="admin-section-title"><i class="fas fa-cogs"></i> ตั้งค่าทั่วไปของระบบ</div>
            <form class="settings-form" method="post" enctype="multipart/form-data">
            <label>หัวข้อใหญ่ (จะแสดงหน้าแรก)</label>
            <input type="text" name="system_title" value="<?= htmlspecialchars($system_title) ?>">
                <label>ชื่อโรงพยาบาล (EN)</label>
                <input type="text" name="hospital_name_en" value="<?= htmlspecialchars($hospital_name_en) ?>">
                <label>ชื่อโรงพยาบาล (TH)</label>
                <input type="text" name="hospital_name_th" value="<?= htmlspecialchars($hospital_name_th) ?>">
                <label>รายละเอียดระบบ (จะแสดงหน้าแรก)</label>
                <textarea name="system_intro" rows="5"><?= htmlspecialchars($system_intro) ?></textarea>
                <label>โลโก้ระบบ</label>
                <?php if ($logo_path): ?>
                    <div style="margin:10px 0;"><img src="<?= htmlspecialchars($logo_path) ?>" alt="โลโก้ระบบ" style="max-height:60px;"></div>
                <?php endif; ?>
                <input type="file" name="system_logo" id="system_logo" onchange="document.getElementById('logo-filename').textContent = this.files[0]?.name || 'ไม่ได้เลือกไฟล์ใด';">
                <span id="logo-filename">ไม่ได้เลือกไฟล์ใด</span>
                <button type="submit">บันทึกการตั้งค่า</button>
            </form>
        </div>
        <div style="margin-bottom:18px;">
</div>
    </div>
</body>
</html>
<?php $conn->close(); ?> ทำให้สามารถเคลียร์ข้อมูลได้