<?php
require_once '../config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) { header("location: ../login.php"); exit; }
if ($_SESSION["role"] === 'staff') { header('Location: ../borrowings.php'); exit; }

/* ===== Charset/Collation ===== */
mysqli_set_charset($link, 'utf8mb4');
mysqli_query($link, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($link, "SET collation_connection = 'utf8mb4_unicode_ci'");

/* ===== รับค่าตัวกรอง (GET) ===== */
$start_date   = $_GET['start_date']   ?? '';
$end_date     = $_GET['end_date']     ?? '';
$model_name   = $_GET['model_name']   ?? '';
$item_number  = $_GET['item_number']  ?? '';
$from_loc     = $_GET['from_location']?? '';
$to_loc       = $_GET['to_location']  ?? '';
$operator_kw  = $_GET['operator']     ?? '';
$q            = $_GET['q']            ?? '';
$limit        = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0 || $limit > 1000) $limit = 10;

/* ===== Helper แสดงประเภท ===== */
if (!function_exists('get_movement_type_text')) {
  function get_movement_type_text($type){
    $m=['borrow'=>'การยืม','return'=>'การคืน','transfer'=>'การโอนย้าย','maintenance'=>'การซ่อมบำรุง','disposal'=>'การจำหน่าย','purchase'=>'การจัดซื้อ','adjustment'=>'การปรับปรุง','ownership_transfer'=>'โอนสิทธิ'];
    return $m[$type] ?? $type;
  }
}
if (!function_exists('get_movement_type_badge')) {
  function get_movement_type_badge($type){
    $b=['borrow'=>'bg-primary','return'=>'bg-success','transfer'=>'bg-info','maintenance'=>'bg-warning','disposal'=>'bg-danger','purchase'=>'bg-secondary','adjustment'=>'bg-dark','ownership_transfer'=>'bg-secondary'];
    $cls=$b[$type] ?? 'bg-secondary';
    return '<span class="badge '.$cls.'">'.get_movement_type_text($type).'</span>';
  }
}

/* ===== Subquery: equipment_movements ===== */
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
    /* reserve for union */
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_name_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_name_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS from_user_dept_r,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS to_user_dept_r
  FROM equipment_movements m
";

/* ===== Subquery: equipment_history (โอนสิทธิ) ===== */
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
    /* old/new เป็นตัวเลขหรือชื่อ */
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

/* ===== ฐานข้อมูลหลัง UNION + JOIN รายละเอียด ===== */
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

/* ===== ตัวกรองแบบ prepared ===== */
$types = '';
$params = [];

if ($start_date !== '') { $sql.=" AND mv.movement_date >= ? "; $types.='s'; $params[] = $start_date.' 00:00:00'; }
if ($end_date   !== '') { $sql.=" AND mv.movement_date <= ? "; $types.='s'; $params[] = $end_date.' 23:59:59'; }
if ($model_name !== '') { $sql.=" AND i.model_name COLLATE utf8mb4_unicode_ci LIKE ? "; $types.='s'; $params[] = '%'.$model_name.'%'; }
if ($item_number!== '') { $sql.=" AND i.item_number COLLATE utf8mb4_unicode_ci LIKE ? "; $types.='s'; $params[] = '%'.$item_number.'%'; }
if ($from_loc   !== '') { $sql.=" AND mv.from_location COLLATE utf8mb4_unicode_ci LIKE ? "; $types.='s'; $params[] = '%'.$from_loc.'%'; }
if ($to_loc     !== '') { $sql.=" AND mv.to_location   COLLATE utf8mb4_unicode_ci LIKE ? "; $types.='s'; $params[] = '%'.$to_loc.'%'; }
if ($operator_kw!== '') { $sql.=" AND uc.full_name     COLLATE utf8mb4_unicode_ci LIKE ? "; $types.='s'; $params[] = '%'.$operator_kw.'%'; }

if ($q !== '') {
  $sql .= " AND (
      i.item_number COLLATE utf8mb4_unicode_ci LIKE ?
      OR i.model_name COLLATE utf8mb4_unicode_ci LIKE ?
      OR i.brand      COLLATE utf8mb4_unicode_ci LIKE ?
      OR mv.notes     COLLATE utf8mb4_unicode_ci LIKE ?
      OR mv.from_location COLLATE utf8mb4_unicode_ci LIKE ?
      OR mv.to_location   COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(uf.full_name, mv.from_user_name_r)  COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(ut.full_name, mv.to_user_name_r)    COLLATE utf8mb4_unicode_ci LIKE ?
      OR uc.full_name COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(uf.department, mv.from_user_dept_r) COLLATE utf8mb4_unicode_ci LIKE ?
      OR COALESCE(ut.department, mv.to_user_dept_r)   COLLATE utf8mb4_unicode_ci LIKE ?
  )";
  $like = '%'.$q.'%';
  // 11 ครั้ง
  for ($i=0; $i<11; $i++){ $types.='s'; $params[]=$like; }
}

