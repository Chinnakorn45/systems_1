<?php
// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) session_start();

// ---------- Load config (flexible path) ----------
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

// ---------- Helpers ----------
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
  function thaidate($date, $format = 'j M Y') {
    if (!$date) return '';
    $ts = is_numeric($date) ? (int)$date : strtotime($date);
    if (!$ts) return '';
    $months = ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    $monthsShort = ['', 'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $m = (int)date('n', $ts);
    $map = [
      'j' => date('j',$ts),'d'=>date('d',$ts),'m'=>date('m',$ts),
      'F' => $months[$m], 'M'=>$monthsShort[$m],
      'Y' => (string)(date('Y',$ts)+543), 'y'=>(string)((date('Y',$ts)+543)%100)
    ];
    $out = $format; foreach ($map as $k=>$v) $out = str_replace($k,$v,$out); return $out;
  }
}

// ---------- AuthZ ----------
if (!isset($_SESSION["user_id"])) { header("location: login.php"); exit; }
// if (($_SESSION["role"] ?? '') !== 'admin') { header('Location: index.php'); exit; }

$current_page = 'reports';

// ---------- Filter inputs ----------
$today = date('Y-m-d');
$default_from = date('Y-m-01');
$date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : $default_from;
$date_to   = isset($_GET['date_to'])   && $_GET['date_to']   !== '' ? $_GET['date_to']   : $today;
$filter_supply = isset($_GET['supply_id']) ? (int)$_GET['supply_id'] : 0;
$filter_user   = isset($_GET['user_id'])   ? (int)$_GET['user_id']   : 0;
$q             = isset($_GET['q']) ? trim($_GET['q']) : '';

// ---------- Supply / Users list ----------
$supplies = mysqli_query($link, "SELECT supply_id, supply_name FROM office_supplies ORDER BY supply_name");
$users    = mysqli_query($link, "SELECT user_id, COALESCE(full_name, username) AS name FROM users ORDER BY name");

// ---------- Build WHERE clause & params ----------
$where = " WHERE 1=1 ";
$params = []; $types = "";

if ($date_from !== '') { $where .= " AND d.dispense_date >= ? "; $types .= "s"; $params[] = $date_from; }
if ($date_to !== '')   { $where .= " AND d.dispense_date <= ? "; $types .= "s"; $params[] = $date_to; }
if ($filter_supply>0)  { $where .= " AND d.supply_id = ? ";      $types .= "i"; $params[] = $filter_supply; }
if ($filter_user>0)    { $where .= " AND d.user_id = ? ";        $types .= "i"; $params[] = $filter_user; }
if ($q !== '')         {
  $where .= " AND (s.supply_name LIKE CONCAT('%',?,'%') OR d.notes LIKE CONCAT('%',?,'%')) ";
  $types .= "ss"; $params[] = $q; $params[] = $q;
}

// ---------- Export CSV ----------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="dispensations_report_' . $date_from . '_to_' . $date_to . '.csv"');
  echo "\xEF\xBB\xBF";
  $sql = "SELECT d.dispense_date, s.supply_name, d.quantity_dispensed, COALESCE(u.full_name,u.username) AS fullname, d.notes
          FROM dispensations d
          LEFT JOIN office_supplies s ON s.supply_id = d.supply_id
          LEFT JOIN users u ON u.user_id = d.user_id
          $where
          ORDER BY d.dispense_date ASC, d.dispense_id ASC";
  $stmt = mysqli_prepare($link, $sql);
  if ($types !== "") mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $out = fopen('php://output', 'w');
  fputcsv($out, ['วันที่', 'วัสดุ', 'จำนวน', 'ผู้เบิก', 'หมายเหตุ']);
  while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($out, [
      thaidate($row['dispense_date'], 'j M Y'),
      $row['supply_name'],
      (int)$row['quantity_dispensed'],
      $row['fullname'],
      $row['notes']
    ]);
  }
  fclose($out);
  exit;
}

// ---------- Summary metrics ----------
$summary = ['txn'=>0,'qty'=>0,'sup'=>0];
$sql = "SELECT COUNT(*) AS txn, COALESCE(SUM(d.quantity_dispensed),0) AS qty, COUNT(DISTINCT d.supply_id) AS sup
        FROM dispensations d
        LEFT JOIN office_supplies s ON s.supply_id = d.supply_id
        $where";
$stmt = mysqli_prepare($link, $sql);
if ($types !== "") mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $summary = ['txn'=>(int)$row['txn'],'qty'=>(int)$row['qty'],'sup'=>(int)$row['sup']];

// ---------- Top supplies dataset ----------
$top_labels = []; $top_counts = [];
$sql = "SELECT s.supply_name, SUM(d.quantity_dispensed) AS total_qty
        FROM dispensations d
        JOIN office_supplies s ON s.supply_id = d.supply_id
        $where
        GROUP BY d.supply_id
        ORDER BY total_qty DESC
        LIMIT 10";
