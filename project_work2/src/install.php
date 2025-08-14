<?php
// src/install.php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); exit('Forbidden');
}

function runSqlFile($link, $path) {
    $sql = file_get_contents($path);
    if ($sql === false) throw new Exception("อ่านไฟล์ไม่สำเร็จ: $path");
    // แตกเป็นหลาย statement อย่างง่าย (ถ้าไฟล์ไม่มี DELIMITER พิเศษ)
    foreach (array_filter(array_map('trim', explode(";", $sql))) as $stmt) {
        if ($stmt === '') continue;
        if (!mysqli_query($link, $stmt)) {
            throw new Exception("SQL error: ".mysqli_error($link)."\nในคำสั่ง: ".$stmt);
        }
    }
}

mysqli_query($link, "CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$applied = [];
$res = mysqli_query($link, "SELECT filename FROM migrations");
while ($row = mysqli_fetch_assoc($res)) $applied[$row['filename']] = true;

$migDir = __DIR__.'/migrations';
$files = glob($migDir.'/*.sql');
sort($files);

$logs = [];
try {
    foreach ($files as $file) {
        $fname = basename($file);
        if (isset($applied[$fname])) {
            $logs[] = "ข้าม $fname (เคยรันแล้ว)";
            continue;
        }
        runSqlFile($link, $file);
        mysqli_query($link, "INSERT INTO migrations(filename) VALUES ('".mysqli_real_escape_string($link, $fname)."')");
        $logs[] = "รัน $fname เรียบร้อย";
    }
    $status = 'success';
} catch (Exception $e) {
    $status = 'error';
    $logs[] = $e->getMessage();
}
?>
<!doctype html><html lang="th">
<head>
<meta charset="utf-8"><title>ติดตั้ง/อัปเกรดฐานข้อมูล</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:720px">
  <h3>ผลการติดตั้ง/อัปเกรดฐานข้อมูล</h3>
  <div class="alert alert-<?= $status==='success'?'success':'danger' ?>">
    <?= $status==='success' ? 'สำเร็จ!' : 'เกิดข้อผิดพลาด' ?>
  </div>
  <pre class="bg-light p-3 border rounded" style="white-space:pre-wrap"><?= htmlspecialchars(implode("\n", $logs)) ?></pre>
  <a class="btn btn-secondary mt-3" href="settings.php">กลับไปหน้าตั้งค่า</a>
</div>
</body></html>
