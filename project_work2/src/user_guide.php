<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
$is_staff = (isset($_SESSION["role"]) && $_SESSION["role"] === 'staff');
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
</head>
<body>
<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">คู่มือการใช้งาน</span>
    <!-- ลบ user dropdown ออก -->
  </div>
</nav>
<?php include 'sidebar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (Desktop Only) -->
        <?php include 'sidebar.php'; ?>
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-0">
            <div class="main-content mt-4 mt-md-5">
                <!-- User Dropdown (Desktop Only) -->
                <!-- ลบ user dropdown (desktop) ออก -->
                <div class="mb-4 text-center">
                    <h1 class="guide-title"><i class="fas fa-book-open"></i> คู่มือการใช้งานระบบ (User Guide)</h1>
                    <p class="lead">สำหรับผู้ดูแลระบบและผู้ใช้งานทั่วไป</p>
                </div>
                <?php if ($is_staff): ?>
                <div class="guide-section">
                    <h3><i class="fas fa-info-circle icon"></i> ภาพรวมระบบ (System Overview)</h3>
                    <ul>
                        <li>ระบบนี้ใช้สำหรับจัดการครุภัณฑ์และวัสดุสำนักงาน (Inventory & Office Supplies Management)</li>
                        <li>รองรับการยืม-คืน, การเบิกวัสดุ, การจัดการหมวดหมู่, ยี่ห้อ, ผู้ใช้, รายงาน, และประวัติการเคลื่อนไหว</li>
                        <li>มีระบบล็อกอิน แยกสิทธิ์ผู้ดูแล (Admin), เจ้าหน้าที่พัสดุ (Procurement), และเจ้าหน้าที่ทั่วไป (Staff)</li>
                        <li>Sidebar แสดงเมนูตามสิทธิ์ของผู้ใช้ (Staff เห็นเฉพาะ "การยืม-คืน" และ "คู่มือการใช้งาน")</li>
                    </ul>
                </div>
                <div class="guide-section">
                    <h3><i class="fas fa-sign-in-alt icon"></i> การเข้าสู่ระบบ (Login)</h3>
                    <ul>
                        <li>เข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่านที่ได้รับ</li>
                        <li>หากลืมรหัสผ่าน สามารถรีเซ็ตได้ที่หน้า <b>ลืมรหัสผ่าน</b></li>
                    </ul>
                </div>
                <div class="guide-section">
                    <h3><i class="fas fa-exchange-alt icon"></i> การยืม-คืนครุภัณฑ์ (Borrow/Return)</h3>
                    <ul>
                        <li>เจ้าหน้าที่ (Staff) สามารถส่งคำขอยืมครุภัณฑ์ (สถานะ "รออนุมัติ")</li>
                        <li>ติดตามสถานะการยืม-คืน: รออนุมัติ, อนุมัติแล้ว, กำลังยืม, รอยืนยันคืน, คืนแล้ว, ถูกปฏิเสธ</li>
                        <li>Staff เห็นเฉพาะรายการของตนเอง</li>
                        <li>สามารถส่งคำขอคืน, และ admin/procurement ยืนยันหรือปฏิเสธการคืน</li>
                    </ul>
                </div>
                    <div class="guide-section">
                        <h3><i class="fas fa-question-circle icon"></i> คำถามที่พบบ่อย (FAQ)</h3>
                        <ul>
                            <li><b>Q:</b> ลืมรหัสผ่านทำอย่างไร? <br><b>A:</b> ใช้เมนู "ลืมรหัสผ่าน" ที่หน้าเข้าสู่ระบบ</li>
                            <li><b>Q:</b> Staff เห็นเมนูอะไรบ้าง? <br><b>A:</b> เห็นเฉพาะ "การยืม-คืน" และ "คู่มือการใช้งาน" เท่านั้น</li>
                            <li><b>Q:</b> ระบบใช้ปี พ.ศ. ไทยหรือไม่? <br><b>A:</b> ใช่ ระบบแสดงปีเป็น พ.ศ. และเวลาตรงกับโซนไทย</li>
                        </ul>
                    </div>
                    <div class="guide-section">
                        <h3><i class="fas fa-user-shield icon"></i> สิทธิ์การเข้าถึงเมนูของ Staff</h3>
                        <ul>
                            <li>ผู้ใช้ประเภท <b>Staff</b> จะไม่เห็นเมนู <b>แดชบอร์ด</b> (Dashboard), <b>วัสดุสำนักงาน</b> (Office Supplies), และ <b>การเบิกวัสดุ</b> (Dispensation) ในแถบเมนูด้านข้าง</li>
                            <li>Staff จะเห็นเฉพาะเมนู <b>การยืม-คืน</b> และ <b>คู่มือการใช้งาน</b> เท่านั้น</li>
                            <li>เมนูอื่น ๆ เช่น รายงาน, จัดการผู้ใช้, จัดการครุภัณฑ์ จะมองเห็นได้เฉพาะผู้ดูแลระบบ (Admin) หรือเจ้าหน้าที่พัสดุ (Procurement)</li>
                        </ul>
                    </div>
                <?php else: ?>
                <div class="guide-section">
                    <h3><i class="fas fa-history icon"></i> ประวัติการเคลื่อนไหว (Equipment Movement History)</h3>
                    <ul>
                        <li>บันทึกการเคลื่อนไหวครุภัณฑ์อัตโนมัติเมื่อมีการยืม-คืน หรือโอนย้าย</li>
                        <li>สามารถเพิ่มประวัติการเคลื่อนไหวแบบ manual ได้</li>
                        <li>แสดงข้อมูลผู้ดำเนินการ, ผู้รับโอน, แผนก, ประเภทการเคลื่อนไหว ฯลฯ</li>
                        <li>ดูประวัติย้อนหลังและกรองข้อมูลได้</li>
                    </ul>
                </div>
                <div class="guide-section">
                    <h3><i class="fas fa-boxes icon"></i> การจัดการครุภัณฑ์ (Item Management)</h3>
                    <ul>
                        <li>เพิ่ม/แก้ไข/ลบข้อมูลครุภัณฑ์ เช่น เลขครุภัณฑ์, รุ่น, หมวดหมู่, ยี่ห้อ, ราคา, สถานะ ฯลฯ</li>
                        <li>รองรับการระบุจำนวน, ราคาต่อหน่วย, และคำนวณราคารวมอัตโนมัติ</li>
                        <li>สามารถเพิ่ม serial number ให้กับครุภัณฑ์แต่ละชิ้น (หนึ่ง serial ต่อแถว)</li>
                        <li>Admin สามารถเพิ่ม serial ให้กับรายการที่มีอยู่แล้ว</li>
                        <li>สามารถแนบรูปภาพและระบุรายละเอียดเพิ่มเติมได้</li>
                    </ul>
                </div>
                <div class="guide-section">
                    <h3><i class="fas fa-paperclip icon"></i> การจัดการวัสดุสำนักงาน (Office Supplies)</h3>
                    <ul>
                        <li>เพิ่ม/แก้ไข/ลบรายการวัสดุสำนักงาน</li>
                        <li>ระบุจำนวนคงเหลือและสต็อกขั้นต่ำ</li>
                        <li>บันทึกการเบิกวัสดุ เลือกวัสดุและระบุจำนวนที่เบิก ระบบจะหักจำนวนคงเหลืออัตโนมัติ</li>
                    </ul>
                </div>
                <div class="guide-section">
                    <h3><i class="fas fa-layer-group icon"></i> การจัดการหมวดหมู่/ยี่ห้อ (Categories/Brands)</h3>
                    <ul>
                        <li>เพิ่ม/แก้ไข/ลบหมวดหมู่และยี่ห้อ เพื่อใช้กับครุภัณฑ์</li>
                    </ul>
                </div>
                <div class="guide-section">
                    <h3><i class="fas fa-users icon"></i> การจัดการผู้ใช้ (User Management)</h3>
                    <ul>
                        <li>Admin สามารถเพิ่ม/แก้ไข/ลบผู้ใช้ และกำหนดสิทธิ์ (admin, procurement, staff)</li>
                        <li>ผู้ใช้สามารถแก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่านได้ รวมถึงแก้ไข username</li>
                    </ul>
                </div>
                <div class="guide-section">
                    <h3><i class="fas fa-chart-bar icon"></i> รายงาน (Reports)</h3>
                    <ul>
                        <li>ดูรายงานสรุปครุภัณฑ์, รายงานคงคลัง, รายงานเบิกจ่าย, รายงานวัสดุใกล้หมด ฯลฯ</li>
                        <li>มีกราฟแสดงมูลค่าครุภัณฑ์รวมแยกตามปีงบประมาณ (Chart.js)</li>
                        <li>สามารถ Export ข้อมูลเป็น Excel/CSV ได้</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
    <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
    | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
    | © 2025
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>