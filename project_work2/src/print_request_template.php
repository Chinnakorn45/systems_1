<?php
require_once 'config.php';
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูล</div>';
    exit;
}
$borrow_id = intval($_GET['id']);
$type = $_GET['type'];
$sql = "SELECT b.*, i.model_name, i.item_number, i.brand, i.serial_number, i.image, u.full_name, u.department, u.username
        FROM borrowings b
        LEFT JOIN items i ON b.item_id = i.item_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.borrow_id = $borrow_id";
$result = mysqli_query($link, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูล</div>';
    exit;
}
$row = mysqli_fetch_assoc($result);
?>
<div style="max-width:700px;margin:auto;font-size:1.1em;">
    <div class="text-center mb-4">
        <img src="img/logo1.png" style="max-height:60px;vertical-align:middle;">
        <img src="img/logo2.png" style="max-height:60px;vertical-align:middle;margin-left:16px;">
        <h3 class="mt-3 mb-1" style="font-weight:bold;letter-spacing:1px;">
            <?php if($type=='borrow'): ?>
                ใบคำขอยืมครุภัณฑ์
            <?php else: ?>
                ใบขอโอนครุภัณฑ์
            <?php endif; ?>
        </h3>
        <div style="font-size:1em;color:#388e3c;">ระบบบันทึกคลังครุภัณฑ์</div>
    </div>
    <table class="table table-borderless mb-3" style="width:100%;">
        <tr>
            <td style="width:50%;"><b>เลขที่คำขอ:</b> <?= $row['borrow_id'] ?></td>
            <td><b>วันที่<?= $type=='borrow'?'ยืม':'โอน' ?>:</b> <?= thaidate('j M Y', $row['borrow_date']) ?></td>
        </tr>
        <tr>
            <td><b>ชื่อผู้<?= $type=='borrow'?'ยืม':'โอน' ?>:</b> <?= htmlspecialchars($row['full_name']) ?></td>
            <td><b>แผนก:</b> <?= htmlspecialchars($row['department']) ?></td>
        </tr>
        <tr>
            <td><b>ชื่อผู้ใช้ระบบ:</b> <?= htmlspecialchars($row['username']) ?></td>
            <td><b>กำหนดคืน:</b> <?= thaidate('j M Y', $row['due_date']) ?></td>
        </tr>
    </table>
    <hr>
    <h5 class="mb-2"><i class="fas fa-box"></i> รายการครุภัณฑ์</h5>
    <table class="table table-bordered mb-4">
        <thead class="table-light">
            <tr>
                <th>รุ่น</th>
                <th>ยี่ห้อ</th>
                <th>เลขครุภัณฑ์</th>
                <th>Serial Number</th>
                <th>รูป</th>
                <th>จำนวน</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($row['model_name']) ?></td>
                <td><?= htmlspecialchars($row['brand']) ?></td>
                <td><?= htmlspecialchars($row['item_number']) ?></td>
                <td><?= htmlspecialchars($row['serial_number'] ?? '') ?></td>
                <td><?php if (!empty($row['image'])): ?><img src="<?= htmlspecialchars($row['image']) ?>" alt="รูป" style="max-width:60px;max-height:60px;object-fit:cover;"> <?php endif; ?></td>
                <td><?= (int)$row['quantity_borrowed'] ?></td>
            </tr>
        </tbody>
    </table>
    <?php if($type=='borrow'): ?>
    <div class="mb-3"><b>เหตุผล/วัตถุประสงค์การยืม:</b> <?= htmlspecialchars($row['purpose'] ?? '-') ?></div>
    <?php else: ?>
    <div class="mb-3"><b>โอนให้ (ชื่อผู้รับ/แผนก):</b> <?= htmlspecialchars($row['transfer_to'] ?? '-') ?></div>
    <?php endif; ?>
    <div class="row mt-4 mb-2">
        <div class="col-6 text-center">
            <div style="height:60px;"></div>
            <div>.......................................................</div>
            <div>ผู้<?= $type=='borrow'?'ขอยืม':'ขอโอน' ?></div>
        </div>
        <div class="col-6 text-center">
            <div style="height:60px;"></div>
            <div>.......................................................</div>
            <div>ผู้อนุมัติ</div>
        </div>
    </div>
    <div class="text-end text-muted" style="font-size:0.95em;">พิมพ์เมื่อ <?= thaidate('j M Y', date('Y-m-d')) ?></div>
</div> 