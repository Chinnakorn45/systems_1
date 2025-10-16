<?php
require_once '../config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: ../login.php");
    exit;
}
if (!isset($_GET['id'])) {
    echo 'ไม่พบข้อมูล';
    exit;
}
$borrow_id = intval($_GET['id']);
$sql = "SELECT b.*, i.item_number, i.model_name, i.brand, i.image, u.full_name, u.department, u.position, u.email
        FROM borrowings b
        LEFT JOIN items i ON b.item_id = i.item_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.borrow_id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'i', $borrow_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
if (!$data) {
    echo 'ไม่พบข้อมูล';
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ใบคำขอยืมครุภัณฑ์ - โรงพยาบาลมะเร็งสุราษฎร์ธานี</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Sarabun', sans-serif;
    margin: 10mm auto;
    max-width: 210mm;
    font-size: 0.8em;
    line-height: 1.4;
    color: #333;
}
@media print {
    body { border: none !important; box-shadow: none !important; }
}
h2 {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin-bottom: 15px;
    font-size: 1.3em;
}
.section-header {
    font-weight: bold;
    font-size: 1em;
    margin-top: 12px;
    margin-bottom: 6px;
    border-bottom: 1px dashed #666;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8em;
    margin-bottom: 10px;
}
table, th, td { border: 1px solid #666; }
th, td { padding: 4px 6px; text-align: left; }
th { background-color: #e0e0e0; text-align: center; }
.image-container {
    width: 100%;
    text-align: center;
    margin-top: 6px;
    margin-bottom: 10px;
}
.image-placeholder {
    max-width: 200px;
    height: 130px;
    background: #f8f8f8;
    border: 1px dashed #999;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    font-size: 0.8em;
    color: #888;
}
.image-placeholder img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.approval-section {
    margin-top: 15px;
    border-top: 1px dashed #999;
    padding-top: 10px;
}
.approval-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
}
.approval-box {
    flex: 1;
    border: 1px solid #ccc;
    padding: 8px;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    background: #fff;
}
.approval-box p {
    font-weight: bold;
    font-size: 0.95em;
    margin: 0 0 4px 0;
}

/* ✅ ปรับให้ลายเซ็นตรงกลาง */
.signature-block-small {
    display: flex;
    flex-direction: column;
    align-items: center; /* จากเดิม flex-end → center */
    justify-content: flex-end;
    margin-top: auto;
    text-align: center;
    width: 100%;
}
.signature-line {
    border-bottom: 1px dotted #000;
    min-width: 160px;
    margin-bottom: 3px;
    margin-top: 20px;
}
.sig-name {
    font-size: 0.9em;
    margin-top: 2px;
    display: block;
    text-align: center;
}

.note-section {
    margin-top: 15px;
    padding: 8px;
    border: 1px solid #ccc;
    background: #f9f9f9;
    font-size: 0.75em;
}
.note-section h4 {
    margin: 0 0 5px 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 3px;
    color: #555;
}
.no-print { margin-top: 10px; text-align: center; }
</style>
</head>
<body>
<div style="text-align:center;font-size:1.3em;font-weight:bold;margin-bottom:4px;">ใบคำขอยืมครุภัณฑ์</div>
<div style="display:flex;justify-content:space-between;margin-bottom:8px;">
    <div><b>เลขที่ใบยืม:</b> <?= htmlspecialchars($data['borrow_id']) ?></div>
    <div><b>วันที่ยื่นคำขอ:</b> <?= thaidate('j M Y', $data['borrow_date']) ?></div>
</div>

<div class="section-header">ข้อมูลผู้ยืม</div>
<div>
    <div><b>ชื่อ-นามสกุล:</b> <?= htmlspecialchars($data['full_name']) ?></div>
    <div><b>ตำแหน่ง:</b> <?= htmlspecialchars($data['position'] ?? '-') ?></div>
    <div><b>หน่วยงาน/สังกัด:</b> <?= htmlspecialchars($data['department'] ?? '-') ?></div>
    <div><b>อีเมล (ราชการ):</b> <?= htmlspecialchars($data['email'] ?? '-') ?></div>
</div>

<div class="section-header">รายละเอียดครุภัณฑ์ที่ขอยืม</div>
<div>
    <div><b>รายการครุภัณฑ์:</b> <?= htmlspecialchars($data['model_name']) ?></div>
    <div><b>หมายเลขครุภัณฑ์:</b> <?= htmlspecialchars($data['item_number']) ?></div>
    <div><b>ยี่ห้อ:</b> <?= htmlspecialchars($data['brand']) ?></div>
    <div><b>รุ่น (Model):</b> <?= htmlspecialchars($data['model_name']) ?></div>
    <div><b>จำนวน:</b> <?= htmlspecialchars($data['quantity_borrowed']) ?></div>
</div>

<div class="image-container">
<p style="margin-bottom:4px;">รูปภาพครุภัณฑ์ (หรือตัวอย่างรุ่น):</p>
<?php
$image_path = '';
if (!empty($data['image'])) {
    if (file_exists('../' . $data['image'])) $image_path = '../' . $data['image'];
    elseif (file_exists('../../' . $data['image'])) $image_path = '../../' . $data['image'];
}
?>
<?php if ($image_path): ?>
<div class="image-placeholder" style="background:#fff;border:none;">
    <img src="<?= htmlspecialchars($image_path) ?>" alt="รูปครุภัณฑ์">
</div>
<?php else: ?>
<div class="image-placeholder">
    <p>พื้นที่สำหรับติดรูปภาพ<br>(ประมาณ 200x130 px)</p>
</div>
<?php endif; ?>
</div>

<div class="section-header">กำหนดวันยืมและคืน</div>
<table style="width:60%;margin-bottom:15px;">
<tr><th>กำหนดวันยืม</th><td><?= thaidate('j M Y', $data['borrow_date']) ?></td></tr>
<tr><th>กำหนดวันส่งคืน</th><td><?= thaidate('j M Y', $data['due_date']) ?></td></tr>
</table>

<div class="section-header approval-section">ความเห็นและลายมือชื่อเจ้าหน้าที่</div>
<div class="approval-row">
    <div class="approval-box">
        <p>ผู้ยืม</p>
        <div class="signature-block-small">
            <span class="signature-line"></span>
            <span class="sig-name">
                (<?= !empty($data['position']) ? htmlspecialchars($data['position']) : '...............................' ?>)
            </span>
        </div>
    </div>
    <div class="approval-box">
        <p>เจ้าหน้าที่พัสดุ/ผู้ดูแลครุภัณฑ์</p>
        <div class="signature-block-small">
            <span class="signature-line"></span>
            <span class="sig-name">(...............................)</span>
        </div>
    </div>
</div>

<div class="note-section">
    <h4>หมายเหตุ:</h4>
    <ul>
        <li>ผู้ยืมโปรดตรวจสอบสภาพครุภัณฑ์ก่อนรับไปใช้งาน</li>
        <li>กรณีขยายเวลา โปรดทำเรื่องขออนุมัติล่วงหน้า</li>
        <li>การยืมครุภัณฑ์มูลค่าสูง อาจต้องมีหลักประกันตามระเบียบของโรงพยาบาล</li>
    </ul>
</div>
</body>
</html>
<script>
window.onload = function() {
    window.print();
    window.onafterprint = function() {
        window.location.href = '../borrowings.php';
    };
};
</script>
