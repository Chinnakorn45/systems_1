<?php
// ----------------- Session -----------------
if (session_status() === PHP_SESSION_NONE) session_start();

// ❗ โชว์การ์ดค้นหาเสมอ แล้วไปเช็คสิทธิ์ที่ equipment_lookup.php
$canLookup = true; 

// ----------------- DB -----------------
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'borrowing_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

// ----------------- Settings / Branding -----------------
$hospital_name_en = 'Suratthani Cancer Hospital';
$hospital_name_th = 'โรงพยาบาลมะเร็งสุราษฎร์ธานี (ศูนย์มะเร็งสุราษฎร์ธานี)';
$system_title     = 'ระบบงานพัสดุ-ครุภัณฑ์';
$system_intro     = '';
$logo_path        = '';
$promo_image_rel  = '';

// โหลดจาก DB
$set = $conn->query("
  SELECT setting_key, setting_value
  FROM system_settings
  WHERE setting_key IN (
    'system_title','hospital_name_en','hospital_name_th','system_intro','system_logo',
    'promo_image','promo_title','promo_desc','promo_btn_text','promo_btn_link',
    'promo_bullet_1','promo_bullet_2','promo_bullet_3'
  )
");
while ($row = $set->fetch_assoc()) {
    if ($row['setting_key'] === 'system_title')       $system_title = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_en')   $hospital_name_en = $row['setting_value'];
    if ($row['setting_key'] === 'hospital_name_th')   $hospital_name_th = $row['setting_value'];
    if ($row['setting_key'] === 'system_intro')       $system_intro = $row['setting_value'];
    if ($row['setting_key'] === 'system_logo')        $logo_path = $row['setting_value'];
    if ($row['setting_key'] === 'promo_image')        $promo_image_rel = $row['setting_value'];
    if ($row['setting_key'] === 'promo_title')        $promo_title = $row['setting_value'];
    if ($row['setting_key'] === 'promo_desc')         $promo_desc = $row['setting_value'];
    if ($row['setting_key'] === 'promo_btn_text')     $promo_btn_text = $row['setting_value'];
    if ($row['setting_key'] === 'promo_btn_link')     $promo_btn_link = $row['setting_value'];
    if ($row['setting_key'] === 'promo_bullet_1')     $promo_b1 = $row['setting_value'];
    if ($row['setting_key'] === 'promo_bullet_2')     $promo_b2 = $row['setting_value'];
    if ($row['setting_key'] === 'promo_bullet_3')     $promo_b3 = $row['setting_value'];
}

// ----------------- Defaults for promo text -----------------
$promo_title    = $promo_title    ?? 'ฟังก์ชันใหม่พร้อมใช้งานแล้ว 🎉';
$promo_desc     = $promo_desc     ?? "ระบบ {$system_title} เพิ่ม ค้นหาอุปกรณ์ (สแกน/พิมพ์) และหน้ารวมสถานะครุภัณฑ์แบบเรียลไทม์";
$promo_btn_text = $promo_btn_text ?? 'ใช้งานได้แล้ววันนี้';
$promo_btn_link = $promo_btn_link ?? '../SCH/equipment_lookup.php';
$promo_b1       = $promo_b1       ?? 'ค้นหาอุปกรณ์ได้ไวขึ้น';
$promo_b2       = $promo_b2       ?? 'ไปหน้า Equipment Lookup ได้ทันที';
$promo_b3       = $promo_b3       ?? 'แสดงรายละเอียดหน่วยงาน/สถานะการใช้งาน';

// ----------------- Helpers -----------------
function build_url_from_relative($relativePath) {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $prefix = ($scriptDir === '/' ? '' : $scriptDir);
    return $prefix . '/' . ltrim($relativePath, '/\\');
}

// ----------------- Logo URL -----------------
$logo_url = '';
if ($logo_path) {
    $fs_path = __DIR__ . DIRECTORY_SEPARATOR . ltrim($logo_path, '/\\');
    if (is_file($fs_path)) $logo_url = build_url_from_relative($logo_path);
}

// ----------------- Promo Image URL -----------------
$promo_image_url = '';
if ($promo_image_rel) {
    $promo_fs = __DIR__ . DIRECTORY_SEPARATOR . ltrim($promo_image_rel, '/\\');
    if (is_file($promo_fs)) $promo_image_url = build_url_from_relative($promo_image_rel);
}
if (!$promo_image_url) { // รูปสำรอง (ถ้ามี)
    $fallback = 'assets/promo-fallback.jpg';
    $promo_fs2 = __DIR__ . DIRECTORY_SEPARATOR . ltrim($fallback, '/\\');
    if (is_file($promo_fs2)) $promo_image_url = build_url_from_relative($fallback);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($system_title) ?> | Faculty of Fine Arts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <!-- ===== Popup Styles ===== -->
    <style>
      .promo-backdrop{
        position:fixed; inset:0; background:rgba(0,0,0,.55);
        display:none; align-items:center; justify-content:center; z-index:9999;
      }
      .promo-backdrop.show{ display:flex; }

      .promo-modal{
        width:min(760px,94vw); background:#fff; border-radius:16px; overflow:hidden;
        box-shadow:0 20px 60px rgba(0,0,0,.25);
        font-family:'Sarabun', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, "Noto Sans Thai", sans-serif;
      }
      .promo-header{
        position:relative; padding:14px 48px 12px 16px;
        background:linear-gradient(135deg,#f0f7ff,#fff); border-bottom:1px solid #eef3f8;
      }
      .promo-title{ margin:0; font-size:1.05rem; font-weight:700; color:#1d3557; }
      .promo-close{
        position:absolute; top:8px; right:8px; border:0; background:transparent; cursor:pointer;
        font-size:20px; padding:8px; line-height:1; color:#334155;
      }
      .promo-body{ display:grid; grid-template-columns: 220px 1fr; gap:16px; padding:16px; }
      .promo-image-wrap{
        width:100%; aspect-ratio:4/3; border-radius:10px; overflow:hidden; background:#f1f5f9;
      }
      .promo-image{ width:100%; height:100%; object-fit:cover; display:block; }
      .promo-text h4{ margin:0 0 6px 0; font-size:1.1rem; }
      .promo-text p{ margin:0 0 8px 0; color:#0f172a; }
      .promo-tag{
        display:inline-block; font-size:.78rem; padding:4px 10px; border-radius:999px;
        background:#ecfeff; color:#0e7490; border:1px solid #a5f3fc; margin:4px 0 10px 0;
      }
      .promo-foot{ padding:0 16px 16px 16px; display:flex; gap:10px; flex-wrap:wrap; }
      .promo-btn{
        display:inline-flex; align-items:center; gap:8px; border:0; cursor:pointer;
        border-radius:999px; font-weight:600; padding:10px 16px; text-decoration:none;
      }
      .promo-btn.primary{ background:#1d4ed8; color:#fff; }
      .promo-btn.secondary{ background:#e2e8f0; color:#0f172a; }

      @media (max-width:640px){
        .promo-body{ grid-template-columns:1fr; }
      }
      @media (prefers-reduced-motion:no-preference){
        .promo-modal{ transform:translateY(8px); opacity:.98; animation:promoPop .25s ease-out; }
        @keyframes promoPop{ from { transform:translateY(24px); opacity:0; } to { transform:translateY(8px); opacity:.98; } }
      }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container header-content">
            <div class="logo">
                <?php if ($logo_url): ?>
                    <img src="<?= htmlspecialchars($logo_url) ?>" alt="System Logo">
                <?php endif; ?>
                <span>
                    <?= htmlspecialchars($hospital_name_en) ?><br>
                    <?= htmlspecialchars($hospital_name_th) ?>
                </span>
            </div>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="container hero-content">
                <div class="card intro-card">
                    <h2><i class="fas fa-boxes"></i> <?= htmlspecialchars($system_title) ?></h2>
                    <p><?= nl2br(htmlspecialchars($system_intro)) ?></p>
                </div>

                <a href="login_admin.php" class="admin-control-badge" style="text-decoration:none; cursor:pointer;">
                    <i class="fas fa-user-shield"></i> Admin Control
                </a>

                <!-- การ์ดเมนูหลัก -->
                <div class="feature-cards">
                    <a href="src/login.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-address-book"></i></div>
                        <h3>ระบบฐานข้อมูลพนักงาน</h3>
                    </a>

                    <a href="../project_work2/src/index.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-box-open"></i></div>
                        <h3>ระบบบันทึกคลังครุภัณฑ์</h3>
                    </a>

                    <a href="../repair/src/login.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-comments"></i></div>
                        <h3>แจ้งซ่อมงานพัสดุ-ครุภัณฑ์</h3>
                    </a>

                    <?php if ($canLookup): ?>
                    <!-- การ์ด: ค้นหาอุปกรณ์ (สแกน/พิมพ์) -->
                    <a href="../SCH/equipment_lookup.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-magnifying-glass"></i></div>
                        <h3>ค้นหาอุปกรณ์ (สแกน/พิมพ์)</h3>
                    </a>
                    <?php endif; ?>

                    <!-- การ์ดใหม่: ระบบจัดการอุปกรณ์สำนักงาน -->
                    <a href="../supplies/dashboard.php" class="feature-card">
                        <div class="icon-wrapper"><i class="fas fa-clipboard-list"></i></div>
                        <h3>ระบบจัดการอุปกรณ์สำนักงาน</h3>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- ===== Promo Popup ===== -->
    <div class="promo-backdrop" id="promoBackdrop" role="dialog" aria-modal="true" aria-labelledby="promoTitle" aria-describedby="promoDesc">
      <div class="promo-modal">
        <div class="promo-header">
          <h3 class="promo-title" id="promoTitle">🔔 ประกาศจากระบบพัสดุ-ครุภัณฑ์</h3>
          <button class="promo-close" id="promoClose" aria-label="ปิดหน้าต่าง">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="promo-body" id="promoDesc">
          <div class="promo-image-wrap">
            <?php if (!empty($promo_image_url)): ?>
              <img src="<?= htmlspecialchars($promo_image_url) ?>" alt="ประกาศ / โปรโมชัน" class="promo-image">
            <?php endif; ?>
          </div>

          <div class="promo-text">
            <h4><?= htmlspecialchars($promo_title ?: '') ?></h4>
            <p><?= nl2br(htmlspecialchars($promo_desc ?: '')) ?></p>
            <span class="promo-tag"><i class="fas fa-star"></i> <?= htmlspecialchars($promo_btn_text ?: 'ดูรายละเอียด') ?></span>
            <ul style="margin:8px 0 0 20px;">
              <?php if (!empty($promo_b1)): ?><li><?= htmlspecialchars($promo_b1) ?></li><?php endif; ?>
              <?php if (!empty($promo_b2)): ?><li><em><?= htmlspecialchars($promo_b2) ?></em></li><?php endif; ?>
              <?php if (!empty($promo_b3)): ?><li><?= htmlspecialchars($promo_b3) ?></li><?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="promo-foot">
          <a href="<?= htmlspecialchars($promo_btn_link ?: '../SCH/equipment_lookup.php') ?>" class="promo-btn primary">
            <i class="fas fa-magnifying-glass"></i> <?= htmlspecialchars($promo_btn_text ?: 'ไปที่หน้า') ?>
          </a>
          <a href="login_admin.php" class="promo-btn secondary">
            <i class="fas fa-user-shield"></i> ตั้งค่าเพิ่มเติม (ผู้ดูแล)
          </a>
        </div>
      </div>
    </div>
    <!-- /Promo Popup -->

    <!-- ===== Popup Script (เด้งทุกครั้ง) ===== -->
    <script>
    (function(){
      const backdrop = document.getElementById('promoBackdrop');
      const btnClose = document.getElementById('promoClose');

      function openPromo(){
        backdrop.classList.add('show');
        setTimeout(()=>btnClose && btnClose.focus(), 50);
      }
      function closePromo(){
        backdrop.classList.remove('show');
      }

      // เด้งทุกครั้งเมื่อโหลดหน้า
      window.addEventListener('DOMContentLoaded', () => {
        setTimeout(openPromo, 150);
      });

      // ปิดด้วยปุ่ม X
      btnClose?.addEventListener('click', closePromo);

      // ปิดเมื่อคลิกนอกโมดัล
      backdrop?.addEventListener('click', (e)=>{
        if (e.target === backdrop) closePromo();
      });

      // ปิดด้วย ESC
      document.addEventListener('keydown', (e)=>{
        if (e.key === 'Escape' && backdrop.classList.contains('show')) closePromo();
      });
    })();
    </script>
</body>
</html>
<?php $conn->close(); ?>
