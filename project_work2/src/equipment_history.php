<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ตรวจสอบสิทธิ์การเข้าถึง
if ($_SESSION["role"] === 'staff') {
    header('Location: borrowings.php');
    exit;
}

// ตัวแปรสำหรับการกรอง
$item_filter = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// สร้าง SQL query สำหรับดึงข้อมูลการเคลื่อนไหว
$sql = "SELECT m.*, i.item_number, i.model_name, i.brand,
        u1.full_name as from_user_name, u1.department as from_user_department,
        u2.full_name as to_user_name, u2.department as to_user_department,
        u3.full_name as created_by_name, u3.department as created_by_department
        FROM equipment_movements m
        LEFT JOIN items i ON m.item_id = i.item_id
        LEFT JOIN users u1 ON m.from_user_id = u1.user_id
        LEFT JOIN users u2 ON m.to_user_id = u2.user_id
        LEFT JOIN users u3 ON m.created_by = u3.user_id
        WHERE 1=1";

$params = [];

if ($item_filter > 0) {
    $sql .= " AND m.item_id = ?";
    $params[] = $item_filter;
}

if (!empty($type_filter)) {
    $sql .= " AND m.movement_type = ?";
    $params[] = $type_filter;
}

if (!empty($date_from)) {
    $sql .= " AND DATE(m.movement_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND DATE(m.movement_date) <= ?";
    $params[] = $date_to;
}

if ($user_filter > 0) {
    $sql .= " AND (m.from_user_id = ? OR m.to_user_id = ?)";
    $params[] = $user_filter;
    $params[] = $user_filter;
}

$sql .= " ORDER BY m.movement_date DESC";

// เตรียม statement
$stmt = mysqli_prepare($link, $sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params)); // ผูกเป็น string ได้ MySQL จะ cast ให้
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ดึงรายการสำหรับตัวกรอง (หากต้องใช้ในฟอร์ม)
$items_result = mysqli_query($link, "SELECT item_id, item_number, model_name, brand FROM items WHERE item_number IS NOT NULL AND item_number != '' ORDER BY item_number");
$users_result = mysqli_query($link, "SELECT user_id, full_name, department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY full_name");

function get_movement_type_text($type) {
    $types = [
        'borrow' => 'การยืม',
        'return' => 'การคืน',
        'transfer' => 'การโอนย้าย',
        'maintenance' => 'การซ่อมบำรุง',
        'disposal' => 'การจำหน่าย',
        'purchase' => 'การจัดซื้อ',
        'adjustment' => 'การปรับปรุง'
    ];
    return $types[$type] ?? $type;
}

function get_movement_type_badge($type) {
    $badges = [
        'borrow' => 'bg-primary',
        'return' => 'bg-success',
        'transfer' => 'bg-info',
        'maintenance' => 'bg-warning',
        'disposal' => 'bg-danger',
        'purchase' => 'bg-secondary',
        'adjustment' => 'bg-dark'
    ];
    $badge_class = $badges[$type] ?? 'bg-secondary';
    return '<span class="badge ' . $badge_class . '">' . get_movement_type_text($type) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ประวัติการเคลื่อนไหวของครุภัณฑ์</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="common-ui.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Mobile Navbar (Hamburger) -->
        <nav class="navbar navbar-light bg-light d-md-none mb-3">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar" aria-label="Toggle navigation" tabindex="0">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="navbar-brand mb-0 h1">ประวัติการเคลื่อนไหว</span>
            </div>
        </nav>

        <!-- Sidebar (Desktop Only) และ Offcanvas (Mobile) -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-0">
            <div class="main-content mt-4 mt-md-5">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <h2 class="mb-0"><i class="fas fa-history me-2"></i>ประวัติการเคลื่อนไหวของครุภัณฑ์</h2>
                    <div class="d-flex align-items-center">
                        <div class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> วันที่ปัจจุบัน: <?= thaidate('j M Y', date('Y-m-d')) ?>
                        </div>
                    </div>
                </div>

                <div class="filter-card p-4">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>ตัวกรองข้อมูล</h5>
                    <!-- ฟอร์มตัวกรองของคุณ (คงเดิม/เพิ่มเติมได้) -->
                    <!-- ... -->
                </div>

                <!-- ตาราง: ทำสไตล์เลื่อนเหมือน users.php -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <!-- เลื่อนเฉพาะตาราง -->
                        <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="sticky-top bg-white" style="z-index: 1020;">
                                  <tr>
                                    <th>วันที่</th>
                                    <th>ประเภท</th>
                                    <th>ครุภัณฑ์</th>
                                    <th>จำนวน</th>
                                    <th>จาก (ผู้ใช้/แผนก)</th>
                                    <th>ไปยัง (ผู้ใช้/แผนก)</th>
                                    <th>หมายเหตุ</th>
                                    <th>ผู้บันทึก (แผนก)</th>
                                  </tr>
                                </thead>
                                <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                  <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                      <td><?= thaidate('j M Y H:i', $row['movement_date']); ?></td>
                                      <td><?= get_movement_type_badge($row['movement_type']); ?></td>
                                      <td>
                                        <strong><?= htmlspecialchars($row['item_number']); ?></strong><br>
                                        <small class="text-muted">
                                          <?= htmlspecialchars(trim($row['model_name'] . ' - ' . $row['brand'], ' -')) ?>
                                        </small>
                                      </td>
                                      <td><?= (int)$row['quantity']; ?></td>
                                      <td>
                                        <?php if ($row['from_location']): ?>
                                          <i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($row['from_location']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($row['from_user_name']): ?>
                                          <i class="fas fa-user text-primary"></i> <strong><?= htmlspecialchars($row['from_user_name']); ?></strong>
                                          <?php if ($row['from_user_department']): ?>
                                            <br><small class="text-muted"><i class="fas fa-building"></i> <?= htmlspecialchars($row['from_user_department']); ?></small>
                                          <?php endif; ?>
                                        <?php endif; ?>
                                      </td>
                                      <td>
                                        <?php if ($row['to_location']): ?>
                                          <i class="fas fa-map-marker-alt text-success"></i> <?= htmlspecialchars($row['to_location']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($row['to_user_name']): ?>
                                          <i class="fas fa-user text-success"></i> <strong><?= htmlspecialchars($row['to_user_name']); ?></strong>
                                          <?php if ($row['to_user_department']): ?>
                                            <br><small class="text-muted"><i class="fas fa-building"></i> <?= htmlspecialchars($row['to_user_department']); ?></small>
                                          <?php endif; ?>
                                        <?php endif; ?>
                                      </td>
                                      <td><?= $row['notes'] ? '<span class="text-muted">' . htmlspecialchars($row['notes']) . '</span>' : '<span class="text-muted">-</span>'; ?></td>
                                      <td class="user-block">
                                        <strong><?= htmlspecialchars($row['created_by_name']); ?></strong>
                                        <?php if ($row['created_by_department']): ?>
                                          <br><small class="text-muted"><i class="fas fa-building"></i> <?= htmlspecialchars($row['created_by_department']); ?></small>
                                        <?php endif; ?>
                                      </td>
                                    </tr>
                                  <?php endwhile; ?>
                                <?php else: ?>
                                  <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                      <i class="fas fa-inbox fa-3x mb-3"></i><br>ไม่พบข้อมูลการเคลื่อนไหว
                                    </td>
                                  </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div><!-- /.table-responsive -->
                    </div><!-- /.card-body -->
                </div><!-- /.card -->
            </div><!-- /.main-content -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</div><!-- /.container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
  <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
  พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี | © 2025
</footer>
</body>
</html>
