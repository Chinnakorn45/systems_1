<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// จำกัดสิทธิ์เฉพาะ admin
if ($_SESSION["role"] !== "admin") {
    header("location: index.php");
    exit;
}

$supply_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$supply_name = $description = $unit = $current_stock = $min_stock_level = '';
$supply_name_err = $unit_err = $current_stock_err = $min_stock_level_err = '';
$is_edit = false;

// ถ้าเป็นการแก้ไข ดึงข้อมูลเดิมมาแสดง
if ($supply_id > 0) {
    $is_edit = true;
    $sql = "SELECT * FROM office_supplies WHERE supply_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $supply_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $supply_name = $row['supply_name'];
            $description = $row['description'];
            $unit = $row['unit'];
            $current_stock = $row['current_stock'];
            $min_stock_level = $row['min_stock_level'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supply_name = trim($_POST['supply_name']);
    $description = trim($_POST['description']);
    $unit = trim($_POST['unit']);
    $current_stock = intval($_POST['current_stock']);
    $min_stock_level = intval($_POST['min_stock_level']);

    // Validate
    if (empty($supply_name)) $supply_name_err = "กรุณากรอกชื่อวัสดุ";
    if (empty($unit)) $unit_err = "กรุณากรอกหน่วยนับ";
    if ($current_stock < 0) $current_stock_err = "จำนวนคงเหลือต้องไม่ติดลบ";
    if ($min_stock_level < 0) $min_stock_level_err = "สต็อกขั้นต่ำต้องไม่ติดลบ";

    if (empty($supply_name_err) && empty($unit_err) && empty($current_stock_err) && empty($min_stock_level_err)) {
        if ($is_edit) {
            $sql = "UPDATE office_supplies SET supply_name=?, description=?, unit=?, current_stock=?, min_stock_level=? WHERE supply_id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssiii", $supply_name, $description, $unit, $current_stock, $min_stock_level, $supply_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: supplies.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "INSERT INTO office_supplies (supply_name, description, unit, current_stock, min_stock_level) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssii", $supply_name, $description, $unit, $current_stock, $min_stock_level);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: supplies.php");
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
    <title><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>วัสดุสำนักงาน - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { background: #e8f5e9; font-family: 'Prompt', 'Kanit', 'Arial', sans-serif; }
        .container { max-width: 600px; margin-top: 40px; padding-top: 24px; padding-bottom: 24px; }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 32px 24px;
        }
        .form-label { font-weight: 500; }
        .btn-main {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-main:hover {
            background: linear-gradient(135deg, #8BC34A 0%, #4CAF50 100%);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: #fff;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 500;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4 text-center text-success"><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>วัสดุสำนักงาน</h2>
        <div class="card">
        <form action="" method="post">
            <div class="mb-3">
                <label for="supply_name" class="form-label">ชื่อวัสดุ</label>
                <input type="text" class="form-control <?php echo !empty($supply_name_err) ? 'is-invalid' : ''; ?>" id="supply_name" name="supply_name" value="<?php echo htmlspecialchars($supply_name); ?>" required>
                <div class="invalid-feedback"><?php echo $supply_name_err; ?></div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">รายละเอียด</label>
                <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="unit" class="form-label">หน่วยนับ</label>
                <input type="text" class="form-control <?php echo !empty($unit_err) ? 'is-invalid' : ''; ?>" id="unit" name="unit" value="<?php echo htmlspecialchars($unit); ?>" required>
                <div class="invalid-feedback"><?php echo $unit_err; ?></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="current_stock" class="form-label">จำนวนคงเหลือ</label>
                    <input type="number" min="0" class="form-control <?php echo !empty($current_stock_err) ? 'is-invalid' : ''; ?>" id="current_stock" name="current_stock" value="<?php echo htmlspecialchars($current_stock); ?>" required>
                    <div class="invalid-feedback"><?php echo $current_stock_err; ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="min_stock_level" class="form-label">สต็อกขั้นต่ำ</label>
                    <input type="number" min="0" class="form-control <?php echo !empty($min_stock_level_err) ? 'is-invalid' : ''; ?>" id="min_stock_level" name="min_stock_level" value="<?php echo htmlspecialchars($min_stock_level); ?>" required>
                    <div class="invalid-feedback"><?php echo $min_stock_level_err; ?></div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-4">
                <button type="submit" class="btn btn-main me-2"><?php echo $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มวัสดุ'; ?></button>
                <a href="supplies.php" class="btn btn-secondary">ยกเลิก</a>
            </div>
        </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>