<?php
require_once 'config.php';
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
// จำกัดสิทธิ์เฉพาะ admin
if ($_SESSION["role"] !== "admin") {
    if ($_SESSION["role"] === 'staff') {
        header('Location: borrowings.php');
        exit;
    }
    header("location: index.php"); // หรือจะแสดงข้อความว่าไม่มีสิทธิ์ก็ได้
    exit;
}

// ลบครุภัณฑ์ (ถ้ามีการส่ง action=delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $item_id = intval($_GET['id']);
    
    // ตรวจสอบว่าครุภัณฑ์มีการยืมอยู่หรือไม่
    $check_borrowings = "SELECT COUNT(*) as count FROM borrowings WHERE item_id = ? AND status IN ('borrowed', 'pending', 'overdue')";
    $stmt_check = mysqli_prepare($link, $check_borrowings);
    mysqli_stmt_bind_param($stmt_check, "i", $item_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $borrowings_count = mysqli_fetch_assoc($result_check)['count'];
    mysqli_stmt_close($stmt_check);
    
    // ตรวจสอบว่าครุภัณฑ์มีการเคลื่อนไหวล่าสุดหรือไม่ (เช่น ส่งซ่อม)
    $check_movements = "SELECT COUNT(*) as count FROM equipment_movements WHERE item_id = ? AND movement_type IN ('maintenance', 'disposal') ORDER BY movement_date DESC LIMIT 1";
    $stmt_movements = mysqli_prepare($link, $check_movements);
    mysqli_stmt_bind_param($stmt_movements, "i", $item_id);
    mysqli_stmt_execute($stmt_movements);
    $result_movements = mysqli_stmt_get_result($stmt_movements);
    $movements_count = mysqli_fetch_assoc($result_movements)['count'];
    mysqli_stmt_close($stmt_movements);
    
    // ตรวจสอบว่าครุภัณฑ์มีการส่งซ่อมอยู่หรือไม่ (ตาราง repairs)
    $check_repairs = "SELECT COUNT(*) as count FROM repairs WHERE item_id = ? AND status NOT IN ('completed', 'cancelled')";
    $stmt_repairs = mysqli_prepare($link, $check_repairs);
    mysqli_stmt_bind_param($stmt_repairs, "i", $item_id);
    mysqli_stmt_execute($stmt_repairs);
    $result_repairs = mysqli_stmt_get_result($stmt_repairs);
    $repairs_count = mysqli_fetch_assoc($result_repairs)['count'];
    mysqli_stmt_close($stmt_repairs);
    
    if ($borrowings_count > 0) {
        // มีการยืมอยู่ ไม่สามารถลบได้
        $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการยืมอยู่";
        header("location: items.php");
        exit;
    }
    
    if ($movements_count > 0) {
        // มีการเคลื่อนไหวล่าสุด (เช่น ส่งซ่อม) ไม่สามารถลบได้
        $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการส่งซ่อมหรือเคลื่อนไหวอยู่";
        header("location: items.php");
        exit;
    }
    
    if ($repairs_count > 0) {
        // มีการส่งซ่อมอยู่ ไม่สามารถลบได้
        $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการส่งซ่อมอยู่";
        header("location: items.php");
        exit;
    }
    
    // ถ้าไม่มีปัญหาใดๆ ให้ลบได้
    $sql = "DELETE FROM items WHERE item_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "ลบครุภัณฑ์เรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบครุภัณฑ์";
        }
        mysqli_stmt_close($stmt);
        header("location: items.php");
        exit;
    }
}

// ดึงข้อมูลครุภัณฑ์
// ฟังก์ชันค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
if ($search !== '') {
    $search_esc = mysqli_real_escape_string($link, $search);
    $where = "WHERE i.item_number LIKE '%$search_esc%' OR i.serial_number LIKE '%$search_esc%' OR i.model_name LIKE '%$search_esc%' OR c.category_name LIKE '%$search_esc%' OR i.note LIKE '%$search_esc%' OR i.location LIKE '%$search_esc%'";
}
$sql = "SELECT i.*, c.category_name,
        CASE 
            WHEN EXISTS (SELECT 1 FROM borrowings b WHERE b.item_id = i.item_id AND b.status IN ('borrowed', 'pending', 'overdue')) THEN 'borrowed'
            WHEN EXISTS (SELECT 1 FROM repairs r WHERE r.item_id = i.item_id AND r.status NOT IN ('completed', 'cancelled')) THEN 'repair'
            WHEN EXISTS (SELECT 1 FROM equipment_movements em WHERE em.item_id = i.item_id AND em.movement_type IN ('maintenance', 'disposal') ORDER BY em.movement_date DESC LIMIT 1) THEN 'maintenance'
            ELSE 'available'
        END as current_status
        FROM items i 
        LEFT JOIN categories c ON i.category_id = c.category_id $where";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการครุภัณฑ์ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Thai (Prompt & Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="common-ui.css">
</head>
<body>
<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
<div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
    <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">จัดการครุภัณฑ์</span>
    <!-- ลบ user dropdown ออก -->
