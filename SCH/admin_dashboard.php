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
$conn->set_charset('utf8mb4');

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
function ensure_upload_dir($path) {
    $dir = dirname($path);
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
}

/** ===== Filter logic (logs) ===== */
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

/** ===== Pagination ===== */
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

$get_params = $_GET;
unset($get_params['page']);
$query_str = http_build_query($get_params);
$query_str = $query_str ? '&'.$query_str : '';

/** ===== Count logs ===== */
$count_sql = "SELECT COUNT(*) FROM user_logs $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_logs);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = max(1, ceil(($total_logs ?: 0) / $per_page));

/** ===== Get logs page ===== */
$log_sql = "SELECT * FROM user_logs $where_sql ORDER BY event_time DESC LIMIT ? OFFSET ?";
$log_stmt = $conn->prepare($log_sql);
if ($params) {
    $bindParams = $params;
    $bindParams[] = $per_page;
    $bindParams[] = $offset;
    $log_stmt->bind_param($types . 'ii', ...$bindParams);
} else {
    $log_stmt->bind_param('ii', $per_page, $offset);
}
$log_stmt->execute();
$logs = $log_stmt->get_result();

/** ===== Load settings ===== */
$sys_name = 'ระบบงานพัสดุ-ครุภัณฑ์';
$logo_path = '';
$hospital_name_en = '';
$hospital_name_th = '';
$system_intro = '';
$system_title = 'ระบบงานพัสดุ-ครุภัณฑ์';
$promo_image = '';

$promo_title = $promo_desc = $promo_btn_text = $promo_btn_link = '';
$promo_b1 = $promo_b2 = $promo_b3 = '';

$set = $conn->query("SELECT setting_key, setting_value 
                     FROM system_settings 
                     WHERE setting_key IN (
                       'system_name','system_logo','hospital_name_en','hospital_name_th',
                       'system_intro','system_title','promo_image',
                       'promo_title','promo_desc','promo_btn_text','promo_btn_link',
                       'promo_bullet_1','promo_bullet_2','promo_bullet_3'
                     )");
while($row = $set->fetch_assoc()) {
    if ($row['setting_key'] === 'system_name')       $sys_name = $row['setting_value'];
    if ($row['setting_key'] === 'system_logo')       $logo_path = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_en')  $hospital_name_en = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_th')  $hospital_name_th = $row['setting_value'];
    if ($row['setting_key'] === 'system_intro')      $system_intro = $row['setting_value'];
    if ($row['setting_key'] === 'system_title')      $system_title = $row['setting_value'];
    if ($row['setting_key'] === 'promo_image')       $promo_image = $row['setting_value'];

    if ($row['setting_key'] === 'promo_title')       $promo_title = $row['setting_value'];
    if ($row['setting_key'] === 'promo_desc')        $promo_desc = $row['setting_value'];
    if ($row['setting_key'] === 'promo_btn_text')    $promo_btn_text = $row['setting_value'];
    if ($row['setting_key'] === 'promo_btn_link')    $promo_btn_link = $row['setting_value'];
    if ($row['setting_key'] === 'promo_bullet_1')    $promo_b1 = $row['setting_value'];
    if ($row['setting_key'] === 'promo_bullet_2')    $promo_b2 = $row['setting_value'];
    if ($row['setting_key'] === 'promo_bullet_3')    $promo_b3 = $row['setting_value'];
}

