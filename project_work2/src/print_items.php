<?php
// print_items.php
require_once 'config.php';
session_start();

/* ===== mysqli โยน exception + charset ===== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (function_exists('mysqli_set_charset')) {
    @mysqli_set_charset($link, 'utf8mb4');
}

// ตรวจสอบสิทธิ์ (ให้เฉพาะ admin)
if (!isset($_SESSION["user_id"])) { header("location: login.php"); exit; }
if ($_SESSION["role"] !== "admin") { header("location: index.php"); exit; }

/* ===== รับตัวกรองจาก GET (เหมือน items.php) ===== */
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$raw_status    = isset($_GET['status']) ? trim($_GET['status']) : '';
$category_id    = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$location_like  = isset($_GET['location']) ? trim($_GET['location']) : '';
$main_department= isset($_GET['main_department']) ? (int)$_GET['main_department'] : 0;
$sub_department = isset($_GET['sub_department']) ? (int)$_GET['sub_department'] : 0;

/* ===== map สถานะ ไทย/อังกฤษ -> โค้ดกลาง ===== */
$status_lc = mb_strtolower($raw_status, 'UTF-8');

$th2code = [
  'พร้อมใช้งาน'   => 'available',
  'กำลังยืม'     => 'borrowed',
  'ส่งซ่อม'      => 'repair',
  'บำรุงรักษา'   => 'maintenance',
  'จำหน่ายแล้ว'  => 'disposed',
];
$code2th = [
  'available'   => 'พร้อมใช้งาน',
  'borrowed'    => 'กำลังยืม',
  'repair'      => 'ส่งซ่อม',
  'maintenance' => 'บำรุงรักษา',
  'disposed'    => 'จำหน่ายแล้ว',
];
$en_codes = array_keys($code2th);

$status_code = '';
if (in_array($status_lc, $en_codes, true)) {
    $status_code = $status_lc;
} else {
    foreach ($th2code as $th => $code) {
        if ($status_lc === mb_strtolower($th, 'UTF-8')) { $status_code = $code; break; }
    }
}

/* ===== where เงื่อนไข ===== */
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
if ($category_id > 0) $conds[] = "i.category_id = " . intval($category_id);
if ($location_like !== '') {
    $esc = mysqli_real_escape_string($link, $location_like);
    $conds[] = "i.location LIKE '%$esc%'";
}
// กรองตามแผนกหลัก/ย่อย (ให้เหมือน items.php)
if ($sub_department > 0) {
    $conds[] = "i.department_id = " . intval($sub_department);
} elseif ($main_department > 0) {
    $mid = intval($main_department);
    $conds[] = "i.department_id IN (SELECT department_id FROM departments WHERE department_id = $mid OR parent_id = $mid)";
}

/* เงื่อนไขสถานะตามโค้ดกลาง */
switch ($status_code) {
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
}

$where = '';
if (!empty($conds)) $where = "WHERE " . implode(" AND ", $conds);

/* ===== ดึงข้อมูลรายการ พร้อมสถานะไทย + รูปหลัก ===== */
$sql = "SELECT 
            i.*,
            c.category_name,
            i.model_name,
            i.brand,
            CASE 
              WHEN i.is_disposed = 1 THEN 'จำหน่ายแล้ว'
              WHEN EXISTS (SELECT 1 FROM borrowings b4 
                           WHERE b4.item_id = i.item_id 
                             AND b4.status IN ('borrowed','pending','overdue')) THEN 'กำลังยืม'
              WHEN EXISTS (SELECT 1 FROM repairs r3 
                           WHERE r3.item_id = i.item_id 
                             AND r3.status NOT IN ('completed','cancelled')) THEN 'ส่งซ่อม'
              WHEN EXISTS (SELECT 1 FROM equipment_movements em3 
                           WHERE em3.item_id = i.item_id 
                             AND em3.movement_type IN ('maintenance','disposal')) THEN 'บำรุงรักษา'
              ELSE 'พร้อมใช้งาน'
            END AS status_text,
            (SELECT image_path 
               FROM item_images ii 
              WHERE ii.item_id = i.item_id 
              ORDER BY is_primary DESC, sort_order, uploaded_at 
              LIMIT 1) as main_image
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        $where
        ORDER BY i.item_id DESC";
$result = mysqli_query($link, $sql);

/* ===== helper ไทยเดท ===== */
if (!function_exists('thaidate')) {
    function thaidate($format, $dateStr) {
        if (!$dateStr) return '';
        $ts = is_numeric($dateStr) ? (int)$dateStr : strtotime($dateStr);
        $thaiYear = (int)date('Y', $ts) + 543;
        $replaced = date($format, $ts);
        $replaced = str_replace(date('Y', $ts), (string)$thaiYear, $replaced);
        return $replaced;
    }
}

