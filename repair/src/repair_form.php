<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: login.php'); exit;
}

/* ===== ตัวช่วยแสดง SweetAlert หลัง POST ===== */
$swal = null; // ['icon'=>'success|error|warning|info','title'=>'','text'=>'','html'=>'']

/* ===== บังคับให้โปรไฟล์ผู้ใช้กรอกข้อมูลให้ครบก่อนแจ้งซ่อม ===== */
try {
    $uid_chk = intval($_SESSION['user_id']);
    $res_u = $conn->query("SELECT username, full_name, email, department, position FROM users WHERE user_id = $uid_chk LIMIT 1");
    $u = $res_u ? $res_u->fetch_assoc() : null;
    $required_ok = true;
    if (!$u) {
        $required_ok = false;
    } else {
        $usernameOk   = !empty(trim($u['username'] ?? ''));
        $fullNameOk   = !empty(trim($u['full_name'] ?? ''));
        $emailVal     = trim($u['email'] ?? '');
        $emailOk      = $emailVal !== '' && filter_var($emailVal, FILTER_VALIDATE_EMAIL);
        $deptOk       = !empty(trim($u['department'] ?? ''));
        $positionOk   = !empty(trim($u['position'] ?? ''));
        $required_ok  = $usernameOk && $fullNameOk && $emailOk && $deptOk && $positionOk;
    }
    if (!$required_ok) {
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>'
            . '</head><body>'
            . '<script>'
            . 'Swal.fire({'
            . '  title: "โปรไฟล์ยังไม่ครบ",'
            . '  html: "กรุณากรอกข้อมูลโปรไฟล์ให้ครบก่อนแจ้งซ่อม<br>(ชื่อผู้ใช้, ชื่อ-นามสกุล, อีเมล, แผนก, ตำแหน่ง)",'
            . '  icon: "warning",'
            . '  confirmButtonText: "ไปกรอกโปรไฟล์",'
            . '  allowOutsideClick: false, allowEscapeKey: false'
            . '}).then(function(){ window.location = "profile.php"; });'
            . '</script></body></html>';
        exit;
    }
} catch (Throwable $e) {
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>'
        . '</head><body>'
        . '<script>'
        . 'Swal.fire({'
        . '  title: "เกิดข้อผิดพลาด",'
        . '  html: "ไม่สามารถตรวจสอบข้อมูลผู้ใช้ได้ กรุณาตรวจสอบโปรไฟล์",'
        . '  icon: "error",'
        . '  confirmButtonText: "ไปที่โปรไฟล์",'
        . '  allowOutsideClick: false, allowEscapeKey: false'
        . '}).then(function(){ window.location = "profile.php"; });'
        . '</script></body></html>';
    exit;
}

/* === แผนที่สถานะ (อังกฤษ -> ไทย) สำหรับแจ้งซ้ำ === */
$status_map = [
    'received'             => 'รับเรื่อง',
    'evaluate_it'          => 'ประเมิน (โดย IT)',
    'evaluate_repairable'  => 'ประเมิน: ซ่อมได้โดย IT',
    'evaluate_external'    => 'ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก',
    'evaluate_disposal'    => 'ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย',
    'external_repair'      => 'ซ่อมไม่ได้ - ส่งซ่อมภายนอก',
    'repair_completed'     => 'ซ่อมเสร็จ',
    'waiting_delivery'     => 'รอส่งมอบ',
    'delivered'            => 'ส่งมอบ',
    'cancelled'            => 'ยกเลิก',
    // legacy/extra
    'pending'              => 'รอดำเนินการ',
    'in_progress'          => 'กำลังซ่อม',
    'done'                 => 'ซ่อมเสร็จ',
    ''                     => 'รอดำเนินการ',
];

// get department name
$dept_name = '-';
$user_id = intval($_SESSION['user_id']);
$user_res = $conn->query("SELECT department FROM users WHERE user_id = $user_id LIMIT 1");
if ($user_res && $user_res->num_rows) {
    $dept_name = $user_res->fetch_assoc()['department'];
}

