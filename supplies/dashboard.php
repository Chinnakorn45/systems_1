<?php
// ---------------- Session ----------------
if (session_status() === PHP_SESSION_NONE) session_start();

// ---------------- Load config (ยืดหยุ่นพาธ) ----------------
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

// ---------------- Helpers ----------------
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
    $months = ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
               'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    $monthsShort = ['', 'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $mIndex = (int)date('n',$ts);
    $map = [
      'j' => date('j',$ts),
      'd' => date('d',$ts),
      'm' => date('m',$ts),
      'F' => $months[$mIndex],
      'M' => $monthsShort[$mIndex],
      'Y' => (string)(date('Y',$ts)+543),
      'y' => (string)((date('Y',$ts)+543)%100),
    ];
    $out = $format;
    foreach ($map as $k=>$v) $out = str_replace($k,$v,$out);
    return $out;
  }
}

// ---------------- AuthZ ----------------
if (!isset($_SESSION["user_id"])) { header("location: login.php"); exit; }
// ✅ อนุญาตเฉพาะผู้ใช้ที่เป็นแอดมินเท่านั้น
if (($_SESSION["role"] ?? '') !== 'admin') {
  header('Location: index.php'); // จะเปลี่ยนเป็นหน้าอื่นก็ได้ เช่น borrowings.php
  exit;
}

$current_page = 'dashboard';

// ---------------- Metrics queries ----------------
// จำนวนชนิดวัสดุทั้งหมด
$total_supplies = 0;
$res = mysqli_query($link, "SELECT COUNT(*) AS c FROM office_supplies");
if ($row = mysqli_fetch_assoc($res)) $total_supplies = (int)$row['c'];

// จำนวนเบิกทั้งหมด
$total_dispensations = 0;
$res = mysqli_query($link, "SELECT COUNT(*) AS c FROM dispensations");
if ($row = mysqli_fetch_assoc($res)) $total_dispensations = (int)$row['c'];

// วัสดุสต็อกต่ำและหมดสต็อก
$low_stock = 0; $out_stock = 0; $ok_stock = 0;
$res = mysqli_query($link, "
  SELECT
    SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) AS out_c,
    SUM(CASE WHEN current_stock > 0 AND min_stock_level > 0 AND current_stock <= min_stock_level THEN 1 ELSE 0 END) AS low_c,
    SUM(CASE WHEN (min_stock_level IS NULL OR min_stock_level = 0 AND current_stock > 0)
              OR (current_stock > min_stock_level) THEN 1 ELSE 0 END) AS ok_c
  FROM office_supplies
");
if ($row = mysqli_fetch_assoc($res)) {
  $out_stock = (int)$row['out_c'];
  $low_stock = (int)$row['low_c'];
  $ok_stock  = (int)$row['ok_c'];
}

// ปริมาณที่เบิกวันนี้ / เดือนนี้
$today = date('Y-m-d');
$firstOfMonth = date('Y-m-01');
$disp_today_qty = 0; $disp_month_qty = 0;

