<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- Mobile top bar (Offcanvas trigger) -->
<div class="mobile-topbar d-md-none" style="position:sticky;top:0;z-index:1100;background:#0066cc;color:#fff;display:flex;align-items:center;gap:12px;padding:10px 12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" style="border:none;box-shadow:none;color:#0066cc;">
        <i class="fas fa-bars"></i>
    </button>
    <div style="font-weight:bold;">ระบบแจ้งซ่อมครุภัณฑ์</div>
    <div style="margin-left:auto"></div>
</div>

<!-- Mobile Offcanvas Navigation -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel" style="width:82%;max-width:340px;">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileNavLabel">เมนู</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="list-group list-group-flush">
      <?php if ($role === 'admin'): ?>
        <li class="list-group-item"><a class="text-decoration-none" href="dashboard.php"><i class="fas fa-home me-2"></i> หน้าแรก</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="repairs_list.php"><i class="fas fa-list me-2"></i> รายการแจ้งซ่อม</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="my_repairs.php"><i class="fas fa-wrench me-2"></i> งานซ่อมของฉัน</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="repair_form.php"><i class="fas fa-plus-circle me-2"></i> แจ้งซ่อมใหม่</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="calendar.php"><i class="fas fa-calendar-alt me-2"></i> ปฏิทิน</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="reports.php"><i class="fas fa-chart-bar me-2"></i> รายงาน</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="settings.php"><i class="fas fa-cog me-2"></i> ตั้งค่าระบบ</a></li>
      <?php elseif ($role === 'procurement'): ?>
        <li class="list-group-item"><a class="text-decoration-none" href="repairs_list.php"><i class="fas fa-list me-2"></i> รายการแจ้งซ่อม</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="calendar.php"><i class="fas fa-calendar-alt me-2"></i> ปฏิทิน</a></li>
      <?php elseif ($role === 'staff'): ?>
        <li class="list-group-item"><a class="text-decoration-none" href="my_repairs.php"><i class="fas fa-wrench me-2"></i> งานซ่อมของฉัน</a></li>
        <li class="list-group-item"><a class="text-decoration-none" href="repair_form.php"><i class="fas fa-plus-circle me-2"></i> แจ้งซ่อมใหม่</a></li>
      <?php endif; ?>
      <li class="list-group-item"><a class="text-decoration-none" href="manual.php"><i class="fas fa-book me-2"></i> คู่มือ</a></li>
      <li class="list-group-item"><a class="text-decoration-none" href="profile.php"><i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์</a></li>
      <li class="list-group-item"><a class="text-decoration-none" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a></li>
    </ul>
  </div>
</div>
<script>
// Ensure Bootstrap JS is available for Offcanvas on pages that didn't include it
(function(){
  if (typeof window.bootstrap === 'undefined') {
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
    s.defer = true;
    document.head.appendChild(s);
  }
})();
</script>
<div class="sidebar">
    <div class="sidebar-logo">
        ระบบแจ้งซ่อมครุภัณฑ์
    </div>
    <ul class="sidebar-menu">
        <?php if ($role === 'admin'): ?>
            <li><a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a></li>
            <li><a href="repairs_list.php"><i class="fas fa-list me-2"></i> รายการแจ้งซ่อม</a></li>
            <li><a href="my_repairs.php"><i class="fas fa-wrench me-2"></i> งานซ่อมของฉัน</a></li>
            <li><a href="repair_form.php"><i class="fas fa-plus-circle me-2"></i> แจ้งซ่อมใหม่</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt me-2"></i> ปฏิทิน</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar me-2"></i> รายงาน</a></li>
            <li><a href="settings.php"><i class="fas fa-cog me-2"></i> ตั้งค่าระบบ</a></li>
        <?php elseif ($role === 'procurement'): ?>
            <li><a href="repairs_list.php"><i class="fas fa-list me-2"></i> รายการแจ้งซ่อม</a></li>
            <li><a href="calendar.php"><i class="fas fa-calendar-alt me-2"></i> ปฏิทิน</a></li>
        <?php elseif ($role === 'staff'): ?>
            <li><a href="my_repairs.php"><i class="fas fa-wrench me-2"></i> งานซ่อมของฉัน</a></li>
            <li><a href="repair_form.php"><i class="fas fa-plus-circle me-2"></i> แจ้งซ่อมใหม่</a></li>
        <?php endif; ?>
        <li><a href="manual.php"><i class="fas fa-book me-2"></i> คู่มือ</a></li>
        <li><a href="profile.php"><i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a></li>
    </ul>
    <div style="margin-top:auto; padding: 18px 0 10px 0; text-align:center; font-size:0.55em; color:#e0e6ef; opacity:0.85;">
        <span style="font-size:1.5em;">พัฒนาโดย <b>นายชินกร ทองสอาด</b> และ</span><br>
        <span style="font-size:1.5em;"><b>นางสาวซากีหนะต์ ปรังเจะ</b></span><br>
        <span style="font-size:1.1em;">© 2025</span>
    </div>
</div>
