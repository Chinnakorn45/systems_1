<?php
require_once 'config.php';
session_start();

/* ===== ทำให้ mysqli โยน exception + ตั้ง charset ===== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (function_exists('mysqli_set_charset')) {
    @mysqli_set_charset($link, 'utf8mb4');
}

/* ===== ตรวจสอบการล็อกอิน/สิทธิ์ ===== */
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
if ($_SESSION["role"] !== "admin") {
    if ($_SESSION["role"] === 'staff') {
        header('Location: borrowings.php');
        exit;
    }
    header("location: index.php");
    exit;
}

/* =========================
   โน้ตส่วนตัวผู้ใช้ (ไม่ผูกครุภัณฑ์)
   - สร้างตารางถ้ายังไม่มี
   - โหลดโน้ตปัจจุบันของผู้ใช้
   - Endpoint AJAX: action=save_quick_note (POST)
   ========================= */
$my_uid = (int)$_SESSION['user_id'];
try {
    mysqli_query($link, "
        CREATE TABLE IF NOT EXISTS user_notes (
            user_id INT NOT NULL PRIMARY KEY,
            note_text MEDIUMTEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (mysqli_sql_exception $e) {
    // ถ้าสร้างไม่ได้ก็จะไม่มีโน้ต (ไม่ทำให้หน้าใช้งานหลักพัง)
}
$my_note = '';
try {
    $stmt = $link->prepare("SELECT note_text FROM user_notes WHERE user_id = ?");
    $stmt->bind_param('i', $my_uid);
    $stmt->execute();
    $stmt->bind_result($my_note);
    $stmt->fetch();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $my_note = '';
}

/* ===== AJAX: บันทึกโน้ตส่วนตัว ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_quick_note') {
    header('Content-Type: application/json; charset=utf-8');
    $note = isset($_POST['note']) ? (string)$_POST['note'] : '';
    if (mb_strlen($note, 'UTF-8') > 5000) {
        $note = mb_substr($note, 0, 5000, 'UTF-8');
    }
    try {
        $stmt = $link->prepare("
            INSERT INTO user_notes (user_id, note_text)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE note_text = VALUES(note_text), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->bind_param('is', $my_uid, $note);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true, 'note' => $note]);
    } catch (mysqli_sql_exception $e) {
        echo json_encode(['ok' => false, 'message' => 'บันทึกไม่สำเร็จ: '.$e->getMessage()]);
    }
    exit;
}

/* =========================
   ลบครุภัณฑ์ (ตรวจ FK ครบ + ดักจับ FK error)
   ========================= */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $item_id = intval($_GET['id']);

    // helper: คืนจำนวนแถวจาก query COUNT(*)
    $get_count = function(mysqli $link, string $sql, int $id): int {
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : ['count' => 0];
        $cnt = isset($row['count']) ? (int)$row['count'] : 0;
        mysqli_stmt_close($stmt);
        return $cnt;
    };

    try {
        // 1) อ้างอิงที่ยัง active (กันลบแน่นอน)
        $borrowings_active = $get_count($link,
            "SELECT COUNT(*) AS count FROM borrowings 
             WHERE item_id = ? AND status IN ('borrowed','pending','overdue')",
            $item_id
        );

        $repairs_active = $get_count($link,
            "SELECT COUNT(*) AS count FROM repairs 
             WHERE item_id = ? AND status NOT IN ('completed','cancelled')",
            $item_id
        );

        $movements_block = $get_count($link,
            "SELECT COUNT(*) AS count FROM equipment_movements 
             WHERE item_id = ? AND movement_type IN ('maintenance','disposal')",
            $item_id
        );

        if ($borrowings_active > 0) {
            $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการยืมอยู่";
            header("location: items.php"); exit;
        }
        if ($repairs_active > 0) {
            $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์ได้ เนื่องจากมีการส่งซ่อมอยู่";
            header("location: items.php"); exit;
        }
        if ($movements_block > 0) {
            $_SESSION['error_message'] = "ไม่สามารถลบครุภัณฑ์���ด้ เนื่องจากมีการเคลื่อนไหว/บำรุงรักษาอยู่";
            header("location: items.php"); exit;
        }

        // 2) อ้างอิงแบบ “ประวัติ” (ถึงจะปิดงานแล้ว แต่ยังมี row ลูก -> FK จะบล็อคการลบ)
        $borrowings_any = $get_count($link,
            "SELECT COUNT(*) AS count FROM borrowings WHERE item_id = ?",
            $item_id
        );
        $repairs_any = $get_count($link,
            "SELECT COUNT(*) AS count FROM repairs WHERE item_id = ?",
            $item_id
        );
        $movements_any = $get_count($link,
            "SELECT COUNT(*) AS count FROM equipment_movements WHERE item_id = ?",
            $item_id
        );

        if ($borrowings_any > 0 || $repairs_any > 0 || $movements_any > 0) {
            $_SESSION['error_message'] =
                "ไม่สามารถลบได้: มีประวัติการยืม/ซ่อม/เคลื่อนไหวที่เชื่อมกับครุภัณฑ์นี้อยู่ (ถึงแม้จะปิดงานแล้ว)";
            header("location: items.php"); exit;
        }

        // 3) ผ่านเงื่อนไขทั้งหมด -> ลบ
        $sql_del = "DELETE FROM items WHERE item_id = ?";
        $stmt = mysqli_prepare($link, $sql_del);
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['success_message'] = "ลบครุภัณฑ์เรียบร้อยแล้ว";
    } catch (mysqli_sql_exception $e) {
        // 1451 = Cannot delete or update a parent row: a foreign key constraint fails
        if ((int)$e->getCode() === 1451) {
            $_SESSION['error_message'] =
                "ไม่สามารถลบได้ เนื่องจากมีข้อมูลอ้างอิงอยู่ (Foreign Key) — "
                ."พิจารณาใช้การ 'จำหน่าย/เลิกใช้' แทนการลบถาวร หรือปรับ FK เป็น ON DELETE CASCADE หากต้องการให้ลบประวัติตาม";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบ: ".$e->getMessage();
        }
    }

    header("location: items.php"); exit;
}

/* =========================
   ดึงค่าตัวกรองจาก GET (เหลือเฉพาะที่ต้องการ)
   ========================= */
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status        = isset($_GET['status']) ? trim($_GET['status']) : ''; // available|borrowed|repair|maintenance|disposed
$category_id    = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$location_like  = isset($_GET['location']) ? trim($_GET['location']) : '';
$main_department= isset($_GET['main_department']) ? (int)$_GET['main_department'] : 0;
$sub_department = isset($_GET['sub_department']) ? (int)$_GET['sub_department'] : 0;

/* ====== สร้างลิงก์ไปหน้า print พร้อมพารามิเตอร์ตัวกรอง ====== */
$qs = [];
if ($search !== '')        $qs['search']      = $search;
if ($status !== '')        $qs['status']      = $status;
if ($category_id > 0)      $qs['category_id'] = $category_id;
if ($location_like !== '') $qs['location']    = $location_like;
$qs['main_department'] = $main_department > 0 ? $main_department : null;
$qs['sub_department']  = $sub_department  > 0 ? $sub_department  : null;
$qs = array_filter($qs, function($v) { return $v !== null; });
$print_url = 'print_items.php' . ($qs ? ('?' . http_build_query($qs)) : '');

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
// กรองตามแผนกหลัก/ย่อย
if ($sub_department > 0) {
    $conds[] = "i.department_id = " . intval($sub_department);
} elseif ($main_department > 0) {
    $mid = intval($main_department);
    // รวมทั้งแผนกหลักเองและแผนกย่อยโดยตรง
    $conds[] = "i.department_id IN (SELECT department_id FROM departments WHERE department_id = $mid OR parent_id = $mid)";
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
        $conds[] = "EXISTS (SELECT 1 FROM repairs r WHERE r.item_id = i.item_id AND r.status NOT IN ('completed','cancelled','delivered','ส่งมอบแล้ว'))";
        break;
    case 'maintenance':
        $conds[] = "EXISTS (SELECT 1 FROM equipment_movements em WHERE em.item_id = i.item_id AND em.movement_type IN ('maintenance','disposal'))";
        break;
    case 'available':
        $conds[] = "i.is_disposed = 0";
        $conds[] = "NOT EXISTS (SELECT 1 FROM borrowings b3 WHERE b3.item_id = i.item_id AND b3.status IN ('borrowed','pending','overdue'))";
        $conds[] = "NOT EXISTS (SELECT 1 FROM repairs r2 WHERE r2.item_id = i.item_id AND r2.status NOT IN ('completed','cancelled','delivered','ส่งมอบแล้ว'))";
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

// ===== Pagination (20 per page) =====
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Count total rows with current filters
$sql_count = "SELECT COUNT(*) AS cnt
              FROM items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              $where";
$total_rows = 0;
try {
    $res_count = mysqli_query($link, $sql_count);
    $row_cnt = $res_count ? mysqli_fetch_assoc($res_count) : ['cnt' => 0];
    $total_rows = isset($row_cnt['cnt']) ? (int)$row_cnt['cnt'] : 0;
} catch (mysqli_sql_exception $e) {
    $total_rows = 0;
}

$total_pages = (int)ceil($total_rows / $per_page);
if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

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
              WHERE r3.item_id = i.item_id AND r3.status NOT IN ('completed', 'cancelled', 'delivered', 'ส่งมอบแล้ว')
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
        ORDER BY i.item_id DESC
        LIMIT $per_page OFFSET $offset";

$result = mysqli_query($link, $sql);

/* ดึงหมวดหมู่เพื่อโชว์ในตัวกรอง */
$cats = [];
$cat_res = mysqli_query($link, "SELECT category_id, category_name FROM categories ORDER BY category_name");
while ($cr = mysqli_fetch_assoc($cat_res)) { $cats[] = $cr; }

/* ดึงแผนกหลักสำหรับตัวกรอง */
$main_departments = [];
try {
    $md_res = mysqli_query($link, "SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
    while ($md_res && ($mr = mysqli_fetch_assoc($md_res))) { $main_departments[] = $mr; }
} catch (Exception $e) { $main_departments = []; }

function is_sel($a, $b) { return $a===$b ? 'selected' : ''; }

/* ===== Helpers สำหรับจัดพาธรูป ===== */

// คืน URL สัมพัทธ์ตามตำแหน่งสคริปต์ปัจจุบัน
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
    if (preg_match('#^https?://#i', $p)) return $p;
    if ($p[0] !== '/' && strpos($p, 'uploads/') !== 0) {
      $p = 'uploads/' . $p;
    }
    return asset_url($p);
  }
}

/* ===== Flash message สำหรับ Swal (อ่านจาก session แล้วเคลียร์) ===== */
$flash_success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$flash_error   = isset($_SESSION['error_message'])   ? $_SESSION['error_message']   : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
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

    /* ปุ่มโน้ตส่วนตัว มุมล่างขวา */
    .btn-quick-note{
      position: fixed;
      right: 18px;
      bottom: 20px;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 16px rgba(0,0,0,.18);
      z-index: 1080;
    }
    .btn-quick-note i{
      font-size: 20px;
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
    <span class="fw-bold">จัดการครุภัณฑ์</span>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'sidebar.php'; ?>

    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content mt-4 mt-md-5">

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
          <h2 class="mb-0"><i class="fas fa-box me-2"></i>จัดการครุภัณฑ์</h2>
          <div class="d-flex gap-2">
            <a href="categories.php" class="btn btn-secondary"><i class="fas fa-list"></i> หมวดหมู่</a>
            <a href="brands.php" class="btn btn-secondary"><i class="fas fa-trademark"></i> ยี่ห้อ/รุ่น</a>
            <a href="item_form.php" class="btn btn-add"><i class="fas fa-plus"></i> เพิ่มครุภัณฑ์</a>
            <!-- ปุ่มพิมพ์รายการครุภัณฑ์ (พกตัวกรองไปด้วย) -->
            <a href="<?= htmlspecialchars($print_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-success">
              <i class="fas fa-print"></i> พิมพ์รายการครุภัณฑ์
            </a>
          </div>
        </div>

        <!-- ตัวกรอง -->
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
              <div class="col-6 col-md-2">
                <label class="form-label mb-1">แผนกหลัก</label>
                <select id="filter_main_department" name="main_department" class="form-select">
                  <option value="0">ทั้งหมด</option>
                  <?php foreach ($main_departments as $md): ?>
                    <option value="<?= (int)$md['department_id'] ?>" <?= ($main_department===$md['department_id'])?'selected':'' ?>>
                      <?= htmlspecialchars($md['department_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label mb-1">แผนกย่อย</label>
                <select id="filter_sub_department" name="sub_department" class="form-select">
                  <option value="0">ทั้งหมด</option>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label mb-1">ประเภทบริการ</label>
                <input type="text" id="filter_service_type" class="form-control" placeholder="-" value="" readonly>
              </div>
              <div class="col-12 col-md-2 text-md-end mt-2">
                <button class="btn btn-primary me-1"><i class="fas fa-filter me-1"></i> กรอง</button>
                <a href="items.php" class="btn btn-outline-secondary"><i class="fas fa-rotate-left me-1"></i> ล้างตัวกรอง</a>
              </div>
            </div>
          </div>
        </form>

        <!-- ค้นหาทุกหน้า (server-side) -->
        <div class="mb-3">
          <input type="text" id="itemSearch" class="form-control" style="max-width:350px;" placeholder="ค้นหาทุกหน้า (พิมพ์แล้วระบบจะค้นหา)" value="<?= htmlspecialchars($search) ?>">
        </div>

        <!-- ตาราง -->
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 54vh; overflow-y: auto;">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="sticky-top bg-white" style="z-index: 1020;">
                  <tr>
                    <th>เลขครุภัณฑ์</th>
                    <th>Serial Number</th>
                    <th>ยี่ห้อ</th>
                    <th>รุ่นครุภัณฑ์</th>
                    <th>หมวดหมู่</th>
                    <th>รูป</th>
                    <th>หมายเหตุ</th>
                    <th>ตำแหน่ง</th>
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
                    <td>
                      <?php
                        // แสดง thumbnail รูปแรก + เปิดแกลเลอรี
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
                          $img_urls = array_map('normalize_image_url', $img_list);
                          $first = $img_urls[0];
                          $json = htmlspecialchars(json_encode(array_values($img_urls)), ENT_QUOTES, 'UTF-8');
                          echo '<a href="' . htmlspecialchars($first) . '" class="img-preview-link" data-images="' . $json . '" onclick="openGallery(event,this)">' .
                               '<img src="' . htmlspecialchars($first) . '" alt="img" style="max-width:60px;max-height:60px;object-fit:cover;" ' .
                               'onerror="this.onerror=null;this.src=\'' . htmlspecialchars(asset_url('img/placeholder.png')) . '\';\">' .
                               '</a>';
                        }
                      ?>
                    </td>
                    <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['location'] ?? '-') ?></td>
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
                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= (int)$row['item_id'] ?>)" title="ลบครุภัณฑ์">
                          <i class="fas fa-trash"></i>
                        </button>
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

        <?php
          // Build pagination links preserving filters (without page)
          $page_qs = $qs; // from filters
          $make_page_link = function($p) use ($page_qs) {
              $params = $page_qs;
              $params['page'] = $p;
              return 'items.php?' . htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8');
          };
          $display_current_page = ($total_pages > 0) ? $page : 0;
        ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-2 px-md-0 mt-3">
          <div class="text-muted">
            หน้าที่ <?= $display_current_page ?> จาก <?= $total_pages ?> หน้า (ทั้งหมด <?= (int)$total_rows ?> รายการ)
          </div>
          <?php if ($total_pages > 1): ?>
          <nav aria-label="Pagination">
            <ul class="pagination mb-0">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $page > 1 ? $make_page_link($page - 1) : '#' ?>" tabindex="-1">ก่อนหน้า</a>
              </li>
              <?php
                $maxToShow = 5;
                $start = max(1, $page - 2);
                $end = min($total_pages, $start + $maxToShow - 1);
                if (($end - $start + 1) < $maxToShow) {
                  $start = max(1, $end - $maxToShow + 1);
                }
                if ($start > 1) {
                  echo '<li class="page-item"><a class="page-link" href="'.$make_page_link(1).'">1</a></li>';
                  if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                for ($i = $start; $i <= $end; $i++) {
                  $active = ($i == $page) ? 'active' : '';
                  echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$make_page_link($i).'">'.$i.'</a></li>';
                }
                if ($end < $total_pages) {
                  if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                  echo '<li class="page-item"><a class="page-link" href="'.$make_page_link($total_pages).'">'.$total_pages.'</a></li>';
                }
              ?>
              <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $page < $total_pages ? $make_page_link($page + 1) : '#' ?>">ถัดไป</a>
              </li>
            </ul>
          </nav>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ปุ่มโน้ตส่วนตัว (ไม่เกี่ยวกับครุภัณฑ์) -->
<button type="button" id="quickNoteBtn" class="btn btn-primary btn-quick-note" title="โน้ตส่วนตัว">
  <i class="far fa-sticky-note"></i>
</button>

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

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
  const BRAND_GREEN = '#41B143';

  // ----- Flash จาก PHP (หลัง redirect การลบ/บันทึก) -----
  const flashSuccess = <?php echo json_encode($flash_success, JSON_UNESCAPED_UNICODE); ?>;
  const flashError   = <?php echo json_encode($flash_error,   JSON_UNESCAPED_UNICODE); ?>;

  if (flashError) {
    Swal.fire({
      icon: 'warning',
      title: 'ไม่สามารถดำเนินการได้',
      text: flashError,
      confirmButtonText: 'ตกลง',
      confirmButtonColor: BRAND_GREEN
    });
  }
  if (flashSuccess) {
    Swal.fire({
      icon: 'success',
      title: 'สำเร็จ',
      text: flashSuccess,
      timer: 1600,
      showConfirmButton: false
    });
  }

  // ----- ยืนยันการลบ (แทน confirm() เดิม) -----
  window.confirmDelete = function(id){
    Swal.fire({
      icon: 'question',
      title: 'ยืนยันการลบ?',
      text: 'เมื่อลบแล้วจะไม่สามารถกู้คืนได้',
      showCancelButton: true,
      confirmButtonText: 'ลบ',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'items.php?action=delete&id=' + encodeURIComponent(id);
      }
    });
  };

  // ----- แกลเลอรีรูปภาพ -----
  let galleryImages = [];
  let galleryIndex = 0;

  window.openGallery = function (e, anchor) {
    if (e.ctrlKey || e.metaKey || e.button === 1) return; // อนุญาตเปิดแท็บใหม่
    e.preventDefault();
    try {
      const json = anchor.getAttribute('data-images');
      galleryImages = JSON.parse(json || '[]');
    } catch (_) { galleryImages = []; }
    if (!galleryImages.length) return;

    galleryIndex = 0;
    showGalleryImage(galleryIndex);

    const modalEl = document.getElementById('imageGalleryModal');
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

  // ----- โน้ตส่วนตัว (ไม่ผูกครุภัณฑ์) -----
  let quickNote = <?php echo json_encode($my_note, JSON_UNESCAPED_UNICODE); ?> || '';

  function openQuickNote(){
    Swal.fire({
      title: 'โน้ตส่วนตัว',
      input: 'textarea',
      inputValue: quickNote,
      inputAttributes: { 'aria-label': 'พิมพ์โน้ตของคุณที่นี่' },
      inputAutoTrim: false,
      showCancelButton: true,
      confirmButtonText: 'บันทึก',
      cancelButtonText: 'ยกเลิก',
      width: 600,
      preConfirm: (val) => {
        return fetch('items.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({ action: 'save_quick_note', note: val ?? '' })
        })
        .then(r => r.json())
        .then(data => {
          if (!data.ok) throw new Error(data.message || 'บันทึกไม่สำเร็จ');
          return data;
        })
        .catch(err => {
          Swal.showValidationMessage(err.message);
        });
      }
    }).then(res => {
      if (res.isConfirmed && res.value && res.value.ok) {
        quickNote = res.value.note || '';
        Swal.fire({
          icon: 'success',
          title: 'บันทึกแล้ว',
          timer: 1200,
          showConfirmButton: false
        });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    // ค้นหาทุกหน้า (server-side): อัปเดตฟอร์มตัวกรองแล้วส่งคำขอใหม่ พร้อมรีเซ็ตไปหน้า 1
    const searchEl = document.getElementById('itemSearch');
    const filterForm = document.querySelector('form.filters');
    const serverSearchInput = filterForm ? filterForm.querySelector('input[name="search"]') : null;

    function submitWithSearch(val) {
      if (!filterForm || !serverSearchInput) return;
      serverSearchInput.value = val;
      let pageInput = filterForm.querySelector('input[name="page"]');
      if (!pageInput) {
        pageInput = document.createElement('input');
        pageInput.type = 'hidden';
        pageInput.name = 'page';
        filterForm.appendChild(pageInput);
      }
      pageInput.value = '1';
      filterForm.submit();
    }

    if (searchEl) {
      let debounceTimer;
      searchEl.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const v = this.value;
        debounceTimer = setTimeout(() => submitWithSearch(v), 500);
      });
      searchEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          submitWithSearch(searchEl.value);
        }
      });
    }

    // ปุ่มเลื่อนรูปในแกลเลอรี
    const prevBtn = document.getElementById('galleryPrev');
    const nextBtn = document.getElementById('galleryNext');
    if (prevBtn) prevBtn.addEventListener('click', () => showGalleryImage(galleryIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => showGalleryImage(galleryIndex + 1));

    const modalEl = document.getElementById('imageGalleryModal');
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', function () {
        const img = document.getElementById('galleryImg');
        if (img) img.src = '';
        galleryImages = [];
      });
    }

    // ไอคอนโน้ตส่วนตัว + ช็อตคัท Alt+N
    const btn = document.getElementById('quickNoteBtn');
    if (btn) btn.addEventListener('click', openQuickNote);
    document.addEventListener('keydown', (e) => {
      if ((e.altKey || e.metaKey) && (e.key === 'n' || e.key === 'N')) {
        e.preventDefault();
        openQuickNote();
      }
    });
  });
})();
</script>

