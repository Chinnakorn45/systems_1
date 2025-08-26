<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: login.php'); exit;
}

// get department name
$dept_name = '-';
$user_id = intval($_SESSION['user_id']);
$user_res = $conn->query("SELECT department FROM users WHERE user_id = $user_id LIMIT 1");
if ($user_res && $user_res->num_rows) {
    $dept_name = $user_res->fetch_assoc()['department'];
}

// handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_name = $dept_name;
    if (isset($_POST['item_id']) && $_POST['item_id'] === 'other') {
        $item_id       = null;
        $asset_number  = $_POST['asset_number'] ?? '';
        $serial_number = $_POST['other_serial_number'] ?? '';
        $brand         = $_POST['other_brand'] ?? '';
        $model         = $_POST['other_model'] ?? '';
        $desc          = $_POST['issue_description'] ?? '';
    } else {
        $item_id       = $_POST['item_id'] ?? null;
        $asset_number  = $_POST['asset_number'] ?? '';
        $serial_number = $_POST['serial_number'] ?? '';
        $brand         = $_POST['brand'] ?? '';
        $model         = $_POST['model_name'] ?? '';
        $desc          = $_POST['issue_description'] ?? '';
    }

    $img = '';
    if (!empty($_FILES['image']['name'])) {
        $img = 'uploads/' . uniqid('', true) . '_' . basename($_FILES['image']['name']);
        @mkdir(dirname($img), 0777, true);
        move_uploaded_file($_FILES['image']['tmp_name'], $img);
    }

    $sql = "INSERT INTO repairs
            (item_id, reported_by, issue_description, image, asset_number, serial_number, location_name, brand, model_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql) or die('Prepare failed: '.$conn->error);
    $uid = intval($_SESSION['user_id']);
    $stmt->bind_param('iisssssss', $item_id, $uid, $desc, $img, $asset_number, $serial_number, $location_name, $brand, $model);
    $stmt->execute();
    $stmt->close();
    $success = true;

    @require_once __DIR__ . '/send_discord_notification.php';
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
  <title>แจ้งซ่อมครุภัณฑ์</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="style.css">
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
  <?php if (!empty($success)): ?>
    <div class="alert alert-success">แจ้งซ่อมสำเร็จ</div>
  <?php endif; ?>

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

// เลือกจาก select → เติมข้อมูลผ่าน get_item_info.php (ยังคงไว้ได้)
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

// ====== Auto-suggest: เติมอัตโนมัติจากผลค้นหา (ไม่พึ่ง endpoint อื่น) ======
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

  // คลิกผลค้นหา → เติมช่องทันที + เซ็ต select
  resultsBox.addEventListener('click', function(e){
    const row = e.target.closest('.sr-item'); if(!row) return;

    const id     = row.getAttribute('data-id');
    const asset  = row.getAttribute('data-asset')  || '';
    const serial = row.getAttribute('data-serial') || '';
    const brand  = row.getAttribute('data-brand')  || '';
    const model  = row.getAttribute('data-model')  || '';

    // เติมฟิลด์ทันที
    document.getElementById('asset_number').value  = asset;
    document.getElementById('serial_number').value = serial;
    document.getElementById('brand').value         = brand;
    document.getElementById('model_name').value    = model;

    // อัพเดต select (ถ้า option ยังไม่มี ให้เพิ่มก่อน other)
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
      // ถ้าต้องการดึงจาก get_item_info.php เพิ่มความชัวร์ ก็ปล่อย change นี้ไว้
      itemSelect.dispatchEvent(new Event('change'));
    }

    // ปิดกล่องผลลัพธ์
    searchInput.value = '';
    resultsBox.style.display = 'none';
    resultsBox.innerHTML = '';
  });

  // คลิกรอบนอก → ปิด
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
</body>
</html>
