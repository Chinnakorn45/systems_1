<?php
// ====== DB ======
$host = 'localhost'; $user = 'root'; $pass = ''; $db = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: '.$conn->connect_error);
$conn->set_charset('utf8mb4');

// ====== Helpers ======
function table_exists(mysqli $c, $t){ $t=$c->real_escape_string($t); $r=$c->query("SHOW TABLES LIKE '$t'"); return $r&&$r->num_rows>0; }
function col_exists(mysqli $c, $t, $col){ $t=$c->real_escape_string($t); $col=$c->real_escape_string($col); $r=$c->query("SHOW COLUMNS FROM `$t` LIKE '$col'"); return $r&&$r->num_rows>0; }
function status_th_repair($s){
  $m=['pending'=>'รอรับงาน','received'=>'รับงานแล้ว','assigned'=>'มอบหมายช่าง','in_progress'=>'กำลังซ่อม','completed'=>'ซ่อมเสร็จ','cancelled'=>'ยกเลิก']; 
  return $m[$s]??$s;
}
function badge_class_repair($s){ return match($s){ 'pending'=>'warn','received','assigned','in_progress'=>'info','completed'=>'ok','cancelled'=>'warn', default=>'info', }; }

// ====== รับคำค้น ======
$sn = trim($_GET['sn'] ?? '');
$item = $borrow = null;

// ====== ค้นหา item ======
if ($sn !== '' && table_exists($conn,'items')) {
  $hasSerial = col_exists($conn,'items','serial_number');
  if ($hasSerial) {
    $q="SELECT * FROM items WHERE item_number=? OR serial_number=? LIMIT 1";
    $st=$conn->prepare($q); $st->bind_param('ss',$sn,$sn);
  } else {
    $q="SELECT * FROM items WHERE item_number=? LIMIT 1";
    $st=$conn->prepare($q); $st->bind_param('s',$sn);
  }
  $st->execute(); $rs=$st->get_result();
  if ($rs && $rs->num_rows) $item=$rs->fetch_assoc();
  $st->close();

  // ====== สถานะยืม-คืน ล่าสุด ======
  if ($item && table_exists($conn,'borrowings')) {
    if (isset($item['item_id'])) {
      $q="SELECT b.*,u.full_name,u.department
          FROM borrowings b LEFT JOIN users u ON b.user_id=u.user_id
          WHERE b.item_id=? ORDER BY b.borrow_id DESC LIMIT 1";
      $st=$conn->prepare($q); $st->bind_param('i',$item['item_id']);
    } else {
      $q="SELECT b.*,u.full_name,u.department
          FROM borrowings b LEFT JOIN items i ON b.item_id=i.item_id
          LEFT JOIN users u ON b.user_id=u.user_id
          WHERE i.item_number=? ORDER BY b.borrow_id DESC LIMIT 1";
      $st=$conn->prepare($q); $st->bind_param('s',$item['item_number']);
    }
    $st->execute(); $r=$st->get_result();
    if ($r && $r->num_rows) $borrow=$r->fetch_assoc();
    $st->close();
  }
}

