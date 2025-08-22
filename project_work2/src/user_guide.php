<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
$is_staff = (isset($_SESSION["role"]) && $_SESSION["role"] === 'staff');
$role_badge = $is_staff ? 'Staff' : (($_SESSION["role"] ?? '') === 'procurement' ? 'Procurement' : 'Admin');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คู่มือการใช้งาน - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Thai (Prompt & Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="common-ui.css">
    <style>
        body{font-family:'Prompt','Kanit','Arial',sans-serif;background:#f7faf8;color:#2b2b2b}
        .guide-hero{
            background: radial-gradient(1200px 500px at 20% -20%, #e8f5e9 0%, #ffffff 55%),
                        linear-gradient(180deg,#ffffff 0%, #f7faf8 100%);
            border-bottom:1px solid #e9ecef;
        }
        .guide-title{font-weight:700;color:#256029}
        .lead{color:#466a4a}
        .badge-role{background:#2e7d32}
        .quick-card .icon-wrap{
            width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;
            background:#e8f5e9;border:1px dashed #b7dfbb
        }
        .quick-card{border:1px solid #e6efe7;border-radius:16px}
        .quick-card:hover{box-shadow:0 8px 20px rgba(0,0,0,.06);transform:translateY(-2px)}
        .toc-sticky{
            position:sticky;top:96px
        }
        .toc-sticky .list-group-item{
            border:0;border-left:3px solid transparent;padding-left:12px
        }
        .toc-sticky .list-group-item.active{
            background:#e8f5e9;border-left-color:#2e7d32;color:#1b5e20;font-weight:600
        }
        .guide-section{margin-bottom:32px}
        .guide-section h3{color:#1b5e20;margin-bottom:12px}
        .icon{color:#2e7d32}
        .callout{
            border-left:4px solid #2e7d32;background:#f0f7f1;padding:14px 16px;border-radius:6px
        }
        .kbd{background:#212529;color:#fff;border-radius:6px;padding:2px 8px;font-size:.9rem}
        .foot-meta{font-size:13px;color:#667a6a}
        .table-sm td,.table-sm th{padding:.45rem .6rem}
        .shadow-soft{box-shadow:0 8px 24px rgba(34,119,68,.06)}
        .section-divider{height:1px;background:linear-gradient(90deg,transparent,#dfe9e1,transparent);margin:24px 0}
        .bg-chip{background:#eef8ef;border:1px solid #d6edd9;padding:.25rem .6rem;border-radius:999px;font-size:.85rem}
        .btn-ghost{
            border:1px solid #cfe6d1;background:#fff;color:#2e7d32
        }
        .btn-ghost:hover{background:#eaf5eb;color:#1b5e20}
        /* Scroll behavior for in-page anchors */
        html{scroll-behavior:smooth}
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#tocList" data-bs-offset="120" tabindex="0">

<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">คู่มือการใช้งาน</span>
  </div>
</nav>

<?php include 'sidebar.php'; ?>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar (Desktop Only) -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 px-0">
      <!-- HERO -->
      <div class="guide-hero py-4 py-md-5 px-3 px-md-4 shadow-sm">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h1 class="guide-title mb-1"><i class="fas fa-book-open me-2"></i> คู่มือการใช้งานระบบ</h1>
            <p class="lead mb-0">สำหรับผู้ดูแลระบบ เจ้าหน้าที่พัสดุ และผู้ใช้งานทั่วไป</p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge badge-role"><?php echo htmlspecialchars($role_badge); ?></span>
            <span class="bg-chip"><i class="fa-solid fa-earth-asia me-1"></i>TH Locale</span>
            <span class="bg-chip"><i class="fa-regular fa-clock me-1"></i>อัปเดตล่าสุด: 2025-08-21</span>
          </div>
        </div>
      </div>

      <div class="main-content mt-4 mt-md-5 px-3 px-md-4">
        <div class="row g-4">
          <!-- TOC -->
          <div class="col-lg-3 order-2 order-lg-1">
            <div class="toc-sticky shadow-soft p-3 bg-white rounded-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0"><i class="fa-solid fa-list-check me-2 text-success"></i>สารบัญ</h6>
                <a href="#top" class="small text-decoration-none"><i class="fa-solid fa-angles-up"></i> บนสุด</a>
              </div>
              <div id="tocList" class="list-group list-group-flush">
                <a class="list-group-item" href="#overview">ภาพรวมระบบ</a>
                <a class="list-group-item" href="#login">เข้าสู่ระบบ</a>
                <a class="list-group-item" href="#quickstart">เริ่มต้นใช้งานเร็ว</a>
                <a class="list-group-item" href="#borrowreturn">ยืม-คืน</a>
                <?php if (!$is_staff): ?>
                  <a class="list-group-item" href="#items">จัดการครุภัณฑ์</a>
                  <a class="list-group-item" href="#supplies">วัสดุสำนักงาน</a>
                  <a class="list-group-item" href="#catsbrands">หมวดหมู่/ยี่ห้อ</a>
                  <a class="list-group-item" href="#users">ผู้ใช้และสิทธิ์</a>
                  <a class="list-group-item" href="#reports">รายงาน</a>
                <?php endif; ?>
                <?php if ($is_staff): ?>
                  <a class="list-group-item" href="#staffmenu">สิทธิ์เมนูของ Staff</a>
                <?php endif; ?>
                <a class="list-group-item" href="#faq">FAQ</a>
                <a class="list-group-item" href="#shortcuts">คีย์ลัด</a>
                <a class="list-group-item" href="#support">ช่วยเหลือ/ติดต่อ</a>
                <a class="list-group-item" href="#version">เวอร์ชันระบบ</a>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="col-lg-9 order-1 order-lg-2">
            <!-- Quick Actions -->
            <div id="top" class="mb-4">
              <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                  <a href="borrow.php" class="text-decoration-none text-reset">
                    <div class="quick-card p-3 bg-white rounded-3 d-flex align-items-center gap-3">
                      <div class="icon-wrap"><i class="fa-solid fa-arrows-rotate"></i></div>
                      <div>
                        <div class="fw-bold">ยืม-คืน</div>
                        <div class="text-muted small">สร้าง/ติดตามคำขอ</div>
                      </div>
                    </div>
                  </a>
                </div>
                <?php if (!$is_staff): ?>
                <div class="col-sm-6 col-xl-3">
                  <a href="items.php" class="text-decoration-none text-reset">
                    <div class="quick-card p-3 bg-white rounded-3 d-flex align-items-center gap-3">
                      <div class="icon-wrap"><i class="fa-solid fa-boxes-stacked"></i></div>
                      <div>
                        <div class="fw-bold">ครุภัณฑ์</div>
                        <div class="text-muted small">เพิ่ม/แก้ไข/รูปภาพ</div>
                      </div>
                    </div>
                  </a>
                </div>
                <div class="col-sm-6 col-xl-3">
                  <a href="supplies.php" class="text-decoration-none text-reset">
                    <div class="quick-card p-3 bg-white rounded-3 d-flex align-items-center gap-3">
                      <div class="icon-wrap"><i class="fa-solid fa-paperclip"></i></div>
                      <div>
                        <div class="fw-bold">วัสดุ</div>
                        <div class="text-muted small">สต็อก/เบิกจ่าย</div>
                      </div>
                    </div>
                  </a>
                </div>
                <div class="col-sm-6 col-xl-3">
                  <a href="reports.php" class="text-decoration-none text-reset">
                    <div class="quick-card p-3 bg-white rounded-3 d-flex align-items-center gap-3">
                      <div class="icon-wrap"><i class="fa-solid fa-chart-column"></i></div>
                      <div>
                        <div class="fw-bold">รายงาน</div>
                        <div class="text-muted small">สรุป/ดาวน์โหลด</div>
                      </div>
                    </div>
                  </a>
                </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="callout mb-4">
              <i class="fa-regular fa-lightbulb me-2"></i>
              เคล็ดลับ: บนมือถือ ให้แตะปุ่มเมนูมุมซ้ายบนเพื่อเปิดแถบเมนูทั้งหมด (Offcanvas Sidebar)
            </div>

            <!-- Overview -->
            <div id="overview" class="guide-section">
              <h3><i class="fas fa-info-circle icon me-2"></i> ภาพรวมระบบ (System Overview)</h3>
              <ul class="mb-2">
                <li>จัดการครุภัณฑ์และวัสดุสำนักงาน: เพิ่ม/แก้ไข/ค้นหา/แนบรูป/ดูประวัติ</li>
                <li>รองรับกระบวนการยืม-คืน พร้อมสถานะครบถ้วน และบันทึกการเคลื่อนไหว</li>
                <li>แยกสิทธิ์ผู้ใช้: Admin / Procurement / Staff และแสดงเมนูตามสิทธิ์</li>
                <li>สนับสนุนปี พ.ศ. (TH Locale) และโซนเวลาไทย</li>
              </ul>
              <div class="text-muted small">การนำทางด่วน: ใช้สารบัญด้านซ้ายหรือปุ่มลัดด้านบน</div>
            </div>

            <div class="section-divider"></div>

            <!-- Login -->
            <div id="login" class="guide-section">
              <h3><i class="fas fa-sign-in-alt icon me-2"></i> การเข้าสู่ระบบ (Login)</h3>
              <ol class="mb-2">
                <li>เข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่านที่ได้รับ</li>
                <li>ลืมรหัสผ่าน? ใช้เมนู <b>ลืมรหัสผ่าน</b> ที่หน้าเข้าสู่ระบบ</li>
              </ol>
              <div class="alert alert-success py-2 mb-0"><i class="fa-solid fa-check me-2"></i> หลังเข้าสู่ระบบสำเร็จ เมนูด้านซ้ายจะปรับตามสิทธิ์ของคุณโดยอัตโนมัติ</div>
            </div>

            <!-- Quickstart -->
            <div id="quickstart" class="guide-section">
              <h3><i class="fa-solid fa-rocket icon me-2"></i> เริ่มต้นใช้งานอย่างรวดเร็ว</h3>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="p-3 bg-white rounded-3 border">
                    <div class="fw-bold mb-2"><i class="fa-solid fa-barcode me-2 text-success"></i> เพิ่มครุภัณฑ์ (ฉบับย่อ)</div>
                    <ol class="mb-0 small">
                      <li>ไปที่เมนู <b>ครุภัณฑ์</b> → <span class="bg-chip">เพิ่ม</span></li>
                      <li>สแกน/กรอก Serial Number และเลขครุภัณฑ์</li>
                      <li>เลือก <b>รุ่น</b> (ระบบจะเติม <b>ยี่ห้อ</b> ให้อัตโนมัติ)</li>
                      <li>ระบุหมวดหมู่ จำนวน ราคา ระบบคำนวณราคารวมให้อัตโนมัติ</li>
                      <li>แนบรูปภาพ และบันทึก</li>
                    </ol>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="p-3 bg-white rounded-3 border">
                    <div class="fw-bold mb-2"><i class="fa-solid fa-hand-holding me-2 text-success"></i> ยืม-คืน (ฉบับย่อ)</div>
                    <ol class="mb-0 small">
                      <li>ไปที่ <b>ยืม-คืน</b> → สร้างคำขอยืม</li>
                      <li>เลือกครุภัณฑ์/ระบุรายละเอียด</li>
                      <li>ติดตามสถานะ: รออนุมัติ → อนุมัติแล้ว → กำลังยืม → รอยืนยันคืน → คืนแล้ว</li>
                    </ol>
                  </div>
                </div>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- Borrow/Return -->
            <div id="borrowreturn" class="guide-section">
              <h3><i class="fas fa-exchange-alt icon me-2"></i> การยืม-คืนครุภัณฑ์ (Borrow/Return)</h3>
              <ul>
                <li>ส่งคำขอยืม (สถานะเริ่มต้น: <span class="bg-chip">รออนุมัติ</span>)</li>
                <li>ติดตามสถานะ: รออนุมัติ, อนุมัติแล้ว, กำลังยืม, รอยืนยันคืน, คืนแล้ว, ถูกปฏิเสธ</li>
                <li>Staff เห็นเฉพาะรายการของตนเอง</li>
                <li>สามารถส่งคำขอคืน และให้ Admin/Procurement ยืนยันการคืน</li>
              </ul>
            </div>

            <?php if (!$is_staff): ?>
            <div class="section-divider"></div>

            <!-- Items -->
            <div id="items" class="guide-section">
              <h3><i class="fas fa-boxes icon me-2"></i> การจัดการครุภัณฑ์ (Item Management)</h3>
              <ul>
                <li>เพิ่ม/แก้ไข/ลบ: เลขครุภัณฑ์, รุ่น, หมวดหมู่, ยี่ห้อ, ราคา, สถานะ</li>
                <li>รองรับ Serial Number รายชิ้น (หนึ่ง serial ต่อแถว)</li>
                <li>แนบรูปหลายไฟล์ และกำหนดรูปหลักอัตโนมัติ</li>
                <li>คำนวณราคารวมจาก จำนวน × ราคาต่อหน่วย</li>
                <li>แสดง/เพิ่ม <b>ประวัติการเคลื่อนไหว</b> (โอนย้าย, ยืม-คืน, ปรับปรุงสภาพ ฯลฯ)</li>
              </ul>
            </div>

            <div class="section-divider"></div>

            <!-- Supplies -->
            <div id="supplies" class="guide-section">
              <h3><i class="fas fa-paperclip icon me-2"></i> การจัดการวัสดุสำนักงาน (Office Supplies)</h3>
              <ul>
                <li>เพิ่ม/แก้ไข/ลบ รายการวัสดุ</li>
                <li>ระบุจำนวนคงเหลือและสต็อกขั้นต่ำ</li>
                <li>บันทึกการเบิกวัสดุ ระบบจะหักสต็อกอัตโนมัติ</li>
                <li>ดึงรายงานวัสดุใกล้หมด</li>
              </ul>
            </div>

            <div class="section-divider"></div>

            <!-- Categories/Brands -->
            <div id="catsbrands" class="guide-section">
              <h3><i class="fas fa-layer-group icon me-2"></i> การจัดการหมวดหมู่/ยี่ห้อ</h3>
              <ul>
                <li>เพิ่ม/แก้ไข/ลบ หมวดหมู่และยี่ห้อ</li>
                <li>เชื่อมโยง <b>รุ่น</b> กับ <b>ยี่ห้อ</b> เพื่อให้ช่องยี่ห้อกรอกอัตโนมัติ</li>
              </ul>
            </div>

            <div class="section-divider"></div>

            <!-- Users -->
            <div id="users" class="guide-section">
              <h3><i class="fas fa-users icon me-2"></i> การจัดการผู้ใช้ (User Management)</h3>
              <ul>
                <li>Admin เพิ่ม/แก้ไข/ลบผู้ใช้ กำหนดสิทธิ์ (admin, procurement, staff)</li>
                <li>ผู้ใช้แก้ไขข้อมูลส่วนตัว/รหัสผ่าน/ชื่อผู้ใช้ได้</li>
              </ul>
            </div>

            <div class="section-divider"></div>

            <!-- Reports -->
            <div id="reports" class="guide-section">
              <h3><i class="fas fa-chart-bar icon me-2"></i> รายงาน (Reports)</h3>
              <ul>
                <li>สรุปครุภัณฑ์, คงคลัง, เบิกจ่าย, วัสดุใกล้หมด</li>
                <li>กราฟมูลค่าครุภัณฑ์แยกตามปีงบฯ (Chart.js)</li>
                <li>Export Excel/CSV</li>
              </ul>
            </div>
            <?php else: ?>
            <div class="section-divider"></div>

            <!-- Staff Menu Rights -->
            <div id="staffmenu" class="guide-section">
              <h3><i class="fas fa-user-shield icon me-2"></i> สิทธิ์การเข้าถึงเมนูของ Staff</h3>
              <ul>
                <li><b>ไม่เห็น</b>: แดชบอร์ด, วัสดุสำนักงาน, การเบิกวัสดุ, รายงาน, จัดการผู้ใช้, จัดการครุภัณฑ์</li>
                <li><b>เห็น</b>: เฉพาะ “การยืม-คืน” และ “คู่มือการใช้งาน”</li>
              </ul>
            </div>
            <?php endif; ?>

            <div class="section-divider"></div>

            <!-- FAQ -->
            <div id="faq" class="guide-section">
              <h3><i class="fas fa-question-circle icon me-2"></i> คำถามที่พบบ่อย (FAQ)</h3>
              <div class="accordion" id="faqAcc">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="f1">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#c1">ลืมรหัสผ่านทำอย่างไร?</button>
                  </h2>
                  <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc">
                    <div class="accordion-body">ไปที่หน้าเข้าสู่ระบบและเลือก <b>ลืมรหัสผ่าน</b> จากนั้นทำตามขั้นตอนที่ระบบแจ้ง</div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="f2">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">Staff เห็นเมนูอะไรบ้าง?</button>
                  </h2>
                  <div id="c2" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                    <div class="accordion-body">เฉพาะ “การยืม-คืน” และ “คู่มือการใช้งาน” เท่านั้น</div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="f3">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">ระบบใช้ปี พ.ศ. ไทยไหม?</button>
                  </h2>
                  <div id="c3" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                    <div class="accordion-body">ใช่ ระบบแสดงปีเป็น พ.ศ. และเวลาโซนไทย</div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="f4">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c4">เพิ่มรุ่น/ยี่ห้อ/หมวดหมู่จากหน้าไหน?</button>
                  </h2>
                  <div id="c4" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                    <div class="accordion-body">จากหน้าเพิ่ม/แก้ไขครุภัณฑ์ กดปุ่ม “จัดการรุ่น/ยี่ห้อ/หมวดหมู่” ภายในฟอร์มได้เลย</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- Shortcuts -->
            <div id="shortcuts" class="guide-section">
              <h3><i class="fa-solid fa-keyboard icon me-2"></i> คีย์ลัด (Keyboard Shortcuts)</h3>
              <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white mb-2">
                  <thead class="table-light">
                    <tr><th>คีย์</th><th>การทำงาน</th></tr>
                  </thead>
                  <tbody>
                    <tr><td><span class="kbd">/</span></td><td>โฟกัสช่องค้นหา (ถ้ามี)</td></tr>
                    <tr><td><span class="kbd">g</span> แล้ว <span class="kbd">b</span></td><td>ไปหน้า ยืม-คืน</td></tr>
                    <?php if (!$is_staff): ?>
                    <tr><td><span class="kbd">g</span> แล้ว <span class="kbd">i</span></td><td>ไปหน้า ครุภัณฑ์</td></tr>
                    <tr><td><span class="kbd">g</span> แล้ว <span class="kbd">r</span></td><td>ไปหน้า รายงาน</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <div class="text-muted small">* คีย์ลัดทำงานในหน้าเดสก์ท็อปเป็นหลัก และอาจถูกเบราเซอร์สงวนไว้บางส่วน</div>
            </div>

            <div class="section-divider"></div>

            <!-- Support -->
            <div id="support" class="guide-section">
              <h3><i class="fa-solid fa-headset icon me-2"></i> ช่วยเหลือ/ติดต่อ</h3>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="p-3 bg-white rounded-3 border h-100">
                    <div class="fw-bold mb-2"><i class="fa-regular fa-envelope me-2 text-success"></i> ช่องทางติดต่อ</div>
                    <ul class="mb-0">
                      <li>อีเมลฝ่ายดูแลระบบ: <a href="mailto:it-support@example.com">it-support@example.com</a></li>
                      <li>โทรภายใน: 1234</li>
                      <li>เวลาทำการ: จันทร์–ศุกร์ 08:30–16:30</li>
                    </ul>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="p-3 bg-white rounded-3 border h-100">
                    <div class="fw-bold mb-2"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i> แนะนำก่อนแจ้งปัญหา</div>
                    <ol class="small mb-0">
                      <li>ลองรีเฟรชหน้า หรือออก-เข้าใหม่</li>
                      <li>จับภาพหน้าจอข้อความผิดพลาด (ถ้ามี)</li>
                      <li>แจ้งขั้นตอนที่ทำก่อนเกิดปัญหาเพื่อให้ตรวจสอบได้เร็วขึ้น</li>
                    </ol>
                  </div>
                </div>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- Version -->
            <div id="version" class="guide-section">
              <h3><i class="fa-solid fa-code-branch icon me-2"></i> เวอร์ชันระบบ</h3>
              <div class="bg-white border rounded-3 p-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <div><span class="bg-chip"><i class="fa-solid fa-tag me-1"></i> v1.6.0</span></div>
                  <div><span class="bg-chip"><i class="fa-regular fa-calendar me-1"></i> 2025-08-21</span></div>
                  <div><span class="bg-chip"><i class="fa-solid fa-shield-halved me-1"></i> Security patches</span></div>
                </div>
                <div class="mt-3">
                  <details>
                    <summary class="fw-semibold cursor-pointer">บันทึกการเปลี่ยนแปลง (Changelog)</summary>
                    <ul class="mt-2 mb-0">
                      <li>เพิ่มหน้า “คู่มือ” รูปแบบใหม่ พร้อม TOC และคีย์ลัด</li>
                      <li>ปรับปรุงการนำทางบนมือถือ/เดสก์ท็อป</li>
                      <li>ปรับสี และระยะห่างให้อ่านง่ายขึ้น</li>
                    </ul>
                  </details>
                </div>
              </div>
              <div class="foot-meta mt-2">© 2025 | พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี</div>
            </div>

            <div class="d-flex gap-2 justify-content-end my-4">
              <a href="index.php" class="btn btn-ghost"><i class="fa-solid fa-house me-2"></i>กลับหน้าแรก</a>
              <a href="#top" class="btn btn-success"><i class="fa-solid fa-arrow-up me-2"></i>กลับขึ้นบน</a>
            </div>
          </div>
        </div>
      </div>

      <footer class="py-3 text-center bg-white border-top">
        <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
        พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
        | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
        | © 2025
      </footer>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Scrollspy refresh (เผื่อ sidebar/TOC โหลดหลังสุด)
document.addEventListener('DOMContentLoaded', function(){
  var dataSpyList = [].slice.call(document.querySelectorAll('[data-bs-spy="scroll"]'))
  dataSpyList.forEach(function (dataSpyEl) {
    bootstrap.ScrollSpy.getInstance(dataSpyEl) || new bootstrap.ScrollSpy(document.body, {
      target: '#tocList',
      offset: 120
    })
  })

  // คีย์ลัดพื้นฐาน
  document.addEventListener('keydown', function(e){
    // focus search (ถ้าหน้ามีช่องค้นหาใช้ id=search)
    if(e.key === '/'){
      var s = document.getElementById('search');
      if(s){ e.preventDefault(); s.focus(); }
    }
    // g then x
    window.__gkey = window.__gkey || {last:null,at:0}
    if(e.key.toLowerCase() === 'g'){
      window.__gkey.last='g'; window.__gkey.at=Date.now(); return;
    }
    if(window.__gkey.last==='g' && Date.now()-window.__gkey.at<1000){
      const k = e.key.toLowerCase();
      <?php if (!$is_staff): ?>
      if(k==='i'){ location.href='items.php'; }
      if(k==='r'){ location.href='reports.php'; }
      <?php endif; ?>
      if(k==='b'){ location.href='borrow.php'; }
      window.__gkey.last=null;
    }
  })
})
</script>
</body>
</html>