$stmt = mysqli_prepare($link, $sql);
if ($types !== "") mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $top_labels[] = $r['supply_name']; $top_counts[] = (int)$r['total_qty']; }

// ---------- Daily totals dataset ----------
$daily_labels = []; $daily_values = [];
$sql = "SELECT d.dispense_date AS dt, SUM(d.quantity_dispensed) AS total_qty
        FROM dispensations d
        LEFT JOIN office_supplies s ON s.supply_id = d.supply_id
        $where
        GROUP BY d.dispense_date
        ORDER BY d.dispense_date ASC";
$stmt = mysqli_prepare($link, $sql);
if ($types !== "") mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $daily_labels[] = thaidate($r['dt'], 'j M Y'); $daily_values[] = (int)$r['total_qty']; }

// ---------- Rows for table ----------
$rows = [];
$sql = "SELECT d.dispense_id, d.dispense_date, d.quantity_dispensed, d.notes,
               s.supply_name, COALESCE(u.full_name,u.username) AS fullname
        FROM dispensations d
        LEFT JOIN office_supplies s ON s.supply_id = d.supply_id
        LEFT JOIN users u ON u.user_id = d.user_id
        $where
        ORDER BY d.dispense_date DESC, d.dispense_id DESC
        LIMIT 1000";
$stmt = mysqli_prepare($link, $sql);
if ($types !== "") mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>รายงานการเบิกวัสดุ - ระบบบันทึกคลังครุภัณฑ์</title>

  <!-- Vendor CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6.5.2 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">

  <!-- Project CSS -->
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('sidebar.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('common-ui.css')) ?>">

  <style>
    .card-elev{ border-radius:14px; }
    .chart-wrap{ height: 320px; }
    @media (min-width: 992px){ .chart-wrap{ height: 360px; } }
    .filter-row .form-control, .filter-row .form-select { min-height: 40px; }
    .print-hide { display: block; }
    @media print {
      .print-hide { display: none !important; }
      .main-content { padding: 0 !important; }
      .card { box-shadow: none !important; }
    }
  </style>
</head>
<body>
<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top print-hide">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">รายงานการเบิกวัสดุ</span>
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

    <!-- Main -->
    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content mt-4 mt-md-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
          <h2 class="mb-0"><i class="fa-solid fa-file-lines me-2"></i>รายงานการเบิกวัสดุ</h2>
          <div class="print-hide d-flex gap-2">
            <a class="btn btn-outline-secondary" href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>">
              <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </a>
            <button class="btn btn-primary" onclick="window.print()">
              <i class="fa-solid fa-print me-1"></i> พิมพ์รายงาน
            </button>
          </div>
        </div>

        <!-- Filters -->
        <form class="card card-elev p-3 mb-3 print-hide" method="get" autocomplete="off">
          <div class="row g-2 filter-row">
            <div class="col-12 col-md-3">
              <label class="form-label mb-1">วันที่เริ่ม</label>
              <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label mb-1">ถึงวันที่</label>
              <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label mb-1">วัสดุ</label>
              <select name="supply_id" class="form-select">
                <option value="0">-- ทั้งหมด --</option>
                <?php mysqli_data_seek($supplies, 0); while($s = mysqli_fetch_assoc($supplies)): ?>
                  <option value="<?= (int)$s['supply_id'] ?>" <?= $filter_supply==(int)$s['supply_id']?'selected':'' ?>>
                    <?= htmlspecialchars($s['supply_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label mb-1">ผู้เบิก</label>
              <select name="user_id" class="form-select">
                <option value="0">-- ทั้งหมด --</option>
                <?php mysqli_data_seek($users, 0); while($u = mysqli_fetch_assoc($users)): ?>
                  <option value="<?= (int)$u['user_id'] ?>" <?= $filter_user==(int)$u['user_id']?'selected':'' ?>>
                    <?= htmlspecialchars($u['name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">คำค้น (ชื่อวัสดุ/หมายเหตุ)</label>
              <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="พิมพ์คำค้น...">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end gap-2">
              <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i> ค้นหา</button>
              <a class="btn btn-outline-secondary" href="reports.php"><i class="fa-solid fa-rotate-left me-1"></i> ล้างตัวกรอง</a>
              <!-- Presets -->
              <button type="button" class="btn btn-outline-dark ms-auto" onclick="presetToday()">วันนี้</button>
              <button type="button" class="btn btn-outline-dark" onclick="presetThisMonth()">เดือนนี้</button>
              <button type="button" class="btn btn-outline-dark" onclick="preset30()">30 วันล่าสุด</button>
            </div>
          </div>
        </form>

        <!-- Summary -->
        <div class="row g-3 g-lg-4 mb-3">
          <div class="col-12 col-sm-4">
            <div class="card card-elev p-3">
              <div class="small text-muted">จำนวนธุรกรรม</div>
              <div class="fs-3 fw-bold mt-1"><?= number_format($summary['txn']) ?></div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="card card-elev p-3">
              <div class="small text-muted">ยอดเบิกรวม (หน่วย)</div>
              <div class="fs-3 fw-bold mt-1"><?= number_format($summary['qty']) ?></div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="card card-elev p-3">
              <div class="small text-muted">จำนวนชนิดวัสดุที่ถูกเบิก</div>
              <div class="fs-3 fw-bold mt-1"><?= number_format($summary['sup']) ?></div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div class="row g-3 g-lg-4">
          <div class="col-lg-7">
            <div class="card card-elev p-3">
              <div class="fw-bold mb-2"><i class="fa-solid fa-chart-line me-2"></i>ยอดเบิกต่อวัน</div>
              <div class="chart-wrap"><canvas id="dailyChart"></canvas></div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card card-elev p-3">
              <div class="fw-bold mb-2"><i class="fa-solid fa-chart-simple me-2"></i>Top วัสดุที่เบิกบ่อย</div>
              <div class="chart-wrap"><canvas id="topChart"></canvas></div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="card card-elev p-3 mt-3">
          <div class="fw-bold mb-2"><i class="fa-solid fa-table-list me-2"></i>ผลการค้นหา (สูงสุด 1,000 แถว)</div>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="min-width:110px;">วันที่</th>
                  <th>วัสดุ</th>
                  <th class="text-end" style="width:120px;">จำนวน</th>
                  <th style="width:220px;">ผู้เบิก</th>
                  <th>หมายเหตุ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($rows)===0): ?>
                  <tr><td colspan="5" class="text-center text-muted py-3">ไม่พบข้อมูล</td></tr>
                <?php else: foreach ($rows as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars(thaidate($r['dispense_date'],'j M Y')) ?></td>
                    <td><?= htmlspecialchars($r['supply_name']) ?></td>
                    <td class="text-end"><?= number_format((int)$r['quantity_dispensed']) ?></td>
                    <td><?= htmlspecialchars($r['fullname']) ?></td>
                    <td><?= htmlspecialchars($r['notes']) ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /.main-content -->
    </div><!-- /.col main -->
  </div><!-- /.row -->
</div><!-- /.container-fluid -->

<!-- Vendor JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
// ---- Chart Data ----
const dailyLabels = <?= json_encode($daily_labels, JSON_UNESCAPED_UNICODE) ?>;
const dailyValues = <?= json_encode($daily_values, JSON_UNESCAPED_UNICODE) ?>;
const topLabels   = <?= json_encode($top_labels, JSON_UNESCAPED_UNICODE) ?>;
const topValues   = <?= json_encode($top_counts, JSON_UNESCAPED_UNICODE) ?>;

// Daily line chart
(() => {
  const el = document.getElementById('dailyChart');
  if (!el) return;
  new Chart(el.getContext('2d'), {
    type: 'line',
    data: {
      labels: dailyLabels,
      datasets: [{ label: 'ยอดเบิก', data: dailyValues, tension: .3, pointRadius: 3 }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } },
      scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } }
    }
  });
})();

// Top bar (horizontal)
(() => {
  const el = document.getElementById('topChart');
  if (!el) return;
  new Chart(el.getContext('2d'), {
    type: 'bar',
    data: { labels: topLabels, datasets: [{ label:'ยอดรวม', data: topValues, borderWidth:1, borderRadius:8, barThickness:22, maxBarThickness:28 }] },
    options: {
      indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } },
      scales:{ x:{ beginAtZero:true, ticks:{ precision:0 } }, y:{ grid:{ display:false } } }
    }
  });
})();

// Filter presets
function presetToday(){
  const d = new Date(); const iso = d.toISOString().slice(0,10);
  document.querySelector('input[name="date_from"]').value = iso;
  document.querySelector('input[name="date_to"]').value = iso;
}
function presetThisMonth(){
  const d = new Date(); const y = d.getFullYear(); const m = d.getMonth()+1;
  const first = new Date(y, m-1, 1).toISOString().slice(0,10);
  const today = new Date().toISOString().slice(0,10);
  document.querySelector('input[name="date_from"]').value = first;
  document.querySelector('input[name="date_to"]').value = today;
}
function preset30(){
  const d = new Date(); const to = d.toISOString().slice(0,10);
  d.setDate(d.getDate()-29); const from = d.toISOString().slice(0,10);
  document.querySelector('input[name="date_from"]').value = from;
  document.querySelector('input[name="date_to"]').value = to;
}
</script>
</body>
</html>
