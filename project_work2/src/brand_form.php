<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$brand_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$brand_name = '';
$brand_name_err = '';
$is_edit = false;

// ถ้าเป็นการแก้ไข ดึงข้อมูลเดิมมาแสดง
if ($brand_id > 0) {
    $is_edit = true;
    $sql = "SELECT * FROM brands WHERE brand_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $brand_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $brand_name = $row['brand_name'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand_name = trim($_POST['brand_name']);
    if (empty($brand_name)) {
        $brand_name_err = "กรุณากรอกชื่อยี่ห้อ";
    } else {
        // ตรวจสอบชื่อซ้ำ (ยกเว้นของตัวเอง)
        $sql = "SELECT brand_id FROM brands WHERE brand_name = ?" . ($is_edit ? " AND brand_id != ?" : "");
        if ($stmt = mysqli_prepare($link, $sql)) {
            if ($is_edit) {
                mysqli_stmt_bind_param($stmt, "si", $brand_name, $brand_id);
            } else {
                mysqli_stmt_bind_param($stmt, "s", $brand_name);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $brand_name_err = "ชื่อยี่ห้อนี้ถูกใช้แล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }
    if (empty($brand_name_err)) {
        if ($is_edit) {
            $sql = "UPDATE brands SET brand_name = ? WHERE brand_id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $brand_name, $brand_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: brands.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "INSERT INTO brands (brand_name) VALUES (?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $brand_name);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: brands.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>ยี่ห้อ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 500px; margin-top: 40px; }
        .form-label { font-weight: 500; }
        .btn-main { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .btn-main:hover { background: #764ba2; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4"><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>ยี่ห้อ</h2>
        <form action="" method="post">
            <div class="mb-3">
                <label for="brand_name" class="form-label">ชื่อยี่ห้อ</label>
                <input type="text" class="form-control <?php echo !empty($brand_name_err) ? 'is-invalid' : ''; ?>" id="brand_name" name="brand_name" value="<?php echo htmlspecialchars($brand_name); ?>" required>
                <div class="invalid-feedback"><?php echo $brand_name_err; ?></div>
            </div>
            <button type="submit" class="btn btn-main"><?php echo $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มยี่ห้อ'; ?></button>
            <a href="brands.php" class="btn btn-secondary ms-2">ยกเลิก</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>