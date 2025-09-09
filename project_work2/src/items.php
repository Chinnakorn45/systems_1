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
    header("location: index.php");
    exit;
}

/* =========================
   ลบครุภัณฑ์ (ตามเงื่อนไขเดิม)
   ========================= */
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
    
    // ตรวจสอบการเคลื่อนไหวล่าสุด
    $check_movements = "SELECT COUNT(*) as count FROM equipment_movements WHERE item_id = ? AND movement_type IN ('maintenance', 'disposal')";
    $stmt_movements = mysqli_prepare($link, $check_movements);
    mysqli_stmt_bind_param($stmt_movements, "i", $item_id);
    mysqli_stmt_execute($stmt_movements);
    $result_movements = mysqli_stmt_get_result($stmt_movements);
    $movements_count = mysqli_fetch_assoc($result_movements)['count'];
    mysqli_stmt_close($stmt_movements);
    
    // ตรวจสอบงานซ่อม
    $check_repairs = "SELECT COUNT(*) as count FROM repairs WHERE item_id = ? AND status NOT IN ('completed', 'cancelled')";
    $stmt_repairs = mysqli_prepare($link, $check_repairs);
    mysqli_stmt_bind_param($stmt_repairs, "i", $item_id);
    mysqli_stmt_execute($stmt_repairs);
    $result_repairs = mysqli_stmt_get_result($stmt_repairs);
    $repairs_count = mysqli_fetch_assoc($result_repairs)['count'];
    mysqli_stmt_close($stmt_repairs);
    
    if ($borrowings_count > 0) {
        $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการยืมอยู่";
        header("location: items.php");
        exit;
    }
    if ($movements_count > 0) {
        $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการส่งซ่อมหรือเคลื่อนไหวอยู่";
        header("location: items.php");
        exit;
    }
    if ($repairs_count > 0) {
        $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการส่งซ่อมอยู่";
        header("location: items.php");
        exit;
    }
    
    $sql_del = "DELETE FROM items WHERE item_id = ?";
    if ($stmt = mysqli_prepare($link, $sql_del)) {
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

/* =========================
   ดึงค่าตัวกรองจาก GET (เหลือเฉพาะที่ต้องการ)
   ========================= */
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status        = isset($_GET['status']) ? trim($_GET['status']) : ''; // available|borrowed|repair|maintenance|disposed
$category_id   = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$location_like = isset($_GET['location']) ? trim($_GET['location']) : '';

/* =========================
   สร้างเงื่อนไข WHERE จากตัวกรอง
   ========================= */
$conds = [];
if ($search !== '') {
    $esc = mysqli_real_escape_string($link, $search);
    $conds[] = "(i.item_number LIKE '%$esc%' 
             OR i.serial_number LIKE '%$esc%' 
             OR i.model_name LIKE '%$esc%'
             OR i.brand LIKE '%$esc%'
             OR c.category_name LIKE '%$esc%' 
             OR i.note LIKE '%$esc%' 
             OR i.location LIKE '%$esc%')";
}
if ($category_id > 0) {
    $conds[] = "i.category_id = " . intval($category_id);
}
if ($location_like !== '') {
    $esc = mysqli_real_escape_string($link, $location_like);
    $conds[] = "i.location LIKE '%$esc%'";
}

/* ตัวกรองสถานะ */
$status = strtolower($status);
switch ($status) {
    case 'disposed':
        $conds[] = "i.is_disposed = 1";
        break;
    case 'borrowed':
        $conds[] = "EXISTS (SELECT 1 FROM borrowings b2 WHERE b2.item_id = i.item_id AND b2.status IN ('borrowed','pending','overdue'))";
        break;
    case 'repair':
        $conds[] = "EXISTS (SELECT 1 FROM repairs r WHERE r.item_id = i.item_id AND r.status NOT IN ('completed','cancelled'))";
        break;
    case 'maintenance':
        $conds[] = "EXISTS (SELECT 1 FROM equipment_movements em WHERE em.item_id = i.item_id AND em.movement_type IN ('maintenance','disposal'))";
        break;
    case 'available':
        $conds[] = "i.is_disposed = 0";
        $conds[] = "NOT EXISTS (SELECT 1 FROM borrowings b3 WHERE b3.item_id = i.item_id AND b3.status IN ('borrowed','pending','overdue'))";
        $conds[] = "NOT EXISTS (SELECT 1 FROM repairs r2 WHERE r2.item_id = i.item_id AND r2.status NOT IN ('completed','cancelled'))";
        $conds[] = "NOT EXISTS (SELECT 1 FROM equipment_movements em2 WHERE em2.item_id = i.item_id AND em2.movement_type IN ('maintenance','disposal'))";
        break;
    default:
        // ไม่กรองสถานะ
        break;
}