<script>
// ตัวกรองแผนกหลัก/ย่อย + แสดงประเภทบริการอัตโนมัติ
document.addEventListener('DOMContentLoaded', function(){
  const mainSelect = document.getElementById('filter_main_department');
  const subSelect  = document.getElementById('filter_sub_department');
  const serviceInp = document.getElementById('filter_service_type');

  const selectedMain = '<?= (int)$main_department ?>';
  const selectedSub  = '<?= (int)$sub_department ?>';

  function clearSub(){
    while (subSelect.options.length > 1) subSelect.remove(1);
  }

  function updateServiceType(deptId){
    if (!serviceInp) return;
    serviceInp.value = '';
    const id = parseInt(deptId||'0',10);
    if (!id) return;
    fetch('get_department_service_type.php?department_id=' + encodeURIComponent(id))
      .then(r=>r.json())
      .then(d=>{
        if (d && d.type_name) serviceInp.value = d.type_name + ' - ' + (d.service_status||'');
        else serviceInp.value = '-';
      })
      .catch(()=>{ serviceInp.value=''; });
  }

  function loadSub(parentId, selId){
    clearSub();
    serviceInp.value = '';
    const pid = parseInt(parentId||'0',10);
    if (!pid) return;
    fetch('get_departments_children.php?parent_id=' + encodeURIComponent(pid))
      .then(r=>r.json())
      .then(list=>{
        (list||[]).forEach(dep=>{
          const opt = document.createElement('option');
          opt.value = dep.department_id;
          opt.textContent = dep.department_name;
          if (selId && String(selId) === String(dep.department_id)) opt.selected = true;
          subSelect.appendChild(opt);
        });
        if (subSelect.value && subSelect.value !== '0') updateServiceType(subSelect.value);
      })
      .catch(()=>{});
  }

  if (mainSelect) mainSelect.addEventListener('change', function(){ loadSub(this.value, 0); });
  if (subSelect)  subSelect.addEventListener('change', function(){ updateServiceType(this.value); });

  // Initial populate
  if (selectedMain && selectedMain !== '0') {
    if (mainSelect) mainSelect.value = selectedMain;
    loadSub(selectedMain, selectedSub);
  } else if (selectedSub && selectedSub !== '0') {
    updateServiceType(selectedSub);
  }
});
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
