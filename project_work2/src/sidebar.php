<?php
// Sidebar and Offcanvas menu for inventory system
?>
<!-- Offcanvas (Mobile) -->
<div class="offcanvas offcanvas-start sidebar-offcanvas" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><i class="fas fa-boxes"></i> เมนู</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <nav class="nav flex-column">
            <?php if ($_SESSION["role"] === 'staff'): ?>
                <a class="nav-link text-primary" href="borrowings.php"><i class="fas fa-exchange-alt me-2"></i> การยืม-คืน</a>
                <a class="nav-link text-primary" href="user_guide.php"><i class="fas fa-book-open me-2"></i> คู่มือการใช้งาน</a>
                <a class="nav-link text-primary" href="profile.php"><i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์</a>
                <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            <?php elseif ($_SESSION["role"] === 'procurement'): ?>
                <a class="nav-link text-primary" href="index.php"><i class="fas fa-tachometer-alt me-2"></i> แดชบอร์ด</a>
                <a class="nav-link text-primary" href="borrowings.php"><i class="fas fa-exchange-alt me-2"></i> การยืม-คืน</a>
                <a class="nav-link text-primary" href="supplies.php"><i class="fas fa-paperclip me-2"></i> วัสดุสำนักงาน</a>
                <a class="nav-link text-primary" href="dispensations.php"><i class="fas fa-dolly me-2"></i> การเบิกวัสดุ</a>
                <a class="nav-link text-primary" href="user_guide.php"><i class="fas fa-book-open me-2"></i> คู่มือการใช้งาน</a>
                <a class="nav-link text-primary" href="profile.php"><i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์</a>
                <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            <?php else: ?>
                <a class="nav-link text-primary" href="index.php"><i class="fas fa-tachometer-alt me-2"></i> แดชบอร์ด</a>
                <a class="nav-link text-primary" href="borrowings.php"><i class="fas fa-exchange-alt me-2"></i> การยืม-คืน</a>
                <a class="nav-link text-primary" href="supplies.php"><i class="fas fa-paperclip me-2"></i> วัสดุสำนักงาน</a>
                <a class="nav-link text-primary" href="dispensations.php"><i class="fas fa-dolly me-2"></i> การเบิกวัสดุ</a>
                <a class="nav-link text-primary" href="equipment_history.php"><i class="fas fa-history me-2"></i> ประวัติการเคลื่อนไหว</a>
                <a class="nav-link text-primary" href="reports.php"><i class="fas fa-chart-bar me-2"></i> รายงาน</a>
                <a class="nav-link text-primary" href="items.php"><i class="fas fa-box me-2"></i> จัดการครุภัณฑ์</a>
                <a class="nav-link text-primary" href="users.php"><i class="fas fa-users me-2"></i> จัดการผู้ใช้</a>

                <?php if ($_SESSION["role"] == "admin"): ?>
                    <!-- NEW: เมนูตั้งค่าระบบ (เฉพาะแอดมิน) -->
                    <a class="nav-link text-primary" href="settings.php"><i class="fas fa-cog me-2"></i> ตั้งค่าระบบ</a>
                <?php endif; ?>

                <a class="nav-link text-primary" href="user_guide.php"><i class="fas fa-book-open me-2"></i> คู่มือการใช้งาน</a>
                <a class="nav-link text-primary" href="profile.php"><i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์</a>
                <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Sidebar (Desktop Only) -->
<div class="col-md-3 col-lg-2 px-0 d-none d-md-block">
    <div class="sidebar p-3">
        <div class="text-center mb-4">
            <img src="img/logo1.png" alt="โลโก้1" class="logo-img">
            <img src="img/logo2.png" alt="โลโก้2" class="logo-img">
            <h4 style="margin-top:10px;"><i class="fas fa-boxes"></i> ระบบบันทึกคลังครุภัณฑ์</h4>
            <small>ครุภัณฑ์และอุปกรณ์</small>
        </div>
        <nav class="nav flex-column">
            <?php if ($_SESSION["role"] === 'staff'): ?>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='borrowings.php') echo ' active'; ?>" href="borrowings.php">
                <i class="fas fa-exchange-alt me-2"></i> การยืม-คืน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='user_guide.php') echo ' active'; ?>" href="user_guide.php">
                <i class="fas fa-book-open me-2"></i> คู่มือการใช้งาน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='profile.php') echo ' active'; ?>" href="profile.php">
                <i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์
            </a>
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
            </a>
            <?php elseif ($_SESSION["role"] === 'procurement'): ?>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo ' active'; ?>" href="index.php">
                <i class="fas fa-tachometer-alt me-2"></i> แดชบอร์ด
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='borrowings.php') echo ' active'; ?>" href="borrowings.php">
                <i class="fas fa-exchange-alt me-2"></i> การยืม-คืน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='supplies.php') echo ' active'; ?>" href="supplies.php">
                <i class="fas fa-paperclip me-2"></i> วัสดุสำนักงาน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='dispensations.php') echo ' active'; ?>" href="dispensations.php">
                <i class="fas fa-dolly me-2"></i> การเบิกวัสดุ
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='user_guide.php') echo ' active'; ?>" href="user_guide.php">
                <i class="fas fa-book-open me-2"></i> คู่มือการใช้งาน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='profile.php') echo ' active'; ?>" href="profile.php">
                <i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์
            </a>
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
            </a>
            <?php else: ?>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo ' active'; ?>" href="index.php">
                <i class="fas fa-tachometer-alt me-2"></i> แดชบอร์ด
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='borrowings.php') echo ' active'; ?>" href="borrowings.php">
                <i class="fas fa-exchange-alt me-2"></i> การยืม-คืน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='supplies.php') echo ' active'; ?>" href="supplies.php">
                <i class="fas fa-paperclip me-2"></i> วัสดุสำนักงาน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='dispensations.php') echo ' active'; ?>" href="dispensations.php">
                <i class="fas fa-dolly me-2"></i> การเบิกวัสดุ
            </a>
            <?php if ($_SESSION["role"] == "admin"): ?>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='equipment_history.php') echo ' active'; ?>" href="equipment_history.php">
                <i class="fas fa-history me-2"></i> ประวัติการเคลื่อนไหว
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='reports.php') echo ' active'; ?>" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i> รายงาน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='items.php') echo ' active'; ?>" href="items.php">
                <i class="fas fa-box me-2"></i> จัดการครุภัณฑ์
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='users.php') echo ' active'; ?>" href="users.php">
                <i class="fas fa-users me-2"></i> จัดการผู้ใช้
            </a>
            <!-- NEW: เมนูตั้งค่าระบบ (เฉพาะแอดมิน) -->
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='settings.php') echo ' active'; ?>" href="settings.php">
                <i class="fas fa-cog me-2"></i> ตั้งค่าระบบ
            </a>
            <?php endif; ?>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='user_guide.php') echo ' active'; ?>" href="user_guide.php">
                <i class="fas fa-book-open me-2"></i> คู่มือการใช้งาน
            </a>
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='profile.php') echo ' active'; ?>" href="profile.php">
                <i class="fas fa-user-edit me-2"></i> แก้ไขโปรไฟล์
            </a>
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
            </a>
            <?php endif; ?>
        </nav>
    </div>
</div>