$error = null;
$success = false;

/* ===== Handle form submit ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_name = $dept_name;

    if (isset($_POST['item_id']) && $_POST['item_id'] === 'other') {
        $item_id       = null;
        $asset_number  = trim($_POST['asset_number'] ?? '');
        $serial_number = trim($_POST['other_serial_number'] ?? '');
        $brand         = trim($_POST['other_brand'] ?? '');
        $model         = trim($_POST['other_model'] ?? '');
        $desc          = trim($_POST['issue_description'] ?? '');
    } else {
        $item_id       = isset($_POST['item_id']) && $_POST['item_id'] !== '' ? intval($_POST['item_id']) : null;
        $asset_number  = trim($_POST['asset_number'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $brand         = trim($_POST['brand'] ?? '');
        $model         = trim($_POST['model_name'] ?? '');
        $desc          = trim($_POST['issue_description'] ?? '');
    }

    // ป้องกันแจ้งซ้ำ (ถ้าเคสล่าสุดยังไม่ cancelled/delivered)
    $dup_sql = "
        SELECT r.repair_id, COALESCE(latest.status, r.status) AS current_status, r.created_at
        FROM repairs r
        LEFT JOIN (
            SELECT rl1.repair_id, rl1.status
            FROM repair_logs rl1
            JOIN (
                SELECT repair_id, MAX(updated_at) AS max_updated
                FROM repair_logs
                GROUP BY repair_id
            ) rl2 ON rl1.repair_id = rl2.repair_id AND rl1.updated_at = rl2.max_updated
        ) latest ON latest.repair_id = r.repair_id
        WHERE
        (
            (? IS NOT NULL AND r.item_id = ?)
            OR (? <> '' AND r.asset_number = ?)
            OR (? <> '' AND r.serial_number = ?)
        )
        AND COALESCE(latest.status, r.status) NOT IN ('cancelled', 'delivered')
        ORDER BY r.created_at DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($dup_sql);
    $item_id_param = $item_id; // อาจเป็น null
    $stmt->bind_param('iissss',
        $item_id_param, $item_id_param,
        $asset_number, $asset_number,
        $serial_number, $serial_number
    );
    $stmt->execute();
    $dup_res = $stmt->get_result();
    if ($dup_res && $dup_res->num_rows > 0) {
        $dup = $dup_res->fetch_assoc();
        $status_en   = $dup['current_status'];
        $thai_status = $status_map[$status_en] ?? 'ไม่ระบุสถานะ';
        $error = "มีรายการแจ้งซ่อมค้างอยู่ (เลขที่ {$dup['repair_id']}, สถานะ: {$thai_status}) — ไม่สามารถแจ้งซ้ำได้จนกว่าจะยกเลิกหรือส่งมอบ";
    }
    $stmt->close();

    if (!$error) {
        // อัปโหลดรูป (ถ้ามี) — ปลอดภัยขึ้นเล็กน้อย
        $img = '';
        if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $base = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));
            $img = 'uploads/' . uniqid('rep_', true) . '_' . $base . ($ext ? '.'.$ext : '');
            @mkdir(dirname($img), 0777, true);
            @move_uploaded_file($_FILES['image']['tmp_name'], $img);
        }

        // บันทึกเคสใหม่
        $sql = "INSERT INTO repairs
                (item_id, reported_by, issue_description, image, asset_number, serial_number, location_name, brand, model_name)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql) or die('Prepare failed: '.$conn->error);
        $uid = intval($_SESSION['user_id']);
        $stmt->bind_param('iisssssss', $item_id, $uid, $desc, $img, $asset_number, $serial_number, $location_name, $brand, $model);
        $stmt->execute();
        $stmt->close();
        $success = true;

        // แจ้งเตือนภายนอก (ถ้ามีไฟล์)
        @require_once __DIR__ . '/send_discord_notification.php';

        // เตรียม SweetAlert สำเร็จ
        $swal = [
            'icon'  => 'success',
            'title' => 'แจ้งซ่อมสำเร็จ',
            'html'  => 'ระบบบันทึกคำขอของคุณแล้ว<br>เจ้าหน้าที่จะดำเนินการต่อไป'
        ];
    } else {
        // เตรียม SweetAlert แจ้งซ้ำ
        $swal = [
            'icon'  => 'warning',
            'title' => 'มีรายการค้างอยู่',
            'html'  => htmlspecialchars($error, ENT_QUOTES)
        ];
    }
}

// รายการที่ยืมอยู่ตอนนี้
$items = $conn->query("
    SELECT i.item_id, i.model_name
    FROM borrowings b
    JOIN items i ON b.item_id = i.item_id
    WHERE b.user_id = " . intval($_SESSION['user_id']) . " AND b.return_date IS NULL
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>แจ้งซ่อมครุภัณฑ์</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="style.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .search-wrap { position: relative; }
    .search-results{
      position:absolute; top:100%; left:0; right:0;
      background:#fff; border:1px solid #ddd; border-radius:6px;
      box-shadow:0 4px 12px rgba(0,0,0,.08);
      max-height:260px; overflow:auto; z-index:1050; display:none;
    }
    .search-results .sr-item{ padding:8px 10px; cursor:pointer; }
    .search-results .sr-item:hover{ background:#f5f5f5; }
    .search-results .sr-empty{ padding:8px 10px; color:#888; }
    .search-muted{ font-size:12px; color:#6c757d; }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
  <h3>แจ้งซ่อมครุภัณฑ์</h3>

  <!-- เอา Bootstrap alert ออก และใช้ SweetAlert2 แทนผ่าน JS ด้านล่าง -->

  <form method="post" enctype="multipart/form-data">
    <!-- ค้นหาเฉพาะ หมายเลขครุภัณฑ์ / Serial Number -->
    <div class="mb-2">
      <label class="form-label mb-1">ค้นหาครุภัณฑ์ (พิมพ์ “หมายเลขครุภัณฑ์ หรือ Serial Number”)</label>
      <div class="search-wrap">
        <input type="text" id="itemSearch" class="form-control"
               placeholder="เช่น หมายเลขครุภัณฑ์ 5820-001-xxxx หรือ Serial 5CDxxxx">
        <div id="searchResults" class="search-results"></div>
      </div>
      <div class="search-muted mt-1">คลิกผลค้นหาเพื่อเลือกในช่อง “เลือกครุภัณฑ์” และระบบจะเติมข้อมูลให้อัตโนมัติ</div>
    </div>

    <div class="mb-3">
      <label class="form-label">เลือกครุภัณฑ์ (จากรายการที่คุณยืมอยู่) / เลือก “อื่น ๆ” หากไม่มี</label>
      <select name="item_id" class="form-select" required onchange="toggleOtherDetails(this)">
        <option value="">-- เลือก --</option>
        <?php while ($row = $items->fetch_assoc()): ?>
          <option value="<?= $row['item_id'] ?>"><?= htmlspecialchars($row['model_name']) ?></option>
        <?php endwhile; ?>
        <option value="other">อื่น ๆ / ไม่ระบุ</option>
      </select>
    </div>

    <div class="mb-3" id="other-details" style="display:none;">
      <label class="form-label">ยี่ห้อ</label>
      <input type="text" name="other_brand" class="form-control">
      <label class="form-label mt-2">รุ่น</label>
      <input type="text" name="other_model" class="form-control">
      <label class="form-label mt-2">ซีเรียลนัมเบอร์</label>
      <input type="text" name="other_serial_number" class="form-control">
      <label class="form-label mt-2">หมายเลขครุภัณฑ์</label>
      <input type="text" name="asset_number" id="asset_number_other" class="form-control">
    </div>

    <div id="item-info-fields">
      <div class="mb-3">
        <label class="form-label">หมายเลขครุภัณฑ์</label>
        <input type="text" name="asset_number" id="asset_number" class="form-control" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">Serial Number</label>
        <input type="text" name="serial_number" id="serial_number" class="form-control" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">ยี่ห้อ</label>
        <input type="text" name="brand" id="brand" class="form-control" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">รุ่น</label>
        <input type="text" name="model_name" id="model_name" class="form-control" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">ตำแหน่ง</label>
        <input type="text" name="location_name" id="location_name" class="form-control" value="<?= htmlspecialchars($dept_name) ?>" readonly>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">รายละเอียดปัญหา</label>
      <textarea name="issue_description" class="form-control" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">อัปโหลดรูป (ถ้ามี)</label>
      <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <div class="mb-3">
      <label class="form-label">แผนก/ฝ่าย</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($dept_name) ?>" readonly>
    </div>
    <button type="submit" class="btn btn-primary">แจ้งซ่อม</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- URL ชัวร์ไปยัง search_items.php -->
<script>
  const SEARCH_URL = "<?= htmlspecialchars( ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http' )
                     . '://' . $_SERVER['HTTP_HOST']
                     . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')
                     . '/search_items.php', ENT_QUOTES); ?>";
</script>

<script>
function toggleOtherDetails(sel) {
  const isOther = sel.value === 'other';
  document.getElementById('other-details').style.display = isOther ? 'block' : 'none';
  document.getElementById('item-info-fields').style.display = isOther ? 'none' : 'block';
  if(isOther){
    document.getElementById('asset_number').value  = '';
    document.getElementById('serial_number').value = '';
    document.getElementById('brand').value         = '';
    document.getElementById('model_name').value    = '';
  }
}

// เลือกจาก select → เติมข้อมูลผ่าน get_item_info.php
const itemSelect = document.querySelector('select[name="item_id"]');
itemSelect.addEventListener('change', function() {
  const itemId = this.value;
  if (itemId && itemId !== 'other') {
    fetch('get_item_info.php?item_id=' + encodeURIComponent(itemId))
    .then(res => res.json())
    .then(data => {
      document.getElementById('asset_number').value  = data.asset_number || '';
      document.getElementById('serial_number').value = data.serial_number || '';
      document.getElementById('brand').value         = data.brand || '';
      document.getElementById('model_name').value    = data.model_name || '';
    }).catch(()=>{});
    document.getElementById('other-details').style.display = 'none';
  } else {
    document.getElementById('asset_number').value  = '';
    document.getElementById('serial_number').value = '';
    document.getElementById('brand').value         = '';
    document.getElementById('model_name').value    = '';
    document.getElementById('other-details').style.display = (itemId==='other') ? 'block' : 'none';
  }
});

// ====== Auto-suggest from search_items.php ======
(function(){
  const searchInput = document.getElementById('itemSearch');
  const resultsBox  = document.getElementById('searchResults');
  let timer = null;

  function esc(s){ return (s||'').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

  searchInput.addEventListener('input', function(){
    const q = this.value.trim();
    clearTimeout(timer);
    if(q.length < 2){
      resultsBox.style.display = 'none';
      resultsBox.innerHTML = '';
      return;
    }
    timer = setTimeout(() => {
      fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
        .then(async r => {
          const text = await r.text();
          try { return JSON.parse(text); }
          catch(e){ console.error('Invalid JSON from search_items.php:', text); return []; }
        })
        .then(list => {
          if(!Array.isArray(list) || list.length === 0){
            resultsBox.innerHTML = '<div class="sr-empty">ไม่พบรายการ</div>';
            resultsBox.style.display = 'block';
            return;
          }
          resultsBox.innerHTML = list.map(i => {
            const lineMain = i.asset_number || (i.serial_number ? 'SN: '+i.serial_number : 'รายการ');
            const sub = [
              i.asset_number && i.serial_number ? ('SN: '+i.serial_number) : '',
              [i.brand, i.model_name].filter(Boolean).join(' • ')
            ].filter(Boolean).join(' • ');
            return `
              <div class="sr-item"
                   data-id="${esc(i.item_id)}"
                   data-asset="${esc(i.asset_number||'')}"
                   data-serial="${esc(i.serial_number||'')}"
                   data-brand="${esc(i.brand||'')}"
                   data-model="${esc(i.model_name||'')}">
                <div><strong>${esc(lineMain)}</strong></div>
                <div class="search-muted">${esc(sub)}</div>
              </div>`;
          }).join('');
          resultsBox.style.display = 'block';
        })
        .catch(err => { console.error(err); resultsBox.style.display = 'none'; });
    }, 250);
  });

  // คลิกผลค้นหา → เติมฟอร์ม + อัพเดต select
  resultsBox.addEventListener('click', function(e){
    const row = e.target.closest('.sr-item'); if(!row) return;

    const id     = row.getAttribute('data-id');
    const asset  = row.getAttribute('data-asset')  || '';
    const serial = row.getAttribute('data-serial') || '';
    const brand  = row.getAttribute('data-brand')  || '';
    const model  = row.getAttribute('data-model')  || '';

    document.getElementById('asset_number').value  = asset;
    document.getElementById('serial_number').value = serial;
    document.getElementById('brand').value         = brand;
    document.getElementById('model_name').value    = model;

    if (id) {
      let opt = itemSelect.querySelector(`option[value="${CSS.escape(id)}"]`);
      if(!opt){
        opt = document.createElement('option');
        opt.value = id;
        opt.textContent = model || asset || (serial ? 'SN: '+serial : 'รายการที่เลือก');
        const otherOpt = itemSelect.querySelector('option[value="other"]');
        itemSelect.insertBefore(opt, otherOpt);
      }
      itemSelect.value = id;
      itemSelect.dispatchEvent(new Event('change'));
    }

    searchInput.value = '';
    resultsBox.style.display = 'none';
    resultsBox.innerHTML = '';
  });

  // ปิดผลลัพธ์เมื่อคลิกรอบนอก
  document.addEventListener('click', function(e){
    if(!resultsBox.contains(e.target) && e.target !== searchInput){
      resultsBox.style.display = 'none';
    }
  });

  // sync หมายเลขครุภัณฑ์ จาก "อื่น ๆ"
  const assetOther = document.getElementById('asset_number_other');
  if(assetOther){
    assetOther.addEventListener('input', function(){
      document.getElementById('asset_number').value = this.value;
    });
  }
})();
</script>

<script>
/* ===== SweetAlert หลัง POST (สำเร็จ/แจ้งซ้ำ) ===== */
<?php if ($swal): ?>
  (function(){
    const opt = {
      icon: <?= json_encode($swal['icon'] ?? 'info') ?>,
      title: <?= json_encode($swal['title'] ?? '') ?>,
      <?= isset($swal['html']) ? ('html: '.json_encode($swal['html']).',') : ('text: '.json_encode($swal['text'] ?? '').',') ?>
      showDenyButton: <?= json_encode(($swal['icon'] ?? '') === 'success') ?>,
      confirmButtonText: <?= json_encode(($swal['icon'] ?? '') === 'success' ? 'ดูรายการของฉัน' : 'ตกลง') ?>,
      denyButtonText: <?= json_encode('แจ้งใหม่') ?>
    };
    Swal.fire(opt).then((res) => {
      if (<?= json_encode(($swal['icon'] ?? '') === 'success') ?>) {
        if (res.isConfirmed) {
          // ไปหน้ารายการของฉัน
          window.location.href = 'my_repairs.php';
        } else if (res.isDenied) {
          // รีเฟรชเพื่อเคลียร์ฟอร์ม
          window.location.reload();
        }
      }
    });
  })();
<?php endif; ?>
</script>

</body>
</html>
