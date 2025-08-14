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
            margin: 5mm;
            padding: 0;
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
            font-size: 0.9em;
            line-height: 1.5;
            color: #333;
            box-sizing: border-box;
        }
        @media print {
            body {
                border: 1px solid #000;
                box-shadow: none;
            }
        }
        h2 {
            text-align: center;
            color: #000;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 20px;
            font-size: 1.7em;
            letter-spacing: 0.5px;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.95em;
        }
        .header-info div {
            flex: 1;
        }
        .header-info .right {
            text-align: right;
        }
        .section-header {
            font-weight: bold;
            font-size: 1.05em;
            margin-top: 15px;
            margin-bottom: 8px;
            border-bottom: 1px dashed #666;
            padding-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 0.85em;
        }
        table, th, td {
            border: 1px solid #666;
        }
        th, td {
            padding: 5px 8px;
            text-align: left;
        }
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }
        .image-container {
            width: 100%;
            text-align: center;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .image-placeholder {
            width: 100%;
            max-width: 220px;
            height: 150px;
            background-color: #f8f8f8;
            border: 1px dashed #999;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            color: #aaa;
            font-size: 0.9em;
            text-align: center;
            line-height: 1.3;
            overflow: hidden;
        }
        .image-placeholder img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .purpose-section, .conditions-section {
            margin-bottom: 15px;
        }
        .purpose-section p, .conditions-section ul {
            margin-top: 5px;
            margin-bottom: 5px;
        }
        ul {
            padding-left: 20px;
        }
        .signature-block {
            margin-top: 25px;
            text-align: right;
            margin-right: 30px;
            line-height: 1.4;
        }
        .signature-line {
            display: inline-block;
            min-width: 200px;
            border-bottom: 1px dotted #000;
            text-align: center;
            margin-bottom: 5px;
        }
        .approval-section {
            margin-top: 20px;
            border-top: 1px dashed #999;
            padding-top: 15px;
        }
        .approval-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 15px;
        }
        .approval-box {
            flex: 1;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 0 5px;
            min-height: 100px;
            position: relative;
        }
        .approval-box p {
            margin: 0;
        }
        .approval-box .signature-block-small {
            position: absolute;
            bottom: 5px;
            right: 5px;
            text-align: right;
            font-size: 0.85em;
            line-height: 1.3;
        }
        .approval-box .signature-block-small .signature-line {
            min-width: 150px;
            margin-bottom: 2px;
        }
        .note-section {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            font-size: 0.85em;
        }
        .note-section h4 {
            margin-top: 0;
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin-bottom: 8px;
        }
        .note-section ul {
            list-style-type: disc;
            padding-left: 20px;
            margin-top: 0;
            margin-bottom: 0;
        }
        .no-print {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div style="text-align:center; font-size:1.5em; font-weight:bold; margin-bottom:2px; margin-top:0;">ใบคำขอยืมครุภัณฑ์</div>
    <div style="display: flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div style="font-size:1em;"><b>เลขที่ใบยืม:</b> <?= htmlspecialchars($data['borrow_id']) ?></span></div>
        <div style="font-size:1em;"><b>วันที่ยื่นคำขอ:</b> <?= thaidate('j M Y', $data['borrow_date']) ?></div>
    </div>
    <div class="section-header">ข้อมูลผู้ยืม</div>
    <div style="margin-bottom: 10px;">
        <div><b>ชื่อ-นามสกุล:</b> <?= htmlspecialchars($data['full_name']) ?></div>
        <div><b>ตำแหน่ง:</b> <?= htmlspecialchars($data['position'] ?? '-') ?></div>
        <div><b>หน่วยงาน/สังกัด:</b> <?= htmlspecialchars($data['department'] ?? '-') ?></div>
        <div><b>อีเมล (ราชการ):</b> <?= htmlspecialchars($data['email'] ?? '-') ?></div>
    </div>
    <div class="section-header">รายละเอียดครุภัณฑ์ที่ขอยืม</div>
    <div style="margin-bottom: 10px;">
        <div><b>รายการครุภัณฑ์:</b> <?= htmlspecialchars($data['model_name']) ?></div>
        <div><b>หมายเลขครุภัณฑ์:</b> <?= htmlspecialchars($data['item_number']) ?></div>
        <div><b>ยี่ห้อ:</b> <?= htmlspecialchars($data['brand']) ?></div>
        <div><b>รุ่น (Model):</b> <?= htmlspecialchars($data['model_name']) ?></div>
        <div><b>จำนวน:</b> <?= htmlspecialchars($data['quantity_borrowed']) ?></div>
    </div>
    <div class="image-container">
        <p style="margin-bottom: 5px;">รูปภาพครุภัณฑ์ (หรือตัวอย่างรุ่น):</p>
        <?php
        $image_path = '';
        if (!empty($data['image'])) {
            // ตรวจสอบว่ามีไฟล์ใน ../uploads หรือ ../../uploads
            if (file_exists('../' . $data['image'])) {
                $image_path = '../' . $data['image'];
            } elseif (file_exists('../../' . $data['image'])) {
                $image_path = '../../' . $data['image'];
            }
        }
        ?>
        <?php if ($image_path): ?>
            <div class="image-placeholder" style="background: #fff; border: none;">
                <img src="<?= htmlspecialchars($image_path) ?>" alt="รูปครุภัณฑ์" style="max-width: 220px; max-height: 150px; object-fit: contain;">
            </div>
        <?php else: ?>
            <div class="image-placeholder">
                <p>พื้นที่สำหรับติดรูปภาพ<br> (ขนาดประมาณ 220x150 px)</p>
            </div>
        <?php endif; ?>
    </div>
    <div class="section-header">กำหนดวันยืมและคืน</div>
    <table style="width: 60%; margin-bottom: 25px;">
        <tr>
            <th style="width: 40%;">กำหนดวันยืม</th>
            <td><?= thaidate('j M Y', $data['borrow_date']) ?></td>
        </tr>
        <tr>
            <th>กำหนดวันส่งคืน</th>
            <td><?= thaidate('j M Y', $data['due_date']) ?></td>
        </tr>
    </table>
    <div class="section-header approval-section">ความเห็นและลายมือชื่อเจ้าหน้าที่</div>
    <style>
    body {
        /* ลบ border กรอบขอบนอก */
        border: none !important;
        box-shadow: none !important;
    }
    .approval-row {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        margin-bottom: 15px;
        gap: 10px;
    }
    .approval-box {
        flex: 1;
        border: 1px solid #ddd;
        padding: 10px 10px 0 10px;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        position: relative;
        background: #fff;
    }
    .approval-box p {
        margin: 0 0 10px 0;
        font-weight: bold;
        font-size: 1.05em;
    }
    .signature-block-small {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        padding-bottom: 10px;
    }
    .signature-line {
        border-bottom: 1.5px dotted #333;
        min-width: 180px;
        margin-bottom: 5px;
        margin-top: 25px;
        display: block;
    }
    .signature-block-small .sig-name {
        text-align: center;
        width: 100%;
        font-size: 1em;
        margin-bottom: 2px;
    }
    .signature-block-small .sig-pos {
        text-align: left;
        width: 100%;
        font-size: 0.95em;
        color: #444;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }
    .info-row .left, .info-row .right {
        font-size: 1em;
        font-weight: normal;
    }
    </style>
    <div class="approval-row">
        <div class="approval-box">
            <p>ผู้ยืม</p>
            <div class="signature-block-small">
                <span class="signature-line"></span>
                <span class="sig-name">(...............................)</span>
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