// ====== ซ่อม + ไทม์ไลน์ ======
$REPAIR_DB_NAME = null; // ถ้าแยกฐาน ให้ใส่ชื่อฐานเช่น 'repair_db'
$connRepair = $conn;
if ($REPAIR_DB_NAME) {
  $connRepair = new mysqli($host,$user,$pass,$REPAIR_DB_NAME);
  if ($connRepair->connect_error) die('Repair DB failed: '.$connRepair->connect_error);
  $connRepair->set_charset('utf8mb4');
}
$repairs=[]; $logsById=[];
if ($sn !== '' && table_exists($connRepair,'repairs')) {
  $conds=[]; $bind=[]; $tps='';
  if ($item && isset($item['item_id'])) { $conds[]='item_id=?'; $bind[]=$item['item_id']; $tps.='i'; }
  $asset=$item['item_number']??$sn; $serial=$item['serial_number']??$sn;
  if ($asset!==''){ $conds[]='asset_number=?';  $bind[]=$asset;  $tps.='s'; }
  if ($serial!==''){ $conds[]='serial_number=?'; $bind[]=$serial; $tps.='s'; }
  if ($conds){
    $where=implode(' OR ',array_map(fn($c)=>"($c)",$conds));
    $q="SELECT repair_id,item_id,reported_by,issue_description,image,status,assigned_to,
               fix_description,created_at,updated_at,asset_number,serial_number,location_name,brand,model_name
        FROM repairs WHERE $where ORDER BY created_at DESC LIMIT 20";
    $st=$connRepair->prepare($q); $st->bind_param($tps,...$bind); $st->execute();
    $r=$st->get_result(); while($r && $row=$r->fetch_assoc()) $repairs[]=$row; $st->close();

    if ($repairs && table_exists($connRepair,'repair_logs')) {
      $ids=array_map('intval',array_column($repairs,'repair_id'));
      if ($ids){
        $ph=implode(',',array_fill(0,count($ids),'?')); $types=str_repeat('i',count($ids));
        $q="SELECT repair_id,status,detail,updated_at FROM repair_logs
            WHERE repair_id IN ($ph) ORDER BY updated_at DESC";
        $st=$connRepair->prepare($q); $st->bind_param($types,...$ids); $st->execute();
        $r=$st->get_result();
        while($r && $lg=$r->fetch_assoc()){
          $rid=(int)$lg['repair_id'];
          if (!isset($logsById[$rid])) $logsById[$rid]=[];
          if (count($logsById[$rid])<5) $logsById[$rid][]=$lg;
        }
        $st->close();
      }
    }
  }
}