$where = '';
if (!empty($conds)) {
    $where = "WHERE " . implode(" AND ", $conds);
}

/* =========================
   คิวรี่หลัก + current_status (รวม is_disposed)
   - ใช้ i.brand ตรง ๆ ไม่ JOIN ตาราง brands
   ========================= */
$sql = "SELECT 
          i.*,
          c.category_name,
          i.model_name,
          i.brand,
          CASE 
            WHEN i.is_disposed = 1 THEN 'disposed'
            WHEN EXISTS (
              SELECT 1 FROM borrowings b4 
              WHERE b4.item_id = i.item_id AND b4.status IN ('borrowed', 'pending', 'overdue')
            ) THEN 'borrowed'
            WHEN EXISTS (
              SELECT 1 FROM repairs r3 
              WHERE r3.item_id = i.item_id AND r3.status NOT IN ('completed', 'cancelled')
            ) THEN 'repair'
            WHEN EXISTS (
              SELECT 1 FROM equipment_movements em3 
              WHERE em3.item_id = i.item_id AND em3.movement_type IN ('maintenance', 'disposal')
            ) THEN 'maintenance'
            ELSE 'available'
          END as current_status,
          (SELECT GROUP_CONCAT(CONCAT(image_id, '::', image_path) 
                               ORDER BY is_primary DESC, sort_order, uploaded_at 
                               SEPARATOR '||') 
             FROM item_images ii 
            WHERE ii.item_id = i.item_id) AS images_concat
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        $where
        ORDER BY i.item_id DESC";

$result = mysqli_query($link, $sql);

/* ดึงหมวดหมู่เพื่อโชว์ในตัวกรอง */
$cats = [];
$cat_res = mysqli_query($link, "SELECT category_id, category_name FROM categories ORDER BY category_name");
while ($cr = mysqli_fetch_assoc($cat_res)) { $cats[] = $cr; }

function is_sel($a, $b) { return $a===$b ? 'selected' : ''; }

/* ===== Helpers สำหรับจัดพาธรูป ===== */

// คืน URL สัมพัทธ์ตามตำแหน่งสคริปต์ปัจจุบัน (รองรับกรณีรันในซับโฟลเดอร์)
if (!function_exists('asset_url')) {
  function asset_url(string $rel): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return ($base === '/' ? '' : $base) . '/' . ltrim($rel, '/\\');
  }
}

