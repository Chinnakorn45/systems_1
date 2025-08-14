<?php
// borrowing-system/src/dashboard.php

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

if ($_SESSION["role"] === 'staff') {
    header('Location: borrowings.php');
    exit;
}

// ดึงข้อมูลสถิติ
$stats = [];

// จำนวนครุภัณฑ์ทั้งหมด
$sql = "SELECT SUM(total_quantity) as total_items FROM items";
$result = mysqli_query($link, $sql);
$stats['total_items'] = (int)mysqli_fetch_assoc($result)['total_items'];

// จำนวนที่ถูกยืม (ยังไม่คืน)
$sql = "SELECT IFNULL(SUM(quantity_borrowed),0) as borrowed FROM borrowings WHERE status IN ('borrowed', 'return_pending')";
$result = mysqli_query($link, $sql);
$borrowed = (int)mysqli_fetch_assoc($result)['borrowed'];

// จำนวนครุภัณฑ์ที่พร้อมให้ยืม
$stats['available_items'] = $stats['total_items'] - $borrowed;

// จำนวนการยืมที่ยังไม่คืน (จำนวนชิ้น)
$sql = "SELECT IFNULL(SUM(quantity_borrowed),0) as active_borrowed_items FROM borrowings WHERE status IN ('borrowed', 'return_pending')";
$result = mysqli_query($link, $sql);
$stats['active_borrowed_items'] = (int)mysqli_fetch_assoc($result)['active_borrowed_items'];

// จำนวนการยืมที่เกินกำหนด
$sql = "SELECT COUNT(*) as overdue_borrowings FROM borrowings WHERE status IN ('borrowed', 'return_pending') AND due_date < CURDATE()";
$result = mysqli_query($link, $sql);
$stats['overdue_borrowings'] = mysqli_fetch_assoc($result)['overdue_borrowings'];

// ดึงหมวดหมู่
$categories = [];
$cat_result = mysqli_query($link, "SELECT * FROM categories ORDER BY category_name");
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row;
}
// ดึงยี่ห้อทั้งหมด
$brands = [];
$brand_result = mysqli_query($link, "SELECT * FROM brands ORDER BY brand_name");
while ($row = mysqli_fetch_assoc($brand_result)) {
    $brands[] = $row;
}

// ดึงจำนวนครุภัณฑ์แต่ละหมวดหมู่
$category_counts = [];
$count_result = mysqli_query($link, "SELECT category_id, COUNT(*) as total FROM items GROUP BY category_id");
while ($row = mysqli_fetch_assoc($count_result)) {
    $category_counts[$row['category_id']] = $row['total'];
}

// ดึงผลรวม total_quantity ของแต่ละหมวดหมู่
$category_totals = [];
$total_result = mysqli_query($link, "SELECT category_id, SUM(total_quantity) as total FROM items GROUP BY category_id");
while ($row = mysqli_fetch_assoc($total_result)) {
    $category_totals[$row['category_id']] = $row['total'];
}