// ====== สรุปตำแหน่งแสดงผล ======
$locationText='-';
if ($item){
  $parts=[];
  if (!empty($item['location']))   $parts[]=$item['location'];
  if (!empty($item['room']))       $parts[]='ห้อง '.$item['room'];
  if (!empty($item['department'])) $parts[]='แผนก '.$item['department'];
  if ($parts) $locationText=htmlspecialchars(implode(' | ',$parts));
}
if ($locationText==='-' && $repairs && !empty($repairs[0]['location_name'])) {
  $locationText=htmlspecialchars($repairs[0]['location_name']);
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>ค้นหาอุปกรณ์ | ระบบงานพัสดุ-ครุภัณฑ์</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body{font-family:system-ui,-apple-system,'Sarabun',Segoe UI,Roboto,Arial;background:#f7fafc;margin:0}
    .wrap{max-width:1000px;margin:24px auto;padding:0 16px}
    .card{background:#fff;border:1px solid #e6ecf2;border-radius:12px;padding:16px;margin-bottom:16px}
    .title{font-weight:700;color:#1a3e6d;margin:6px 0 12px 0}
    .search-box{display:flex;gap:8px;flex-wrap:wrap}
    .search-box input{flex:1;min-width:240px;padding:10px;border:1px solid #cfd8e3;border-radius:8px}
    .btn{padding:10px 14px;border:0;border-radius:8px;cursor:pointer}
    .btn.primary{background:#1a3e6d;color:#fff}
    .btn.secondary{background:#6b7280;color:#fff}
    .muted{color:#6b7280}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px}
    .pill.warn{background:#fff7ed;color:#b45309;border:1px solid #fde68a}
    .pill.ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
    .pill.info{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #e6ecf2;padding:8px;text-align:left}
    .back{display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#1a3e6d;margin-bottom:8px}
    .scanner-modal{position:fixed;inset:0;background:rgba(0,0,0,.75);display:none;align-items:center;justify-content:center;z-index:1000}
    .scanner-modal.show{display:flex}
    .scanner-box{background:#000;border-radius:12px;padding:12px;width:min(92vw,560px)}
    .scanner-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:8px}
    video#scannerVideo{width:100%;border-radius:10px}
    .banner{background:#fff7ed;border:1px solid #fde68a;color:#92400e;padding:10px;border-radius:8px;margin-top:10px}
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}
  </style>
</head>
<body>
  <div class="wrap">
    <a class="back" href="./"><i class="fa fa-arrow-left"></i> กลับหน้าหลัก</a>

    <div class="card">
      <div class="title"><i class="fa fa-magnifying-glass"></i> ค้นหาอุปกรณ์</div>

      <form class="search-box" method="get" autocomplete="off">
        <label class="sr-only" for="sn">Serial / เลขครุภัณฑ์</label>
        <input id="sn" name="sn" value="<?= htmlspecialchars($sn) ?>" placeholder="กรอกหรือสแกน Serial / เลขครุภัณฑ์" required>
        <button class="btn primary" type="submit"><i class="fa fa-search"></i> ค้นหา</button>

        <!-- ปุ่มสแกนกล้อง -->
        <button class="btn secondary" type="button" id="btnScan"><i class="fa fa-camera"></i> สแกนบาร์โค้ด</button>

        <!-- ปุ่มอัปโหลดรูปบาร์โค้ด -->
        <input type="file" id="fileBarcode" accept="image/*" capture="environment" style="display:none">
        <button class="btn secondary" type="button" id="btnUpload"><i class="fa fa-image"></i> อัปโหลดรูปบาร์โค้ด</button>
      </form>

      <!-- banner แจ้งเหตุผลใช้งานกล้องไม่ได้ -->
      <div id="camBanner" class="banner" style="display:none">
        <strong>ไม่สามารถใช้กล้องได้:</strong>
        ต้องเปิดผ่าน <code>https://</code> หรือ <code>localhost</code> เท่านั้นบนเบราว์เซอร์สมัยใหม่
        (คุณยังสามารถอัปโหลดรูปบาร์โค้ดแทนได้)
      </div>

      <?php if ($sn !== '' && !$item && !count($repairs)): ?>
        <div class="muted" style="margin-top:10px"><i class="fa fa-info-circle"></i> ไม่พบข้อมูลที่ตรงกับ “<?= htmlspecialchars($sn) ?>”</div>
      <?php endif; ?>
    </div>

    <?php if ($item): ?>
    <div class="card">
      <div class="title"><i class="fa fa-box-open"></i> ข้อมูลอุปกรณ์</div>
      <div class="grid">
        <div><strong>เลขครุภัณฑ์</strong><br><?= htmlspecialchars($item['item_number'] ?? '-') ?></div>
        <?php if (isset($item['serial_number'])): ?>
        <div><strong>Serial</strong><br><?= htmlspecialchars($item['serial_number'] ?? '-') ?></div>
        <?php endif; ?>
        <?php if (isset($item['brand'])): ?>
        <div><strong>ยี่ห้อ</strong><br><?= htmlspecialchars($item['brand']) ?></div>
        <?php endif; ?>
        <?php if (isset($item['model_name'])): ?>
        <div><strong>รุ่น</strong><br><?= htmlspecialchars($item['model_name']) ?></div>
        <?php endif; ?>
        <div style="grid-column:1/-1"><strong>ตำแหน่งที่ตั้ง</strong><br><?= $locationText ?></div>
      </div>
      <div style="margin-top:10px"><strong>สถานะครุภัณฑ์:</strong>
        <?php
        $pill = '<span class="pill ok">ว่าง (Available)</span>';
        if ($borrow){
          $s=$borrow['status'];
          if ($s==='borrowed' || $s==='return_pending'){
            $who = htmlspecialchars($borrow['full_name'] ?? 'ไม่ทราบผู้ยืม');
            $due = $borrow['due_date'] ? date('d/m/Y', strtotime($borrow['due_date'])) : '-';
            $pill = '<span class="pill warn">กำลังยืม</span> โดย '.$who.' (กำหนดคืน '.$due.')';
          } elseif ($s==='approved'){
            $pill = '<span class="pill info">รอรับของ/อยู่ระหว่างดำเนินการ</span>';
          }
        }
        echo $pill;
        ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="title"><i class="fa fa-screwdriver-wrench"></i> ประวัติการซ่อม</div>
      <?php if (!table_exists($connRepair,'repairs')): ?>
        <div class="muted">ยังไม่มีตาราง <code>repairs</code> ในฐานข้อมูลนี้</div>
      <?php elseif (!$repairs): ?>
        <div class="muted">ไม่พบประวัติการซ่อม</div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>เลขงาน</th><th>วันที่แจ้ง</th><th>สถานะ</th><th>อาการ/ปัญหา</th><th>มอบหมาย</th><th>สรุปการแก้ไข</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($repairs as $r): $rid=(int)$r['repair_id']; $st=$r['status'] ?? '-'; $badge=badge_class_repair($st); ?>
          <tr>
            <td><?= htmlspecialchars($rid) ?></td>
            <td><?= htmlspecialchars($r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-') ?></td>
            <td><span class="pill <?= $badge ?>"><?= htmlspecialchars(status_th_repair($st)) ?></span></td>
            <td><?= htmlspecialchars($r['issue_description'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['assigned_to'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['fix_description'] ?? '-') ?></td>
          </tr>
          <?php if (!empty($logsById[$rid])): ?>
          <tr>
            <td colspan="6" style="background:#fafbfd">
              <div class="muted"><i class="fa fa-stream"></i> ไทม์ไลน์สถานะ (ล่าสุด 5 รายการ)</div>
              <ul style="margin:6px 0 0 18px">
                <?php foreach($logsById[$rid] as $lg): ?>
                  <li><strong><?= htmlspecialchars(status_th_repair($lg['status'] ?? '-')) ?></strong>
                    — <?= htmlspecialchars($lg['detail'] ?? '-') ?>
                    <span class="muted">(<?= htmlspecialchars($lg['updated_at'] ? date('d/m/Y H:i', strtotime($lg['updated_at'])) : '-') ?>)</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </td>
          </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- ZXing (ใช้ทั้งสแกนกล้องและถอดรหัสจากรูป) -->
  <script src="https://unpkg.com/@zxing/library@0.21.2"></script>
  <script>
  (function(){
    const btnScan    = document.getElementById('btnScan');
    const btnUpload  = document.getElementById('btnUpload');
    const fileInput  = document.getElementById('fileBarcode');
    const inputSN    = document.getElementById('sn');
    const banner     = document.getElementById('camBanner');

    // modal สแกนกล้อง
    const modal=document.createElement('div'); modal.className='scanner-modal'; modal.id='scannerModal';
    modal.innerHTML = `
      <div class="scanner-box">
        <video id="scannerVideo" autoplay playsinline></video>
        <div class="scanner-actions">
          <button id="btnCloseScan" class="btn secondary"><i class="fa fa-times"></i> ปิด</button>
        </div>
        <div style="color:#ddd;font-size:12px;margin-top:4px">ชี้กล้องไปที่บาร์โค้ดหรือ QR</div>
      </div>`;
    document.body.appendChild(modal);
    const video=modal.querySelector('#scannerVideo');
    const btnClose=modal.querySelector('#btnCloseScan');

    const isSecureOrigin =
      location.protocol === 'https:' ||
      location.hostname === 'localhost' ||
      location.hostname === '127.0.0.1';

    const hasMedia = !!(navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function');

    // ถ้า origin ไม่ปลอดภัยหรือไม่มี getUserMedia → ปิดปุ่มสแกน + แสดงแบนเนอร์
    if (!isSecureOrigin || !hasMedia) {
      btnScan.disabled = true;
      btnScan.title = 'ต้องเปิดผ่าน https หรือ localhost เท่านั้น';
      banner.style.display = 'block';
    }

    let usingZXing=false, zxingReader=null, stream=null, rafId=null, detector=null, running=false;

    async function startScan(){
      if (!isSecureOrigin || !hasMedia) {
        alert('ไม่สามารถใช้กล้องได้: ต้อง https หรือ localhost\nให้ใช้อัปโหลดรูปบาร์โค้ดแทน');
        return;
      }
      if (running) return; running=true; modal.classList.add('show');

      // Native BarcodeDetector ก่อน
      if ('BarcodeDetector' in window) {
        try{
          detector = new BarcodeDetector({formats:['code_128','qr_code','ean_13','ean_8','code_39','code_93','upc_a','upc_e']});
          stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'},audio:false});
          video.srcObject=stream; await video.play();

          const canvas=document.createElement('canvas'); const ctx=canvas.getContext('2d');
          const tick = async () => {
            if (!modal.classList.contains('show')) return;
            if (video.readyState >= 2){
              canvas.width=video.videoWidth; canvas.height=video.videoHeight;
              ctx.drawImage(video,0,0,canvas.width,canvas.height);
              try{
                const bmp = await createImageBitmap(canvas);
                const codes = await detector.detect(bmp);
                if (codes && codes.length){
                  const val=codes[0].rawValue||'';
                  if (val){ inputSN.value=val; stopScan(); return; }
                }
              }catch(e){}
            }
            rafId=requestAnimationFrame(tick);
          };
          rafId=requestAnimationFrame(tick);
          return;
        }catch(e){ /* fallback */ }
      }

      // ZXing จากกล้อง
      usingZXing=true;
      zxingReader=new ZXing.BrowserMultiFormatReader();
      try{
        await zxingReader.decodeFromVideoDevice(null,'scannerVideo',(res,err)=>{
          if (res){ inputSN.value=res.text||''; stopScan(); }
        });
      }catch(err){
        alert('เปิดกล้องไม่สำเร็จ: '+ (err && err.message ? err.message : err));
        stopScan();
      }
    }

    async function stopScan(){
      if (rafId) cancelAnimationFrame(rafId);
      if (usingZXing && zxingReader){ try{ await zxingReader.reset(); }catch(e){} usingZXing=false; zxingReader=null; }
      if (video && video.srcObject){
        try{ video.pause(); video.srcObject.getTracks().forEach(t=>t.stop()); video.srcObject=null; }catch(e){}
      }
      modal.classList.remove('show'); running=false;
    }

    // ถอดรหัสจาก "รูป" ด้วย ZXing
    async function decodeFromFile(file){
      if (!file) return;
      const reader = new FileReader();
      reader.onload = async () => {
        try{
          const dataUrl = reader.result;
          const codeReader = new ZXing.BrowserMultiFormatReader();
          const res = await codeReader.decodeFromImageUrl(dataUrl);
          if (res && res.text){
            inputSN.value = res.text;
            // auto submit ก็ได้ ถ้าต้องการ:
            // document.querySelector('form.search-box').submit();
            alert('อ่านบาร์โค้ดสำเร็จ: ' + res.text);
          } else {
            alert('อ่านบาร์โค้ดจากรูปไม่สำเร็จ');
          }
        }catch(e){
          alert('อ่านบาร์โค้ดจากรูปไม่สำเร็จ: ' + (e && e.message ? e.message : e));
        }
      };
      reader.readAsDataURL(file);
    }

    // Events
    btnScan.addEventListener('click', e=>{ e.preventDefault(); startScan(); });
    btnClose.addEventListener('click', e=>{ e.preventDefault(); stopScan(); });
    modal.addEventListener('click', e=>{ if (e.target===modal) stopScan(); });
    window.addEventListener('keydown', e=>{ if (e.key==='Escape' && modal.classList.contains('show')) stopScan(); });

    btnUpload.addEventListener('click', e => { e.preventDefault(); fileInput.click(); });
    fileInput.addEventListener('change', e => { const f=e.target.files && e.target.files[0]; decodeFromFile(f); });
  })();
  </script>
</body>
</html>
<?php
$conn->close();
if (isset($connRepair) && $connRepair instanceof mysqli && $connRepair !== $conn) $connRepair->close();
