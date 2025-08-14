<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ดึงยี่ห้อทั้งหมด
$brands = [];
$brand_result = mysqli_query($link, "SELECT * FROM brands ORDER BY brand_name");
while ($row = mysqli_fetch_assoc($brand_result)) {
    $brands[] = $row;
}

$model_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$model_name = "";
$brand_id = "";
$error = "";

// ถ้าแก้ไข ดึงข้อมูลเดิม
if ($model_id) {
    $q = mysqli_query($link, "SELECT * FROM models WHERE model_id = $model_id");
    if ($data = mysqli_fetch_assoc($q)) {
        $model_name = $data['model_name'];
        $brand_id = $data['brand_id'];
    } else {
        $error = "ไม่พบข้อมูลชื่อรุ่นนี้";
    }
}

// บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model_name = trim($_POST['model_name']);
    $brand_id = intval($_POST['brand_id']);
    if ($model_name === "" || !$brand_id) {
        $error = "กรุณากรอกชื่อรุ่นและเลือกยี่ห้อ";
    } else {
        // ตรวจสอบซ้ำชื่อรุ่นในยี่ห้อเดียวกัน
        $dup_sql = "SELECT * FROM models WHERE model_name=? AND brand_id=?" . ($model_id ? " AND model_id!=?" : "");
        $stmt = mysqli_prepare($link, $dup_sql);
        if ($model_id) {
            mysqli_stmt_bind_param($stmt, "sii", $model_name, $brand_id, $model_id);
        } else {
            mysqli_stmt_bind_param($stmt, "si", $model_name, $brand_id);
        }
        mysqli_stmt_execute($stmt);
        $dup = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($dup) > 0) {
            $error = "ชื่อรุ่นนี้มีอยู่แล้วในยี่ห้อเดียวกัน";
        } else {
            if ($model_id) {
                $stmt = mysqli_prepare($link, "UPDATE models SET model_name=?, brand_id=? WHERE model_id=?");
                mysqli_stmt_bind_param($stmt, "sii", $model_name, $brand_id, $model_id);
                mysqli_stmt_execute($stmt);
            } else {
                $stmt = mysqli_prepare($link, "INSERT INTO models (model_name, brand_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "si", $model_name, $brand_id);
                mysqli_stmt_execute($stmt);
            }
            header("location: brands.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $model_id ? "แก้ไขชื่อรุ่น" : "เพิ่มชื่อรุ่น"; ?> - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width:500px; margin-top:40px;">
    <h2 class="mb-4"><?php echo $model_id ? "แก้ไขชื่อรุ่น" : "เพิ่มชื่อรุ่น"; ?></h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="model_name" class="form-label">ชื่อรุ่น <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="model_name" name="model_name" value="<?php echo htmlspecialchars($model_name); ?>" required>
        </div>
        <div class="mb-3">
            <label for="brand_id" class="form-label">ยี่ห้อ <span class="text-danger">*</span></label>
            <select class="form-select" id="brand_id" name="brand_id" required>
                <option value="">-- เลือกยี่ห้อ --</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?php echo $b['brand_id']; ?>" <?php if($brand_id==$b['brand_id']) echo 'selected'; ?>><?php echo htmlspecialchars($b['brand_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-flex justify-content-between">
            <a href="brands.php" class="btn btn-secondary">ยกเลิก</a>
            <button type="submit" class="btn btn-success"><?php echo $model_id ? "บันทึกการแก้ไข" : "เพิ่มชื่อรุ่น"; ?></button>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 