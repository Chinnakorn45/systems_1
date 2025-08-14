<?php
session_start();
require_once __DIR__ . '/../src/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['procurement', 'admin', 'staff'])) {
    die('Unauthorized');
}
function thaidate($date, $format = 'd/m/Y H:i') {
    $ts = strtotime($date);
    $result = date($format, $ts);
    $year_th = date('Y', $ts) + 543;
    return str_replace(date('Y', $ts), $year_th, $result);
}
// รับรายการที่เลือก
$selected = isset($_POST['selected']) && is_array($_POST['selected']) ? array_map('intval', $_POST['selected']) : [];
// เตรียมตัวแปรสำหรับหน่วยงาน/กลุ่มงาน
$department_id = '';
$department_name = '';
if ($selected) {
    $ids = implode(',', $selected);
    $sql = "SELECT r.*, u_reported.full_name AS reported_by_name, u_reported.department, u_assigned.full_name AS assigned_to_name FROM repairs r
    LEFT JOIN users u_reported ON r.reported_by = u_reported.user_id
    LEFT JOIN users u_assigned ON r.assigned_to = u_assigned.user_id
    WHERE r.repair_id IN ($ids)
    ORDER BY r.created_at DESC";
    $repairs = $conn->query($sql);
    if ($row = $repairs->fetch_assoc()) {
        // ดึงชื่อหน่วยงาน (แผนกหลัก) และกลุ่มงาน (แผนกย่อย) จาก departments
        $department_id = $row['department'] ?? '';
        $main_department = '';
        $sub_department = '';
        $department_name = $row['department'] ?? '';
        if ($department_name) {
            $sql_dep = "SELECT department_id, department_name, parent_id FROM departments WHERE department_name = '" . $conn->real_escape_string($department_name) . "' LIMIT 1";
            $res_dep = $conn->query($sql_dep);
            if ($dep = $res_dep->fetch_assoc()) {
                // ถ้า parent_id เป็น null หรือ 'NULL' แสดงว่าเป็นแผนกหลัก
                if (is_null($dep['parent_id']) || $dep['parent_id'] === null || strtoupper($dep['parent_id']) === 'NULL') {
                    $main_department = $dep['department_name'];
                    $sub_department = '';
                } else {
                    // ถ้ามี parent_id แสดงว่าเป็นกลุ่มงาน
                    $sub_department = $dep['department_name'];
                    $sql_main = "SELECT department_name FROM departments WHERE department_id = '" . intval($dep['parent_id']) . "' LIMIT 1";
                    $res_main = $conn->query($sql_main);
                    if ($main = $res_main->fetch_assoc()) {
                        $main_department = $main['department_name'];
                    }
                }
            }
        }
        $repairs->data_seek(0); // reset pointer for while loop
    }
} else {
    $repairs = false;
}
// เตรียมเดือนภาษาไทย
$months_th = [
  1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
  5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
  9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
$day = $month = $year_th = '';
if ($repairs && $repairs->num_rows > 0) {
    $row = $repairs->fetch_assoc();
    $ts = strtotime($row['created_at']);
    $day = date('j', $ts);
    $month = $months_th[(int)date('n', $ts)];
    $year_th = date('Y', $ts) + 543;
    $repairs->data_seek(0); // reset pointer for while loop
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 20px;
            font-size: 14px; /* ลดขนาดฟอนต์จาก 14px เป็น 12px */
            color: #333;
        }
        .container {
            width: 850px;
            margin: 0 auto;
            padding: 30px;
            border: none !important;
            box-shadow: none !important;
            background: none !important;
        }
        .header h3 {
            margin: 0;
            font-size: 18px; /* ลดขนาดฟอนต์หัวเรื่อง */
            font-weight: 700;
        }
        .doc-no {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 11px; /* ลดขนาดฟอนต์เลขที่ */
            display: flex;
            align-items: baseline;
        }
        .doc-no label {
            white-space: nowrap;
            margin-right: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            align-items: baseline;
        }
        .info-item {
            display: flex;
            align-items: baseline;
            flex-grow: 1;
            margin-right: 20px;
        }
        .info-item:last-child {
            margin-right: 0;
        }
        .info-item label {
            white-space: nowrap;
            margin-right: 5px;
            font-weight: bold;
        }
        .dotted-line {
            border-bottom: 1px dotted #000;
            flex-grow: 1;
            min-width: 50px;
            padding-bottom: 2px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 6px 4px; /* ลด padding ตาราง */
            font-size: 12px;   /* ลดขนาดฟอนต์ในตาราง */
            text-align: center;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: 700;
        }
        td {
            height: 25px;
        }

        /* --- Signature Specific Styles --- */
        .signature-container-full-width {
            display: flex;
            width: 100%;
            margin-top: 30px;
        }

        .signature-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Default alignment */
            min-width: 300px; /* Ensure enough space for signature line */
        }
        .signature-group.align-right {
            margin-left: auto; /* Pushes the group to the right */
            align-items: flex-end; /* Aligns contents to the right within the group */
        }

        .signature-row {
            display: flex;
            align-items: baseline;
            width: 100%;
            margin-bottom: 5px;
        }
        .signature-label-fixed,
        .signature-role,
        .signature-paren-row {
            font-size: 12px; /* ลดขนาดฟอนต์ลายเซ็น */
        }
        .signature-flex-dotted-line {
            border-bottom: 1px dotted #000;
            flex-grow: 1;
            min-width: 50px;
            padding-bottom: 2px;
            text-align: center;
        }
        .signature-role {
            white-space: nowrap;
            margin-left: 5px;
            font-size: 14px;
        }
        .signature-paren-row {
            display: flex;
            width: 100%;
            font-size: 14px;
        }
        .signature-paren-start {
            white-space: nowrap;
            margin-right: 2px;
        }
        .signature-paren-end {
            white-space: nowrap;
            margin-left: 2px;
        }

        /* --- Other Styles --- */
        .checkbox-group {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.1);
        }
        .indent {
            margin-left: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="position: relative; text-align: center; margin-bottom: 20px;">
            <div class="doc-no" style="position: absolute; top: 0; right: 0; font-size: 11px; display: flex; align-items: baseline;">
                <label for="doc-no">เลขที่</label>
                <div class="dotted-line" style="width: 100px;"></div>
            </div>
            <h3 style="margin: 0; font-size: 18px; font-weight: 700;">ใบแจ้งซ่อม โรงพยาบาลมะเร็งสุราษฎร์ธานี</h3>
        </div>

        <div class="info-row">
            <div class="info-item" style="flex-basis: 20%;">
                <label for="date">วันที่</label>
                <div class="dotted-line" style="width: 40px; display:inline-block; text-align:center;"><?= htmlspecialchars($day) ?></div>
            </div>
            <div class="info-item" style="flex-basis: 20%;">
                <label for="month">เดือน</label>
                <div class="dotted-line" style="width: 70px; display:inline-block; text-align:center;"><?= htmlspecialchars($month) ?></div>
            </div>
            <div class="info-item" style="flex-basis: 20%;">
                <label for="year">พ.ศ.</label>
                <div class="dotted-line" style="width: 60px; display:inline-block; text-align:center;"><?= htmlspecialchars($year_th) ?></div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-item" style="flex-basis: 30%;">
                <label for="unit">หน่วยงาน</label>
                <div class="dotted-line"><?= htmlspecialchars($main_department) ?></div>
            </div>
            <div class="info-item" style="flex-basis: 30%;">
                <label for="section">กลุ่มงาน</label>
                <div class="dotted-line"><?= htmlspecialchars($sub_department) ?></div>
            </div>
        </div>

        <br>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">ลำดับที่</th>
                    <th style="width: 42%;">รายการส่งซ่อม</th>
                    <th style="width: 25%;">หมายเลขครุภัณฑ์</th>
                    <th style="width: 25%;">รายการชำรุด</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $max_rows = 4;
            $i = 1;
            if ($repairs && $repairs->num_rows > 0) {
                while ($row = $repairs->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $i . '</td>';
                    echo '<td>' . htmlspecialchars($row['model_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['asset_number']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['issue_description']) . '</td>';
                    echo '</tr>';
                    $i++;
                    if ($i > $max_rows) break;
                }
            }
            // เติมแถวว่างถ้ายังไม่ครบ 4
            for (; $i <= $max_rows; $i++) {
                echo '<tr><td>' . $i . '</td><td></td><td></td><td></td></tr>';
            }
            ?>
            </tbody>
        </table>

        <div class="signature-container-full-width">
            <div class="signature-group align-right">
                <div class="signature-row">
                    <span class="signature-label-fixed">ลงชื่อ</span>
                    <div class="signature-flex-dotted-line"></div>
                    <span class="signature-role">ผู้ส่งซ่อม</span>
                </div>
                <div class="signature-paren-row">
                    <span class="signature-paren-start">(</span>
                    <div class="signature-flex-dotted-line"></div>
                    <span class="signature-paren-end">)</span>
                </div>
            </div>
        </div>


        <div style="margin-top: 25px;">
            <p class="section-title">เรียน หัวหน้างานพัสดุ</p>
            <p>ผลการตรวจสอบเบื้องต้น พบว่า</p>
            <div class="checkbox-group indent">
                <label><input type="checkbox" name="repair-status"> ซ่อมได้ โดยไม่ต้องขออนุมัติซื้อวัสดุ</label>
                <label><input type="checkbox" name="repair-status"> ซ่อมได้ โดยขออนุมัติซื้อวัสดุเพื่อดำเนินการซ่อม ดังนี้</label>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">ลำดับที่</th>
                    <th style="width: 50%;">รายการ</th>
                    <th style="width: 20%;">จำนวน</th>
                    <th style="width: 20%;">หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>1</td><td></td><td></td><td></td></tr>
                <tr><td>2</td><td></td><td></td><td></td></tr>
                <tr><td>3</td><td></td><td></td><td></td></tr>
                <tr><td>4</td><td></td><td></td><td></td></tr>
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            <p class="section-title">ซ่อมไม่ได้</p>
            <div class="checkbox-group indent">
                <label><input type="checkbox" name="cannot-repair"> เห็นควรส่งอนุมัติจ้างช่างภายนอกเพื่อดำเนินการ</label>
                <label><input type="checkbox" name="cannot-repair"> เห็นควรแจ้งหน่วยงานเพื่อดำเนินการขออนุมัติทดแทน</label>
            </div>
        </div>

        <div class="signature-container-full-width">
            <div class="signature-group align-right">
                <div class="signature-row">
                    <span class="signature-label-fixed">ลงชื่อ</span>
                    <div class="signature-flex-dotted-line"></div>
                    <span class="signature-role">ช่างผู้ตรวจสอบ</span>
                </div>
                <div class="signature-paren-row">
                    <span class="signature-paren-start">(</span>
                    <div class="signature-flex-dotted-line"></div>
                    <span class="signature-paren-end">)</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <p class="section-title">ผลการตรวจสอบ/ประเมินของช่างภายนอก ดังนี้</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">ลำดับที่</th>
                    <th style="width: 50%;">รายการ</th>
                    <th style="width: 20%;">จำนวน</th>
                    <th style="width: 20%;">หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>1</td><td></td><td></td><td></td></tr>
                <tr><td>2</td><td></td><td></td><td></td></tr>
                <tr><td>3</td><td></td><td></td><td></td></tr>
            </tbody>
        </table>

        <div class="signature-container-full-width">
            <div class="signature-group align-right">
                <div class="signature-row">
                    <span class="signature-label-fixed">ลงชื่อ</span>
                    <div class="signature-flex-dotted-line"></div>
                    <span class="signature-role">ช่างผู้ตรวจสอบ</span>
                </div>
                <div class="signature-paren-row">
                    <span class="signature-paren-start">(</span>
                    <div class="signature-flex-dotted-line"></div>
                    <span class="signature-paren-end">)</span>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
window.onload = function() {
  window.print();
};
window.onafterprint = function() {
  window.location.href = document.referrer || 'my_repairs.php';
};
</script>
</html>