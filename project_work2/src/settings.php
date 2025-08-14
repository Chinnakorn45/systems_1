<?php
// src/settings.php

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); exit('Forbidden');
}

// โหลด config หลังยืนยันสิทธิ์ เพื่อหลีกเลี่ยง side effects ไม่จำเป็น
require_once __DIR__ . '/config.php';

$errors = [];
$success = null;

// เก็บค่าปัจจุบันจากคอนสแตนต์
$currentHostConst = defined('DB_SERVER')   ? DB_SERVER   : 'localhost';
$currentUserConst = defined('DB_USERNAME') ? DB_USERNAME : 'root';
$currentPassConst = defined('DB_PASSWORD') ? DB_PASSWORD : '';
$currentNameConst = defined('DB_NAME')     ? DB_NAME     : 'borrowing_db';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    // ถ้าผู้ใช้ไม่กรอกรหัสผ่าน ให้คงค่ารหัสผ่านเดิม
    $passInput = $_POST['db_pass'] ?? '';
    $pass = ($passInput !== '') ? trim($passInput) : $currentPassConst;

    $name = trim($_POST['db_name'] ?? '');

    if ($host === '' || $user === '' || $name === '') {
        $errors[] = 'กรอก Host, User, และ Database name ให้ครบ';
    } else {
        // ทดสอบการเชื่อมต่อ
        $test = @mysqli_connect($host, $user, $pass);
        if (!$test) {
            $errors[] = 'เชื่อมต่อ MySQL ไม่ได้: ' . mysqli_connect_error();
        } else {
            // สร้างฐานข้อมูลถ้ายังไม่มี แล้วเลือกใช้งาน
            $escapedDb = str_replace('`', '``', $name);
            @mysqli_query($test, "CREATE DATABASE IF NOT EXISTS `{$escapedDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            if (!@mysqli_select_db($test, $name)) {
                $errors[] = 'เลือกฐานข้อมูลไม่สำเร็จ';
            } else {
                // เขียน config.local.php แบบกัน define ซ้ำ
                $local = __DIR__ . '/config.local.php';
                $content =
                    "<?php\n" .
                    "// สร้างโดยหน้า settings.php\n" .
                    "if (!defined('DB_SERVER'))   define('DB_SERVER', "   . var_export($host, true) . ");\n" .
                    "if (!defined('DB_USERNAME')) define('DB_USERNAME', " . var_export($user, true) . ");\n" .
                    "if (!defined('DB_PASSWORD')) define('DB_PASSWORD', " . var_export($pass, true) . ");\n" .
                    "if (!defined('DB_NAME'))     define('DB_NAME', "     . var_export($name, true) . ");\n";

                if (@file_put_contents($local, $content) === false) {
                    $errors[] = 'บันทึกไฟล์ config.local.php ไม่สำเร็จ (ตรวจสิทธิ์แฟ้ม/โฟลเดอร์)';
                } else {
                    $success = 'บันทึกการตั้งค่าเรียบร้อย';
                }
            }
            @mysqli_close($test);
        }
    }

    // ถ้ากดติดตั้ง/อัปเกรดทันที
    if (isset($_POST['run_install']) && empty($errors)) {
        header('Location: install.php');
        exit;
    }

    // อัปเดตค่าที่จะแสดงในฟอร์มหลัง submit
    $currentHostConst = $host ?: $currentHostConst;
    $currentUserConst = $user ?: $currentUserConst;
    $currentNameConst = $name ?: $currentNameConst;
    // รหัสผ่าน ไม่แสดงคืนในฟอร์มเพื่อความปลอดภัย
}

// ค่าที่จะแสดงในฟอร์ม (password จะไม่เติมค่าเดิม)
$currentHost = $currentHostConst;
$currentUser = $currentUserConst;
$currentPass = ''; // ไม่เติมกลับเพื่อความปลอดภัย
$currentName = $currentNameConst;
?>
<!doctype html><html lang="th">
<head>
<meta charset="utf-8"><title>ตั้งค่าระบบ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:720px">
  <h3 class="mb-3">ตั้งค่าฐานข้อมูล</h3>

  <?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
  </div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="mb-3">
      <label class="form-label" for="db_host">Host</label>
      <input class="form-control" id="db_host" name="db_host" value="<?= htmlspecialchars($currentHost) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="db_user">Username</label>
      <input class="form-control" id="db_user" name="db_user" value="<?= htmlspecialchars($currentUser) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="db_pass">Password</label>
      <input class="form-control" id="db_pass" name="db_pass" type="password" placeholder="(ปล่อยว่าง = ใช้รหัสผ่านเดิม)">
    </div>
    <div class="mb-3">
      <label class="form-label" for="db_name">Database name</label>
      <input class="form-control" id="db_name" name="db_name" value="<?= htmlspecialchars($currentName) ?>" required>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit" name="save_only" value="1">บันทึก</button>
      <button class="btn btn-success" type="submit" name="run_install" value="1">บันทึกและสร้าง/อัปเกรดฐานข้อมูล</button>
    </div>
  </form>
</div>
</body></html>
