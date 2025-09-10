<?php
// borrowing-system/src/dashboard.php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ฟังก์ชันช่วยตรวจสอบ query
function safe_query($link, $sql) {
    $result = mysqli_query($link, $sql);
    if (!$result) {
        die("SQL Error: " . mysqli_error($link) . " in query: " . $sql);
    }
    return $result;
}

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
if ($_SESSION["role"] === 'staff') {
    header('Location: borrowings.php');
    exit;
}

// -------------------- ดึงข้อมูลสถิติ --------------------
$stats = [];

/* จำนวนครุภัณฑ์ทั้งหมด (รวมทุกสถานะ) */
$sql = "SELECT IFNULL(SUM(total_quantity),0) AS total_items FROM items";
$result = safe_query($link, $sql);
$stats['total_items'] = (int)mysqli_fetch_assoc($result)['total_items'];

/* จำนวนที่ถูกยืม (ยังไม่คืน) */
$sql = "SELECT IFNULL(SUM(quantity_borrowed),0) AS borrowed
        FROM borrowings
        WHERE status IN ('borrowed','return_pending')";
$result = safe_query($link, $sql);
$borrowed_qty = (int)mysqli_fetch_assoc($result)['borrowed'];

/* พูลที่พร้อมให้ยืม */
$sql = "SELECT IFNULL(SUM(i.total_quantity),0) AS pool_available
        FROM items i
        WHERE i.is_disposed = 0
          AND NOT EXISTS (
                SELECT 1 FROM repairs r
                WHERE r.item_id = i.item_id
                  AND r.status NOT IN ('completed','cancelled')
          )
          AND NOT EXISTS (
                SELECT 1 FROM equipment_movements em
                WHERE em.item_id = i.item_id
                  AND em.movement_type IN ('maintenance','disposal')
          )";
$result = safe_query($link, $sql);
$pool_available = (int)mysqli_fetch_assoc($result)['pool_available'];

/* พร้อมให้ยืมจริง */
$stats['available_items'] = max(0, $pool_available - $borrowed_qty);

/* จำนวนชิ้นที่กำลังยืม */
$sql = "SELECT IFNULL(SUM(quantity_borrowed),0) AS active_borrowed_items
        FROM borrowings
        WHERE status IN ('borrowed','return_pending')";
$result = safe_query($link, $sql);
$stats['active_borrowed_items'] = (int)mysqli_fetch_assoc($result)['active_borrowed_items'];

/* จำนวนการยืมที่เกินกำหนด */
$sql = "SELECT COUNT(*) AS overdue_borrowings
        FROM borrowings
        WHERE status IN ('borrowed','return_pending')
          AND due_date < CURDATE()";
$result = safe_query($link, $sql);
$stats['overdue_borrowings'] = (int)mysqli_fetch_assoc($result)['overdue_borrowings'];

/* การ์ดแจ้งซ่อม */
$sql = "SELECT COUNT(DISTINCT item_id) AS repair_items
        FROM repairs
        WHERE status NOT IN ('completed','cancelled')";
$result = safe_query($link, $sql);
$stats['repair_items'] = (int)mysqli_fetch_assoc($result)['repair_items'];

/* การ์ดจำหน่ายแล้ว */
$sql = "SELECT COUNT(*) AS disposed_items
        FROM items
        WHERE is_disposed = 1";
$result = safe_query($link, $sql);
$stats['disposed_items'] = (int)mysqli_fetch_assoc($result)['disposed_items'];

// -------------------- ข้อมูลหมวดหมู่/ยี่ห้อ --------------------
$categories = [];
$cat_result = safe_query($link, "SELECT * FROM categories ORDER BY category_name");
while ($row = mysqli_fetch_assoc($cat_result)) { $categories[] = $row; }

$brands = [];
$brand_result = safe_query($link, "SELECT * FROM brands ORDER BY brand_name");
while ($row = mysqli_fetch_assoc($brand_result)) { $brands[] = $row; }

$category_counts = [];
$count_result = safe_query($link, "SELECT category_id, COUNT(*) AS total FROM items GROUP BY category_id");
while ($row = mysqli_fetch_assoc($count_result)) { $category_counts[$row['category_id']] = $row['total']; }

$category_totals = [];
$total_result = safe_query($link, "SELECT category_id, IFNULL(SUM(total_quantity),0) AS total FROM items GROUP BY category_id");
while ($row = mysqli_fetch_assoc($total_result)) { $category_totals[$row['category_id']] = $row['total']; }

