<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$dispense_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $supply_id = $dispense_date = $quantity_dispensed = $notes = '';
$user_id_err = $supply_id_err = $dispense_date_err = $quantity_dispensed_err = '';
$is_edit = false;
$old_supply_id = null;
$old_quantity = 0;

// ดึงรายการวัสดุ
$supplies = mysqli_query($link, "SELECT supply_id, supply_name FROM office_supplies ORDER BY supply_name");

if ($dispense_id > 0) {
    $is_edit = true;
    $sql = "SELECT * FROM dispensations WHERE dispense_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $dispense_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $user_id = $row['user_id'];
            $supply_id = $row['supply_id'];
            $dispense_date = $row['dispense_date'];
            $quantity_dispensed = $row['quantity_dispensed'];
            $notes = $row['notes'];
            $old_supply_id = $row['supply_id'];
            $old_quantity = $row['quantity_dispensed'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"]; // ใช้ user_id ของคนที่ login
    $supply_id = intval($_POST['supply_id']);
    $dispense_date = trim($_POST['dispense_date']);
    $quantity_dispensed = intval($_POST['quantity_dispensed']);
    $notes = trim($_POST['notes']);

    if ($supply_id <= 0) $supply_id_err = "กรุณาเลือกวัสดุ";
    if (empty($dispense_date)) $dispense_date_err = "กรุณาเลือกวันที่เบิก";
    if ($quantity_dispensed <= 0) $quantity_dispensed_err = "กรุณากรอกจำนวนที่เบิก";

    if (empty($supply_id_err) && empty($dispense_date_err) && empty($quantity_dispensed_err)) {
        if ($is_edit) {
            // ปรับ stock กลับก่อน แล้วค่อยลบใหม่ (กรณีเปลี่ยนวัสดุหรือจำนวน)
            if ($old_supply_id !== null) {
                $restore_stock = mysqli_prepare($link, "UPDATE office_supplies SET current_stock = current_stock + ? WHERE supply_id = ?");
                mysqli_stmt_bind_param($restore_stock, "ii", $old_quantity, $old_supply_id);
                mysqli_stmt_execute($restore_stock);
                mysqli_stmt_close($restore_stock);
            }
            $sql = "UPDATE dispensations SET user_id=?, supply_id=?, dispense_date=?, quantity_dispensed=?, notes=? WHERE dispense_id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "iisssi", $user_id, $supply_id, $dispense_date, $quantity_dispensed, $notes, $dispense_id);
                if (mysqli_stmt_execute($stmt)) {
                    // หัก stock ใหม่
                    $update_stock = mysqli_prepare($link, "UPDATE office_supplies SET current_stock = current_stock - ? WHERE supply_id = ?");
                    mysqli_stmt_bind_param($update_stock, "ii", $quantity_dispensed, $supply_id);
                    mysqli_stmt_execute($update_stock);
                    mysqli_stmt_close($update_stock);
                    header("location: dispensations.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "INSERT INTO dispensations (user_id, supply_id, dispense_date, quantity_dispensed, notes) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "iisis", $user_id, $supply_id, $dispense_date, $quantity_dispensed, $notes);
                if (mysqli_stmt_execute($stmt)) {
                    // อัปเดต stock
                    $update_stock = mysqli_prepare($link, "UPDATE office_supplies SET current_stock = current_stock - ? WHERE supply_id = ?");
                    mysqli_stmt_bind_param($update_stock, "ii", $quantity_dispensed, $supply_id);
                    mysqli_stmt_execute($update_stock);
                    mysqli_stmt_close($update_stock);
                    header("location: dispensations.php");
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
    <title><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>การเบิกวัสดุ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Prompt', 'Kanit', 'Arial', sans-serif;
    }
    .btn-main {
        background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
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
        transition: all 0.3s ease;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
        color: #fff;
    }
    </style>
</head>
<body style="background:#e8f5e9;">
<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-dolly"></i> <?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>การเบิกวัสดุ</h2>
    <form method="post" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">ผู้เบิก</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['full_name']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">เลือกวัสดุ</label>
            <select name="supply_id" class="form-select <?php echo !empty($supply_id_err) ? 'is-invalid' : ''; ?>" required>
                <option value="">-- เลือกวัสดุ --</option>
                <?php mysqli_data_seek($supplies, 0); while($s = mysqli_fetch_assoc($supplies)): ?>
                    <option value="<?= $s['supply_id'] ?>" <?php if($supply_id == $s['supply_id']) echo 'selected'; ?>><?= htmlspecialchars($s['supply_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <div class="invalid-feedback"><?php echo $supply_id_err; ?></div>
        </div>
        <div class="mb-3">
            <label class="form-label">วันที่เบิก</label>
            <input type="date" name="dispense_date" class="form-control <?php echo !empty($dispense_date_err) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($dispense_date ?: date('Y-m-d')); ?>" required>
            <div class="invalid-feedback"><?php echo $dispense_date_err; ?></div>
        </div>
        <div class="mb-3">
            <label class="form-label">จำนวนที่เบิก</label>
            <input type="number" name="quantity_dispensed" class="form-control <?php echo !empty($quantity_dispensed_err) ? 'is-invalid' : ''; ?>" min="1" value="<?php echo htmlspecialchars($quantity_dispensed ?: 1); ?>" required>
            <div class="invalid-feedback"><?php echo $quantity_dispensed_err; ?></div>
        </div>
        <div class="mb-3">
            <label class="form-label">หมายเหตุ</label>
            <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($notes); ?></textarea>
        </div>
        <div class="d-flex justify-content-center mt-4">
            <button type="submit" class="btn btn-main me-2"><?php echo $is_edit ? 'บันทึกการแก้ไข' : 'บันทึก'; ?></button>
        <a href="dispensations.php" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/4b2b6e7e8a.js" crossorigin="anonymous"></script>
</body>
</html> 