$pending = 0;
if (in_array($_SESSION['role'], ['admin','procurement'])) {
    $pending_q = mysqli_query($link, "SELECT COUNT(*) AS cnt FROM borrowings WHERE status='pending'");
    $pending = mysqli_fetch_assoc($pending_q)['cnt'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบันทึกคลังครุภัณฑ์ - แดชบอร์ด</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="common-ui.css">
</head>
<body>
    <!-- Navbar (Mobile Only) -->
    <nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
    <div class="container-fluid px-2">
        <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
        <span class="fw-bold">แดชบอร์ด</span>
        <!-- ลบ user dropdown ออก -->
    </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (Desktop Only) และ Offcanvas (Mobile) -->
            <?php include 'sidebar.php'; ?>
            <div class="col-md-9 col-lg-10 px-0">
                <div class="main-content mt-4 mt-md-5">
                    <?php if ($pending > 0): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="font-size:1.1em;">
                        <i class="fas fa-bell fa-lg me-2"></i>
                        <div>
                            มีคำขอการยืมรออนุมัติ <b><?= $pending ?></b> รายการ กรุณาตรวจสอบที่หน้า <a href="borrowings.php" class="alert-link">การยืม-คืน</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>สวัสดี, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h2>
                            <p class="text-muted">ยินดีต้อนรับสู่ระบบบันทึกคลังครุภัณฑ์</p>
                        </div>
                        <!-- ลบ user dropdown (desktop) ออก -->
                    </div>
                    
                    <div class="row mb-4 justify-content-center justify-content-md-start">
                        <div class="col-sm-6 col-md-3 mb-3 d-flex justify-content-center">
                            <div class="stats-card" style="cursor:pointer;">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary-gradient me-3">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['total_items']; ?></h3>
                                        <p class="text-muted mb-0">ครุภัณฑ์ทั้งหมด</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 mb-3 d-flex justify-content-center">
                            <div class="stats-card" style="cursor:pointer;">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success-gradient me-3">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['available_items']; ?></h3>
                                        <p class="text-muted mb-0">พร้อมให้ยืม</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 mb-3 d-flex justify-content-center">
                            <div class="stats-card" style="cursor:pointer;">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning-gradient me-3">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['active_borrowed_items']; ?></h3>
                                        <p class="text-muted mb-0">กำลังยืม</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 mb-3 d-flex justify-content-center">
                            <div class="stats-card" style="cursor:pointer;">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-info-gradient me-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['overdue_borrowings']; ?></h3>
                                        <p class="text-muted mb-0">เกินกำหนด</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3"><i class="fas fa-layer-group me-2"></i>หมวดหมู่ครุภัณฑ์</h5>
                    <div class="row mb-4 justify-content-center justify-content-md-start">
                        <?php 
                        $gradientClasses = ['bg-primary-gradient', 'bg-success-gradient', 'bg-warning-gradient', 'bg-info-gradient', 'bg-danger-gradient', 'bg-secondary-gradient', 'bg-dark-gradient', 'bg-light-gradient'];
                        $gradientIndex = 0;
                        foreach ($categories as $cat):
                        ?>
                        <div class="col-sm-6 col-md-3 mb-3 d-flex justify-content-center">
                            <div class="stats-card category-card h-100" style="cursor:pointer;" data-category-id="<?php echo $cat['category_id']; ?>" data-category-name="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon <?php echo $gradientClasses[$gradientIndex % count($gradientClasses)]; ?> me-3">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                            <span class="badge bg-secondary ms-1">
                                                <?php echo isset($category_totals[$cat['category_id']]) ? $category_totals[$cat['category_id']] : 0; ?>
                                            </span>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                        $gradientIndex++;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">รายการครุภัณฑ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="brandSelect" class="form-label">เลือกยี่ห้อ</label>
                        <select class="form-select" id="brandSelect">
                            <option value="">-- แสดงทุกยี่ห้อ --</option>
                            <?php foreach ($brands as $b): ?>
                            <option value="<?php echo htmlspecialchars($b['brand_name']); ?>"><?php echo htmlspecialchars($b['brand_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="category-items-loading" class="text-center my-4" style="display:none;">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                    <div id="category-items-list"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="statsInfoModal" tabindex="-1" aria-labelledby="statsInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="statsInfoModalLabel">รายละเอียด</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="statsInfoModalBody">
            <!-- เนื้อหาจะถูกเติมโดย JS -->
        </div>
        </div>
    </div>
    </div>
    
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentCategoryId = null;
    document.addEventListener('DOMContentLoaded', function() {
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('click', function() {
                const catId = this.getAttribute('data-category-id');
                const catName = this.getAttribute('data-category-name');
                currentCategoryId = catId;
                document.getElementById('categoryModalLabel').textContent = 'รายการครุภัณฑ์หมวด ' + catName;
                document.getElementById('brandSelect').selectedIndex = 0;
                loadItems(catId, '');
                modal.show();
            });
        });
        document.getElementById('brandSelect').addEventListener('change', function() {
            loadItems(currentCategoryId, this.value);
        });
        function loadItems(catId, brand) {
            document.getElementById('category-items-list').innerHTML = '';
            document.getElementById('category-items-loading').style.display = 'block';
            fetch('dashboard_items_by_category.php?category_id=' + catId + '&brand=' + encodeURIComponent(brand))
                .then(res => res.text())
                .then(html => {
                    document.getElementById('category-items-list').innerHTML = html;
                    document.getElementById('category-items-loading').style.display = 'none';
                });
        }
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