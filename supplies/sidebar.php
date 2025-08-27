<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['role'] ?? 'staff';
$full_name = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'ผู้ใช้');
$current = $current_page ?? basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php');

function is_active($names, $current) {
  $arr = (array)$names;
  return in_array($current, $arr, true) ? 'active' : '';
}
?>

<!-- ========== Mobile Offcanvas Sidebar ========== -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasSidebarLabel">
      <i class="fa-solid fa-layer-group me-2"></i> เมนูระบบ
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="list-group list-group-flush sidebar-menu-mobile">
      <a class="list-group-item list-group-item-action <?= is_active(['dashboard','index'], $current) ?>" href="dashboard.php">
        <i class="fa-solid fa-gauge-high me-2"></i> แดชบอร์ด
      </a>

      <div class="list-group-item small text-muted fw-semibold">อุปกรณ์สำนักงาน</div>
      <a class="list-group-item list-group-item-action <?= is_active('supplies', $current) ?>" href="supplies.php">
        <i class="fa-solid fa-paperclip me-2"></i> วัสดุสำนักงาน
      </a>
      <?php if ($role === 'admin'): ?>
      <a class="list-group-item list-group-item-action <?= is_active('supply_form', $current) ?>" href="supply_form.php">
        <i class="fa-solid fa-circle-plus me-2"></i> เพิ่มวัสดุ
      </a>
      <?php endif; ?>
      <a class="list-group-item list-group-item-action <?= is_active('borrowings', $current) ?>" href="dispensations.php">
        <i class="fa-solid fa-hand-holding me-2"></i> การเบิกวัสดุสำนักงาน
      </a>

      <!-- ✅ คงไว้เฉพาะ "รายงาน" -->
      <?php if ($role === 'admin'): ?>
      <div class="list-group-item small text-muted fw-semibold">รายงาน</div>
      <a class="list-group-item list-group-item-action <?= is_active('reports', $current) ?>" href="reports.php">
        <i class="fa-solid fa-chart-line me-2"></i> รายงาน
      </a>
      <?php endif; ?>

      <a class="list-group-item list-group-item-action" href="logout.php">
        <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> ออกจากระบบ
      </a>
    </nav>
  </div>
</div>

<!-- ========== Desktop Sidebar ========== -->
<div class="col-md-3 col-lg-2 px-0 d-none d-md-block">
  <aside class="app-sidebar h-100 border-end">
    <div class="p-3 border-bottom">
      <div class="fw-semibold"><?= htmlspecialchars($full_name) ?></div>
      <div class="small text-muted"><?= htmlspecialchars($role) ?></div>
    </div>

    <nav class="nav flex-column sidebar-menu">
      <a class="nav-link <?= is_active(['dashboard','index'], $current) ?>" href="dashboard.php">
        <i class="fa-solid fa-gauge-high me-2"></i> แดชบอร์ด
      </a>

      <div class="sidebar-label">อุปกรณ์สำนักงาน</div>
      <a class="nav-link <?= is_active('supplies', $current) ?>" href="supplies.php">
        <i class="fa-solid fa-paperclip me-2"></i> วัสดุสำนักงาน
      </a>
      <?php if ($role === 'admin'): ?>
      <a class="nav-link <?= is_active('supply_form', $current) ?>" href="supply_form.php">
        <i class="fa-solid fa-circle-plus me-2"></i> เพิ่มวัสดุ
      </a>
      <?php endif; ?>
      <a class="nav-link <?= is_active('borrowings', $current) ?>" href="dispensations.php">
        <i class="fa-solid fa-hand-holding me-2"></i> การเบิกวัสดุสำนักงาน
      </a>
      <!-- ✅ คงไว้เฉพาะ "รายงาน" -->
      <?php if ($role === 'admin'): ?>
      <div class="sidebar-label mt-2">รายงาน</div>
      <a class="nav-link <?= is_active('reports', $current) ?>" href="reports.php">
        <i class="fa-solid fa-chart-line me-2"></i> รายงาน
      </a>
      <?php endif; ?>

      <hr class="my-2">
      <a class="nav-link" href="logout.php">
        <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> ออกจากระบบ
      </a>
    </nav>
  </aside>
</div>
