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

/* การ์ดแจ้งซ่อม (ไม่นับรายการที่ส่งมอบแล้ว/ยกเลิก) */
$sql = "SELECT COUNT(DISTINCT item_id) AS repair_items
        FROM repairs
        WHERE status NOT IN ('delivered','cancelled')";
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
    /* Connected stats group for พร้อมให้ยืม / กำลังยืม / เกินกำหนด */
    .stats-connected {
      display: flex;
      gap: 0;
      width: 100%;
      overflow: hidden;
      background: transparent;
    }
    .stats-connected .sc-item { flex: 1 1 0; display: flex; }
    /* ใช้กรอบที่การ์ดด้านใน เพื่อให้ความสูงเท่ากับการ์ดเดี่ยว (ไม่มีกรอบซ้อน) */
    .stats-connected .stats-card {
      border: 1px solid #e9ecef !important;
      border-left: 0 !important;
      border-radius: 0 !important;
      margin: 0 !important;
      box-shadow: none !important;
      width: 100%;
    }
    .stats-connected .sc-item:first-child .stats-card {
      border-left: 1px solid #e9ecef !important;
      border-top-left-radius: 12px !important;
      border-bottom-left-radius: 12px !important;
    }
    .stats-connected .sc-item:last-child .stats-card {
      border-top-right-radius: 12px !important;
      border-bottom-right-radius: 12px !important;
    }
    /* ลบ margin ล่างของการ์ดในแถวสถิติเพื่อให้ความสูงเท่ากันเป๊ะ */
    .stats-row .stats-card { margin-bottom: 0 !important; }
    .stats-card.clickable { cursor: pointer; }

    @media (max-width: 767.98px) {
      .stats-connected { flex-direction: column; }
      .stats-connected .stats-card {
        border-left: 1px solid #e9ecef !important;
        border-right: 1px solid #e9ecef !important;
        border-top: 0 !important;
        border-bottom: 1px solid #e9ecef !important;
      }
      .stats-connected .sc-item:first-child .stats-card {
        border-top: 1px solid #e9ecef !important;
        border-top-left-radius: 8px !important;
        border-top-right-radius: 8px !important;
      }
      .stats-connected .sc-item:last-child .stats-card {
        border-bottom-left-radius: 8px !important;
        border-bottom-right-radius: 8px !important;
      }
    }

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
        <!-- Top-right user dropdown -->
        <div class="d-flex justify-content-end align-items-center pt-2 pe-3">
          <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle fa-lg me-2 text-secondary"></i>
              <span class="text-start">
                <strong>สวัสดี, <?= htmlspecialchars($_SESSION["full_name"]); ?>!</strong>
                <small class="text-muted d-block">ยินดีต้อนรับสู่ระบบบันทึกคลังครุภัณฑ์</small>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>โปรไฟล์</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
            </ul>
          </div>
        </div>

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

          

          <!-- การ์ดสถิติ (บรรทัดเดียวบน Desktop) -->
          <div class="row g-3 mb-4 align-items-stretch stats-row">
            <!-- ครุภัณฑ์ทั้งหมด -->
            <div class="col-6 col-md-4 col-lg-2 d-flex">
              <div class="stats-card w-100">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-primary-gradient me-3"><i class="fas fa-boxes"></i></div>
                  <div>
                    <h3 class="mb-0"><?= $stats['total_items']; ?></h3>
                    <p class="text-muted mb-0">ครุภัณฑ์ทั้งหมด</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- กลุ่มเชื่อม: พร้อมให้ยืม / กำลังยืม / เกินกำหนด -->
            <div class="col-12 col-lg-6 d-flex">
              <div class="stats-connected w-100">
                <div class="sc-item">
                  <div class="stats-card h-100">
                    <div class="d-flex align-items-center">
                      <div class="stats-icon bg-success-gradient me-3"><i class="fas fa-check-circle"></i></div>
                      <div>
                        <h3 class="mb-0"><?= $stats['available_items']; ?></h3>
                        <p class="text-muted mb-0">พร้อมให้ยืม</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="sc-item">
                  <div class="stats-card h-100 clickable js-open-list" data-list="borrowings" data-filter="active">
                    <div class="d-flex align-items-center">
                      <div class="stats-icon bg-warning-gradient me-3"><i class="fas fa-clock"></i></div>
                      <div>
                        <h3 class="mb-0"><?= $stats['active_borrowed_items']; ?></h3>
                        <p class="text-muted mb-0">กำลังยืม</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="sc-item">
                  <div class="stats-card h-100 clickable js-open-list" data-list="borrowings" data-filter="overdue">
                    <div class="d-flex align-items-center">
                      <div class="stats-icon bg-info-gradient me-3"><i class="fas fa-exclamation-triangle"></i></div>
                      <div>
                        <h3 class="mb-0"><?= $stats['overdue_borrowings']; ?></h3>
                        <p class="text-muted mb-0">เกินกำหนด</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- แจ้งซ่อม -->
            <div class="col-6 col-md-4 col-lg-2 d-flex">
              <div class="stats-card w-100 clickable js-open-list" data-list="repairs">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-danger-gradient me-3"><i class="fas fa-screwdriver-wrench"></i></div>
                  <div>
                    <h3 class="mb-0"><?= $stats['repair_items']; ?></h3>
                    <p class="text-muted mb-0">แจ้งซ่อม</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- จำหน่ายแล้ว -->
            <div class="col-6 col-md-4 col-lg-2 d-flex">
              <div class="stats-card w-100">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-secondary-gradient me-3"><i class="fas fa-trash"></i></div>
                  <div>
                    <h3 class="mb-0"><?= $stats['disposed_items']; ?></h3>
                    <p class="text-muted mb-0">จำหน่ายแล้ว</p>
                  </div>
                </div>
              </div>
            </div>
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

  <!-- List Modal for Borrowings/Repairs -->
  <div class="modal fade" id="listModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="listModalTitle">รายการ</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="listModalLoading" class="text-center my-4" style="display:none;">
            <div class="spinner-border text-primary" role="status"></div>
          </div>
          <div id="listModalBody"></div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let currentCategoryId = null;
    document.addEventListener('DOMContentLoaded', function() {
      const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
      const listModal = new bootstrap.Modal(document.getElementById('listModal'));
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

      // Attach click handlers for list triggers (กำลังยืม / เกินกำหนด / แจ้งซ่อม)
      document.querySelectorAll('.js-open-list').forEach(el => {
        el.addEventListener('click', function() {
          const target = this.getAttribute('data-list'); // borrowings | repairs
          const filter = this.getAttribute('data-filter') || '';
          openListModal(target, filter);
        });
      });

      function openListModal(target, filter) {
        const titleEl = document.getElementById('listModalTitle');
        const bodyEl = document.getElementById('listModalBody');
        const loadingEl = document.getElementById('listModalLoading');
        bodyEl.innerHTML = '';
        loadingEl.style.display = 'block';

        let url = '';
        if (target === 'borrowings') {
          titleEl.textContent = filter === 'overdue' ? 'รายการยืมที่เกินกำหนด' : 'รายการยืมที่กำลังยืมอยู่';
          url = 'dashboard_borrowings_list.php?filter=' + encodeURIComponent(filter || 'active');
        } else if (target === 'repairs') {
          titleEl.textContent = 'รายการแจ้งซ่อม';
          url = 'dashboard_repairs_list.php';
        }

        fetch(url)
          .then(res => res.text())
          .then(html => {
            bodyEl.innerHTML = html;
            loadingEl.style.display = 'none';
            listModal.show();
          })
          .catch(() => {
            bodyEl.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดข้อมูลได้</div>';
            loadingEl.style.display = 'none';
            listModal.show();
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

