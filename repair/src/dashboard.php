<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'procurement', 'staff'])) {
    header('Location: login.php'); exit;
}

// จำนวนแจ้งซ่อม - แก้ไขให้ดึงสถานะล่าสุดจาก repair_logs
$total_repairs = $conn->query("SELECT COUNT(*) FROM repairs")->fetch_row()[0];

// ดึงสถานะล่าสุดจาก repair_logs
$pending_repairs = $conn->query("
    SELECT COUNT(*) FROM repairs r 
    LEFT JOIN (
        SELECT repair_id, status 
        FROM repair_logs 
        WHERE (repair_id, updated_at) IN (
            SELECT repair_id, MAX(updated_at) 
            FROM repair_logs 
            GROUP BY repair_id
        )
    ) latest ON r.repair_id = latest.repair_id
    WHERE COALESCE(latest.status, r.status) IN ('pending', '')
")->fetch_row()[0];

$inprogress_repairs = $conn->query("
    SELECT COUNT(*) FROM repairs r 
    LEFT JOIN (
        SELECT repair_id, status 
        FROM repair_logs 
        WHERE (repair_id, updated_at) IN (
            SELECT repair_id, MAX(updated_at) 
            FROM repair_logs 
            GROUP BY repair_id
        )
    ) latest ON r.repair_id = latest.repair_id
    WHERE COALESCE(latest.status, r.status) IN ('received', 'evaluate_it', 'evaluate_repairable', 'evaluate_external', 'evaluate_disposal', 'external_repair', 'procurement_managing', 'procurement_returned', 'waiting_delivery', 'in_progress')
")->fetch_row()[0];

$done_repairs = $conn->query("
    SELECT COUNT(*) FROM repairs r 
    LEFT JOIN (
        SELECT repair_id, status 
        FROM repair_logs 
        WHERE (repair_id, updated_at) IN (
            SELECT repair_id, MAX(updated_at) 
            FROM repair_logs 
            GROUP BY repair_id
        )
    ) latest ON r.repair_id = latest.repair_id
    WHERE COALESCE(latest.status, r.status) IN ('done', 'delivered', 'repair_completed')
")->fetch_row()[0];

$cancelled_repairs = $conn->query("
    SELECT COUNT(*) FROM repairs r 
    LEFT JOIN (
        SELECT repair_id, status 
        FROM repair_logs 
        WHERE (repair_id, updated_at) IN (
            SELECT repair_id, MAX(updated_at) 
            FROM repair_logs 
            GROUP BY repair_id
        )
    ) latest ON r.repair_id = latest.repair_id
    WHERE COALESCE(latest.status, r.status) = 'cancelled'
")->fetch_row()[0];

$done_percent = $total_repairs > 0 ? round(($done_repairs / $total_repairs) * 100, 1) : 0;

// จำนวนผู้ใช้งาน
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$admin_users = $conn->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetch_row()[0];
$procurement_users = $conn->query("SELECT COUNT(*) FROM users WHERE role='procurement'")->fetch_row()[0];
$staff_users = $conn->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetch_row()[0];

// แจ้งซ่อมล่าสุด 5 รายการ - แก้ไขให้ดึงสถานะล่าสุดจาก repair_logs
$latest_repairs = [];
$sql = "SELECT r.repair_id, r.created_at, r.asset_number, r.model_name, r.location_name, u_reported.full_name AS reporter, u_reported.department,
        COALESCE(latest.status, r.status) as current_status
        FROM repairs r
        LEFT JOIN users u_reported ON r.reported_by = u_reported.user_id
        LEFT JOIN (
            SELECT repair_id, status 
            FROM repair_logs 
            WHERE (repair_id, updated_at) IN (
                SELECT repair_id, MAX(updated_at) 
                FROM repair_logs 
                GROUP BY repair_id
            )
        ) latest ON r.repair_id = latest.repair_id
        ORDER BY r.created_at DESC LIMIT 5";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $latest_repairs[] = [
        'repair_id' => $row['repair_id'],
        'ref_no' => 'REQ' . $row['repair_id'],
        'item_name' => $row['model_name'] ?: '-',
        'reporter' => $row['reporter'] ?: '-',
        'department' => $row['department'] ?: '-',
        'status' => $row['current_status'],
        'created_at' => $row['created_at'],
    ];
}

// แสดงเฉพาะรายการแจ้งซ่อมใหม่ที่ยังไม่ดำเนินการ - แก้ไขให้ดึงสถานะล่าสุดจาก repair_logs
$alerts = [];
$sql_pending = "SELECT r.repair_id, r.model_name, r.asset_number, r.location_name, r.created_at, u.full_name AS reporter, u.department
FROM repairs r
LEFT JOIN users u ON r.reported_by = u.user_id
LEFT JOIN (
    SELECT repair_id, status 
    FROM repair_logs 
    WHERE (repair_id, updated_at) IN (
        SELECT repair_id, MAX(updated_at) 
        FROM repair_logs 
        GROUP BY repair_id
    )
) latest ON r.repair_id = latest.repair_id
WHERE COALESCE(latest.status, r.status) IN ('pending', '')
ORDER BY r.created_at DESC LIMIT 5";
$res_pending = $conn->query($sql_pending);
while ($row = $res_pending->fetch_assoc()) {
    $alerts[] = [
        'item_name' => $row['model_name'] ?: 'ไม่ระบุ',
        'desc' => ($row['asset_number'] ? $row['asset_number'] . ' - ' : '') . ($row['location_name'] ?: '-') . '<br>' .
            '<span class="text-muted">' . ($row['reporter'] ?: '-') . '</span><br>' .
            '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> แจ้งใหม่ ยังไม่ดำเนินการ</span>'
    ];
}

function status_badge($status) {
    $map = [
        'received' => ['รับเรื่อง', 'info'],
        'evaluate_it' => ['ประเมิน (โดย IT)', 'warning'],
        'evaluate_repairable' => ['ประเมิน: ซ่อมได้โดย IT', 'success'],
        'evaluate_external' => ['ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'evaluate_disposal' => ['ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย', 'dark'],
        'external_repair' => ['ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'procurement_managing' => ['พัสดุจัดการส่งซ่อม', 'info'],
        'procurement_returned' => ['พัสดุซ่อมเสร็จส่งคืน IT', 'success'],
        'repair_completed' => ['ซ่อมเสร็จ', 'success'],
        'waiting_delivery' => ['รอส่งมอบ', 'warning'],
        'delivered' => ['ส่งมอบ', 'success'],
        'cancelled' => ['ยกเลิก', 'secondary'],
        // สถานะเก่า (เพื่อความเข้ากันได้)
        'pending' => ['รอดำเนินการ', 'secondary'],
        'in_progress' => ['กำลังซ่อม', 'info'],
        'done' => ['ซ่อมเสร็จ', 'success'],
        // สถานะค่าว่าง
        '' => ['รอดำเนินการ', 'secondary'],
    ];
    if (!isset($map[$status])) return '<span class="badge bg-secondary">ไม่ระบุสถานะ</span>';
    [$th, $color] = $map[$status];
    return "<span class='badge bg-$color'>$th</span>";
}

function thaidate($date, $format = 'j F Y') {
    $ts = strtotime($date);
    $thai_months = [
        '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    $day = date('j', $ts);
    $month = (int)date('n', $ts);
    $year = date('Y', $ts) + 543;
    return "$day {$thai_months[$month]} $year";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
  <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h2>
  <div class="row g-4 dashboard-section">
    <div class="col-md-4">
      <div class="dashboard-card p-4 bg-white">
        <div class="d-flex align-items-center mb-2">
          <span class="icon text-warning"><i class="fas fa-tools"></i></span>
          <span class="ms-auto text-muted">การแจ้งซ่อมทั้งหมด</span>
        </div>
        <div class="number text-warning"><?= $total_repairs ?></div>
        <div class="small text-danger"><i class="fas fa-exclamation-circle"></i> <?= $pending_repairs ?> รอดำเนินการ</div>
        <div class="small text-info"><i class="fas fa-cog"></i> <?= $inprogress_repairs ?> กำลังซ่อม</div>
        <div class="small text-secondary"><i class="fas fa-times"></i> <?= $cancelled_repairs ?> ยกเลิก</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dashboard-card p-4 bg-white">
        <div class="d-flex align-items-center mb-2">
          <span class="icon text-success"><i class="fas fa-check"></i></span>
          <span class="ms-auto text-muted">การซ่อมเสร็จสิ้น</span>
        </div>
        <div class="number text-success"><?= $done_repairs ?></div>
        <div class="small text-success"><i class="fas fa-arrow-up"></i> <?= $done_percent ?>% อัตราเสร็จสิ้น</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dashboard-card p-4 bg-white">
        <div class="d-flex align-items-center mb-2">
          <span class="icon text-info"><i class="fas fa-users"></i></span>
          <span class="ms-auto text-muted">ผู้ใช้งานทั้งหมด</span>
        </div>
        <div class="number text-info"><?= $total_users ?></div>
        <div class="small text-primary"><i class="fas fa-user-shield"></i> <?= $admin_users ?> แอดมิน/ทีมซ่อม</div>
        <div class="small text-warning"><i class="fas fa-user-cog"></i> <?= $procurement_users ?> เจ้าหน้าที่พัสดุ</div>
        <div class="small text-info"><i class="fas fa-user"></i> <?= $staff_users ?> พนักงาน </div>
      </div>
    </div>
  </div>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="dashboard-card p-4 bg-white">
        <div class="mb-3 fw-bold"><i class="fas fa-list me-2"></i> การแจ้งซ่อมล่าสุด</div>
        <table class="table dashboard-table">
          <thead>
            <tr>
              <th>รหัส</th><th>ครุภัณฑ์</th><th>ผู้แจ้ง</th><th>แผนก</th><th>สถานะ</th><th>วันที่แจ้ง</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latest_repairs as $r): ?>
            <tr>
              <td><?= $r['repair_id'] ?></td>
              <td><?= $r['item_name'] ?></td>
              <td><?= $r['reporter'] ?></td>
              <td><?= $r['department'] ?></td>
              <td><?= status_badge($r['status']) ?></td>
              <td><?= thaidate($r['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="dashboard-card p-4 bg-white">
        <div class="mb-2 fw-bold text-warning"><i class="fas fa-exclamation-triangle me-2"></i> รายการแจ้งซ่อมใหม่ที่รอดำเนินการ</div>
        <?php foreach ($alerts as $a): ?>
        <div class="dashboard-alert">
          <div class="fw-bold"><?= $a['item_name'] ?></div>
          <div class="small text-muted"><?= $a['desc'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