</div>
</nav>
<!-- Offcanvas Mobile Menu -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
<div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileMenuLabel"><i class="fas fa-boxes me-2"></i>เมนู</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>
<div class="offcanvas-body p-0">
    <nav class="nav flex-column">
    <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo ' active'; ?>" href="index.php">
        <i class="fas fa-tachometer-alt me-2"></i> แดชบอร์ด
    </a>
    <a class="nav-link text-danger<?php if(basename($_SERVER['PHP_SELF'])=='http://192.168.14.10/repair/src/login.php') echo ' active'; ?>" href="http://192.168.14.10/repair/src/login.php">
        <i class="fas fa-tools me-2"></i> แจ้งซ่อมครุภัณฑ์
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
    <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='user_guide.php') echo ' active'; ?>" href="user_guide.php">
        <i class="fas fa-book-open me-2"></i> คู่มือการใช้งาน
    </a>
    </nav>
</div>
</div>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (Desktop Only) และ Offcanvas (Mobile) -->
        <?php include 'sidebar.php'; ?>
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-0">
            <div class="main-content mt-4 mt-md-5">
                <!-- User Dropdown (Desktop Only) -->
                <!-- ลบ user dropdown (desktop) ออก -->
                
                <!-- แสดงข้อความแจ้งเตือน -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <div class="d-flex align-items-center mb-4">
                    <h2 class="me-auto"><i class="fas fa-box"></i> จัดการครุภัณฑ์</h2>
                    <div class="d-flex justify-content-end flex-nowrap" style="gap: 6px;">
                        <a href="item_form.php" class="btn btn-add"><i class="fas fa-plus"></i> เพิ่มครุภัณฑ์</a>
                        <a href="categories.php" class="btn btn-secondary"><i class="fas fa-list"></i> จัดการหมวดหมู่</a>
                        <a href="brands.php" class="btn btn-secondary"><i class="fas fa-trademark"></i> จัดการยี่ห้อ/ชื่อรุ่น</a>
                    </div>
                </div>
                <input type="text" id="itemSearch" class="form-control mb-3" style="max-width:350px;" placeholder="ค้นหารายการครุภัณฑ์...">
                <div class="card shadow-sm">
                    <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>เลขครุภัณฑ์</th>
                                <th>Serial Number</th>
                                <th>รุ่นครุภัณฑ์</th>
                                <th>หมวดหมู่</th>
                                <th>จำนวน</th>
                                <th>ราคาต่อหน่วย</th>
                                <th>ราคารวม</th>
                                <th>รูป</th>
                                <th>หมายเหตุ</th>
                                <th>ตำแหน่ง</th>
                                <th>วันที่จัดซื้อ</th>
                                <th>สถานะ</th>
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
                                <td><?php echo htmlspecialchars($row['item_number']); ?></td>
                                <td><?php echo htmlspecialchars(isset($row['serial_number']) ? $row['serial_number'] : ''); ?></td>
                                <td><?php echo htmlspecialchars($row['model_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo $row['total_quantity']; ?></td>
                                <td><?php echo isset($row['price_per_unit']) ? number_format($row['price_per_unit'], 2) : '-'; ?></td>
                                <td><?php echo isset($row['total_price']) ? number_format($row['total_price'], 2) : '-'; ?></td>
                                <td><?php if (!empty($row['image'])): ?><img src="<?php echo htmlspecialchars($row['image']); ?>" alt="รูป" style="max-width:60px;max-height:60px;object-fit:cover;"> <?php endif; ?></td>
                                <td><?php echo htmlspecialchars($row['note'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo $row['purchase_date'] ? thaidate('j M Y', $row['purchase_date']) : '-'; ?></td>
                                <td>
                                    <?php 
                                    $status_text = '';
                                    $status_class = '';
                                    switch($row['current_status']) {
                                        case 'borrowed':
                                            $status_text = 'กำลังยืม';
                                            $status_class = 'badge bg-warning text-dark';
                                            break;
                                        case 'repair':
                                            $status_text = 'ส่งซ่อม';
                                            $status_class = 'badge bg-danger';
                                            break;
                                        case 'maintenance':
                                            $status_text = 'บำรุงรักษา';
                                            $status_class = 'badge bg-info';
                                            break;
                                        case 'available':
                                            $status_text = 'พร้อมใช้งาน';
                                            $status_class = 'badge bg-success';
                                            break;
                                        default:
                                            $status_text = 'ไม่ทราบสถานะ';
                                            $status_class = 'badge bg-secondary';
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td class="d-flex gap-1 align-items-center">
                                    <a href="item_form2.php?id=<?php echo $row['item_id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <?php if ($row['current_status'] === 'available'): ?>
                                        <a href="items.php?action=delete&id=<?php echo $row['item_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบครุภัณฑ์นี้?');"><i class="fas fa-trash"></i></a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="ไม่สามารถลบครุภัณฑ์ที่มีการยืมหรือส่งซ่อมอยู่ได้"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if($row_count == 0): ?>
                                <tr>
                                    <td colspan="13" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-box fa-3x mb-3"></i>
                                            <h5>ไม่พบข้อมูลครุภัณฑ์</h5>
                                            <p class="mb-0">ยังไม่มีรายการครุภัณฑ์ในระบบ</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('itemSearch').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>
<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
    <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
    | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
    | © 2025
</footer>
</body>
</html>