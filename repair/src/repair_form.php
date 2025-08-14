<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: login.php'); exit;
}
// get department name for logged-in user
$dept_name = '-';
$user_id = intval($_SESSION['user_id']);
$user_res = $conn->query("SELECT department FROM users WHERE user_id = $user_id LIMIT 1");
if ($user_res && $user_res->num_rows) {
    $dept_name = $user_res->fetch_assoc()['department'];
}
// handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_name = $dept_name; // ใช้แผนก/ฝ่ายของผู้ใช้ที่ล็อกอินเสมอ
    if ($_POST['item_id'] === 'other') {
        $item_id = null;
        $asset_number = $_POST['asset_number']; // รับจาก input asset_number
        $serial_number = $_POST['other_serial_number'];
        $brand = $_POST['other_brand'];
        $model = $_POST['other_model'];
        $desc = $_POST['issue_description'];
    } else {
        $item_id = $_POST['item_id'];
        $asset_number = $_POST['asset_number']; // รับจาก input asset_number
        $serial_number = $_POST['serial_number'];
        $desc = $_POST['issue_description'];
        $brand = $_POST['brand'];
        $model = $_POST['model_name'];
    }
    $img = '';
    if (!empty($_FILES['image']['name'])) {
        $img = 'uploads/' . uniqid() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $img);
    }
    $stmt = $conn->prepare("INSERT INTO repairs (item_id, reported_by, issue_description, image, asset_number, serial_number, location_name, brand, model_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iisssssss', $item_id, $_SESSION['user_id'], $desc, $img, $asset_number, $serial_number, $location_name, $brand, $model);
    $stmt->execute();
    $success = true;

    // เรียกแจ้งเตือน Discord หลังบันทึกข้อมูลสำเร็จ
    require_once __DIR__ . '/send_discord_notification.php';
}
// get items
//$items = $conn->query("SELECT item_id, model_name FROM items");
$items = $conn->query("SELECT i.item_id, i.model_name FROM borrowings b JOIN items i ON b.item_id = i.item_id WHERE b.user_id = " . intval($_SESSION['user_id']) . " AND b.return_date IS NULL");
// get latest borrowing notes for this user
$latest_borrowing = $conn->query("SELECT notes FROM borrowings WHERE user_id = " . intval($_SESSION['user_id']) . " ORDER BY borrow_date DESC LIMIT 1");
$latest_notes = ($latest_borrowing && $latest_borrowing->num_rows) ? $latest_borrowing->fetch_assoc()['notes'] : '-';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แจ้งซ่อมครุภัณฑ์</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
    <h3>แจ้งซ่อมครุภัณฑ์</h3>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">แจ้งซ่อมสำเร็จ</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">เลือกครุภัณฑ์ (กรณีเลือกจะเป็นการดึงข้อมูลจากการยืมครุภัณฑ์ / ให้เลือกอื่น ๆ หากไม่มีการยืมครุภัณฑ์)</label>
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
<script>
function toggleOtherDetails(sel) {
    const isOther = sel.value === 'other';
    document.getElementById('other-details').style.display = isOther ? 'block' : 'none';
    document.getElementById('item-info-fields').style.display = isOther ? 'none' : 'block';
    if(isOther) {
        document.getElementById('asset_number').readOnly = true;
        document.getElementById('asset_number').value = '';
        document.getElementById('serial_number').value = '';
        document.getElementById('location_name').value = '';
    } else {
        document.getElementById('asset_number').readOnly = true;
    }
}
// ดึงข้อมูลครุภัณฑ์เมื่อเลือก
const itemSelect = document.querySelector('select[name="item_id"]');
itemSelect.addEventListener('change', function() {
    var itemId = this.value;
    if (itemId && itemId !== 'other') {
        fetch('get_item_info.php?item_id=' + itemId)
        .then(res => res.json())
        .then(data => {
            document.getElementById('asset_number').value = data.asset_number || '';
            document.getElementById('serial_number').value = data.serial_number || '';
            document.getElementById('brand').value = data.brand || '';
            document.getElementById('model_name').value = data.model_name || '';
            // ไม่ต้อง set location_name เพราะใช้ dept_name เสมอ
        });
        document.getElementById('other-details').style.display = 'none';
    } else {
        document.getElementById('asset_number').value = '';
        document.getElementById('serial_number').value = '';
        document.getElementById('brand').value = '';
        document.getElementById('model_name').value = '';
        document.getElementById('other-details').style.display = 'block';
        document.getElementById('asset_number').readOnly = true;
    }
});
// เมื่อเลือกอื่น ๆ ให้กรอก asset_number จากช่องอื่น ๆ
const assetOther = document.getElementById('asset_number_other');
if(assetOther) {
    assetOther.addEventListener('input', function() {
        document.getElementById('asset_number').value = this.value;
    });
}
</script>
</body>
</html>