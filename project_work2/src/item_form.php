<?php
require_once 'config.php';
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ดึงหมวดหมู่
$categories = [];
$cat_result = mysqli_query($link, "SELECT * FROM categories ORDER BY category_name");
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row;
}

// ดึงชื่อรุ่น (models) พร้อมยี่ห้อ
$models = [];
// ใช้ LEFT JOIN เพื่อให้แน่ใจว่าได้ข้อมูล model_id แม้ว่า brand_id จะเป็น NULL หรือไม่มีในตาราง brands
// แก้ไข: ใช้ m.model_name เพื่อดึงชื่อรุ่นที่ถูกต้องจากตาราง models
$model_result = mysqli_query($link, "SELECT m.model_id, m.model_name, b.brand_name FROM models m LEFT JOIN brands b ON m.brand_id = b.brand_id ORDER BY m.model_name");
while ($row = mysqli_fetch_assoc($model_result)) {
    $models[] = $row;
}

// กำหนดตัวแปรเริ่มต้น
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item_number = $serial_number = $description = $note = $category_id = $total_quantity = $location = $purchase_date = $budget_year = $price_per_unit = $total_price = $image = '';
$item_number_err = $serial_number_err = $brand_err = $category_id_err = $total_quantity_err = $budget_year_err = $price_per_unit_err = $image_err = $model_id_err = ''; // เพิ่ม model_id_err
$is_edit = false;
$model_id = '';
$brand_name_display = ''; // เพิ่มตัวแปรสำหรับแสดงยี่ห้อในฟอร์ม
$brand = ''; // ต้องมีตัวแปร $brand สำหรับบันทึกลง DB

// ถ้าเป็นการแก้ไข ดึงข้อมูลเดิมมาแสดง
if ($item_id > 0) {
    $is_edit = true;
    // JOIN ตาราง models และ brands โดยใช้ model_name แทน model_id
    $sql = "SELECT i.*, m.model_id, m.model_name, b.brand_name 
            FROM items i 
            LEFT JOIN models m ON i.model_name = m.model_name
            LEFT JOIN brands b ON m.brand_id = b.brand_id
            WHERE i.item_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $item_number = $row['item_number'];
            $serial_number = isset($row['serial_number']) ? $row['serial_number'] : '';
            $brand = $row['brand']; // ค่า 'brand' ที่บันทึกใน DB
            $brand_name_display = isset($row['brand_name']) ? $row['brand_name'] : $row['brand']; // ใช้ brand_name จาก JOIN ถ้ามี, ถ้าไม่มีใช้ค่าจากฟิลด์ brand เดิม
            $description = $row['description'];
            $category_id = $row['category_id'];
            $total_quantity = $row['total_quantity'];
            $location = $row['location'];
            $purchase_date = $row['purchase_date'];
            $budget_year = $row['budget_year'];
            $price_per_unit = isset($row['price_per_unit']) ? $row['price_per_unit'] : '';
            $total_price = isset($row['total_price']) ? $row['total_price'] : '';
            $image = isset($row['image']) ? $row['image'] : '';
            $note = isset($row['note']) ? $row['note'] : '';
            $model_id = isset($row['model_id']) ? $row['model_id'] : '';
        } else {
            // ถ้าไม่พบ item_id ที่ระบุ ให้ redirect หรือแสดงข้อผิดพลาด
            echo "<script>alert('ไม่พบข้อมูลครุภัณฑ์ที่ต้องการแก้ไข'); window.location='items.php';</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing select item for edit: " . mysqli_error($link));
        echo "<script>alert('เกิดข้อผิดพลาดในการดึงข้อมูล (DB Error)'); window.location='items.php';</script>";
        exit;
    }
}

// แสดงข้อความแจ้งเตือน (Success/Error) หลัง redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        // ใช้ setTimeout เพื่อให้ alert แสดงผลก่อน redirect
        echo "<script>setTimeout(function(){ alert('บันทึกข้อมูลสำเร็จ!'); window.location = 'items.php'; }, 100);</script>";
    } else if ($_GET['success'] == 0) {
        echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');</script>";
    }
}


