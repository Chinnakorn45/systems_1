<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category_name = '';
$category_name_err = '';
$is_edit = false;

// ถ้าเป็นการแก้ไข ดึงข้อมูลเดิมมาแสดง
if ($category_id > 0) {
    $is_edit = true;
    $sql = "SELECT * FROM categories WHERE category_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $category_name = $row['category_name'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name']);
    if (empty($category_name)) {
        $category_name_err = "กรุณากรอกชื่อหมวดหมู่";
    } else {
        // ตรวจสอบชื่อซ้ำ (ยกเว้นของตัวเอง)
        $sql = "SELECT category_id FROM categories WHERE category_name = ?" . ($is_edit ? " AND category_id != ?" : "");
        if ($stmt = mysqli_prepare($link, $sql)) {
            if ($is_edit) {
                mysqli_stmt_bind_param($stmt, "si", $category_name, $category_id);
            } else {
                mysqli_stmt_bind_param($stmt, "s", $category_name);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $category_name_err = "ชื่อหมวดหมู่นี้ถูกใช้แล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }
    if (empty($category_name_err)) {
        if ($is_edit) {
            $sql = "UPDATE categories SET category_name = ? WHERE category_id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $category_name, $category_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: categories.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "INSERT INTO categories (category_name) VALUES (?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $category_name);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: categories.php");
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
    <title><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>หมวดหมู่ - ระบบบันทึกคลังครุภัณฑ์</title>
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
        <h2 class="mb-4"><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>หมวดหมู่</h2>
        <form action="" method="post">
            <div class="mb-3">
                <label for="category_name" class="form-label">ชื่อหมวดหมู่</label>
                <input type="text" class="form-control <?php echo !empty($category_name_err) ? 'is-invalid' : ''; ?>" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" required>
                <div class="invalid-feedback"><?php echo $category_name_err; ?></div>
            </div>
            <button type="submit" class="btn btn-main"><?php echo $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มหมวดหมู่'; ?></button>
            <a href="categories.php" class="btn btn-secondary ms-2">ยกเลิก</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 