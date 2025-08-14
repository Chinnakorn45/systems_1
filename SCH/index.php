<?php
$host = 'localhost'; // Change this to your database host
$user = 'root'; // Change this to your database username
$pass = ''; // Change this to your database password
$db = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db); // Ensure to change the database name if necessary
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
$hospital_name_en = 'Suratthani Cancer Hospital';
$hospital_name_th = 'โรงพยาบาลมะเร็งสุราษฎร์ธานี (ศูนย์มะเร็งสุราษฎร์ธานี)';
$system_title = 'ระบบงานพัสดุ-ครุภัณฑ์';
$system_intro = '';
$set = $conn->query("SELECT * FROM system_settings WHERE setting_key IN ('system_title','hospital_name_en','hospital_name_th','system_intro')");
while($row = $set->fetch_assoc()) {
    if ($row['setting_key'] === 'system_title') $system_title = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_en') $hospital_name_en = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_th') $hospital_name_th = $row['setting_value'];
    if ($row['setting_key'] === 'system_intro') $system_intro = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="th"> <!-- Change 'th' to 'en' if you want the page in English -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบงานพัสดุ-ครุภัณฑ์ | Faculty of Fine Arts</title>
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
                <img src="/SCH/uploads/system_logo.png" alt="Faculty of Fine Arts Logo">
                <span><?= htmlspecialchars($hospital_name_en) ?><br>
                    <?= htmlspecialchars($hospital_name_th) ?></span>
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
                <div class="feature-cards">
                    <a href="src/login.php" class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-address-book"></i>
                        </div>
                        <h3>ระบบฐานข้อมูลพนักงาน</h3>
                    </a>
                    <a href="../project_work2/src/index.php" class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3>ระบบบันทึกคลังครุภัณฑ์</h3>
                    </a>
                    <a href="../repair/src/login.php" class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>แจ้งซ่อมงานพัสดุ-ครุภัณฑ์</h3>
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
<?php $conn->close(); ?>