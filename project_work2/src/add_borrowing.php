<?php
require_once 'config.php';
require_once 'movement_logger.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ดึงรายการครุภัณฑ์พร้อมจำนวนที่พร้อมให้ยืม (หักลบที่ถูกยืมไปแล้ว)
$items = mysqli_query($link, "
    SELECT 
        i.item_id, 
        i.item_number, 
        i.model_name, 
        i.brand,
        i.total_quantity,
        (COALESCE(i.total_quantity,0) - COALESCE((SELECT SUM(quantity_borrowed) FROM borrowings WHERE item_id=i.item_id AND status IN ('borrowed','approved','return_pending')),0)) as available_items,
        c.category_name
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.category_id
    ORDER BY i.item_number
");

$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION["user_id"];
    $item_id = $_POST['item_id'];
    $borrow_date = $_POST['borrow_date'];
    $due_date = $_POST['due_date'];
    $quantity = intval($_POST['quantity']);
    if (!$item_id || !$borrow_date || !$due_date || $quantity < 1) {
        $err = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        // ตรวจสอบจำนวนที่เหลือจริง
        $q = mysqli_query($link, "SELECT total_quantity FROM items WHERE item_id=".intval($item_id));
        $item = mysqli_fetch_assoc($q);
        $total = (int)$item['total_quantity'];
        $q2 = mysqli_query($link, "SELECT IFNULL(SUM(quantity_borrowed),0) as borrowed FROM borrowings WHERE item_id=".intval($item_id)." AND status IN ('borrowed','approved','return_pending')");
        $borrowed = (int)mysqli_fetch_assoc($q2)['borrowed'];
        $available = $total - $borrowed;
        if ($quantity > $available) {
            $err = 'จำนวนที่ยืมเกินจำนวนที่มีอยู่ในระบบ (เหลือให้ยืม '.$available.' ชิ้น)';
        } else {
            $status = ($_SESSION["role"] == "staff") ? 'pending' : 'borrowed';
            $sql = "INSERT INTO borrowings (user_id, item_id, borrow_date, due_date, quantity_borrowed, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'iissis', $user_id, $item_id, $borrow_date, $due_date, $quantity, $status);
            if (mysqli_stmt_execute($stmt)) {
                $borrow_id = mysqli_insert_id($link);
                
                // ลดจำนวนครุภัณฑ์ที่พร้อมให้ยืม (ถ้าไม่ใช่ staff)
                if ($_SESSION["role"] != "staff") {
                    $update_item_sql = "UPDATE items SET available_quantity = available_quantity - ? WHERE item_id = ?";
                    $update_item_stmt = mysqli_prepare($link, $update_item_sql);
                    mysqli_stmt_bind_param($update_item_stmt, 'ii', $quantity, $item_id);
                    mysqli_stmt_execute($update_item_stmt);
                    mysqli_stmt_close($update_item_stmt);
                }
                
                // บันทึกการเคลื่อนไหว
                if ($_SESSION["role"] != "staff") {
                    log_borrow_movement($item_id, $user_id, $quantity, $borrow_id, 'สร้างการยืมโดย ' . $_SESSION['username']);
                } else {
                    log_equipment_movement($item_id, 'adjustment', null, null, null, null, $quantity, 'ส่งคำขอยืมโดย ' . $_SESSION['username'], $borrow_id);
                }
                
                header('Location: borrowings.php');
                exit;
            } else {
                $err = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
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
    <title>เพิ่มการยืมครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
    .btn-cancel, .btn-secondary {
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
    .btn-cancel:hover, .btn-secondary:hover {
        background-color: #5a6268;
        color: #fff;
    }
    .form-label {
        font-weight: 500;
        color: #185a9d;
    }
    </style>
</head>
<body style="background:#e8f5e9;">
<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-plus"></i> เพิ่มการยืมครุภัณฑ์</h2>
    <?php if($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
    <form method="post" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">ผู้ยืม</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['full_name']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">เลือกครุภัณฑ์</label>
            <select name="item_id" class="form-select" required>
                <option value="">-- เลือกครุภัณฑ์ --</option>
                <?php while($i = mysqli_fetch_assoc($items)): ?>
                    <?php 
                    $status_text = $i['available_items'] > 0 ? 'พร้อมให้ยืม' : 'ไม่พร้อมให้ยืม';
                    $is_disabled = ($i['available_items'] <= 0);
                    ?>
                    <option value="<?= $i['item_id'] ?>" <?= $is_disabled ? 'disabled' : '' ?>
                        data-available="<?= $i['available_items'] ?>">
                        <?= htmlspecialchars($i['item_number']) ?> 
                        (<?= htmlspecialchars($i['model_name']) ?>, <?= htmlspecialchars($i['brand']) ?>) 
                        [หมวดหมู่: <?= htmlspecialchars($i['category_name']) ?>]
                        - <?= $status_text ?> จำนวน: <?= $i['available_items'] ?> 
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">จำนวนที่ยืม</label>
            <input type="number" name="quantity" class="form-control" min="1" value="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">วันที่ยืม</label>
            <input type="date" name="borrow_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">กำหนดคืน</label>
            <input type="date" name="due_date" class="form-control" required>
        </div>
        <div class="d-flex justify-content-center mt-4">
            <button type="submit" class="btn btn-main me-2"><i class="fas fa-save me-1"></i> บันทึก</button>
            <a href="borrowings.php" class="btn btn-cancel"><i class="fas fa-times-circle me-1"></i> ยกเลิก</a>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 