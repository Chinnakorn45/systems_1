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

// ดึงข้อมูล + ค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
if ($search !== '') {
    $search_esc = mysqli_real_escape_string($link, $search);
    $where = "WHERE i.item_number LIKE '%$search_esc%' 
              OR i.serial_number LIKE '%$search_esc%' 
              OR i.model_name LIKE '%$search_esc%' 
              OR c.category_name LIKE '%$search_esc%' 
              OR i.note LIKE '%$search_esc%' 
              OR i.location LIKE '%$search_esc%'";
}
$sql = "SELECT i.*, c.category_name,
        CASE 
            WHEN EXISTS (SELECT 1 FROM borrowings b WHERE b.item_id = i.item_id AND b.status IN ('borrowed', 'pending', 'overdue')) THEN 'borrowed'
            WHEN EXISTS (SELECT 1 FROM repairs r WHERE r.item_id = i.item_id AND r.status NOT IN ('completed', 'cancelled')) THEN 'repair'
            WHEN EXISTS (SELECT 1 FROM equipment_movements em WHERE em.item_id = i.item_id AND em.movement_type IN ('maintenance', 'disposal')) THEN 'maintenance'
            ELSE 'available'
        END as current_status
  ,
  -- aggregate image list from item_images (image_id::image_path separated by '||')
  (SELECT GROUP_CONCAT(CONCAT(image_id, '::', image_path) ORDER BY is_primary DESC, sort_order, uploaded_at SEPARATOR '||') FROM item_images ii WHERE ii.item_id = i.item_id) AS images_concat
  FROM items i 
        LEFT JOIN categories c ON i.category_id = c.category_id
        $where";
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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="common-ui.css">
    <style>
    /* Ensure footer doesn't cover the table on small screens */
    @media (max-width: 767px) {
      .main-content { padding-bottom: 88px; }
      .table-responsive { padding-bottom: 8px; }
      footer { position: relative; z-index: 100; }
    }
    </style>
</head>
<body>