// เมื่อมีการส่งฟอร์ม (POST request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_number = trim($_POST['item_number']);
    $serial_number = trim($_POST['serial_number']);
    
    // ดึงค่า model_id ที่เลือกจาก POST
    $selected_model_id_for_post = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
    $model_id = $selected_model_id_for_post; // กำหนดค่า model_id สำหรับบันทึก
    
    // กำหนดค่า default ให้ $brand_from_model
    $brand_from_model = '';
    // หา model_name จาก $models array โดยใช้ model_id ที่เลือก
    $model_name = '';
    if ($model_id > 0) {
        foreach ($models as $m) {
            if ($m['model_id'] == $selected_model_id_for_post) {
                $brand_from_model = $m['brand_name'];
                $model_name = $m['model_name'];
                break;
            }
        }
    }
    $brand = $brand_from_model; // ใช้ brand_name ที่ดึงมาจาก model_id เป็นค่า 'brand' ที่จะบันทึกลง DB

    $description = trim($_POST['description']);
    $note = trim($_POST['note']);
    $category_id = intval($_POST['category_id']);
    $total_quantity = intval($_POST['total_quantity']);
    $location = trim($_POST['location']);
    $purchase_date = $_POST['purchase_date'];
    $budget_year = trim($_POST['budget_year']);
    $price_per_unit = trim($_POST['price_per_unit']);
    // $total_price = trim($_POST['total_price']); // ค่านี้จะถูกคำนวณจาก JS แต่เราจะคำนวณซ้ำเพื่อความแม่นยำ

    // Validate
    if (empty($serial_number)) $serial_number_err = "กรุณากรอกซีเรียลนัมเบอร์";
    if ($model_id <= 0) $model_id_err = "กรุณาเลือกชื่อรุ่น"; // ตรวจสอบ model_id
    if (empty($brand)) $brand_err = "ไม่สามารถระบุยี่ห้อได้ กรุณาเลือกชื่อรุ่นที่มียี่ห้อ หรือเพิ่มยี่ห้อ/รุ่นก่อน";
    if ($category_id <= 0) $category_id_err = "กรุณาเลือกหมวดหมู่";
    if ($total_quantity < 0) $total_quantity_err = "จำนวนรวมต้องไม่ติดลบ";
    if (empty($budget_year) || !preg_match('/^[0-9]{4}$/', $budget_year)) $budget_year_err = "กรุณากรอกปีงบประมาณ 4 หลัก";
    if ($price_per_unit === '' || !is_numeric($price_per_unit) || $price_per_unit < 0) $price_per_unit_err = "กรุณากรอกราคาต่อหน่วย (ตัวเลขไม่ติดลบ)";
    else $price_per_unit = floatval($price_per_unit); // Convert to float for calculation
    
    // ตรวจสอบความยาวของ Serial Number และ Item Number
    if (strlen($serial_number) > 100) $serial_number_err = "Serial Number ต้องไม่เกิน 100 ตัวอักษร";
    if (!empty($item_number) && strlen($item_number) > 100) $item_number_err = "เลขครุภัณฑ์ต้องไม่เกิน 100 ตัวอักษร";
    
    // ตรวจสอบรูปแบบของ Serial Number และ Item Number
    if (!empty($serial_number) && !preg_match('/^[a-zA-Z0-9\-\_\.\s]+$/', $serial_number)) {
        $serial_number_err = "Serial Number ต้องประกอบด้วยตัวอักษร ตัวเลข และเครื่องหมาย - _ . เท่านั้น";
    }
    if (!empty($item_number) && !preg_match('/^[a-zA-Z0-9\-\_\.\s]+$/', $item_number)) {
        $item_number_err = "เลขครุภัณฑ์ต้องประกอบด้วยตัวอักษร ตัวเลข และเครื่องหมาย - _ . เท่านั้น";
    }

    // คำนวณราคารวมอีกครั้งเพื่อความถูกต้องใน DB
    $total_price = $total_quantity * $price_per_unit; 


    // ตรวจสอบ Serial Number ซ้ำ
    $old_serial_number = '';
    if ($is_edit && $item_id > 0) {
        $sql_get_old_serial = "SELECT serial_number FROM items WHERE item_id = ?";
        if ($stmt_get_old = mysqli_prepare($link, $sql_get_old_serial)) {
            mysqli_stmt_bind_param($stmt_get_old, "i", $item_id);
            mysqli_stmt_execute($stmt_get_old);
            $result_get_old = mysqli_stmt_get_result($stmt_get_old);
            if ($old_row = mysqli_fetch_assoc($result_get_old)) {
                $old_serial_number = $old_row['serial_number'];
            }
            mysqli_stmt_close($stmt_get_old);
        }
    }

    if (!$is_edit || ($is_edit && $serial_number !== $old_serial_number)) { 
        if (!empty($serial_number)) {
            $sql_check_serial = "SELECT COUNT(*) AS cnt FROM items WHERE serial_number = ?";
            if ($is_edit) {
                $sql_check_serial .= " AND item_id != ?";
            }
            $stmt_check = mysqli_prepare($link, $sql_check_serial);
            if ($is_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $serial_number, $item_id);
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $serial_number);
            }
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            $row_check = mysqli_fetch_assoc($result_check);
            if ($row_check['cnt'] > 0) {
                $serial_number_err = "Serial Number นี้ถูกใช้ไปแล้วในระบบ";
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // ตรวจสอบ Item Number ซ้ำ (เฉพาะตอนเพิ่มใหม่ หรือแก้ไข item_number)
    $old_item_number = '';
    if ($is_edit && $item_id > 0) {
        $sql_get_old_item_num = "SELECT item_number FROM items WHERE item_id = ?";
        if ($stmt_get_old_item_num = mysqli_prepare($link, $sql_get_old_item_num)) {
            mysqli_stmt_bind_param($stmt_get_old_item_num, "i", $item_id);
            mysqli_stmt_execute($stmt_get_old_item_num);
            $result_get_old_item_num = mysqli_stmt_get_result($stmt_get_old_item_num);
            if ($old_row_item_num = mysqli_fetch_assoc($result_get_old_item_num)) {
                $old_item_number = $old_row_item_num['item_number'];
            }
            mysqli_stmt_close($stmt_get_old_item_num);
        }
    }

    if (!empty($item_number)) {
        if (!$is_edit || ($is_edit && $item_number !== $old_item_number)) { 
            $sql_check_item_num = "SELECT COUNT(*) AS cnt FROM items WHERE item_number = ?";
            if ($is_edit) {
                $sql_check_item_num .= " AND item_id != ?";
            }
            $stmt_check_item_num = mysqli_prepare($link, $sql_check_item_num);
            if ($is_edit) {
                mysqli_stmt_bind_param($stmt_check_item_num, "si", $item_number, $item_id);
            } else {
                mysqli_stmt_bind_param($stmt_check_item_num, "s", $item_number);
            }
            mysqli_stmt_execute($stmt_check_item_num);
            $result_check_item_num = mysqli_stmt_get_result($stmt_check_item_num);
            $row_check_item_num = mysqli_fetch_assoc($result_check_item_num);
            if ($row_check_item_num['cnt'] > 0) {
                $item_number_err = "เลขครุภัณฑ์นี้ถูกใช้ไปแล้วในระบบ";
            }
            mysqli_stmt_close($stmt_check_item_num);
        }
    }

    // อัปโหลดไฟล์รูป
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed)) {
            $newname = 'uploads/item_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            // ตรวจสอบและสร้างโฟลเดอร์ uploads ถ้ายังไม่มี
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            if (move_uploaded_file($_FILES['image']['tmp_name'], $newname)) {
                // ถ้ามีการอัปโหลดรูปใหม่ และมีรูปเก่าอยู่ ให้ลบรูปเก่า
                if ($is_edit && !empty($_POST['old_image']) && file_exists($_POST['old_image'])) {
                    unlink($_POST['old_image']);
                }
                $image = $newname;
            } else {
                $image_err = 'อัปโหลดรูปไม่สำเร็จ';
            }
        } else {
            $image_err = 'อนุญาตเฉพาะไฟล์ jpg, jpeg, png, gif';
        }
    } elseif (isset($_POST['old_image'])) {
        $image = $_POST['old_image'];
    } else {
        $image = ''; // ถ้าไม่มีรูปเก่าและไม่ได้อัปโหลดใหม่ ให้เป็นค่าว่าง
    }

    // หากไม่มี error
    if (empty($serial_number_err) && empty($item_number_err) && empty($brand_err) && empty($category_id_err) && empty($total_quantity_err) && empty($budget_year_err) && empty($price_per_unit_err) && empty($image_err) && empty($model_id_err)) {
        
        if ($is_edit) {
            $sql = "UPDATE items SET model_name=?, item_number=?, serial_number=?, brand=?, description=?, note=?, category_id=?, total_quantity=?, image=?, location=?, purchase_date=?, budget_year=?, price_per_unit=?, total_price=? WHERE item_id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssssiissssddi", $model_name, $item_number, $serial_number, $brand, $description, $note, $category_id, $total_quantity, $image, $location, $purchase_date, $budget_year, $price_per_unit, $total_price, $item_id);
                if (mysqli_stmt_execute($stmt)) {
                    // Success
                    echo "<script>window.location = 'item_form.php?id=" . $item_id . "&success=1';</script>";
                } else {
                    // Error
                    error_log("Error executing UPDATE statement: " . mysqli_error($link));
                    echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกการแก้ไข: " . mysqli_error($link) . "');</script>";
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Error preparing UPDATE statement: " . mysqli_error($link));
                echo "<script>alert('เกิดข้อผิดพลาดในการเตรียมการอัปเดตข้อมูล');</script>";
            }
        } else {
            $sql = "INSERT INTO items (model_name, item_number, serial_number, brand, description, note, category_id, total_quantity, image, location, purchase_date, budget_year, price_per_unit, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssssissssssd", $model_name, $item_number, $serial_number, $brand, $description, $note, $category_id, $total_quantity, $image, $location, $purchase_date, $budget_year, $price_per_unit, $total_price);
                if (mysqli_stmt_execute($stmt)) {
                    // Success
                    echo "<script>window.location = 'item_form.php?success=1';</script>";
                } else {
                    // Error
                    error_log("Error executing INSERT statement: " . mysqli_error($link));
                    echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูลใหม่: " . mysqli_error($link) . "');</script>";
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Error preparing INSERT statement: " . mysqli_error($link));
                echo "<script>alert('เกิดข้อผิดพลาดในการเตรียมการบันทึกข้อมูลใหม่');</script>";
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
    <title><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>ครุภัณฑ์ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #e8f5e9;
            font-family: 'Prompt', 'Kanit', 'Arial', sans-serif;
            color: #333;
        }
        .container { 
            max-width: 700px; /* ขยายความกว้างของฟอร์ม */
            margin-top: 40px; 
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .form-label { 
            font-weight: 500; 
            color: #2e7d32; /* สีเขียวเข้มสำหรับ label */
        }
        .btn-main { 
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%); /* Green gradient for primary action */
            color: #fff; 
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-main:hover { 
            background: linear-gradient(135deg, #8BC34A 0%, #4CAF50 100%); 
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: #fff; /* Ensure text color remains white on hover */
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
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
        }
        .input-group .btn-outline-secondary {
            border-color: #ced4da;
            color: #495057;
        }
        .input-group .btn-outline-secondary:hover {
            background-color: #e2e6ea;
            border-color: #dae0e5;
        }
        /* Style for image thumbnail */
        .mb-3 img {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        #qr-reader {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: auto;
            height: 320px;
            background: #000;
        }
        #qr-reader video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .scan-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
        }
        .scan-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 80%;
            height: 80px;
            transform: translate(-50%, -50%);
            border-radius: 18px;
            box-sizing: border-box;
        }
        .scan-corner {
            position: absolute;
            width: 28px;
            height: 28px;
        }
        .scan-corner.tl { top: 0; left: 0; border-top: 4px solid #00ff66; border-left: 4px solid #00ff66; border-top-left-radius: 18px; }
        .scan-corner.tr { top: 0; right: 0; border-top: 4px solid #00ff66; border-right: 4px solid #00ff66; border-top-right-radius: 18px; }
        .scan-corner.bl { bottom: 0; left: 0; border-bottom: 4px solid #00ff66; border-left: 4px solid #00ff66; border-bottom-left-radius: 18px; }
        .scan-corner.br { bottom: 0; right: 0; border-bottom: 4px solid #00ff66; border-right: 4px solid #00ff66; border-bottom-right-radius: 18px; }
        .scan-dim {
            position: absolute;
            background: rgba(0,0,0,0.55);
        }
        .scan-dim.top { left:0; right:0; top:0; height: 32%; }
        .scan-dim.bottom { left:0; right:0; bottom:0; height: 32%; }
        .scan-dim.left { left:0; top:32%; bottom:32%; width:10%; }
        .scan-dim.right { right:0; top:32%; bottom:32%; width:10%; }
        .scan-instruction {
            position: absolute;
            left: 50%;
            top: calc(50% + 60px);
            transform: translateX(-50%);
            color: #fff;
            font-size: 1.1rem;
            text-shadow: 0 1px 4px #000;
            width: 100%;
            text-align: center;
            z-index: 2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4 text-center text-success"><i class="fas fa-box me-2"></i><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>ครุภัณฑ์</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="item_number" class="form-label">เลขครุภัณฑ์</label>
                <div class="input-group">
                    <input type="text" class="form-control <?php echo !empty($item_number_err) ? 'is-invalid' : ''; ?>" id="item_number" name="item_number" value="<?php echo htmlspecialchars($item_number); ?>" maxlength="100">
                    <button type="button" class="btn btn-outline-secondary" onclick="openScannerQuagga('item_number')"><i class="fas fa-qrcode me-1"></i> สแกน</button>
                </div>
                <div class="invalid-feedback"><?php echo $item_number_err; ?></div>
                <small class="form-text text-muted">เลขครุภัณฑ์จะถูกตรวจสอบความซ้ำซ้อนอัตโนมัติ</small>
            </div>
            <div class="mb-3">
                <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control <?php echo !empty($serial_number_err) ? 'is-invalid' : ''; ?>" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($serial_number); ?>" required maxlength="100">
                    <button type="button" class="btn btn-outline-secondary" onclick="openScannerQuagga('serial_number')"><i class="fas fa-qrcode me-1"></i> สแกน</button>
                </div>
                <div class="invalid-feedback"><?php echo $serial_number_err; ?></div>
                <small class="form-text text-muted">Serial Number จะถูกตรวจสอบความซ้ำซ้อนอัตโนมัติ</small>
            </div>
            <div class="mb-3">
                <label for="model_id" class="form-label">ชื่อรุ่น <span class="text-danger">*</span></label>
                <div class="input-group">
                    <select class="form-select <?php echo !empty($model_id_err) ? 'is-invalid' : ''; ?>" id="model_id" name="model_id" required onchange="updateBrand()">
                        <option value="">-- เลือกรุ่น --</option>
                        <?php foreach ($models as $m): ?>
                            <option value="<?php echo $m['model_id']; ?>" data-brand="<?php echo htmlspecialchars($m['brand_name']); ?>" <?php if ($model_id == $m['model_id']) echo 'selected'; ?>><?php echo htmlspecialchars($m['model_name']) . (isset($m['brand_name']) && $m['brand_name'] ? ' (' . htmlspecialchars($m['brand_name']) . ')' : ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-primary" onclick="openModelModal()"><i class="fas fa-plus me-1"></i> จัดการ</button>
                </div>
                <div class="invalid-feedback"><?php echo $model_id_err; ?></div>
            </div>
            <div class="mb-3">
                <label for="brand_name" class="form-label">ยี่ห้อ</label>
                <div class="input-group">
                    <input type="text" class="form-control <?php echo !empty($brand_err) ? 'is-invalid' : ''; ?>" id="brand_name" name="brand_display" value="<?php echo htmlspecialchars($brand_name_display); ?>" readonly>
                    <button type="button" class="btn btn-outline-primary" onclick="openBrandModal()"><i class="fas fa-plus me-1"></i> จัดการ</button>
                </div>
                <div class="invalid-feedback"><?php echo $brand_err; ?></div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">รายละเอียด</label>
                <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                <div class="input-group">
                    <select class="form-select <?php echo !empty($category_id_err) ? 'is-invalid' : ''; ?>" id="category_id" name="category_id" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php if ($category_id == $cat['category_id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-primary" onclick="openCategoryModal()"><i class="fas fa-plus me-1"></i> จัดการ</button>
                </div>
                <div class="invalid-feedback"><?php echo $category_id_err; ?></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="total_quantity" class="form-label">จำนวน <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control <?php echo !empty($total_quantity_err) ? 'is-invalid' : ''; ?>" id="total_quantity" name="total_quantity" value="<?php echo htmlspecialchars($total_quantity); ?>" required>
                    <div class="invalid-feedback"><?php echo $total_quantity_err; ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="price_per_unit" class="form-label">ราคาต่อหน่วย (บาท) <span class="text-danger">*</span></label>
                    <input type="number" min="0" step="0.01" class="form-control <?php echo !empty($price_per_unit_err) ? 'is-invalid' : ''; ?>" id="price_per_unit" name="price_per_unit" value="<?php echo htmlspecialchars($price_per_unit); ?>" required>
                    <div class="invalid-feedback"><?php echo $price_per_unit_err; ?></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="total_price" class="form-label">ราคารวม (บาท)</label>
                    <input type="text" class="form-control" id="total_price" name="total_price" value="<?php echo htmlspecialchars($total_price); ?>" readonly>
                    <small class="form-text text-muted">คำนวณอัตโนมัติจาก จำนวน × ราคาต่อหน่วย</small>
                </div>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">ตำแหน่งที่ติดตั้ง</label>
                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
            </div>
            <div class="mb-3">
                <label for="budget_year" class="form-label">ปีงบประมาณ <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php echo !empty($budget_year_err) ? 'is-invalid' : ''; ?>" id="budget_year" name="budget_year" value="<?php echo htmlspecialchars($budget_year); ?>" maxlength="4" required>
                <div class="invalid-feedback"><?php echo $budget_year_err; ?></div>
            </div>

            <div class="mb-4">
                <label for="purchase_date" class="form-label">วันที่จัดซื้อ</label>
                <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?php echo htmlspecialchars($purchase_date); ?>">
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">รูปภาพ</label>
                <input type="file" class="form-control <?php echo !empty($image_err) ? 'is-invalid' : ''; ?>" id="image" name="image" accept="image/*">
                <?php if ($image): ?>
                    <div class="mt-2"><img src="<?php echo htmlspecialchars($image); ?>" alt="รูปภาพ" style="max-width:150px; height: auto;"></div>
                    <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($image); ?>">
                <?php endif; ?>
                <div class="invalid-feedback"><?php echo $image_err; ?></div>
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">หมายเหตุ</label>
                <textarea class="form-control" id="note" name="note" rows="2"><?php echo htmlspecialchars($note); ?></textarea>
            </div>
            <div class="d-flex justify-content-center mt-4">
                <button type="submit" class="btn btn-main me-2"><i class="fas fa-save me-1"></i> <?php echo $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มครุภัณฑ์'; ?></button>
                <a href="items.php" class="btn btn-secondary"><i class="fas fa-times-circle me-1"></i> ยกเลิก</a>
            </div>
        </form>
    </div>
    <div class="modal fade" id="scanModal" tabindex="-1" aria-labelledby="scanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="scanModalLabel"><i class="fas fa-qrcode me-2"></i> สแกนบาร์โค้ด/QR Code</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
            <div id="qr-reader" style="width:100%; max-width:400px; margin: auto;"></div>
            <div class="text-muted small mb-2">กรุณาวางบาร์โค้ดให้อยู่ในกรอบแนวนอน และเพิ่มแสงสว่าง</div>
            <div id="qr-reader-results" class="mt-2 text-danger small"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
        </div>
    </div>
    </div>

    <!-- Modal สำหรับจัดการหมวดหมู่ -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="categoryModalLabel"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row">
            <div class="col-md-6">
                <h6>เพิ่มหมวดหมู่ใหม่</h6>
                <form id="addCategoryForm">
                <div class="mb-3">
                    <label for="newCategoryName" class="form-label">ชื่อหมวดหมู่</label>
                    <input type="text" class="form-control" id="newCategoryName" required>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i> เพิ่ม</button>
                </form>
            </div>
            <div class="col-md-6">
                <h6>รายการหมวดหมู่</h6>
                <div id="categoryList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                <!-- รายการหมวดหมู่จะถูกโหลดที่นี่ -->
                </div>
            </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
        </div>
    </div>
    </div>

    <!-- Modal สำหรับจัดการยี่ห้อ -->
    <div class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="brandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header bg-info text-white">
            <h5 class="modal-title" id="brandModalLabel"><i class="fas fa-trademark me-2"></i> จัดการยี่ห้อ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row">
            <div class="col-md-6">
                <h6>เพิ่มยี่ห้อใหม่</h6>
                <form id="addBrandForm">
                <div class="mb-3">
                    <label for="newBrandName" class="form-label">ชื่อยี่ห้อ</label>
                    <input type="text" class="form-control" id="newBrandName" required>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i> เพิ่ม</button>
                </form>
            </div>
            <div class="col-md-6">
                <h6>รายการยี่ห้อ</h6>
                <div id="brandList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                <!-- รายการยี่ห้อจะถูกโหลดที่นี่ -->
                </div>
            </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
        </div>
    </div>
    </div>

    <!-- Modal สำหรับจัดการชื่อรุ่น -->
    <div class="modal fade" id="modelModal" tabindex="-1" aria-labelledby="modelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="modelModalLabel"><i class="fas fa-cube me-2"></i> จัดการชื่อรุ่น</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row">
            <div class="col-md-6">
                <h6>เพิ่มรุ่นใหม่</h6>
                <form id="addModelForm">
                <div class="mb-3">
                    <label for="newModelName" class="form-label">ชื่อรุ่น</label>
                    <input type="text" class="form-control" id="newModelName" required>
                </div>
                <div class="mb-3">
                    <label for="newModelBrand" class="form-label">ยี่ห้อ</label>
                    <select class="form-select" id="newModelBrand" required>
                    <option value="">-- เลือกยี่ห้อ --</option>
                    <!-- รายการยี่ห้อจะถูกโหลดที่นี่ -->
                    </select>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i> เพิ่ม</button>
                </form>
            </div>
            <div class="col-md-6">
                <h6>รายการรุ่น</h6>
                <div id="modelList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                <!-- รายการรุ่นจะถูกโหลดที่นี่ -->
                </div>
            </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
        </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script>
        // คำนวณราคารวมอัตโนมัติ
        function calculateTotalPrice() {
            const quantity = parseFloat(document.getElementById('total_quantity').value) || 0;
            const pricePerUnit = parseFloat(document.getElementById('price_per_unit').value) || 0;
            const totalPrice = quantity * pricePerUnit;
            document.getElementById('total_price').value = totalPrice.toFixed(2);
        }

        // เพิ่ม event listeners
        document.getElementById('total_quantity').addEventListener('input', calculateTotalPrice);
        document.getElementById('price_per_unit').addEventListener('input', calculateTotalPrice);

        // คำนวณครั้งแรกเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', calculateTotalPrice);

        // อัปเดตยี่ห้อตามชื่อรุ่นที่เลือก
        function updateBrand() {
            var modelSelect = document.getElementById('model_id');
            var brandInput = document.getElementById('brand_name'); 
            var selectedOption = modelSelect.options[modelSelect.selectedIndex];
            brandInput.value = selectedOption.getAttribute('data-brand') || '';
        }
        document.getElementById('model_id').addEventListener('change', updateBrand);
        document.addEventListener('DOMContentLoaded', updateBrand);

        // ตรวจสอบข้อมูลซ้ำแบบ real-time
        function checkDuplicateData(fieldName, value, currentItemId) {
            if (!value) return;
            
            fetch('check_duplicate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'field=' + fieldName + '&value=' + encodeURIComponent(value) + '&item_id=' + currentItemId
            })
            .then(response => response.json())
            .then(data => {
                const inputField = document.getElementById(fieldName);
                const feedbackDiv = inputField.parentNode.querySelector('.invalid-feedback');
                
                if (data.duplicate) {
                    inputField.classList.add('is-invalid');
                    if (feedbackDiv) {
                        feedbackDiv.textContent = data.message;
                    }
                } else {
                    inputField.classList.remove('is-invalid');
                    if (feedbackDiv) {
                        feedbackDiv.textContent = '';
                    }
                }
            })
            .catch(error => {
                console.error('Error checking duplicate:', error);
            });
        }

        // เพิ่ม event listeners สำหรับตรวจสอบข้อมูลซ้ำ
        document.addEventListener('DOMContentLoaded', function() {
            const serialNumberInput = document.getElementById('serial_number');
            const itemNumberInput = document.getElementById('item_number');
            const currentItemId = '<?php echo $item_id; ?>';
            
            if (serialNumberInput) {
                serialNumberInput.addEventListener('blur', function() {
                    checkDuplicateData('serial_number', this.value, currentItemId);
                });
                serialNumberInput.addEventListener('input', function() {
                    // ลบ error state เมื่อผู้ใช้เริ่มพิมพ์
                    this.classList.remove('is-invalid');
                    const feedbackDiv = this.parentNode.querySelector('.invalid-feedback');
                    if (feedbackDiv) {
                        feedbackDiv.textContent = '';
                    }
                });
            }
            
            if (itemNumberInput) {
                itemNumberInput.addEventListener('blur', function() {
                    checkDuplicateData('item_number', this.value, currentItemId);
                });
                itemNumberInput.addEventListener('input', function() {
                    // ลบ error state เมื่อผู้ใช้เริ่มพิมพ์
                    this.classList.remove('is-invalid');
                    const feedbackDiv = this.parentNode.querySelector('.invalid-feedback');
                    if (feedbackDiv) {
                        feedbackDiv.textContent = '';
                    }
                });
            }
            
            // ตรวจสอบก่อนส่งฟอร์ม
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let hasError = false;
                    
                    // ตรวจสอบ Serial Number
                    if (serialNumberInput && serialNumberInput.classList.contains('is-invalid')) {
                        hasError = true;
                    }
                    
                    // ตรวจสอบ Item Number
                    if (itemNumberInput && itemNumberInput.classList.contains('is-invalid')) {
                        hasError = true;
                    }
                    
                    if (hasError) {
                        e.preventDefault();
                        alert('กรุณาแก้ไขข้อผิดพลาดก่อนส่งฟอร์ม');
                        return false;
                    }
                });
            }
        });


        // --- Barcode Scanner Logic (QuaggaJS) ---
        let scanTargetInputId = null;
        function openScannerQuagga(targetInputId) {
            scanTargetInputId = targetInputId;
            const scanModal = new bootstrap.Modal(document.getElementById('scanModal'));
            scanModal.show();
            setTimeout(startQuaggaScanner, 400);
        }

        function startQuaggaScanner() {
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#qr-reader'),
                    constraints: {
                        facingMode: "environment",
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                },
                decoder: {
                    readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
                },
                locate: true
            }, function(err) {
                if (err) {
                    document.getElementById('qr-reader-results').innerHTML = '<div class="text-danger">ไม่สามารถเปิดกล้องได้: ' + err + '</div>';
                return;
            }
                Quagga.start();
                setTimeout(() => {
                    const qrReader = document.getElementById('qr-reader');
                    if (!qrReader.querySelector('.scan-overlay')) {
                        const overlay = document.createElement('div');
                        overlay.className = 'scan-overlay';
                        overlay.innerHTML = `
                            <div class="scan-dim top"></div>
                            <div class="scan-dim bottom"></div>
                            <div class="scan-dim left"></div>
                            <div class="scan-dim right"></div>
                            <div class="scan-frame">
                                <div class="scan-corner tl"></div>
                                <div class="scan-corner tr"></div>
                                <div class="scan-corner bl"></div>
                                <div class="scan-corner br"></div>
                            </div>
                            <div class="scan-instruction">นำบาร์โค้ดของคุณมาแสกนที่นี่</div>
                        `;
                        qrReader.appendChild(overlay);
                    }
                }, 500);
            });

            // --- Confirm Detection ---
            let lastCode = '';
            let sameCodeCount = 0;
            const confirmThreshold = 2; // ต้องเจอซ้ำกันกี่ครั้งถึงจะยืนยัน

            Quagga.onDetected(function(result) {
                if (result && result.codeResult && result.codeResult.code) {
                    let code = result.codeResult.code.trim();
                    if (code === lastCode) {
                        sameCodeCount++;
                    } else {
                        lastCode = code;
                        sameCodeCount = 1;
                    }
                    if (sameCodeCount >= confirmThreshold) {
                        document.getElementById('qr-reader-results').innerHTML = '<div class="text-success">สแกนสำเร็จ: ' + code + '</div>';
                        setTimeout(() => {
                            if (scanTargetInputId) {
                                document.getElementById(scanTargetInputId).value = code;
                            }
                            Quagga.stop();
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('scanModal'));
                                    modal.hide();
                                    document.getElementById('qr-reader-results').innerHTML = '';
                            lastCode = '';
                            sameCodeCount = 0;
                        }, 1000);
                } else {
                        document.getElementById('qr-reader-results').innerHTML = '<div class="text-warning">กำลังจับโฟกัส... (' + sameCodeCount + '/' + confirmThreshold + ')</div>';
                    }
                }
            });
        }

        document.getElementById('scanModal').addEventListener('hidden.bs.modal', function () {
            if (Quagga) {
                Quagga.stop();
            }
            document.getElementById('qr-reader-results').innerHTML = '';
            const overlay = document.querySelector('.scan-overlay');
            if (overlay) {
                overlay.remove();
            }
            scanTargetInputId = null;
        });

        // --- Modal Management Functions ---
        
        // เปิดโมดัลจัดการหมวดหมู่
        function openCategoryModal() {
            loadCategories();
            const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            modal.show();
        }

        // เปิดโมดัลจัดการยี่ห้อ
        function openBrandModal() {
            loadBrands();
            const modal = new bootstrap.Modal(document.getElementById('brandModal'));
            modal.show();
        }

        // เปิดโมดัลจัดการชื่อรุ่น
        function openModelModal() {
            loadModels();
            loadBrandsForModel();
            const modal = new bootstrap.Modal(document.getElementById('modelModal'));
            modal.show();
        }

        // โหลดรายการหมวดหมู่
        function loadCategories() {
            fetch('get_categories.php')
                .then(response => response.json())
                .then(data => {
                    const categoryList = document.getElementById('categoryList');
                    categoryList.innerHTML = '';
                    
                    if (data.length === 0) {
                        categoryList.innerHTML = '<p class="text-muted">ไม่มีหมวดหมู่</p>';
                        return;
                    }
                    
                    data.forEach(category => {
                        const div = document.createElement('div');
                        div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border-bottom';
                        div.innerHTML = `
                            <span>${category.category_name}</span>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(${category.category_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        categoryList.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                    document.getElementById('categoryList').innerHTML = '<p class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
                });
        }

        // โหลดรายการยี่ห้อ
        function loadBrands() {
            fetch('get_brands.php')
                .then(response => response.json())
                .then(data => {
                    const brandList = document.getElementById('brandList');
                    brandList.innerHTML = '';
                    
                    if (data.length === 0) {
                        brandList.innerHTML = '<p class="text-muted">ไม่มียี่ห้อ</p>';
                        return;
                    }
                    
                    data.forEach(brand => {
                        const div = document.createElement('div');
                        div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border-bottom';
                        div.innerHTML = `
                            <span>${brand.brand_name}</span>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteBrand(${brand.brand_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        brandList.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error loading brands:', error);
                    document.getElementById('brandList').innerHTML = '<p class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
                });
        }

        // โหลดรายการยี่ห้อสำหรับโมดัลรุ่น
        function loadBrandsForModel() {
            fetch('get_brands.php')
                .then(response => response.json())
                .then(data => {
                    const brandSelect = document.getElementById('newModelBrand');
                    brandSelect.innerHTML = '<option value="">-- เลือกยี่ห้อ --</option>';
                    
                    data.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand.brand_id;
                        option.textContent = brand.brand_name;
                        brandSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading brands for model:', error);
                });
        }

        // โหลดรายการรุ่น
        function loadModels() {
            fetch('get_models.php')
                .then(response => response.json())
                .then(data => {
                    const modelList = document.getElementById('modelList');
                    modelList.innerHTML = '';
                    
                    if (data.length === 0) {
                        modelList.innerHTML = '<p class="text-muted">ไม่มีรุ่น</p>';
                        return;
                    }
                    
                    data.forEach(model => {
                        const div = document.createElement('div');
                        div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border-bottom';
                        div.innerHTML = `
                            <div>
                                <strong>${model.model_name}</strong>
                                ${model.brand_name ? `<br><small class="text-muted">${model.brand_name}</small>` : ''}
                            </div>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteModel(${model.model_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        modelList.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error loading models:', error);
                    document.getElementById('modelList').innerHTML = '<p class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
                });
        }

        // เพิ่มหมวดหมู่ใหม่
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const categoryName = document.getElementById('newCategoryName').value.trim();
            
            if (!categoryName) return;
            
            fetch('add_category.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'category_name=' + encodeURIComponent(categoryName)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('newCategoryName').value = '';
                    loadCategories();
                    refreshCategorySelect();
                    alert('เพิ่มหมวดหมู่สำเร็จ!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error adding category:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่');
            });
        });

        // เพิ่มยี่ห้อใหม่
        document.getElementById('addBrandForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const brandName = document.getElementById('newBrandName').value.trim();
            
            if (!brandName) return;
            
            fetch('add_brand.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'brand_name=' + encodeURIComponent(brandName)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('newBrandName').value = '';
                    loadBrands();
                    loadBrandsForModel();
                    alert('เพิ่มยี่ห้อสำเร็จ!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error adding brand:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มยี่ห้อ');
            });
        });

        // เพิ่มรุ่นใหม่
        document.getElementById('addModelForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const modelName = document.getElementById('newModelName').value.trim();
            const brandId = document.getElementById('newModelBrand').value;
            
            if (!modelName || !brandId) return;
            
            fetch('add_model.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'model_name=' + encodeURIComponent(modelName) + '&brand_id=' + brandId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('newModelName').value = '';
                    document.getElementById('newModelBrand').value = '';
                    loadModels();
                    refreshModelSelect();
                    alert('เพิ่มรุ่นสำเร็จ!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error adding model:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มรุ่น');
            });
        });

        // ลบหมวดหมู่
        function deleteCategory(categoryId) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบหมวดหมู่นี้?')) return;
            
            fetch('delete_category.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'category_id=' + categoryId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCategories();
                    refreshCategorySelect();
                    alert('ลบหมวดหมู่สำเร็จ!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting category:', error);
                alert('เกิดข้อผิดพลาดในการลบหมวดหมู่');
            });
        }

        // ลบยี่ห้อ
        function deleteBrand(brandId) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบยี่ห้อนี้?')) return;
            
            fetch('delete_brand.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'brand_id=' + brandId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadBrands();
                    loadBrandsForModel();
                    alert('ลบยี่ห้อสำเร็จ!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting brand:', error);
                alert('เกิดข้อผิดพลาดในการลบยี่ห้อ');
            });
        }

        // ลบรุ่น
        function deleteModel(modelId) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบรุ่นนี้?')) return;
            
            fetch('delete_model.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'model_id=' + modelId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadModels();
                    refreshModelSelect();
                    alert('ลบรุ่นสำเร็จ!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting model:', error);
                alert('เกิดข้อผิดพลาดในการลบรุ่น');
            });
        }

        // รีเฟรช select หมวดหมู่
        function refreshCategorySelect() {
            fetch('get_categories.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('category_id');
                    const currentValue = select.value;
                    
                    select.innerHTML = '<option value="">-- เลือกหมวดหมู่ --</option>';
                    data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.category_id;
                        option.textContent = category.category_name;
                        if (category.category_id == currentValue) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error refreshing category select:', error);
                });
        }

        // รีเฟรช select รุ่น
        function refreshModelSelect() {
            fetch('get_models.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('model_id');
                    const currentValue = select.value;
                    
                    select.innerHTML = '<option value="">-- เลือกรุ่น --</option>';
                    data.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.model_id;
                        option.setAttribute('data-brand', model.brand_name || '');
                        option.textContent = model.model_name + (model.brand_name ? ' (' + model.brand_name + ')' : '');
                        if (model.model_id == currentValue) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                    
                    // อัปเดตยี่ห้อหลังจากรีเฟรช
                    updateBrand();
                })
                .catch(error => {
                    console.error('Error refreshing model select:', error);
                });
        }
    </script>
</body>
</html>