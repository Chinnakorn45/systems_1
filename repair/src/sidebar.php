<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        <span style="font-size:1.1em;">พัฒนาโดย <b>นายชินกร ทองสอาด</b> และ</span><br>
        <span style="font-size:1.1em;"><b>นางสาวซากีหนะต์ ปรังเจะ</b></span><br>
        <span style="font-size:0.9em;">© 2025</span>
    </div>
</div>