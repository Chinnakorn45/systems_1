<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
if ($_SESSION["role"] === 'staff') {
    header('Location: borrowings.php');
    exit;
}

/* ===== Charset/Collation (กัน mix collation) ===== */
mysqli_set_charset($link, 'utf8mb4');
mysqli_query($link, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($link, "SET collation_connection = 'utf8mb4_unicode_ci'");

/* ===== Helpers ===== */
if (!function_exists('safe_number')) {
  function safe_number($v, $dec = 0) {
      if (!is_numeric($v)) $v = 0;
      return number_format((float)$v, $dec);
  }
}
if (!function_exists('get_movement_type_text')) {
  function get_movement_type_text($type){
    $m = [
      'borrow'=>'การยืม','return'=>'การคืน','transfer'=>'การโอนย้าย',
      'maintenance'=>'การซ่อมบำรุง','disposal'=>'การจำหน่าย',
      'purchase'=>'การจัดซื้อ','adjustment'=>'การปรับปรุง',
      'ownership_transfer'=>'โอนสิทธิ'
    ];
    return $m[$type] ?? $type;
  }
}
if (!function_exists('get_movement_type_badge')) {
  function get_movement_type_badge($type){
    $b=['borrow'=>'bg-primary','return'=>'bg-success','transfer'=>'bg-info',
        'maintenance'=>'bg-warning','disposal'=>'bg-danger',
        'purchase'=>'bg-secondary','adjustment'=>'bg-dark','ownership_transfer'=>'bg-secondary'];
    $cls=$b[$type] ?? 'bg-secondary';
    return '<span class="badge '.$cls.'">'.get_movement_type_text($type).'</span>';
  }
}

/* ===== สรุปสถานะครุภัณฑ์ ===== */
$equipment_stats = ['total_items'=>0,'total_quantity'=>0,'borrowed_quantity'=>0,'available_quantity'=>0];

$sql_stats = "SELECT COUNT(*) as total_items, COALESCE(SUM(total_quantity),0) as total_quantity FROM items";
if ($res = mysqli_query($link, $sql_stats)) {
    $equipment_stats = array_merge($equipment_stats, mysqli_fetch_assoc($res) ?: []);
}

$sql_borrowed = "SELECT COALESCE(SUM(quantity_borrowed),0) as borrowed_quantity 
                 FROM borrowings WHERE status IN ('borrowed', 'return_pending')";
if ($res = mysqli_query($link, $sql_borrowed)) {
    $row = mysqli_fetch_assoc($res) ?: ['borrowed_quantity'=>0];
    $equipment_stats['borrowed_quantity'] = (int)$row['borrowed_quantity'];
}
$equipment_stats['available_quantity'] = max(0, ((int)$equipment_stats['total_quantity']) - ((int)$equipment_stats['borrowed_quantity']));

/* ===== กราฟ: สถานะการยืม ===== */
$status_chart_data = [];
$sql_status_chart = "SELECT status, COUNT(*) as count FROM borrowings GROUP BY status";
if ($res = mysqli_query($link, $sql_status_chart)) {
    while ($row = mysqli_fetch_assoc($res)) $status_chart_data[] = $row;
}

/* ===== กราฟ: ยืมตามเดือน (12 เดือนล่าสุด) ===== */
$monthly_chart_data = [];
$sql_monthly_chart = "SELECT DATE_FORMAT(borrow_date, '%Y-%m') as month, COUNT(*) as borrow_count
                      FROM borrowings
                      WHERE borrow_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                      GROUP BY month ORDER BY month";
if ($res = mysqli_query($link, $sql_monthly_chart)) {
    while ($row = mysqli_fetch_assoc($res)) $monthly_chart_data[] = $row;
}

/* ===== กราฟ: จำนวนชิ้นตามปีงบประมาณ (แทนมูลค่า) ===== */
$budget_chart_data = [];
$sql_budget_chart = "SELECT budget_year, SUM(total_quantity) as total_quantity
                     FROM items
                     WHERE budget_year IS NOT NULL AND budget_year <> ''
                     GROUP BY budget_year
                     ORDER BY budget_year";
if ($res = mysqli_query($link, $sql_budget_chart)) {
    while ($row = mysqli_fetch_assoc($res)) $budget_chart_data[] = $row;
}

/* ===== ตาราง: การยืม-คืน 10 ล่าสุด ===== */
$sql_borrowings = "SELECT b.*, i.model_name, i.item_number, u.full_name
                   FROM borrowings b
                   LEFT JOIN items i ON b.item_id = i.item_id
                   LEFT JOIN users u ON b.user_id = u.user_id
                   ORDER BY b.borrow_date DESC LIMIT 10";
$result_borrowings = mysqli_query($link, $sql_borrowings);

/* =================================================================
   ตาราง: การเคลื่อนไหว 10 ล่าสุด (แบบหน้าตัวอย่าง)
   - รวม equipment_movements + equipment_history (โอนสิทธิ)
================================================================= */
$sub_movements = "
  SELECT
    m.movement_date,
    CAST(m.movement_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS movement_type,
    m.item_id,
    m.quantity,
    m.from_user_id,
    m.to_user_id,
    CONVERT(m.from_location USING utf8mb4) COLLATE utf8mb4_unicode_ci AS from_location,
    CONVERT(m.to_location   USING utf8mb4) COLLATE utf8mb4_unicode_ci AS to_location,
    CONVERT(m.notes        USING utf8mb4) COLLATE utf8mb4_unicode_ci AS notes,
    m.created_by,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_name_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_name_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_dept_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_dept_r
  FROM equipment_movements m
";

$sub_history = "
  SELECT
    h.change_date AS movement_date,
    CAST('ownership_transfer' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS movement_type,
    h.item_id,
    1 AS quantity,
    CASE WHEN h.old_value REGEXP '^[0-9]+$' THEN CAST(h.old_value AS UNSIGNED) ELSE NULL END AS from_user_id,
    CASE WHEN h.new_value REGEXP '^[0-9]+$' THEN CAST(h.new_value AS UNSIGNED) ELSE NULL END AS to_user_id,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_location,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_location,
    CONVERT(h.remarks USING utf8mb4) COLLATE utf8mb4_unicode_ci AS notes,
    h.changed_by AS created_by,
    CONVERT(COALESCE(uo_id.full_name, uo_name.full_name) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_name_r,
    CONVERT(COALESCE(un_id.full_name, un_name.full_name) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_name_r,
    CONVERT(COALESCE(uo_id.department, uo_name.department) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_dept_r,
    CONVERT(COALESCE(un_id.department, un_name.department) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_dept_r
  FROM equipment_history h
  LEFT JOIN users uo_id  ON (h.old_value REGEXP '^[0-9]+$' AND uo_id.user_id = CAST(h.old_value AS UNSIGNED))
  LEFT JOIN users un_id  ON (h.new_value REGEXP '^[0-9]+$' AND un_id.user_id = CAST(h.new_value AS UNSIGNED))
  LEFT JOIN users uo_name ON (NOT h.old_value REGEXP '^[0-9]+$' AND uo_name.full_name COLLATE utf8mb4_unicode_ci = h.old_value COLLATE utf8mb4_unicode_ci)
  LEFT JOIN users un_name ON (NOT h.new_value REGEXP '^[0-9]+$' AND un_name.full_name COLLATE utf8mb4_unicode_ci = h.new_value COLLATE utf8mb4_unicode_ci)
  WHERE h.action_type = 'transfer_ownership'
";

$base_union = "($sub_movements UNION ALL $sub_history) AS mv";

$sql_movements_latest = "
  SELECT
    mv.movement_date, mv.movement_type, mv.item_id, mv.quantity,
    mv.from_user_id, mv.to_user_id, mv.from_location, mv.to_location, mv.notes, mv.created_by,
    i.item_number, i.model_name, i.brand,
    COALESCE(uf.full_name, mv.from_user_name_r)  AS from_user_name,
    COALESCE(ut.full_name, mv.to_user_name_r)    AS to_user_name,
    COALESCE(uf.department, mv.from_user_dept_r) AS from_user_department,
    COALESCE(ut.department, mv.to_user_dept_r)   AS to_user_department,
    uc.full_name AS created_by_name,
    uc.department AS created_by_department
  FROM $base_union
  LEFT JOIN items i ON i.item_id = mv.item_id
  LEFT JOIN users uf ON uf.user_id = mv.from_user_id
  LEFT JOIN users ut ON ut.user_id = mv.to_user_id
  LEFT JOIN users uc ON uc.user_id = mv.created_by
  ORDER BY mv.movement_date DESC
  LIMIT 10
";
$result_movements = mysqli_query($link, $sql_movements_latest);

/* ===== ตาราง: มูลค่าครุภัณฑ์ตามหมวด ===== */
$sql_category_value = "SELECT c.category_name,
                              COALESCE(SUM(i.total_price),0) as category_value,
                              COUNT(i.item_id) as item_count
                       FROM categories c
                       LEFT JOIN items i ON c.category_id = i.category_id AND i.total_price > 0
                       GROUP BY c.category_id
                       ORDER BY category_value DESC";
$result_category_value = mysqli_query($link, $sql_category_value);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>รายงาน - ระบบบันทึกคลังครุภัณฑ์</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="sidebar.css">

<style>
    body{background:#f5f8f6;font-family:'Prompt','Kanit','Arial',sans-serif;color:#2b2b2b}
    .page-hero{
        background: radial-gradient(1200px 520px at 20% -20%, #e8f5e9 0%, #ffffff 55%),
                    linear-gradient(180deg,#ffffff 0%, #f5f8f6 100%);
        border-bottom:1px solid #e9ecef
    }
    .page-hero h2{color:#1b5e20;font-weight:700}
    .btn-primary{background:#2e7d32;border-color:#2e7d32}
    .btn-primary:hover{background:#256b29;border-color:#256b29}

    .stats-card{background:#fff;border:1px solid #e3eee5;border-radius:16px;padding:16px;box-shadow:0 6px 16px rgba(0,0,0,.04)}
    .stats-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff}
    .bg-primary-gradient{background:linear-gradient(135deg,#4dabf7,#1971c2)}
    .bg-success-gradient{background:linear-gradient(135deg,#51cf66,#2b8a3e)}
    .bg-warning-gradient{background:linear-gradient(135deg,#fcc419,#e67700)}
    .bg-info-gradient{background:linear-gradient(135deg,#66d9e8,#0c8599)}

    .section-title{color:#1b5e20}
    .card{border-radius:14px}
    .chart-container{position:relative}
    .badge-soft{background:#eef8ef;border:1px solid #d6edd9;color:#2e7d32;border-radius:999px;padding:.25rem .6rem;font-size:.85rem}
    .table thead th{background:#f1f6f2}
    .table tbody tr:hover{background:#fbfffc}
    .empty-state{padding:24px;border:1px dashed #cfe6d1;border-radius:12px;background:#f7fbf8;color:#5b7163}
    .modal-header{background:#2e7d32;color:#fff}
    .modal-header .btn-close{filter:invert(1)}
    footer{background:#ffffff;border-top:1px solid #e9ecef}
</style>
</head>
<body>

<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">รายงาน</span>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main -->
    <div class="col-md-9 col-lg-10 px-0">
      <!-- Hero -->
      <div class="page-hero px-3 px-md-4 py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i> รายงานและสรุปภาพรวม</h2>
          <div class="d-flex align-items-center gap-2">
            <span class="badge-soft"><i class="fa-regular fa-clock me-1"></i>
              อัปเดตล่าสุด: <?php echo thaidate('j M Y', 'now'); ?>
            </span>
            <button class="btn btn-primary" id="btnPrintReport"><i class="fas fa-print me-1"></i> พิมพ์รายงาน</button>
          </div>
        </div>
      </div>

      <div class="main-content px-3 px-md-4 my-4">
        <!-- Stats -->
        <div id="report-summary">
          <h5 class="section-title mb-3"><i class="fas fa-boxes me-2 text-success"></i> รายงานสถานะครุภัณฑ์</h5>
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
              <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-primary-gradient me-3"><i class="fas fa-boxes"></i></div>
                  <div>
                    <h3 class="mb-0"><?php echo safe_number($equipment_stats['total_quantity']); ?></h3>
                    <small class="text-muted">ครุภัณฑ์ทั้งหมด (ชิ้น)</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-success-gradient me-3"><i class="fas fa-check-circle"></i></div>
                  <div>
                    <h3 class="mb-0"><?php echo safe_number($equipment_stats['available_quantity']); ?></h3>
                    <small class="text-muted">จำนวนที่ว่าง</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-warning-gradient me-3"><i class="fas fa-clock"></i></div>
                  <div>
                    <h3 class="mb-0"><?php echo safe_number($equipment_stats['borrowed_quantity']); ?></h3>
                    <small class="text-muted">จำนวนที่ยืมอยู่</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-info-gradient me-3"><i class="fas fa-layer-group"></i></div>
                  <div>
                    <h3 class="mb-0"><?php echo safe_number($equipment_stats['total_items']); ?></h3>
                    <small class="text-muted">จำนวนรายการ (ชนิด)</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <h5 class="section-title mb-3"><i class="fas fa-chart-pie me-2 text-success"></i> กราฟและแผนภูมิ</h5>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="card shadow-sm h-100">
              <div class="card-header"><i class="fas fa-chart-pie me-2"></i> สถานะการยืม-คืน</div>
              <div class="card-body">
                <?php if (count($status_chart_data)): ?>
                  <div class="chart-container" style="height:260px"><canvas id="statusChart"></canvas></div>
                <?php else: ?>
                  <div class="empty-state text-center"><i class="fa-regular fa-circle-question me-2"></i>ยังไม่มีข้อมูลการยืม-คืน</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card shadow-sm h-100">
              <div class="card-header"><i class="fas fa-chart-line me-2"></i> การยืมตามเดือน (12 เดือน)</div>
              <div class="card-body">
                <?php if (count($monthly_chart_data)): ?>
                  <div class="chart-container" style="height:260px"><canvas id="monthlyChart"></canvas></div>
                <?php else: ?>
                  <div class="empty-state text-center"><i class="fa-regular fa-circle-question me-2"></i>ยังไม่มีข้อมูลในช่วงเวลา</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-12">
            <div class="card shadow-sm">
              <div class="card-header"><i class="fas fa-chart-bar me-2"></i> จำนวนครุภัณฑ์ตามปีงบประมาณ</div>
              <div class="card-body">
                <?php if (count($budget_chart_data)): ?>
                  <div class="chart-container" style="height:260px"><canvas id="budgetChart"></canvas></div>
                <?php else: ?>
                  <div class="empty-state text-center"><i class="fa-regular fa-circle-question me-2"></i>ยังไม่มีข้อมูลจำนวน</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Borrowings Table -->
        <div id="report-borrowings">
          <h5 class="section-title mb-3"><i class="fas fa-exchange-alt me-2 text-success"></i> รายงานการยืม-คืน (ล่าสุด)</h5>
          <div class="card shadow-sm mb-4">
            <div class="card-body">
              <?php if ($result_borrowings && mysqli_num_rows($result_borrowings) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle">
                    <thead>
                      <tr>
                        <th>วันที่ยืม</th>
                        <th>ผู้ยืม</th>
                        <th>ครุภัณฑ์</th>
                        <th>เลขครุภัณฑ์</th>
                        <th class="text-center">จำนวน</th>
                        <th>วันที่คืน</th>
                        <th>สถานะ</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php
                      $status_labels = [
                        'pending'         => '<span class="badge bg-warning">รออนุมัติ</span>',
                        'borrowed'        => '<span class="badge bg-primary">กำลังยืม</span>',
                        'return_pending'  => '<span class="badge bg-info">รอยืนยันคืน</span>',
                        'returned'        => '<span class="badge bg-success">คืนแล้ว</span>',
                        'cancelled'       => '<span class="badge bg-secondary">ยกเลิก</span>',
                        'overdue'         => '<span class="badge bg-danger">เกินกำหนด</span>'
                      ];
                      while ($row = mysqli_fetch_assoc($result_borrowings)):
                    ?>
                      <tr>
                        <td><?php echo $row['borrow_date'] ? thaidate('j M Y', $row['borrow_date']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['model_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['item_number'] ?? '-'); ?></td>
                        <td class="text-center"><?php echo safe_number($row['quantity_borrowed']); ?></td>
                        <td><?php echo !empty($row['return_date']) ? thaidate('j M Y', $row['return_date']) : '-'; ?></td>
                        <td><?php echo $status_labels[$row['status']] ?? htmlspecialchars($row['status'] ?? '-'); ?></td>
                      </tr>
                    <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="empty-state text-center"><i class="fa-regular fa-file-lines me-2"></i>ยังไม่มีประวัติการยืม-คืน</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Movements Table -->
        <div id="report-movements">
          <h5 class="section-title mb-3"><i class="fas fa-route me-2 text-success"></i> รายงานการเคลื่อนไหว (ล่าสุด)</h5>
          <div class="card shadow-sm mb-4">
            <div class="card-body p-0">
              <?php if ($result_movements && mysqli_num_rows($result_movements) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                      <tr>
                        <th>วันที่</th>
                        <th>ประเภท</th>
                        <th>ครุภัณฑ์</th>
                        <th class="text-center">จำนวน</th>
                        <th>จาก (ผู้ใช้/แผนก/สถานที่)</th>
                        <th>ไปยัง (ผู้ใช้/แผนก/สถานที่)</th>
                        <th>หมายเหตุ</th>
                        <th>ผู้บันทึก (แผนก)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = mysqli_fetch_assoc($result_movements)): ?>
                      <tr>
                        <td><?php echo $row['movement_date'] ? thaidate('j M Y H:i', $row['movement_date']) : '-'; ?></td>
                        <td><?php echo get_movement_type_badge($row['movement_type']); ?></td>
                        <td>
                          <strong><?php echo htmlspecialchars($row['item_number'] ?? ''); ?></strong><br>
                          <small class="text-muted"><?php echo htmlspecialchars(trim(($row['model_name'] ?? '').' - '.($row['brand'] ?? ''), ' -')); ?></small>
                        </td>
                        <td class="text-center"><?php echo (int)($row['quantity'] ?? 0); ?></td>
                        <td>
                          <?php if (!empty($row['from_location'])): ?>
                            <i class="fas fa-map-marker-alt text-danger"></i> <?php echo htmlspecialchars($row['from_location']); ?><br>
                          <?php endif; ?>
                          <?php if (!empty($row['from_user_name'])): ?>
                            <i class="fas fa-user text-primary"></i> <strong><?php echo htmlspecialchars($row['from_user_name']); ?></strong>
                            <?php if (!empty($row['from_user_department'])): ?>
                              <br><small class="text-muted"><i class="fas fa-building"></i> <?php echo htmlspecialchars($row['from_user_department']); ?></small>
                            <?php endif; ?>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!empty($row['to_location'])): ?>
                            <i class="fas fa-map-marker-alt text-success"></i> <?php echo htmlspecialchars($row['to_location']); ?><br>
                          <?php endif; ?>
                          <?php if (!empty($row['to_user_name'])): ?>
                            <i class="fas fa-user text-success"></i> <strong><?php echo htmlspecialchars($row['to_user_name']); ?></strong>
                            <?php if (!empty($row['to_user_department'])): ?>
                              <br><small class="text-muted"><i class="fas fa-building"></i> <?php echo htmlspecialchars($row['to_user_department']); ?></small>
                            <?php endif; ?>
                          <?php endif; ?>
                        </td>
                        <td><?php echo $row['notes'] ? '<span class="text-muted">'.htmlspecialchars($row['notes']).'</span>' : '<span class="text-muted">-</span>'; ?></td>
                        <td>
                          <strong><?php echo htmlspecialchars($row['created_by_name'] ?? ''); ?></strong>
                          <?php if (!empty($row['created_by_department'])): ?>
                            <br><small class="text-muted"><i class="fas fa-building"></i> <?php echo htmlspecialchars($row['created_by_department']); ?></small>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="empty-state text-center"><i class="fa-regular fa-file-lines me-2"></i>ยังไม่มีประวัติการเคลื่อนไหว</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Value Table -->
        <div id="report-value">
          <h5 class="section-title mb-3"><i class="fas fa-money-bill-wave me-2 text-success"></i> รายงานมูลค่าครุภัณฑ์</h5>
          <div class="card shadow-sm mb-4">
            <div class="card-body">
              <?php if ($result_category_value && mysqli_num_rows($result_category_value) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle">
                    <thead>
                      <tr>
                        <th>หมวดหมู่</th>
                        <th class="text-center">จำนวนครุภัณฑ์</th>
                        <th class="text-end">มูลค่ารวม (บาท)</th>
                        <th class="text-end">มูลค่าเฉลี่ย (บาท)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = mysqli_fetch_assoc($result_category_value)):
                        $count = (int)($row['item_count'] ?? 0);
                        $value = (float)($row['category_value'] ?? 0);
                        $avg = $count > 0 ? $value / $count : 0;
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                        <td class="text-center"><?php echo safe_number($count); ?></td>
                        <td class="text-end"><?php echo safe_number($value, 2); ?></td>
                        <td class="text-end"><?php echo safe_number($avg, 2); ?></td>
                      </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="empty-state text-center"><i class="fa-regular fa-circle-question me-2"></i>ยังไม่มีข้อมูลมูลค่าครุภัณฑ์</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Print Modal -->
      <div class="modal fade" id="printReportModal" tabindex="-1" aria-labelledby="printReportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="printReportModalLabel"><i class="fas fa-print me-2"></i> เลือกรายงานที่ต้องการพิมพ์</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="printSection" id="printSummary" value="summary" checked>
                <label class="form-check-label" for="printSummary">สรุปสถานะครุภัณฑ์</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="printSection" id="printBorrowings" value="borrowings">
                <label class="form-check-label" for="printBorrowings">รายงานการยืม-คืน</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="printSection" id="printMovements" value="movements">
                <label class="form-check-label" for="printMovements">รายงานการเคลื่อนไหว</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="printSection" id="printValue" value="value">
                <label class="form-check-label" for="printValue">รายงานมูลค่าครุภัณฑ์</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
              <button type="button" class="btn btn-primary" id="printSelectedReport">พิมพ์</button>
            </div>
          </div>
        </div>
      </div>

      <footer class="py-3 text-center">
        <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
        พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
        | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
        | © 2025
      </footer>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  // เปิด Modal พิมพ์
  document.getElementById('btnPrintReport')?.addEventListener('click', function(){
    new bootstrap.Modal(document.getElementById('printReportModal')).show();
  });
  // เลือกและเปิดหน้าพิมพ์
  document.getElementById('printSelectedReport')?.addEventListener('click', function(){
    const sel = document.querySelector('input[name="printSection"]:checked')?.value || 'summary';
    let url = 'print/report_summary.php';
    if (sel==='borrowings') url='print/report_borrowings.php';
    else if (sel==='movements') url='print/report_movements.php';
    else if (sel==='value') url='print/report_value.php';
    window.open(url, '_blank', 'width=1100,height=900');
    bootstrap.Modal.getInstance(document.getElementById('printReportModal'))?.hide();
  });

  // กราฟ: สถานะการยืม-คืน
  const statusData = <?php echo json_encode($status_chart_data, JSON_UNESCAPED_UNICODE); ?>;
  if (statusData && statusData.length && document.getElementById('statusChart')) {
    const mapTH = {
      'pending':'รออนุมัติ',
      'borrowed':'กำลังยืม',
      'return_pending':'รอยืนยันคืน',
      'returned':'คืนแล้ว',
      'cancelled':'ยกเลิก',
      'overdue':'เกินกำหนด'
    };
    const labels = statusData.map(r => mapTH[r.status] ?? r.status);
    const data = statusData.map(r => Number(r.count)||0);
    new Chart(document.getElementById('statusChart'), {
      type:'doughnut',
      data:{ labels, datasets:[{ data, backgroundColor:['#1971c2','#2b8a3e','#0c8599','#2f9e44','#868e96','#d63939'] }] },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
    });
  }

  // กราฟรายเดือน (label MM/YY โดย YY = พ.ศ. สองหลัก)
  const monthlyData = <?php echo json_encode($monthly_chart_data, JSON_UNESCAPED_UNICODE); ?>;
  if (monthlyData && monthlyData.length && document.getElementById('monthlyChart')) {
    const labels = monthlyData.map(r=>{
      const [y,m] = (r.month||'').split('-');
      if (!y || !m) return r.month;
      const yy = String(Number(y) + 543).slice(-2);
      return `${m}/${yy}`;
    });
    const vals = monthlyData.map(r => Number(r.borrow_count)||0);
    new Chart(document.getElementById('monthlyChart'), {
      type:'line',
      data:{ labels, datasets:[{ label:'จำนวนการยืม', data:vals, borderColor:'#2b8a3e', backgroundColor:'rgba(43,138,62,.12)', tension:.15, fill:true }] },
      options:{ responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } } }
    });
  }

  // กราฟจำนวนชิ้นตามปีงบ (แทนมูลค่า) — ใช้ budgetData เพื่อลดโอกาสชนชื่อ/TDZ
  const budgetData = <?php echo json_encode($budget_chart_data, JSON_UNESCAPED_UNICODE); ?>;
  const budgetCanvas = document.getElementById('budgetChart');

  if (Array.isArray(budgetData) && budgetData.length && budgetCanvas) {
    const labels = budgetData.map(r => r.budget_year || '');
    const vals   = budgetData.map(r => Number(r.total_quantity) || 0);

    new Chart(budgetCanvas, {
      type:'bar',
      data:{
        labels,
        datasets:[{
          label:'จำนวนครุภัณฑ์ (ชิ้น)',
          data: vals,
          backgroundColor:'rgba(54,162,235,.85)',
          borderColor:'rgba(54,162,235,1)',
          borderWidth:1
        }]
      },
      options:{ responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } } }
    });
  }
})();
</script>
</body>
</html>
