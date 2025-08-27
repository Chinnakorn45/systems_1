<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// โหลด config.php แบบยืดหยุ่น
$configCandidates = [
  __DIR__ . '/config.php',
  __DIR__ . '/../config.php',
  __DIR__ . '/includes/config.php',
  __DIR__ . '/../includes/config.php',
  $_SERVER['DOCUMENT_ROOT'] . '/systems_1/config.php',
  $_SERVER['DOCUMENT_ROOT'] . '/systems_1/includes/config.php',
];
$configLoaded = false;
foreach ($configCandidates as $cfg) {
  if ($cfg && is_file($cfg)) { require_once $cfg; $configLoaded = true; break; }
}
if (!$configLoaded) { http_response_code(500); die('ไม่พบไฟล์ config.php'); }

// helper กันแคชสำหรับ CSS ภายใน
if (!function_exists('asset_url')) {
  function asset_url(string $rel): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $url  = ($base === '/' ? '' : $base) . '/' . ltrim($rel, '/\\');
    $fs   = __DIR__ . DIRECTORY_SEPARATOR . ltrim($rel, '/\\');
    if (is_file($fs)) $url .= (strpos($url,'?')!==false?'&':'?') . 'v=' . filemtime($fs);
    return $url;
  }
}

// AuthZ
if (!isset($_SESSION["user_id"])) { header("location: login.php"); exit; }
if (($_SESSION["role"] ?? '') !== "admin") { header("location: index.php"); exit; }

$current_page = 'supply_form';

// Init
$supply_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$supply_name = $description = $unit = '';
$current_stock = $min_stock_level = 0;
$supply_name_err = $unit_err = $current_stock_err = $min_stock_level_err = '';
$is_edit = $supply_id > 0;

if ($is_edit) {
  $sql = "SELECT * FROM office_supplies WHERE supply_id = ?";
  if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $supply_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
      $supply_name = $row['supply_name'] ?? '';
      $description = $row['description'] ?? '';
      $unit = $row['unit'] ?? '';
      $current_stock = (int)($row['current_stock'] ?? 0);
      $min_stock_level = (int)($row['min_stock_level'] ?? 0);
    }
    mysqli_stmt_close($stmt);
  }
}

// Handle POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $supply_name     = trim($_POST['supply_name'] ?? '');
  $description     = trim($_POST['description'] ?? '');
  $unit            = trim($_POST['unit'] ?? '');
  $current_stock   = (int)($_POST['current_stock'] ?? 0);
  $min_stock_level = (int)($_POST['min_stock_level'] ?? 0);

  if ($supply_name === '') $supply_name_err = "กรุณากรอกชื่อวัสดุ";
  if ($unit === '')        $unit_err        = "กรุณากรอกหน่วยนับ";
  if ($current_stock < 0)  $current_stock_err = "จำนวนคงเหลือต้องไม่ติดลบ";
  if ($min_stock_level < 0)$min_stock_level_err = "สต็อกขั้นต่ำต้องไม่ติดลบ";

  if ($supply_name_err === '' && $unit_err === '' && $current_stock_err === '' && $min_stock_level_err === '') {
    if ($is_edit) {
      $sql = "UPDATE office_supplies
              SET supply_name=?, description=?, unit=?, current_stock=?, min_stock_level=?
              WHERE supply_id=?";
      if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "sssiii",
          $supply_name, $description, $unit, $current_stock, $min_stock_level, $supply_id
        );
        if (mysqli_stmt_execute($stmt)) { header("location: supplies.php"); exit; }
        mysqli_stmt_close($stmt);
      }
    } else {
      $sql = "INSERT INTO office_supplies
              (supply_name, description, unit, current_stock, min_stock_level)
              VALUES (?, ?, ?, ?, ?)";
      if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "sssii",
          $supply_name, $description, $unit, $current_stock, $min_stock_level
        );
        if (mysqli_stmt_execute($stmt)) { header("location: supplies.php"); exit; }
        mysqli_stmt_close($stmt);
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $is_edit ? 'แก้ไข' : 'เพิ่ม' ?>วัสดุสำนักงาน - ระบบบันทึกคลังครุภัณฑ์</title>

  <!-- Vendor CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- ✅ Font Awesome 6.5.2 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">

  <!-- Project CSS -->
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('sidebar.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('common-ui.css')) ?>">
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Mobile Navbar -->
    <nav class="navbar navbar-light bg-light d-md-none mb-3">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar"
                aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <span class="navbar-brand mb-0 h1"><?= $is_edit ? 'แก้ไขวัสดุ' : 'เพิ่มวัสดุ' ?></span>
      </div>
    </nav>

    <!-- Sidebar -->
    <?php
      $sidebarPath = __DIR__ . '/sidebar.php';
      if (!is_file($sidebarPath)) $sidebarPath = __DIR__ . '/../sidebar.php';
      if (is_file($sidebarPath)) include $sidebarPath;
      else echo '<div class="col-md-3 col-lg-2 d-none d-md-block"></div>';
    ?>

    <!-- Main -->
    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content position-relative mt-4 mt-md-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
          <h2 class="mb-0">
            <i class="fa-solid fa-circle-plus me-2"></i><?= $is_edit ? 'แก้ไขวัสดุสำนักงาน' : 'เพิ่มวัสดุสำนักงาน' ?>
          </h2>
          <div>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <form method="post" autocomplete="off" novalidate>
              <div class="mb-3">
                <label for="supply_name" class="form-label">ชื่อวัสดุ</label>
                <input type="text" id="supply_name" name="supply_name"
                  class="form-control <?= $supply_name_err ? 'is-invalid' : '' ?>"
                  value="<?= htmlspecialchars($supply_name) ?>" required>
                <?php if ($supply_name_err): ?><div class="invalid-feedback"><?= $supply_name_err ?></div><?php endif; ?>
              </div>

              <div class="mb-3">
                <label for="description" class="form-label">รายละเอียด</label>
                <textarea id="description" name="description" rows="2" class="form-control"><?= htmlspecialchars($description) ?></textarea>
              </div>

              <div class="mb-3">
                <label for="unit" class="form-label">หน่วยนับ</label>
                <input type="text" id="unit" name="unit"
                  class="form-control <?= $unit_err ? 'is-invalid' : '' ?>"
                  value="<?= htmlspecialchars($unit) ?>" required>
                <?php if ($unit_err): ?><div class="invalid-feedback"><?= $unit_err ?></div><?php endif; ?>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="current_stock" class="form-label">จำนวนคงเหลือ</label>
                  <input type="number" min="0" id="current_stock" name="current_stock"
                    class="form-control <?= $current_stock_err ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars((string)$current_stock) ?>" required>
                  <?php if ($current_stock_err): ?><div class="invalid-feedback"><?= $current_stock_err ?></div><?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="min_stock_level" class="form-label">สต็อกขั้นต่ำ</label>
                  <input type="number" min="0" id="min_stock_level" name="min_stock_level"
                    class="form-control <?= $min_stock_level_err ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars((string)$min_stock_level) ?>" required>
                  <?php if ($min_stock_level_err): ?><div class="invalid-feedback"><?= $min_stock_level_err ?></div><?php endif; ?>
                </div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-add">
                  <?= $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มวัสดุ' ?>
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
    <!-- /Main -->
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