// ทำให้พาธรูปเป็นรูปแบบมาตรฐานที่เปิดได้แน่ ๆ
if (!function_exists('normalize_image_url')) {
  function normalize_image_url(string $p): string {
    $p = trim($p);
    if ($p === '') return '';
    // เป็น URL เต็มอยู่แล้ว
    if (preg_match('#^https?://#i', $p)) return $p;
    // ถ้าเป็นชื่อไฟล์เฉย ๆ หรือไม่มี uploads/ ก็นำหน้าให้ครบ
    if ($p[0] !== '/' && strpos($p, 'uploads/') !== 0) {
      $p = 'uploads/' . $p;
    }
    return asset_url($p);
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>จัดการครุภัณฑ์ - ระบบบันทึกคลังครุภัณฑ์</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="common-ui.css">
  <style>
    @media (max-width: 767px) {
      .main-content { padding-bottom: 88px; }
      .table-responsive { padding-bottom: 8px; }
      footer { position: relative; z-index: 100; }
    }
    .filters .form-control, .filters .form-select { height: 38px; }
  </style>
</head>
<body>

<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">จัดการครุภัณฑ์</span>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'sidebar.php'; ?>

    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content mt-4 mt-md-5">

        <!-- Alerts -->
        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
          <h2 class="mb-0"><i class="fas fa-box me-2"></i>จัดการครุภัณฑ์</h2>
          <div class="d-flex gap-2">
            <a href="categories.php" class="btn btn-secondary"><i class="fas fa-list"></i> หมวดหมู่</a>
            <a href="brands.php" class="btn btn-secondary"><i class="fas fa-trademark"></i> ยี่ห้อ/รุ่น</a>
            <a href="item_form.php" class="btn btn-add"><i class="fas fa-plus"></i> เพิ่มครุภัณฑ์</a>
          </div>
        </div>

        <!-- ตัวกรอง (คงไว้เฉพาะที่ต้องการ) -->
        <form class="card shadow-sm mb-3 filters" method="get">
          <div class="card-body">
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-4">
                <label class="form-label mb-1">ค้นหารวม</label>
                <input type="text" name="search" class="form-control" placeholder="เลขครุภัณฑ์ / Serial / รุ่น / ยี่ห้อ / คำอธิบาย..." value="<?= htmlspecialchars($search) ?>">
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label mb-1">สถานะ</label>
                <select name="status" class="form-select">
                  <option value="">ทั้งหมด</option>
                  <option value="available"   <?= is_sel($status,'available') ?>>พร้อมใช้งาน</option>
                  <option value="borrowed"    <?= is_sel($status,'borrowed') ?>>กำลังยืม</option>
                  <option value="repair"      <?= is_sel($status,'repair') ?>>ส่งซ่อม</option>
                  <option value="maintenance" <?= is_sel($status,'maintenance') ?>>บำรุงรักษา</option>
                  <option value="disposed"    <?= is_sel($status,'disposed') ?>>จำหน่ายแล้ว</option>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label mb-1">หมวดหมู่</label>
                <select name="category_id" class="form-select">
                  <option value="0">ทั้งหมด</option>
                  <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>" <?= $category_id===$c['category_id']?'selected':'' ?>>
                      <?= htmlspecialchars($c['category_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label mb-1">ตำแหน่ง</label>
                <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($location_like) ?>" placeholder="ห้อง/อาคาร">
              </div>
              <div class="col-12 col-md-2 text-md-end mt-2">
                <button class="btn btn-primary me-1"><i class="fas fa-filter me-1"></i> กรอง</button>
                <a href="items.php" class="btn btn-outline-secondary"><i class="fas fa-rotate-left me-1"></i> ล้างตัวกรอง</a>
              </div>
            </div>
          </div>
        </form>

        <!-- ค้นหาแบบทันทีฝั่งหน้า (client-side) -->
        <div class="mb-3">
          <input type="text" id="itemSearch" class="form-control" style="max-width:350px;" placeholder="ค้นหารายการในหน้านี้แบบทันที (client-side)">
        </div>

        <!-- ตาราง -->
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 68vh; overflow-y: auto;">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="sticky-top bg-white" style="z-index: 1020;">
                  <tr>
                    <th>เลขครุภัณฑ์</th>
                    <th>Serial Number</th>
                    <th>ยี่ห้อ</th>
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
                    <td><?= htmlspecialchars($row['item_number']) ?></td>
                    <td><?= htmlspecialchars($row['serial_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['brand'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['model_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                    <td><?= (int)$row['total_quantity'] ?></td>
                    <td><?= isset($row['price_per_unit']) ? number_format($row['price_per_unit'], 2) : '-' ?></td>
                    <td><?= isset($row['total_price']) ? number_format($row['total_price'], 2) : '-' ?></td>
                    <td>
                      <?php
                        // แสดง thumbnail รูปแรก + เปิดแกลเลอรี (normalize path ให้ถูกเสมอ)
                        $img_list = [];
                        if (!empty($row['images_concat'])) {
                          $parts = explode('||', $row['images_concat']);
                          foreach ($parts as $part) {
                            $p = explode('::', $part, 2);
                            if (count($p) == 2 && $p[1] !== '') $img_list[] = $p[1];
                          }
                        }
                        if (empty($img_list) && !empty($row['image'])) $img_list[] = $row['image'];

                        if (!empty($img_list)) {
                          // แปลงพาธทั้งหมดให้เป็น URL ใช้งานได้
                          $img_urls = array_map('normalize_image_url', $img_list);
                          $first = $img_urls[0];
                          $json = htmlspecialchars(json_encode(array_values($img_urls)), ENT_QUOTES, 'UTF-8');
                          echo '<a href="' . htmlspecialchars($first) . '" class="img-preview-link" data-images="' . $json . '" onclick="openGallery(event,this)">' .
                               '<img src="' . htmlspecialchars($first) . '" alt="img" style="max-width:60px;max-height:60px;object-fit:cover;" ' .
                               'onerror="this.onerror=null;this.src=\'' . htmlspecialchars(asset_url('img/placeholder.png')) . '\';">' .
                               '</a>';
                        }
                      ?>
                    </td>
                    <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['location'] ?? '-') ?></td>
                    <td><?= !empty($row['purchase_date']) ? thaidate('j M Y', $row['purchase_date']) : '-' ?></td>
                    <td>
                      <?php 
                        switch($row['current_status']) {
                          case 'disposed':     echo '<span class="badge bg-secondary">จำหน่ายแล้ว</span>'; break;
                          case 'borrowed':     echo '<span class="badge bg-warning text-dark">กำลังยืม</span>'; break;
                          case 'repair':       echo '<span class="badge bg-danger">ส่งซ่อม</span>'; break;
                          case 'maintenance':  echo '<span class="badge bg-info">บำรุงรักษา</span>'; break;
                          default:             echo '<span class="badge bg-success">พร้อมใช้งาน</span>';
                        }
                      ?>
                    </td>
                    <td class="d-flex gap-1 align-items-center">
                      <a href="item_form2.php?id=<?= (int)$row['item_id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                      <?php if ($row['current_status'] === 'available'): ?>
                        <a href="items.php?action=delete&id=<?= (int)$row['item_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบครุภัณฑ์นี้?');"><i class="fas fa-trash"></i></a>
                      <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled title="ไม่สามารถลบครุภัณฑ์ที่ไม่พร้อมใช้งานได้">
                          <i class="fas fa-trash"></i>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endwhile; ?>

                  <?php if ($row_count == 0): ?>
                    <tr>
                      <td colspan="14" class="text-center py-4">
                        <div class="text-muted">
                          <i class="fas fa-box fa-3x mb-3"></i>
                          <h5>ไม่พบข้อมูลครุภัณฑ์</h5>
                          <p class="mb-0">ลองปรับตัวกรองหรือล้างตัวกรองทั้งหมด</p>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div><!-- /.table-responsive -->
          </div><!-- /.card-body -->
        </div><!-- /.card -->

      </div>
    </div>
  </div>
</div>

<!-- Gallery modal (อยู่ก่อนสคริปต์) -->
<div class="modal fade" id="imageGalleryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body p-0 text-center position-relative">
        <button type="button" class="btn btn-sm btn-light position-absolute top-50 start-0 translate-middle-y ms-2" id="galleryPrev">&larr;</button>
        <img id="galleryImg" src="" alt="Preview" style="max-width:100%; max-height:80vh; height:auto; border-radius:6px;">
        <button type="button" class="btn btn-sm btn-light position-absolute top-50 end-0 translate-middle-y me-2" id="galleryNext">&rarr;</button>
      </div>
      <div class="modal-footer border-0 justify-content-between">
        <div class="text-white"><small id="galleryCounter"></small></div>
        <div>
          <a id="galleryOpenNewTab" href="#" target="_blank" class="btn btn-sm btn-outline-light">Open in new tab</a>
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JS (ย้ายมาไว้หลังโมดัล + ห่อ DOMContentLoaded) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  // ----- state ของแกลเลอรี -----
  let galleryImages = [];
  let galleryIndex = 0;

  // ทำให้เรียกได้จาก onclick บน <a>
  window.openGallery = function (e, anchor) {
    if (e.ctrlKey || e.metaKey || e.button === 1) return; // อนุญาตเปิดแท็บใหม่
    e.preventDefault();

    try {
      const json = anchor.getAttribute('data-images');
      galleryImages = JSON.parse(json || '[]');
    } catch (_) {
      galleryImages = [];
    }
    if (!galleryImages.length) return;

    galleryIndex = 0;
    showGalleryImage(galleryIndex);

    const modalEl = document.getElementById('imageGalleryModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  };

  function showGalleryImage(idx) {
    if (!galleryImages.length) return;
    const img = document.getElementById('galleryImg');
    const counter = document.getElementById('galleryCounter');
    const openLink = document.getElementById('galleryOpenNewTab');

    idx = (idx + galleryImages.length) % galleryImages.length;
    galleryIndex = idx;

    if (img) img.src = galleryImages[galleryIndex];
    if (openLink) openLink.href = galleryImages[galleryIndex];
    if (counter) counter.textContent = (galleryIndex + 1) + ' / ' + galleryImages.length;
  }

  document.addEventListener('DOMContentLoaded', function () {
    // ----- ค้นหาแบบทันทีในตาราง -----
    const searchEl = document.getElementById('itemSearch');
    if (searchEl) {
      searchEl.addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          const show = text.includes(filter);
          row.style.display = show ? '' : 'none';
          if (show) visibleCount++;
        });

        let noResultsRow = document.querySelector('.no-results-row');
        if (visibleCount === 0 && !noResultsRow) {
          const tbody = document.querySelector('table tbody');
          const newRow = document.createElement('tr');
          newRow.className = 'no-results-row';
          newRow.innerHTML = `
            <td colspan="14" class="text-center py-4">
              <div class="text-muted">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h5>ไม่พบผลลัพธ์</h5>
                <p class="mb-0">ลองค้นหาด้วยคำอื่น</p>
              </div>
            </td>`;
          if (tbody) tbody.appendChild(newRow);
        } else if (visibleCount > 0 && noResultsRow) {
          noResultsRow.remove();
        }
      });
    }

    // ----- ปุ่ม/เหตุการณ์ของโมดัลแกลเลอรี -----
    const prevBtn = document.getElementById('galleryPrev');
    const nextBtn = document.getElementById('galleryNext');
    const modalEl = document.getElementById('imageGalleryModal');

    if (prevBtn) prevBtn.addEventListener('click', () => showGalleryImage(galleryIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => showGalleryImage(galleryIndex + 1));
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', function () {
        const img = document.getElementById('galleryImg');
        if (img) img.src = '';
        galleryImages = [];
      });
    }
  });
})();
</script>

<!-- Footer -->
<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
  <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
  พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
  | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
  | © 2025
</footer>

</body>
</html>