$pending = 0;
if (in_array($_SESSION['role'], ['admin','procurement'])) {
    $pending_q = safe_query($link, "SELECT COUNT(*) AS cnt FROM borrowings WHERE status='pending'");
    $pending = (int)mysqli_fetch_assoc($pending_q)['cnt'];
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
  <style>
    @media (min-width: 992px) {
      .stats-card { padding: 12px 14px; }
      .stats-card .stats-icon { width: 42px; height: 42px; }
      .stats-card h3 { font-size: 1.35rem; }
      .stats-card p  { font-size: .95rem; }
    }
  </style>
</head>
<body>
  <!-- Navbar (Mobile Only) -->
  <nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
    <div class="container-fluid px-2">
      <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <span class="fw-bold">แดชบอร์ด</span>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row">
      <?php include 'sidebar.php'; ?>
      <div class="col-md-9 col-lg-10 px-0">
        <div class="main-content mt-4 mt-md-5">

          <?php if ($pending > 0): ?>
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="font-size:1.1em;">
              <i class="fas fa-bell fa-lg me-2"></i>
              <div>
                มีคำขอการยืมรออนุมัติ <b><?= $pending ?></b> รายการ กรุณาตรวจสอบที่หน้า
                <a href="borrowings.php" class="alert-link">การยืม-คืน</a>
              </div>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h2>สวัสดี, <?= htmlspecialchars($_SESSION["full_name"]); ?>!</h2>
              <p class="text-muted">ยินดีต้อนรับสู่ระบบบันทึกคลังครุภัณฑ์</p>
            </div>
          </div>

          <!-- การ์ดสถิติ -->
          <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 mb-4">
            <div class="col"><div class="stats-card h-100"><div class="d-flex align-items-center"><div class="stats-icon bg-primary-gradient me-3"><i class="fas fa-boxes"></i></div><div><h3 class="mb-0"><?= $stats['total_items']; ?></h3><p class="text-muted mb-0">ครุภัณฑ์ทั้งหมด</p></div></div></div></div>
            <div class="col"><div class="stats-card h-100"><div class="d-flex align-items-center"><div class="stats-icon bg-success-gradient me-3"><i class="fas fa-check-circle"></i></div><div><h3 class="mb-0"><?= $stats['available_items']; ?></h3><p class="text-muted mb-0">พร้อมให้ยืม</p></div></div></div></div>
            <div class="col"><div class="stats-card h-100"><div class="d-flex align-items-center"><div class="stats-icon bg-warning-gradient me-3"><i class="fas fa-clock"></i></div><div><h3 class="mb-0"><?= $stats['active_borrowed_items']; ?></h3><p class="text-muted mb-0">กำลังยืม</p></div></div></div></div>
            <div class="col"><div class="stats-card h-100"><div class="d-flex align-items-center"><div class="stats-icon bg-info-gradient me-3"><i class="fas fa-exclamation-triangle"></i></div><div><h3 class="mb-0"><?= $stats['overdue_borrowings']; ?></h3><p class="text-muted mb-0">เกินกำหนด</p></div></div></div></div>
            <div class="col"><div class="stats-card h-100"><div class="d-flex align-items-center"><div class="stats-icon bg-danger-gradient me-3"><i class="fas fa-screwdriver-wrench"></i></div><div><h3 class="mb-0"><?= $stats['repair_items']; ?></h3><p class="text-muted mb-0">แจ้งซ่อม</p></div></div></div></div>
            <div class="col"><div class="stats-card h-100"><div class="d-flex align-items-center"><div class="stats-icon bg-secondary-gradient me-3"><i class="fas fa-trash"></i></div><div><h3 class="mb-0"><?= $stats['disposed_items']; ?></h3><p class="text-muted mb-0">จำหน่ายแล้ว</p></div></div></div></div>
          </div>

          <h5 class="mb-3"><i class="fas fa-layer-group me-2"></i>หมวดหมู่ครุภัณฑ์</h5>
          <div class="row mb-4 justify-content-center justify-content-md-start">
            <?php 
            $gradientClasses = ['bg-primary-gradient','bg-success-gradient','bg-warning-gradient','bg-info-gradient','bg-danger-gradient','bg-secondary-gradient','bg-dark-gradient','bg-light-gradient'];
            $gradientIndex = 0;
            foreach ($categories as $cat):
            ?>
            <div class="col-sm-6 col-md-3 mb-3 d-flex justify-content-center">
              <div class="stats-card category-card h-100" style="cursor:pointer;"
                   data-category-id="<?= $cat['category_id']; ?>"
                   data-category-name="<?= htmlspecialchars($cat['category_name']); ?>">
                <div class="d-flex align-items-center">
                  <div class="stats-icon <?= $gradientClasses[$gradientIndex % count($gradientClasses)]; ?> me-3">
                    <i class="fas fa-folder-open"></i>
                  </div>
                  <div>
                    <h5 class="mb-0">
                      <?= htmlspecialchars($cat['category_name']); ?>
                      <span class="badge bg-secondary ms-1">
                        <?= isset($category_totals[$cat['category_id']]) ? $category_totals[$cat['category_id']] : 0; ?>
                      </span>
                    </h5>
                  </div>
                </div>
              </div>
            </div>
            <?php $gradientIndex++; endforeach; ?>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header">
      <h5 class="modal-title" id="categoryModalLabel">รายการครุภัณฑ์</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div><div class="modal-body">
      <div class="mb-3">
        <label for="brandSelect" class="form-label">เลือกยี่ห้อ</label>
        <select class="form-select" id="brandSelect">
          <option value="">-- แสดงทุกยี่ห้อ --</option>
          <?php foreach ($brands as $b): ?>
            <option value="<?= htmlspecialchars($b['brand_name']); ?>"><?= htmlspecialchars($b['brand_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="category-items-loading" class="text-center my-4" style="display:none;">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
      <div id="category-items-list"></div>
    </div></div></div>
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
