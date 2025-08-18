<?php
// ----------------- Session -----------------
if (session_status() === PHP_SESSION_NONE) session_start();

// ❗ โชว์การ์ดค้นหาเสมอ แล้วไปเช็คสิทธิ์ที่ equipment_lookup.php
$canLookup = true; 

// ----------------- DB -----------------
require_once __DIR__ . '/db.php';

// ----------------- Settings / Branding -----------------
$hospital_name_en = 'Suratthani Cancer Hospital';
$hospital_name_th = 'โรงพยาบาลมะเร็งสุราษฎร์ธานี (ศูนย์มะเร็งสุราษฎร์ธานี)';
$system_title     = 'ระบบงานพัสดุ-ครุภัณฑ์';
$system_intro     = '';
$logo_path        = '';

$set = $conn->query("
  SELECT setting_key, setting_value
  FROM system_settings
  WHERE setting_key IN ('system_title','hospital_name_en','hospital_name_th','system_intro','system_logo')
");
while ($row = $set->fetch_assoc()) {
    if ($row['setting_key'] === 'system_title')       $system_title = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_en')   $hospital_name_en = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_th')   $hospital_name_th = $row['setting_value'];
    if ($row['setting_key'] === 'system_intro')       $system_intro = $row['setting_value'];
    if ($row['setting_key'] === 'system_logo')        $logo_path = $row['setting_value'];
}

// ----------------- Helpers -----------------
function build_url_from_relative($relativePath) {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $prefix = ($scriptDir === '/' ? '' : $scriptDir);
    return $prefix . '/' . ltrim($relativePath, '/\\');
}

// ----------------- Logo URL -----------------
$logo_url = '';
if ($logo_path) {
    $fs_path = __DIR__ . DIRECTORY_SEPARATOR . ltrim($logo_path, '/\\');
    if (is_file($fs_path)) $logo_url = build_url_from_relative($logo_path);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($system_title) ?> | Faculty of Fine Arts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="main-header">
        <div class="container header-content">
            <div class="logo">
                <?php if ($logo_url): ?>
                    <img src="<?= htmlspecialchars($logo_url) ?>" alt="System Logo">
                <?php endif; ?>
                <span>
                    <?= htmlspecialchars($hospital_name_en) ?><br>
                    <?= htmlspecialchars($hospital_name_th) ?>
                </span>
            </div>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="container hero-content">
                <div class="card intro-card">
                    <h2><i class="fas fa-boxes"></i> <?= htmlspecialchars($system_title) ?></h2>
                    <p><?= nl2br(htmlspecialchars($system_intro)) ?></p>
                </div>

                <a href="login_admin.php" class="admin-control-badge" style="text-decoration:none; cursor:pointer;">
                    <i class="fas fa-user-shield"></i> Admin Control
                </a>

                <!-- การ์ดเมนูหลัก -->
                <div class="feature-cards">
                    <a href="src/login.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-address-book"></i></div>
                        <h3>ระบบฐานข้อมูลพนักงาน</h3>
                    </a>

                    <a href="../project_work2/src/index.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-box-open"></i></div>
                        <h3>ระบบบันทึกคลังครุภัณฑ์</h3>
                    </a>

                    <a href="../repair/src/login.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-comments"></i></div>
                        <h3>แจ้งซ่อมงานพัสดุ-ครุภัณฑ์</h3>
                    </a>

                    <?php if ($canLookup): ?>
                    <!-- การ์ดใหม่: ค้นหาอุปกรณ์ (ไปหน้าแยก) -->
                    <!-- ✅ แก้ลิงก์ให้ถูก: อยู่โฟลเดอร์เดียวกับไฟล์นี้ -->
                    <a href="../SCH/equipment_lookup.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-magnifying-glass"></i></div>
                        <h3>ค้นหาอุปกรณ์ (สแกน/พิมพ์)</h3>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- ส่วนค้นหา/สแกนถูกย้ายไป equipment_lookup.php แล้ว -->

            </div>
        </section>
    </main>
</body>
</html>
<?php $conn->close(); ?>