$res = mysqli_query($link, "SELECT COALESCE(SUM(quantity_dispensed),0) AS q
                            FROM dispensations
                            WHERE dispense_date = '{$today}'");
if ($row = mysqli_fetch_assoc($res)) $disp_today_qty = (int)$row['q'];

$res = mysqli_query($link, "SELECT COALESCE(SUM(quantity_dispensed),0) AS q
                            FROM dispensations
                            WHERE dispense_date BETWEEN '{$firstOfMonth}' AND '{$today}'");
if ($row = mysqli_fetch_assoc($res)) $disp_month_qty = (int)$row['q'];

// รายการเบิกล่าสุด 10 รายการ
$recent = [];
$res = mysqli_query($link, "
  SELECT d.dispense_id, d.dispense_date, d.quantity_dispensed, d.notes,
         s.supply_name, u.full_name
  FROM dispensations d
  LEFT JOIN office_supplies s ON s.supply_id = d.supply_id
  LEFT JOIN users u ON u.user_id = d.user_id
  ORDER BY d.dispense_date DESC, d.dispense_id DESC
  LIMIT 10
");
while ($row = mysqli_fetch_assoc($res)) {
  $recent[] = [
    'id' => (int)$row['dispense_id'],
    'date' => $row['dispense_date'],
    'qty' => (int)$row['quantity_dispensed'],
    'name' => $row['supply_name'] ?: '-',
    'user' => $row['full_name'] ?: '-',
    'notes' => $row['notes'] ?: '',
  ];
}

// Top 5 วัสดุที่ถูกเบิกบ่อย (รวมยอด)
$top_labels = []; $top_counts = [];
$res = mysqli_query($link, "
  SELECT s.supply_name, SUM(d.quantity_dispensed) AS total_qty
  FROM dispensations d
  JOIN office_supplies s ON s.supply_id = d.supply_id
  GROUP BY d.supply_id
  ORDER BY total_qty DESC
  LIMIT 5
");
while ($row = mysqli_fetch_assoc($res)) {
  $top_labels[] = $row['supply_name'];
  $top_counts[] = (int)$row['total_qty'];
}

// วัสดุที่สต็อกใกล้หมด (5 รายการ)
$almost_out = [];
$res = mysqli_query($link, "
  SELECT supply_id, supply_name, current_stock, min_stock_level
  FROM office_supplies
  ORDER BY (CASE WHEN min_stock_level > 0 THEN (current_stock - min_stock_level) ELSE current_stock END) ASC,
           current_stock ASC
  LIMIT 5
");
while ($row = mysqli_fetch_assoc($res)) {
  $almost_out[] = $row;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แดชบอร์ดวัสดุสำนักงาน - ระบบบันทึกคลังครุภัณฑ์</title>

  <!-- Vendor CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6.5.2 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">

  <!-- Project CSS -->
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('sidebar.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('common-ui.css')) ?>">

  <style>
    .stat-number{ font-size:2.0rem; font-weight:700; line-height:1; }
    .card-elev{ border-radius:14px; }
    .chart-wrap{ height: 300px; }
    @media (min-width: 992px){
      .chart-wrap{ height: 340px; }
    }
  </style>
</head>
<body>
<!-- Navbar (Mobile) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">แดชบอร์ดวัสดุสำนักงาน</span>
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
        <!-- Top stats -->
        <div class="row g-3 g-lg-4 mb-3">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-elev p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted">ชนิดวัสดุทั้งหมด</div>
                  <div class="stat-number mt-1"><?= number_format($total_supplies) ?></div>
                </div>
                <i class="fa-solid fa-warehouse fa-2x text-primary"></i>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-elev p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted">การเบิกทั้งหมด</div>
                  <div class="stat-number mt-1"><?= number_format($total_dispensations) ?></div>
                </div>
                <i class="fa-solid fa-cart-arrow-down fa-2x text-success"></i>
              </div>
              <div class="small text-muted mt-2">
                วันนี้: <span class="text-dark fw-semibold"><?= number_format($disp_today_qty) ?></span> |
                เดือนนี้: <span class="text-dark fw-semibold"><?= number_format($disp_month_qty) ?></span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-elev p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted">สต็อกใกล้หมด</div>
                  <div class="stat-number mt-1 text-warning"><?= number_format($low_stock) ?></div>
                </div>
                <i class="fa-solid fa-triangle-exclamation fa-2x text-warning"></i>
              </div>
              <div class="small text-muted mt-2">กำหนดจาก current ≤ min</div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-elev p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted">หมดสต็อก</div>
                  <div class="stat-number mt-1 text-danger"><?= number_format($out_stock) ?></div>
                </div>
                <i class="fa-solid fa-box-open fa-2x text-danger"></i>
              </div>
              <div class="small text-muted mt-2">current = 0 หรือติดลบ</div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div class="row g-3 g-lg-4">
          <div class="col-lg-7">
            <div class="card card-elev p-3">
              <div class="fw-bold mb-2"><i class="fa-solid fa-chart-simple me-2"></i>Top 5 วัสดุที่ถูกเบิกบ่อยสุด</div>
              <div class="chart-wrap">
                <canvas id="top5Chart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card card-elev p-3">
              <div class="fw-bold mb-2"><i class="fa-solid fa-chart-pie me-2"></i>สัดส่วนสถานะสต็อก (ชนิดวัสดุ)</div>
              <div class="chart-wrap">
                <canvas id="stockPie"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent + Low stock list -->
        <div class="row g-3 g-lg-4 mt-1">
          <div class="col-lg-8">
            <div class="card card-elev p-3">
              <div class="fw-bold mb-2"><i class="fa-solid fa-clock-rotate-left me-2"></i>การเบิกล่าสุด</div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>วันที่</th>
                      <th>วัสดุ</th>
                      <th class="text-end">จำนวน</th>
                      <th>ผู้เบิก</th>
                      <th>หมายเหตุ</th>
                      <th class="text-center">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($recent) === 0): ?>
                      <tr><td colspan="6" class="text-center text-muted py-3">ยังไม่มีรายการ</td></tr>
                    <?php else: foreach ($recent as $r): ?>
                      <tr>
                        <td><?= htmlspecialchars(thaidate($r['date'], 'j M Y')) ?></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td class="text-end"><?= number_format($r['qty']) ?></td>
                        <td><?= htmlspecialchars($r['user']) ?></td>
                        <td><?= htmlspecialchars($r['notes']) ?></td>
                        <td class="text-center">
                          <a href="dispensation_form.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-warning" title="แก้ไข">
                            <i class="fa-solid fa-pen-to-square"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card card-elev p-3">
              <div class="fw-bold mb-2"><i class="fa-solid fa-battery-quarter me-2"></i>วัสดุที่ใกล้หมด (Top 5)</div>
              <?php if (count($almost_out) === 0): ?>
                <div class="text-muted">ยังไม่มีรายการ</div>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($almost_out as $it): 
                    $badge = ($it['current_stock'] <= 0) ? 'danger' : (($it['min_stock_level']>0 && $it['current_stock'] <= $it['min_stock_level']) ? 'warning' : 'secondary');
                  ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($it['supply_name']) ?></div>
                      <div class="small text-muted">
                        คงเหลือ <?= (int)$it['current_stock'] ?>
                        <?php if ((int)$it['min_stock_level'] > 0): ?>
                          | ขั้นต่ำ <?= (int)$it['min_stock_level'] ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <span class="badge bg-<?= $badge ?> rounded-pill">
                      <?= ($badge==='danger'?'หมด':($badge==='warning'?'ใกล้หมด':'ปกติ')) ?>
                    </span>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
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
// ---- Charts ----
(() => {
  // Top 5 bar (แนวนอน)
  const topLabels = <?= json_encode($top_labels, JSON_UNESCAPED_UNICODE) ?>;
  const topData   = <?= json_encode($top_counts, JSON_UNESCAPED_UNICODE) ?>;
  const barEl = document.getElementById('top5Chart');
  if (barEl && topLabels.length) {
    new Chart(barEl.getContext('2d'), {
      type: 'bar',
      data: {
        labels: topLabels,
        datasets: [{
          label: 'ยอดเบิกรวม',
          data: topData,
          borderWidth: 1,
          borderRadius: 8,
          barThickness: 24,
          maxBarThickness: 28
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (ctx) => ` ${ctx.raw} ชิ้น` } }
        },
        scales: {
          x: { beginAtZero: true, ticks: { precision: 0 } },
          y: { grid:{display:false} }
        }
      }
    });
  }

  // Stock status pie
  const stockPie = document.getElementById('stockPie');
  if (stockPie) {
    new Chart(stockPie.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: ['ปกติ', 'ใกล้หมด', 'หมดสต็อก'],
        datasets: [{ data: [<?= (int)$ok_stock ?>, <?= (int)$low_stock ?>, <?= (int)$out_stock ?>] }]
      },
      options: {
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { callbacks: { label: (ctx) => ` ${ctx.raw} ชนิด` } }
        }
      }
    });
  }
})();
</script>
</body>
</html>