<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <!-- ปุ่มนี้อ้างถึง offcanvas ที่ประกาศภายใน sidebar.php -->
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">จัดการครุภัณฑ์</span>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <!-- เรียก Sidebar จากไฟล์เดียว (ทั้ง Desktop + Mobile offcanvas ต้องอยู่ใน sidebar.php) -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
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

        <!-- Header actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
          <h2 class="mb-0"><i class="fas fa-box me-2"></i>จัดการครุภัณฑ์</h2>
          <input type="text" id="itemSearch" class="form-control mb-2 mb-md-0" style="max-width:350px;" placeholder="ค้นหารายการครุภัณฑ์...">
          <div class="d-flex gap-2">
            <a href="categories.php" class="btn btn-secondary"><i class="fas fa-list"></i> จัดการหมวดหมู่</a>
            <a href="brands.php" class="btn btn-secondary"><i class="fas fa-trademark"></i> จัดการยี่ห้อ/รุ่น</a>
            <a href="item_form.php" class="btn btn-add"><i class="fas fa-plus"></i> เพิ่มครุภัณฑ์</a>
          </div>
        </div>

        <!-- ตาราง (เลื่อนเฉพาะตาราง + thead sticky) -->
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="sticky-top bg-white" style="z-index: 1020;">
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
                    <td><?= htmlspecialchars($row['item_number']) ?></td>
                    <td><?= htmlspecialchars($row['serial_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['model_name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                    <td><?= (int)$row['total_quantity'] ?></td>
                    <td><?= isset($row['price_per_unit']) ? number_format($row['price_per_unit'], 2) : '-' ?></td>
                    <td><?= isset($row['total_price']) ? number_format($row['total_price'], 2) : '-' ?></td>
                    <td>
                      <?php
            // show only the first image as thumbnail, but attach all images to data-images for gallery
            $img_list = [];
            if (!empty($row['images_concat'])) {
              $parts = explode('||', $row['images_concat']);
              foreach ($parts as $part) {
                $p = explode('::', $part, 2);
                if (count($p) == 2) {
                  $img_list[] = $p[1];
                }
              }
            }
            if (empty($img_list) && !empty($row['image'])) {
              $img_list[] = $row['image'];
            }
            if (!empty($img_list)) {
              $first = $img_list[0];
              $json = htmlspecialchars(json_encode(array_values($img_list)), ENT_QUOTES, 'UTF-8');
              echo '<a href="' . htmlspecialchars($first) . '" class="img-preview-link" data-images="' . $json . '" onclick="openGallery(event,this)">'
                 . '<img src="' . htmlspecialchars($first) . '" alt="img" style="max-width:60px;max-height:60px;object-fit:cover;">'
                 . '</a>';
            }
                      ?>
                    </td>
                    <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= $row['purchase_date'] ? thaidate('j M Y', $row['purchase_date']) : '-' ?></td>
                    <td>
                      <?php 
                        switch($row['current_status']) {
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
                        <button class="btn btn-sm btn-secondary" disabled title="ไม่สามารถลบครุภัณฑ์ที่มีการยืมหรือส่งซ่อมอยู่ได้">
                          <i class="fas fa-trash"></i>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endwhile; ?>

                  <?php if ($row_count == 0): ?>
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
            </div><!-- /.table-responsive -->
          </div><!-- /.card-body -->
        </div><!-- /.card -->

      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ค้นหาแบบเรียลไทม์
document.getElementById('itemSearch').addEventListener('input', function() {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll('table tbody tr');
  let visibleCount = 0;

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const show = text.includes(filter);
    row.style.display = show ? '' : 'none';
    if (show) visibleCount++;
  });

  // แสดง/ซ่อนแถว "ไม่พบผลลัพธ์"
  let noResultsRow = document.querySelector('.no-results-row');
  if (visibleCount === 0 && !noResultsRow) {
    const tbody = document.querySelector('table tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'no-results-row';
    newRow.innerHTML = `
      <td colspan="13" class="text-center py-4">
        <div class="text-muted">
          <i class="fas fa-search fa-3x mb-3"></i>
          <h5>ไม่พบผลลัพธ์</h5>
          <p class="mb-0">ลองค้นหาด้วยคำอื่น</p>
        </div>
      </td>`;
    tbody.appendChild(newRow);
  } else if (visibleCount > 0 && noResultsRow) {
    noResultsRow.remove();
  }
});
</script>

<!-- Gallery modal -->
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

<script>
var galleryImages = [];
var galleryIndex = 0;
function openGallery(e, anchor) {
  // allow ctrl/meta/middle click to open in new tab
  if (e.ctrlKey || e.metaKey || e.button === 1) return;
  e.preventDefault();
  try {
    var json = anchor.getAttribute('data-images');
    galleryImages = JSON.parse(json || '[]');
  } catch (err) {
    galleryImages = [];
  }
  if (!galleryImages || galleryImages.length === 0) return;
  galleryIndex = 0;
  showGalleryImage(galleryIndex);
  var modal = new bootstrap.Modal(document.getElementById('imageGalleryModal'));
  modal.show();
}

function showGalleryImage(idx) {
  var img = document.getElementById('galleryImg');
  var counter = document.getElementById('galleryCounter');
  var openLink = document.getElementById('galleryOpenNewTab');
  idx = (idx + galleryImages.length) % galleryImages.length;
  galleryIndex = idx;
  img.src = galleryImages[galleryIndex];
  openLink.href = galleryImages[galleryIndex];
  counter.textContent = (galleryIndex + 1) + ' / ' + galleryImages.length;
}

document.getElementById('galleryPrev').addEventListener('click', function(){ showGalleryImage(galleryIndex - 1); });
document.getElementById('galleryNext').addEventListener('click', function(){ showGalleryImage(galleryIndex + 1); });

// keyboard navigation
document.addEventListener('keydown', function(e){
  var modalEl = document.getElementById('imageGalleryModal');
  if (!modalEl.classList.contains('show')) return;
  if (e.key === 'ArrowLeft') showGalleryImage(galleryIndex - 1);
  if (e.key === 'ArrowRight') showGalleryImage(galleryIndex + 1);
});

// clear img on hide
document.getElementById('imageGalleryModal').addEventListener('hidden.bs.modal', function(){ document.getElementById('galleryImg').src = ''; galleryImages = []; });
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