$sql .= " ORDER BY mv.movement_date DESC LIMIT ? ";
$types .= 'i'; $params[] = $limit;

$stmt = mysqli_prepare($link, $sql);
if (!$stmt) { http_response_code(500); die('Database error: '.htmlspecialchars(mysqli_error($link))); }
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>พิมพ์รายงานการเคลื่อนไหว</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
:root{
  --bg:#f6f7fb; --card:#ffffff; --ink:#0b0b0b; --line:#e6e6ef;
  --muted:#666; --chip:#f0f0f5; --shadow:0 6px 20px rgba(0,0,0,.08);
}
*{box-sizing:border-box} html,body{height:100%}
body{margin:0; padding:24px; background:var(--bg); color:var(--ink);
  font-family:'Kanit','TH SarabunPSK','Angsana New',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; font-size:16px;
  -webkit-print-color-adjust:exact; print-color-adjust:exact;}
.container{max-width:1100px;margin:0 auto}
.header{display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;}
.brand{display:flex; align-items:center; gap:12px}
.brand-logo{width:40px; height:40px; border-radius:12px; background:#000; box-shadow:var(--shadow); position:relative;}
.brand-logo:before{content:""; position:absolute; inset:8px; border-radius:8px; background:linear-gradient(135deg,#999,#fff)}
.title h1{margin:0; font-size:28px; font-weight:700; letter-spacing:.3px; color:#000}
.subtitle{margin-top:4px; font-size:15px; color:var(--muted)}
.badges{display:flex; gap:8px; flex-wrap:wrap}
.badge{background:var(--chip); border:1px solid var(--line); padding:6px 10px; border-radius:999px; font-size:12px; color:#333}
.card{background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); padding:16px 16px 12px; margin-bottom:16px;}
.card h2{margin:0 0 10px 0; font-size:18px}
.filter-grid{display:grid; gap:10px; grid-template-columns: repeat(12, 1fr);}
.form-row{display:contents}
.form-group{grid-column: span 3}
.form-group.w2{grid-column: span 2}
.form-group.w4{grid-column: span 4}
.form-group.w6{grid-column: span 6}
.form-group.w12{grid-column: span 12}
.label{display:block; font-size:12px; color:#444; margin:2px 2px 6px}
.input{width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--line); background:#fff; font-size:14px; outline:none; transition:border .15s, box-shadow .15s}
.input:focus{border-color:#333; box-shadow:0 0 0 3px rgba(0,0,0,.06)}
.actions{display:flex; gap:8px; margin-top:6px; flex-wrap:wrap}
.btn{appearance:none; border:1px solid #000; background:#000; color:#fff; padding:10px 14px; border-radius:10px; font-size:14px; cursor:pointer; transition:transform .05s ease, opacity .15s ease;}
.btn:active{transform:translateY(1px)} .btn.secondary{background:#fff; color:#000; border-color:#000} .btn.ghost{background:transparent; border-color:#e6e6ef; color:#111}
.btn:hover{opacity:.9} .btn svg{vertical-align:-2px; margin-right:6px}
.table-wrap{background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); overflow:hidden}
.table-head{display:flex; align-items:center; justify-content:space-between; gap:8px; padding:14px 16px; border-bottom:1px solid var(--line); background:#fbfbfd;}
.table-head h3{margin:0; font-size:16px} .meta{color:#555; font-size:13px}
.table-scroll{max-height:70vh; overflow:auto}
table{border-collapse:separate; border-spacing:0; width:100%; font-size:14px}
thead th{position:sticky; top:0; background:#fff; z-index:2; text-align:left; padding:12px 14px; border-bottom:1px solid var(--line); white-space:nowrap; font-weight:600;}
tbody td{padding:10px 14px; border-bottom:1px solid var(--line); white-space:nowrap;}
tbody tr:nth-child(even) td{background:#fafafa} tbody tr:hover td{background:#f2f3f7}
td.num{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-variant-numeric:tabular-nums}
td.model-col{max-width:360px; text-overflow:ellipsis; overflow:hidden}
td.note-col{max-width:420px; text-overflow:ellipsis; overflow:hidden}
td.fullname-col{max-width:240px; text-overflow:ellipsis; overflow:hidden}
.badge{display:inline-block; padding:.35em .6em; border-radius:999px; font-size:12px; line-height:1}
.fab{position:fixed; right:22px; bottom:22px; z-index:10; background:#000; color:#fff; border:1px solid #000; border-radius:999px; padding:12px 16px; box-shadow:var(--shadow); cursor:pointer; font-size:14px;}
.fab:hover{opacity:.92}
.footer{color:#555; text-align:center; font-size:13px; margin:14px 0 0}
@media (max-width: 1024px){ .form-group{grid-column: span 4} .form-group.w6{grid-column: span 6} }
@media (max-width: 720px){ body{padding:16px} .form-group, .form-group.w2, .form-group.w4, .form-group.w6, .form-group.w12{grid-column: span 12} .table-scroll{max-height:none} }
@media print{
  body{background:#fff; padding:0}
  .header .badges, .card, .fab, .table-head .actions, .brand-logo{display:none !important}
  .table-wrap{border:none; box-shadow:none} thead th{position:static} .table-scroll{max-height:none; overflow:visible}
  .title h1{ font-size:32px !important; }
}
</style>
</head>
<body>
<div class="container">

  <div class="header">
    <div class="brand">
      <div class="brand-logo"></div>
      <div class="title">
        <h1>รายงานการเคลื่อนไหวครุภัณฑ์</h1>
        <div class="subtitle">เครื่องมือกรอง + มุมมองพิมพ์ในหน้าเดียว</div>
      </div>
    </div>
    <div class="badges">
      <?php if($start_date || $end_date): ?>
        <span class="badge">วันที่: <?php echo htmlspecialchars($start_date ?: '—'); ?> – <?php echo htmlspecialchars($end_date ?: '—'); ?></span>
      <?php endif; ?>
      <?php if($model_name): ?><span class="badge">ครุภัณฑ์: “<?php echo htmlspecialchars($model_name); ?>”</span><?php endif; ?>
      <?php if($item_number): ?><span class="badge">เลข: “<?php echo htmlspecialchars($item_number); ?>”</span><?php endif; ?>
      <?php if($from_loc): ?><span class="badge">จาก: “<?php echo htmlspecialchars($from_loc); ?>”</span><?php endif; ?>
      <?php if($to_loc): ?><span class="badge">ไปยัง: “<?php echo htmlspecialchars($to_loc); ?>”</span><?php endif; ?>
      <?php if($operator_kw): ?><span class="badge">ผู้บันทึก: “<?php echo htmlspecialchars($operator_kw); ?>”</span><?php endif; ?>
      <?php if($q): ?><span class="badge">คำค้น: “<?php echo htmlspecialchars($q); ?>”</span><?php endif; ?>
      <span class="badge">จำนวน: <?php echo (int)$limit; ?></span>
    </div>
  </div>

  <!-- ฟอร์มตัวกรอง -->
  <div class="card">
    <h2>ตัวกรอง</h2>
    <form method="get">
      <div class="filter-grid">
        <div class="form-group w4">
          <label class="label">ตั้งแต่วันที่</label>
          <input class="input" type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="form-group w4">
          <label class="label">ถึงวันที่</label>
          <input class="input" type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <div class="form-group w4">
          <label class="label">จำนวนรายการ (แสดงสูงสุด)</label>
          <input class="input" type="number" name="limit" min="1" max="1000" value="<?php echo (int)$limit; ?>">
        </div>

        <div class="form-group w6">
          <label class="label">ครุภัณฑ์</label>
          <input class="input" type="text" name="model_name" placeholder="รุ่น/ชื่อครุภัณฑ์" value="<?php echo htmlspecialchars($model_name); ?>">
        </div>
        <div class="form-group w6">
          <label class="label">เลขครุภัณฑ์</label>
          <input class="input" type="text" name="item_number" placeholder="เช่น IT-001" value="<?php echo htmlspecialchars($item_number); ?>">
        </div>

        <div class="form-group w6">
          <label class="label">จาก (สถานที่เดิม)</label>
          <input class="input" type="text" name="from_location" placeholder="ห้อง/หน่วยงานเดิม" value="<?php echo htmlspecialchars($from_loc); ?>">
        </div>
        <div class="form-group w6">
          <label class="label">ไปยัง (สถานที่ใหม่)</label>
          <input class="input" type="text" name="to_location" placeholder="ห้อง/หน่วยงานใหม่" value="<?php echo htmlspecialchars($to_loc); ?>">
        </div>

        <div class="form-group w6">
          <label class="label">ผู้บันทึก</label>
          <input class="input" type="text" name="operator" placeholder="ชื่อ-สกุลผู้บันทึก" value="<?php echo htmlspecialchars($operator_kw); ?>">
        </div>
        <div class="form-group w6">
          <label class="label">คำค้น (ค้นหลายช่อง)</label>
          <input class="input" type="text" name="q" placeholder="รุ่น / เลข / หมายเหตุ / สถานที่ / ผู้ใช้ / แผนก" value="<?php echo htmlspecialchars($q); ?>">
        </div>
      </div>
      <div class="actions">
        <button class="btn" type="submit">กรองข้อมูล</button>
        <a class="btn secondary" href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>">ล้างตัวกรอง</a>
        <button class="btn ghost" type="button" onclick="toggleCompact()">โหมดตารางกะทัดรัด</button>
      </div>
    </form>
  </div>

  <!-- ตาราง -->
  <div class="table-wrap">
    <div class="table-head">
      <h3>ผลลัพธ์</h3>
      <div class="meta">
        <?php $cnt = ($result)? mysqli_num_rows($result) : 0; echo 'พบ '.$cnt.' รายการ • เรียงจากล่าสุด'; ?>
      </div>
      <div class="actions">
        <button class="btn secondary" type="button" onclick="window.print()">พิมพ์หน้านี้</button>
      </div>
    </div>
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>วันที่</th>
            <th>ประเภท</th>
            <th>ครุภัณฑ์</th>
            <th class="num">จำนวน</th>
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
              <td class="num"><?php echo $row['movement_date'] ? thaidate('j M Y H:i', $row['movement_date']) : '-'; ?></td>
              <td><?php echo get_movement_type_badge($row['movement_type']); ?></td>
              <td class="model-col">
                <strong><?php echo htmlspecialchars($row['item_number'] ?? ''); ?></strong><br>
                <small style="color:#666"><?php echo htmlspecialchars(trim(($row['model_name'] ?? '').' - '.($row['brand'] ?? ''), ' -')); ?></small>
              </td>
              <td class="num"><?php echo (int)($row['quantity'] ?? 0); ?></td>
              <td>
                <?php if (!empty($row['from_location'])): ?>
                  <span style="color:#d6336c">📍</span> <?php echo htmlspecialchars($row['from_location']); ?><br>
                <?php endif; ?>
                <?php if (!empty($row['from_user_name'])): ?>
                  👤 <strong><?php echo htmlspecialchars($row['from_user_name']); ?></strong>
                  <?php if (!empty($row['from_user_department'])): ?>
                    <br><small style="color:#666">🏢 <?php echo htmlspecialchars($row['from_user_department']); ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($row['to_location'])): ?>
                  <span style="color:#2b8a3e">📍</span> <?php echo htmlspecialchars($row['to_location']); ?><br>
                <?php endif; ?>
                <?php if (!empty($row['to_user_name'])): ?>
                  👤 <strong><?php echo htmlspecialchars($row['to_user_name']); ?></strong>
                  <?php if (!empty($row['to_user_department'])): ?>
                    <br><small style="color:#666">🏢 <?php echo htmlspecialchars($row['to_user_department']); ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td class="note-col"><?php echo $row['notes'] ? '<span style="color:#666">'.htmlspecialchars($row['notes']).'</span>' : '<span style="color:#999">-</span>'; ?></td>
              <td class="fullname-col">
                <strong><?php echo htmlspecialchars($row['created_by_name'] ?? ''); ?></strong>
                <?php if (!empty($row['created_by_department'])): ?>
                  <br><small style="color:#666">🏢 <?php echo htmlspecialchars($row['created_by_department']); ?></small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center; color:#777; padding:28px 12px;">ไม่พบข้อมูลตามเงื่อนไข</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="footer">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี | © 2025
  </div>
</div>

<!-- ปุ่มลอยพิมพ์ -->
<button class="fab" type="button" onclick="window.print()">🖨️ พิมพ์</button>

<script>
  // โหมดพิมพ์อัตโนมัติเมื่อ ?print=1
  (function(){
    const usp = new URLSearchParams(location.search);
    if (usp.get('print') === '1') {
      window.onload = function(){ window.print(); };
      window.onafterprint = function(){ window.close(); };
    }
  })();

  // โหมดตารางกะทัดรัด
  function toggleCompact(){
    document.body.classList.toggle('compact');
    const styleId = 'compact-style';
    if (!document.getElementById(styleId)){
      const s = document.createElement('style');
      s.id = styleId;
      s.textContent = `
        body.compact tbody td{ padding:6px 10px; font-size:13px }
        body.compact thead th{ padding:8px 10px; font-size:13px }
        body.compact .table-scroll{ max-height:80vh }
      `;
      document.head.appendChild(s);
    }
  }
</script>
</body>
</html>
