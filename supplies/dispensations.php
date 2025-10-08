<?php
// -------- Session --------
if (session_status() === PHP_SESSION_NONE) session_start();

// -------- โหลด config.php แบบยืดหยุ่น --------
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

// -------- Helpers --------
if (!function_exists('asset_url')) {
  function asset_url(string $rel): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $url  = ($base === '/' ? '' : $base) . '/' . ltrim($rel, '/\\');
    $fs   = __DIR__ . DIRECTORY_SEPARATOR . ltrim($rel, '/\\');
    if (is_file($fs)) $url .= (strpos($url,'?')!==false?'&':'?') . 'v=' . filemtime($fs);
    return $url;
  }
}
if (!function_exists('thaidate')) {
  function thaidate($date, $format = 'j F Y') {
    if (!$date) return '';
    $ts = is_numeric($date) ? (int)$date : strtotime($date);
    if (!$ts) return '';
    $months = ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    $monthIndex = (int)date('n', $ts);
    $repls = [
      'j' => date('j', $ts),
      'F' => $months[$monthIndex],
      'M' => mb_substr($months[$monthIndex], 0, 3), // ย่อ 3 ตัวอักษร
      'Y' => (string)(date('Y', $ts) + 543),
      'y' => (string)((date('Y', $ts) + 543) % 100),
      'd' => date('d', $ts),
      'm' => date('m', $ts),
    ];
    $out = $format;
    foreach ($repls as $k=>$v) $out = str_replace($k, $v, $out);
    return $out;
  }
}

// -------- AuthZ --------
if (!isset($_SESSION["user_id"])) { header("location: login.php"); exit; }
if (($_SESSION["role"] ?? '') === 'staff') { header('Location: borrowings.php'); exit; }
$current_page = 'dispensations';

// -------- ลบการเบิก (เฉพาะ admin) --------
if (
  isset($_GET['action'], $_GET['id']) &&
  $_GET['action'] === 'delete' &&
  isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'
) {
  $dispense_id = intval($_GET['id']);
  $sql = "DELETE FROM dispensations WHERE dispense_id = ?";
  if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $dispense_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("location: dispensations.php"); exit;
  }
}

// -------- ดึงข้อมูลการเบิกวัสดุ --------
$sql = "SELECT d.*, s.supply_name, u.full_name
        FROM dispensations d
        LEFT JOIN office_supplies s ON d.supply_id = s.supply_id
        LEFT JOIN users u ON d.user_id = u.user_id
        ORDER BY d.dispense_date DESC, d.dispense_id DESC";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>การเบิกวัสดุ - ระบบบันทึกคลังครุภัณฑ์</title>

  <!-- Vendor CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- ✅ Font Awesome 6.5.2 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">

  <!-- Project CSS -->
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('sidebar.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('common-ui.css')) ?>">
</head>
<body class="table-lock">

<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">การเบิกวัสดุ</span>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <?php
      $sidebarPath = __DIR__ . '/sidebar.php';
      if (!is_file($sidebarPath)) $sidebarPath = __DIR__ . '/../sidebar.php';
      if (is_file($sidebarPath)) include $sidebarPath;
      else echo '<div class="col-md-3 col-lg-2 d-none d-md-block"></div>';
    ?>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content mt-4 mt-md-5">

        <!-- Header + Search + Add -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
          <h2 class="mb-0"><i class="fa-solid fa-dolly me-2"></i>การเบิกวัสดุ</h2>
          <div class="d-flex align-items-center gap-2">
            <input type="text" id="dispenseSearch" class="form-control" style="max-width:350px;" placeholder="ค้นหารายการเบิกวัสดุ...">
            <a href="dispensation_form.php" class="btn btn-add"><i class="fa-solid fa-plus"></i> เพิ่มการเบิกวัสดุ</a>
          </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <!-- เลื่อนเฉพาะตาราง + thead sticky -->
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="sticky-top bg-white" style="z-index: 1020;">
                  <tr>
                    <th>ชื่อวัสดุ</th>
                    <th>ชื่อผู้เบิก</th>
                    <th>วันที่เบิก</th>
                    <th>จำนวนที่เบิก</th>
                    <th>หมายเหตุ</th>
                    <th>จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $row_count = 0;
                  while ($row = mysqli_fetch_assoc($result)):
                      $row_count++;
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['supply_name']); ?></td>
                    <td><?= htmlspecialchars($row['full_name']); ?></td>
                    <td><?= thaidate($row['dispense_date'], 'j M Y'); ?></td>
                    <td><?= (int)$row['quantity_dispensed']; ?></td>
                    <td><?= htmlspecialchars($row['notes']); ?></td>
                    <td>
                      <a href="dispensation_form.php?id=<?= (int)$row['dispense_id']; ?>" class="btn btn-sm btn-warning" title="แก้ไข">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </a>
                      <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                      <a href="dispensations.php?action=delete&id=<?= (int)$row['dispense_id']; ?>" class="btn btn-sm btn-danger"
                         onclick="return confirm('ยืนยันการลบ?');" title="ลบ">
                        <i class="fa-solid fa-trash"></i>
                      </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endwhile; ?>

                  <?php if($row_count == 0): ?>
                    <tr>
                      <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                          <i class="fa-solid fa-dolly fa-3x mb-3"></i>
                          <h5>ไม่พบข้อมูลการเบิกวัสดุ</h5>
                          <p class="mb-0">ยังไม่มีรายการเบิกวัสดุในระบบ</p>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div><!-- /.table-responsive -->
          </div><!-- /.card-body -->
        </div><!-- /.card -->

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ค้นหาแบบเรียลไทม์
document.getElementById('dispenseSearch').addEventListener('input', function() {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll('table tbody tr');
  let visibleCount = 0;

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const show = text.includes(filter);
    row.style.display = show ? '' : 'none';
    if (show) visibleCount++;
  });

  // แถว "ไม่พบผลลัพธ์"
  let noResultsRow = document.querySelector('.no-results-row');
  if (visibleCount === 0 && !noResultsRow) {
    const tbody = document.querySelector('table tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'no-results-row';
    newRow.innerHTML = `
      <td colspan="6" class="text-center py-4">
        <div class="text-muted">
          <i class="fa-solid fa-magnifying-glass fa-3x mb-3"></i>
          <h5>ไม่พบผลลัพธ์</h5>
          <p class="mb-0">ลองค้นหาด้วยคำอื่น</p>
        </div>
      </td>`;
    tbody.appendChild(newRow);
  } else if (visibleCount > 0 && noResultsRow) {
    noResultsRow.remove();
  }
});

// คลิกหัวตารางเพื่อเลื่อนกลับบนสุดของกรอบตาราง
document.querySelectorAll('table thead th').forEach(th => {
  th.style.cursor = 'pointer';
  th.title = 'คลิกเพื่อเลื่อนขึ้นด้านบน';
  th.addEventListener('click', function() {
    const container = document.querySelector('.table-responsive');
    container.scrollTo({ top: 0, behavior: 'smooth' });
  });
});
</script>
</body>
</html>