/** ===== Save settings ===== */
$popup_msg = '';
$popup_color = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sys_name         = trim($_POST['system_name'] ?? $sys_name);
    $hospital_name_en = trim($_POST['hospital_name_en'] ?? $hospital_name_en);
    $hospital_name_th = trim($_POST['hospital_name_th'] ?? $hospital_name_th);
    $system_intro     = trim($_POST['system_intro'] ?? $system_intro);
    $system_title     = trim($_POST['system_title'] ?? $system_title);

    // promo text
    $promo_title      = trim($_POST['promo_title']     ?? $promo_title);
    $promo_desc       = trim($_POST['promo_desc']      ?? $promo_desc);
    $promo_btn_text   = trim($_POST['promo_btn_text']  ?? $promo_btn_text);
    $promo_btn_link   = trim($_POST['promo_btn_link']  ?? $promo_btn_link);
    $promo_b1         = trim($_POST['promo_bullet_1']  ?? $promo_b1);
    $promo_b2         = trim($_POST['promo_bullet_2']  ?? $promo_b2);
    $promo_b3         = trim($_POST['promo_bullet_3']  ?? $promo_b3);

    $success = true;

    // ---- Upload: system_logo ----
    if (isset($_FILES['system_logo']) && !empty($_FILES['system_logo']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed, true)) {
            $logo_path_new = 'uploads/system_logo.' . $ext;
            ensure_upload_dir($logo_path_new);
            if (!move_uploaded_file($_FILES['system_logo']['tmp_name'], $logo_path_new)) {
                $success = false; $popup_msg = 'เกิดข้อผิดพลาดในการอัปโหลดโลโก้'; $popup_color = '#d32f2f';
            } else {
                $logo_path = $logo_path_new;
                $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_logo', '".$conn->real_escape_string($logo_path)."')");
            }
        } else {
            $success = false; $popup_msg = 'โลโก้: อนุญาตเฉพาะไฟล์ jpg, jpeg, png, gif, webp'; $popup_color = '#d32f2f';
        }
    }

    // ---- Upload: promo_image ----
    if ($success && isset($_FILES['promo_image']) && !empty($_FILES['promo_image']['tmp_name'])) {
        $ext2 = strtolower(pathinfo($_FILES['promo_image']['name'], PATHINFO_EXTENSION));
        $allowed2 = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext2, $allowed2, true)) {
            $promo_image_new = 'uploads/promo_image.' . $ext2;
            ensure_upload_dir($promo_image_new);
            if (!move_uploaded_file($_FILES['promo_image']['tmp_name'], $promo_image_new)) {
                $success = false; $popup_msg = 'เกิดข้อผิดพลาดในการอัปโหลดรูปป๊อปอัป'; $popup_color = '#d32f2f';
            } else {
                $promo_image = $promo_image_new;
                $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_image', '".$conn->real_escape_string($promo_image)."')");
            }
        } else {
            $success = false; $popup_msg = 'ป๊อปอัป: อนุญาตเฉพาะไฟล์ jpg, jpeg, png, gif, webp'; $popup_color = '#d32f2f';
        }
    }

    // ---- Save text settings ----
    if ($success) {
        $ok = [];
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_name', '".$conn->real_escape_string($sys_name)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('hospital_name_en', '".$conn->real_escape_string($hospital_name_en)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('hospital_name_th', '".$conn->real_escape_string($hospital_name_th)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_intro', '".$conn->real_escape_string($system_intro)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('system_title', '".$conn->real_escape_string($system_title)."')");

        // promo text
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_title', '".$conn->real_escape_string($promo_title)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_desc', '".$conn->real_escape_string($promo_desc)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_btn_text', '".$conn->real_escape_string($promo_btn_text)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_btn_link', '".$conn->real_escape_string($promo_btn_link)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_bullet_1', '".$conn->real_escape_string($promo_b1)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_bullet_2', '".$conn->real_escape_string($promo_b2)."')");
        $ok[] = $conn->query("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('promo_bullet_3', '".$conn->real_escape_string($promo_b3)."')");

        if (in_array(false, $ok, true)) {
            $popup_msg = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'; $popup_color = '#d32f2f';
        } else {
            $popup_msg = 'บันทึกการตั้งค่าสำเร็จ'; $popup_color = '#43a047';
        }
    }

    // quick popup
    echo '<div class="popup-modal" style="display:flex;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,.4);z-index:9999;">
            <div class="popup-content" style="background:#fff;border-radius:12px;padding:18px 22px;display:flex;align-items:center;gap:12px;box-shadow:0 10px 30px rgba(0,0,0,.2);">
              <i class="fas fa-'.($popup_color==='#43a047'?'circle-check':'circle-exclamation').'" style="font-size:22px;color:'.$popup_color.';"></i>
              <div class="msg" style="color:'.$popup_color.'; font-size:18px; font-weight:700;">'.$popup_msg.'</div>
            </div>
          </div>
          <script>setTimeout(function(){var m=document.querySelector(\'.popup-modal\'); if(m) m.remove();},1600);</script>';
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
    .admin-dashboard-nav a.active, .admin-dashboard-nav a:hover { background: #bfa14a; color: #fff; }
    .admin-section { margin-bottom: 38px; }
    .admin-section-title { font-size: 1.18rem; color: #bfa14a; font-weight: 700; margin-bottom: 12px; letter-spacing: 0.2px; }
    .settings-form label { display: block; margin-top: 12px; font-weight: 600; color: #1a3e6d; }
    .settings-form input[type="text"], .settings-form input[type="file"], .settings-form textarea, .settings-form input[type="url"] {
        width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #d8e0e7; margin-top: 4px;
    }
    .settings-form button {
        margin-top: 18px; background: #bfa14a; color: #fff; border: none; border-radius: 6px; padding: 10px 28px;
        font-weight: 700; font-size: 1rem; cursor: pointer; transition: background 0.18s;
    }
    .settings-form button:hover { background: #a88c2c; }
    .log-page-btn {
        display: inline-block; background: #f7e7b4; color: #1a3e6d; border-radius: 6px; padding: 6px 16px; margin: 0 6px;
        text-decoration: none; font-weight: 600; border: 1px solid #e7e2d1; transition: background 0.18s;
    }
    .log-page-btn:hover { background: #bfa14a; color: #fff; }
    .thumb { border:1px solid #e5e7eb; border-radius:8px; padding:8px; display:inline-block; background:#fafafa; }
    .thumb img { max-height:100px; display:block; }
    .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
    @media(max-width:760px){ .grid2 { grid-template-columns: 1fr; } }
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

        <!-- ===== Logs ===== -->
        <div class="admin-section" id="logs">
            <div class="admin-section-title"><i class="fas fa-history"></i> ประวัติการใช้งานระบบ</div>
            <form method="get" class="log-filter-form">
                <div class="grid2">
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
                </div>
                <button type="submit" style="margin-top:12px;">กรอง</button>
            </form>

            <table class="log-table" style="width:100%; margin-top:12px; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">เวลา</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">ผู้ใช้งาน</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">เหตุการณ์</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($logs && $logs->num_rows): ?>
                    <?php while($log = $logs->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #f1f5f9;"><?php 
                            $dt = strtotime($log['event_time']);
                            $th_year = date('Y', $dt) + 543;
                            $th_date = date('d/m/', $dt) . $th_year . date(' H:i:s', $dt);
                            echo htmlspecialchars($th_date);
                        ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars($log['username']) ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f1f5f9;"><?= event_type_th($log['event_type']) ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars($log['event_detail']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#aaa; padding:12px;">ยังไม่มีข้อมูล log</td></tr>
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

        <!-- ===== Settings ===== -->
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

                <div class="grid2">
                    <div>
                        <label>โลโก้ระบบ</label>
                        <?php if ($logo_path): ?>
                            <div class="thumb" style="margin:10px 0;">
                                <img src="<?= htmlspecialchars($logo_path) ?>" alt="โลโก้ระบบ">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="system_logo" id="system_logo" accept=".jpg,.jpeg,.png,.gif,.webp"
                               onchange="document.getElementById('logo-filename').textContent = this.files[0]?.name || 'ไม่ได้เลือกไฟล์';">
                        <small id="logo-filename">ไม่ได้เลือกไฟล์</small>
                    </div>

                    <div>
                        <label>รูปป๊อปอัป (Promo Image)</label>
                        <?php if ($promo_image): ?>
                            <div class="thumb" style="margin:10px 0;">
                                <img src="<?= htmlspecialchars($promo_image) ?>" alt="รูปป๊อปอัป">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="promo_image" id="promo_image" accept=".jpg,.jpeg,.png,.gif,.webp"
                               onchange="document.getElementById('promo-filename').textContent = this.files[0]?.name || 'ไม่ได้เลือกไฟล์';">
                        <small id="promo-filename">ไม่ได้เลือกไฟล์</small>
                        <div style="margin-top:6px;color:#64748b;font-size:.9rem;">
                            รูปนี้จะแสดงในป๊อปอัปหน้าแรก (เด้งทุกครั้ง) — เปลี่ยนรูปได้ตลอด
                        </div>
                    </div>
                </div>

                <hr style="margin:20px 0;border:none;border-top:1px dashed #e5e7eb;">
                <div style="font-weight:700;color:#1a3e6d;margin-bottom:8px;"><i class="fas fa-bullhorn"></i> ข้อความป๊อปอัปหน้าแรก</div>

                <label>หัวข้อ</label>
                <input type="text" name="promo_title" value="<?= htmlspecialchars($promo_title) ?>">

                <label>คำอธิบาย (หลายบรรทัดได้)</label>
                <textarea name="promo_desc" rows="4"><?= htmlspecialchars($promo_desc) ?></textarea>

                <div class="grid2">
                  <div>
                    <label>ปุ่ม — ข้อความ</label>
                    <input type="text" name="promo_btn_text" value="<?= htmlspecialchars($promo_btn_text) ?>" placeholder="เช่น ใช้งานได้แล้ววันนี้">
                  </div>
                  <div>
                    <label>ปุ่ม — ลิงก์ปลายทาง</label>
                    <input type="url" name="promo_btn_link" value="<?= htmlspecialchars($promo_btn_link) ?>" placeholder="../SCH/equipment_lookup.php">
                  </div>
                </div>

                <div class="grid2">
                  <div>
                    <label>บูลเล็ต #1</label>
                    <input type="text" name="promo_bullet_1" value="<?= htmlspecialchars($promo_b1) ?>">
                  </div>
                  <div>
                    <label>บูลเล็ต #2</label>
                    <input type="text" name="promo_bullet_2" value="<?= htmlspecialchars($promo_b2) ?>">
                  </div>
                </div>
                <label>บูลเล็ต #3</label>
                <input type="text" name="promo_bullet_3" value="<?= htmlspecialchars($promo_b3) ?>">

                <button type="submit">บันทึกการตั้งค่า</button>
            </form>
        </div>
        <div style="margin-bottom:18px;"></div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
