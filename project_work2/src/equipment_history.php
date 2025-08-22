<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) { header("location: login.php"); exit; }
if ($_SESSION["role"] === 'staff') { header('Location: borrowings.php'); exit; }

/* ===== Charset/Collation (ป้องกัน mix collation) ===== */
mysqli_set_charset($link, 'utf8mb4');
mysqli_query($link, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($link, "SET collation_connection = 'utf8mb4_unicode_ci'");

/* ===== ตัวกรองจาก GET ===== */
$item_filter = isset($_GET['item_id'])   ? (int)$_GET['item_id'] : 0;
$type_filter = isset($_GET['type'])      ? trim($_GET['type'])   : '';
$date_from   = isset($_GET['date_from']) ? trim($_GET['date_from']): '';
$date_to     = isset($_GET['date_to'])   ? trim($_GET['date_to'])  : '';
$user_filter = isset($_GET['user_id'])   ? (int)$_GET['user_id'] : 0;
$q           = isset($_GET['q'])         ? trim($_GET['q'])      : '';

/* ===== Subquery: equipment_movements (บังคับ collation) ===== */
$sub_movements = "
  SELECT
    m.movement_date,
    CAST(m.movement_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS movement_type,
    m.item_id,
    m.quantity,
    m.from_user_id,
    m.to_user_id,
    CONVERT(m.from_location USING utf8mb4) COLLATE utf8mb4_unicode_ci AS from_location,
    CONVERT(m.to_location   USING utf8mb4) COLLATE utf8mb4_unicode_ci AS to_location,
    CONVERT(m.notes        USING utf8mb4) COLLATE utf8mb4_unicode_ci AS notes,
    m.created_by,
    /* ช่องสำรองเพื่อ UNION ให้ type ตรง */
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_name_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_name_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_dept_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_dept_r
  FROM equipment_movements m
";

/* ===== Subquery: equipment_history (เฉพาะโอนสิทธิ) ===== */
$sub_history = "
  SELECT
    h.change_date AS movement_date,
    CAST('ownership_transfer' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS movement_type,
    h.item_id,
    1 AS quantity,
    CASE WHEN h.old_value REGEXP '^[0-9]+$' THEN CAST(h.old_value AS UNSIGNED) ELSE NULL END AS from_user_id,
    CASE WHEN h.new_value REGEXP '^[0-9]+$' THEN CAST(h.new_value AS UNSIGNED) ELSE NULL END AS to_user_id,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_location,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_location,
    CONVERT(h.remarks USING utf8mb4) COLLATE utf8mb4_unicode_ci AS notes,
    h.changed_by AS created_by,
    /* กรณี old/new เป็นชื่อ string */
    CONVERT(COALESCE(uo_id.full_name, uo_name.full_name) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_name_r,
    CONVERT(COALESCE(un_id.full_name, un_name.full_name) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_name_r,
    CONVERT(COALESCE(uo_id.department, uo_name.department) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_dept_r,
    CONVERT(COALESCE(un_id.department, un_name.department) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_dept_r
  FROM equipment_history h
  LEFT JOIN users uo_id  ON (h.old_value REGEXP '^[0-9]+$' AND uo_id.user_id = CAST(h.old_value AS UNSIGNED))
  LEFT JOIN users un_id  ON (h.new_value REGEXP '^[0-9]+$' AND un_id.user_id = CAST(h.new_value AS UNSIGNED))
  LEFT JOIN users uo_name ON (NOT h.old_value REGEXP '^[0-9]+$' AND uo_name.full_name COLLATE utf8mb4_unicode_ci = h.old_value COLLATE utf8mb4_unicode_ci)
  LEFT JOIN users un_name ON (NOT h.new_value REGEXP '^[0-9]+$' AND un_name.full_name COLLATE utf8mb4_unicode_ci = h.new_value COLLATE utf8mb4_unicode_ci)
  WHERE h.action_type = 'transfer_ownership'
";

/* ===== UNION ALL แล้ว join รายละเอียด ===== */
$base_union = "($sub_movements UNION ALL $sub_history) AS mv";

$sql = "
  SELECT
    mv.movement_date, mv.movement_type, mv.item_id, mv.quantity,
    mv.from_user_id, mv.to_user_id, mv.from_location, mv.to_location, mv.notes, mv.created_by,
    i.item_number, i.model_name, i.brand,
    COALESCE(uf.full_name, mv.from_user_name_r)  AS from_user_name,
    COALESCE(ut.full_name, mv.to_user_name_r)    AS to_user_name,
    COALESCE(uf.department, mv.from_user_dept_r) AS from_user_department,
    COALESCE(ut.department, mv.to_user_dept_r)   AS to_user_department,
    uc.full_name AS created_by_name,
    uc.department AS created_by_department
  FROM $base_union
  LEFT JOIN items i ON i.item_id = mv.item_id
  LEFT JOIN users uf ON uf.user_id = mv.from_user_id
  LEFT JOIN users ut ON ut.user_id = mv.to_user_id
  LEFT JOIN users uc ON uc.user_id = mv.created_by
  WHERE 1=1
";

$params = [];
if ($item_filter > 0) { $sql .= " AND mv.item_id = ? ";             $params[] = (string)$item_filter; }
if ($type_filter !== '') { $sql .= " AND mv.movement_type = ? ";    $params[] = $type_filter; }
if ($date_from !== '') {   $sql .= " AND DATE(mv.movement_date) >= ? "; $params[] = $date_from; }
if ($date_to !== '') {     $sql .= " AND DATE(mv.movement_date) <= ? "; $params[] = $date_to; }
if ($user_filter > 0) {
  $sql .= " AND (mv.from_user_id = ? OR mv.to_user_id = ?) ";
  $params[] = (string)$user_filter; $params[] = (string)$user_filter;
}

/* ===== ค้นหาด้วยคีย์เวิร์ด (LIKE) ===== */
if ($q !== '') {
  $sql .= "
    AND (
      i.item_number COLLATE utf8mb4_unicode_ci LIKE ?
      OR i.model_name COLLATE utf8mb4_unicode_ci LIKE ?
      OR i.brand      COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(uf.full_name, mv.from_user_name_r)  COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(ut.full_name, mv.to_user_name_r)    COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(uf.department, mv.from_user_dept_r) COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(ut.department, mv.to_user_dept_r)   COLLATE utf8mb4_unicode_ci LIKE ?
      OR mv.from_location COLLATE utf8mb4_unicode_ci LIKE ?
      OR mv.to_location   COLLATE utf8mb4_unicode_ci LIKE ?
      OR mv.notes         COLLATE utf8mb4_unicode_ci LIKE ?
      OR uc.full_name     COLLATE utf8mb4_unicode_ci LIKE ?
    )
  ";
  $like = '%'.$q.'%';
  // push 11 ตัว
  array_push($params, $like,$like,$like,$like,$like,$like,$like,$like,$like,$like,$like);
}

$sql .= " ORDER BY mv.movement_date DESC ";

/* ===== Execute ===== */
$stmt = mysqli_prepare($link, $sql);
if (!empty($params)) {
  $types = str_repeat('s', count($params));
  mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

/* ===== dropdown data ===== */
$items_result = mysqli_query($link, "SELECT item_id, item_number, model_name, brand FROM items WHERE item_number IS NOT NULL AND item_number!='' ORDER BY item_number");
$users_result = mysqli_query($link, "SELECT user_id, full_name, department FROM users ORDER BY full_name");

/* ===== helpers ===== */
function get_movement_type_text($type){
  $m = ['borrow'=>'การยืม','return'=>'การคืน','transfer'=>'การโอนย้าย','maintenance'=>'การซ่อมบำรุง','disposal'=>'การจำหน่าย','purchase'=>'การจัดซื้อ','adjustment'=>'การปรับปรุง','ownership_transfer'=>'โอนสิทธิ'];
  return $m[$type] ?? $type;
}
function get_movement_type_badge($type){
  $b=['borrow'=>'bg-primary','return'=>'bg-success','transfer'=>'bg-info','maintenance'=>'bg-warning','disposal'=>'bg-danger','purchase'=>'bg-secondary','adjustment'=>'bg-dark','ownership_transfer'=>'bg-secondary'];
  $cls=$b[$type] ?? 'bg-secondary';
  return '<span class="badge '.$cls.'">'.get_movement_type_text($type).'</span>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ประวัติการเคลื่อนไหวของครุภัณฑ์</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="sidebar.css">
<link rel="stylesheet" href="common-ui.css">
<style>
  .filter-card .form-label{font-weight:600}
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Mobile Navbar -->
    <nav class="navbar navbar-light bg-light d-md-none mb-3">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar"><span class="navbar-toggler-icon"></span></button>
        <span class="navbar-brand mb-0 h1">ประวัติการเคลื่อนไหว</span>
      </div>
    </nav>

    <?php include 'sidebar.php'; ?>

    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content mt-4 mt-md-5">

        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
          <h2 class="mb-0"><i class="fas fa-history me-2"></i>ประวัติการเคลื่อนไหวของครุภัณฑ์</h2>
          <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> วันที่ปัจจุบัน: <?= thaidate('j M Y', date('Y-m-d')) ?></div>
        </div>

        <!-- ฟอร์มกรอง/ค้นหา -->
        <div class="filter-card p-4 mb-3 bg-white rounded shadow-sm">
          <form method="get" class="row g-3">
            <div class="col-lg-3 col-md-4">
              <label class="form-label"><i class="fas fa-search me-1"></i> คีย์เวิร์ด</label>
              <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="เลขครุภัณฑ์/ชื่อ/แผนก/สถานที่/หมายเหตุ...">
            </div>
            <div class="col-lg-3 col-md-4">
              <label class="form-label">ประเภทเหตุการณ์</label>
              <select name="type" class="form-select">
                <option value="">— ทั้งหมด —</option>
                <?php
                  $types = ['borrow','return','transfer','maintenance','disposal','purchase','adjustment','ownership_transfer'];
                  foreach($types as $t){
                    $sel = ($type_filter===$t)?'selected':'';
                    echo "<option value=\"$t\" $sel>".get_movement_type_text($t)."</option>";
                  }
                ?>
              </select>
            </div>
            <div class="col-lg-3 col-md-4">
              <label class="form-label">ครุภัณฑ์</label>
              <select name="item_id" class="form-select">
                <option value="0">— ทั้งหมด —</option>
                <?php if ($items_result): while($it=mysqli_fetch_assoc($items_result)): ?>
                  <?php
                    $label=$it['item_number']; 
                    if(!empty($it['model_name'])) $label.=" - ".$it['model_name'];
                    if(!empty($it['brand'])) $label.=" (".$it['brand'].")";
                  ?>
                  <option value="<?= (int)$it['item_id'] ?>" <?= $item_filter==(int)$it['item_id']?'selected':''; ?>>
                    <?= htmlspecialchars($label) ?>
                  </option>
                <?php endwhile; endif; ?>
              </select>
            </div>
            <div class="col-lg-3 col-md-4">
              <label class="form-label">ผู้ใช้ (จาก/ถึง)</label>
              <select name="user_id" class="form-select">
                <option value="0">— ทั้งหมด —</option>
                <?php if ($users_result): while($u=mysqli_fetch_assoc($users_result)): ?>
                  <option value="<?= (int)$u['user_id'] ?>" <?= $user_filter==(int)$u['user_id']?'selected':''; ?>>
                    <?= htmlspecialchars($u['full_name'] ?: 'ไม่ระบุชื่อ') ?>
                    <?= $u['department'] ? ' - '.htmlspecialchars($u['department']) : '' ?>
                  </option>
                <?php endwhile; endif; ?>
              </select>
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label">ตั้งแต่วันที่</label>
              <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label">ถึงวันที่</label>
              <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div class="col-md-3 align-self-end">
              <button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> กรอง</button>
            </div>
            <div class="col-md-3 align-self-end">
              <a href="equipment_history.php" class="btn btn-outline-secondary w-100"><i class="fas fa-undo me-1"></i> ล้างตัวกรอง</a>
            </div>
          </form>
        </div>

        <!-- ตาราง -->
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height:60vh; overflow-y:auto;">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="sticky-top bg-white" style="z-index:1020;">
                  <tr>
                    <th>วันที่</th>
                    <th>ประเภท</th>
                    <th>ครุภัณฑ์</th>
                    <th>จำนวน</th>
                    <th>จาก (ผู้ใช้/แผนก/สถานที่)</th>
                    <th>ไปยัง (ผู้ใช้/แผนก/สถานที่)</th>
                    <th>หมายเหตุ</th>
                    <th>ผู้บันทึก (แผนก)</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                  <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= thaidate('j M Y H:i', $row['movement_date']); ?></td>
                    <td><?= get_movement_type_badge($row['movement_type']); ?></td>
                    <td>
                      <strong><?= htmlspecialchars($row['item_number'] ?? ''); ?></strong><br>
                      <small class="text-muted"><?= htmlspecialchars(trim(($row['model_name'] ?? '').' - '.($row['brand'] ?? ''), ' -')); ?></small>
                    </td>
                    <td><?= (int)($row['quantity'] ?? 0); ?></td>
                    <td>
                      <?php if (!empty($row['from_location'])): ?>
                        <i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($row['from_location']); ?><br>
                      <?php endif; ?>
                      <?php if (!empty($row['from_user_name'])): ?>
                        <i class="fas fa-user text-primary"></i> <strong><?= htmlspecialchars($row['from_user_name']); ?></strong>
                        <?php if (!empty($row['from_user_department'])): ?>
                          <br><small class="text-muted"><i class="fas fa-building"></i> <?= htmlspecialchars($row['from_user_department']); ?></small>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($row['to_location'])): ?>
                        <i class="fas fa-map-marker-alt text-success"></i> <?= htmlspecialchars($row['to_location']); ?><br>
                      <?php endif; ?>
                      <?php if (!empty($row['to_user_name'])): ?>
                        <i class="fas fa-user text-success"></i> <strong><?= htmlspecialchars($row['to_user_name']); ?></strong>
                        <?php if (!empty($row['to_user_department'])): ?>
                          <br><small class="text-muted"><i class="fas fa-building"></i> <?= htmlspecialchars($row['to_user_department']); ?></small>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td><?= $row['notes'] ? '<span class="text-muted">'.htmlspecialchars($row['notes']).'</span>' : '<span class="text-muted">-</span>'; ?></td>
                    <td class="user-block">
                      <strong><?= htmlspecialchars($row['created_by_name'] ?? ''); ?></strong>
                      <?php if (!empty($row['created_by_department'])): ?>
                        <br><small class="text-muted"><i class="fas fa-building"></i> <?= htmlspecialchars($row['created_by_department']); ?></small>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox fa-3x mb-3"></i><br>ไม่พบข้อมูล</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div><!-- /.main-content -->
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<footer style="text-align:center; padding:5px; font-size:14px; color:#555; background-color:#f9f9f9;">
  <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height:40px; vertical-align:middle; margin-right:10px;">
  พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี | © 2025
</footer>
</body>
</html>