/* helper แปลง path รูป */
function normalize_image_url(string $p): string {
    $p = trim($p);
    if ($p === '') return '';
    if (preg_match('#^https?://#i', $p)) return $p;
    if ($p[0] !== '/' && strpos($p, 'uploads/') !== 0) {
        $p = 'uploads/' . $p;
    }
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return ($base === '/' ? '' : $base) . '/' . ltrim($p, '/\\');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>พิมพ์รายการครุภัณฑ์</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* Screen defaults */
    body { font-family: "Kanit","Prompt",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; font-size: 12px; }
    table { font-size: 11px; }
    th, td { padding: 4px 6px; vertical-align: middle; }
    img.thumb { max-width: 50px; max-height: 50px; object-fit: cover; border-radius: 4px; }
    .print-header { display:flex; justify-content:space-between; align-items:center; margin:16px 0; }
    .print-header h4 { font-size: 16px; }
    .meta { font-size: 10px; color:#555; }

    /* Reduce size specifically for print */
    @media print {
        @page { size: A4; margin: 8mm; }
        html, body { font-size: 10px; }
        table { font-size: 9px; }
        th, td { padding: 2px 4px; }
        img.thumb { max-width: 40px; max-height: 40px; }
        .print-header { margin: 8px 0; }
        .print-header h4 { font-size: 14px; }
        .noprint { display:none !important; }
        table { page-break-inside:auto; }
        tr { page-break-inside:avoid; page-break-after:auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        /* Most Chromium-based browsers honor zoom in print */
        body { zoom: 0.9; }
    }
</style>
</head>
<body class="p-3">

<div class="print-header">
  <div class="meta">
    พิมพ์เมื่อ: <?= thaidate('j M Y H:i', date('Y-m-d H:i:s')) ?><br>
    <?php
      $filters = [];
      if ($search !== '')        $filters[] = "ค้นหา: " . htmlspecialchars($search);

      // แสดงสถานะเป็นไทยเสมอ
      if ($status_code !== '') {
          $status_th = $code2th[$status_code] ?? $raw_status;
          $filters[] = "สถานะ: " . htmlspecialchars($status_th);
      }

      if ($category_id > 0) {
          $cat_res = mysqli_query($link, "SELECT category_name FROM categories WHERE category_id = " . intval($category_id));
          if ($cat_row = mysqli_fetch_assoc($cat_res)) {
              $filters[] = "หมวดหมู่: " . htmlspecialchars($cat_row['category_name']);
          }
      }

      if ($location_like !== '') $filters[] = "ตำแหน่ง: " . htmlspecialchars($location_like);

      // แผนกหลัก/ย่อย + ประเภทบริการ (แสดงเฉพาะข้อความ)
      if ($sub_department > 0) {
          $q = mysqli_query($link, "SELECT d.department_name, t.type_name, t.service_status FROM departments d LEFT JOIN type_service_clinic t ON d.type_service_id = t.id WHERE d.department_id = " . intval($sub_department));
          if ($r = $q ? mysqli_fetch_assoc($q) : null) {
              $filters[] = "แผนกย่อย: " . htmlspecialchars($r['department_name']);
              if (!empty($r['type_name'])) {
                  $filters[] = "ประเภทบริการ: " . htmlspecialchars($r['type_name'] . ' - ' . ($r['service_status'] ?? ''));
              }
          }
      } elseif ($main_department > 0) {
          $q = mysqli_query($link, "SELECT department_name FROM departments WHERE department_id = " . intval($main_department));
          if ($r = $q ? mysqli_fetch_assoc($q) : null) {
              $filters[] = "แผนกหลัก: " . htmlspecialchars($r['department_name']);
          }
      }

      echo !empty($filters) ? "ตัวกรอง: " . implode(" , ", $filters) : "ตัวกรอง: ทั้งหมด";
    ?>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-bordered table-sm align-middle">
    <thead>
      <tr class="text-center">
        <th>ลำดับ</th>
        <th>เลขครุภัณฑ์</th>
        <th>Serial</th>
        <th>ยี่ห้อ</th>
        <th>รุ่น</th>
        <th>หมวดหมู่</th>
        <th>รูป</th>
        <th>ตำแหน่ง</th>
        <th>สถานะ</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $count = 0;
      while($row = mysqli_fetch_assoc($result)):
        $count++;
        $img_url = '';
        if (!empty($row['main_image'])) {
            $img_url = normalize_image_url($row['main_image']);
        } elseif (!empty($row['image'])) {
            $img_url = normalize_image_url($row['image']);
        }
      ?>
      <tr>
        <td class="text-center"><?= $count ?></td>
        <td><?= htmlspecialchars($row['item_number']) ?></td>
        <td><?= htmlspecialchars($row['serial_number'] ?? '') ?></td>
        <td><?= htmlspecialchars($row['brand'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['model_name'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
        <td class="text-center">
          <?php if ($img_url): ?>
            <img src="<?= htmlspecialchars($img_url) ?>" class="thumb" alt="รูป">
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($row['location'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['status_text']) ?></td>
      </tr>
      <?php endwhile; ?>

      <?php if ($count===0): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">ไม่พบข้อมูลตามตัวกรอง</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="mt-2">
  รวมทั้งหมด: <?= number_format($count) ?> รายการ
</div>

<script>
  window.addEventListener('load', () => {
    // สั่งพิมพ์อัตโนมัติเมื่อโหลดหน้า
    window.print();
  });

  // หลังจากปิดหน้าต่างพิมพ์ (ทั้งพิมพ์จริงหรือกดยกเลิก)
  window.onafterprint = function() {
    // กลับไปยังหน้า items.php พร้อมพารามิเตอร์ตัวกรองเดิม
    window.location.href = "items.php?<?= htmlspecialchars(http_build_query($_GET), ENT_QUOTES, 'UTF-8') ?>";
  };
</script>

</body>
</html>
