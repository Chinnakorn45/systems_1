<?php
require_once 'config.php';
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

/* ===== ดึงข้อมูลพื้นฐาน ===== */
// ดึงหมวดหมู่
$categories = [];
$cat_result = mysqli_query($link, "SELECT * FROM categories ORDER BY category_name");
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row;
}

// ดึงชื่อรุ่น (models) พร้อมยี่ห้อ
$models = [];
$model_result = mysqli_query($link, "SELECT m.model_id, m.model_name, b.brand_name FROM models m LEFT JOIN brands b ON m.brand_id = b.brand_id ORDER BY m.model_name");
while ($row = mysqli_fetch_assoc($model_result)) {
    $models[] = $row;
}

// ดึงยี่ห้อ
$brands = [];
$brand_result = mysqli_query($link, "SELECT * FROM brands ORDER BY brand_name");
while ($br = mysqli_fetch_assoc($brand_result)) {
    $brands[] = $br;
}

/* ===== ดึงค่า DISTINCT สำหรับ datalist ===== */
// ปีงบประมาณ
$budget_years = [];
$by_result = mysqli_query($link, "
    SELECT DISTINCT budget_year 
    FROM items 
    WHERE budget_year IS NOT NULL AND budget_year <> '' 
    ORDER BY budget_year DESC
");
while ($by = mysqli_fetch_assoc($by_result)) {
    $budget_years[] = $by['budget_year'];
}
// ตำแหน่งที่ติดตั้ง
$locations_list = [];
$loc_result = mysqli_query($link, "
    SELECT DISTINCT location 
    FROM items 
    WHERE location IS NOT NULL AND location <> '' 
    ORDER BY location ASC
");
while ($lc = mysqli_fetch_assoc($loc_result)) {
    $locations_list[] = $lc['location'];
}

/* ===== ดึงเลขครุภัณฑ์เดิม (ล่าสุด 100 รายการ) สำหรับ datalist ===== */
$prev_item_numbers = [];
$inum_result = mysqli_query($link, "
    SELECT item_number 
    FROM items 
    WHERE item_number IS NOT NULL AND item_number <> ''
    ORDER BY item_id DESC
    LIMIT 100
");
while ($in = mysqli_fetch_assoc($inum_result)) {
    $prev_item_numbers[] = $in['item_number'];
}

/* ===== กำหนดตัวแปรเริ่มต้น ===== */
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item_number = $serial_number = $description = $note = $category_id = $total_quantity = $location = $purchase_date = $budget_year = $price_per_unit = $total_price = '';
$image = ''; // backward-compat single image field
$images = []; // array of image rows from item_images
$item_number_err = $serial_number_err = $brand_err = $category_id_err = $total_quantity_err = $budget_year_err = $price_per_unit_err = $image_err = $model_id_err = '';
$is_edit = false;
$model_id = '';
$brand_name_display = '';
$brand = '';
$is_disposed = 0; // 0=ยังไม่จำหน่าย, 1=จำหน่าย(ส่งคืนพัสดุ)

// สำหรับ Swal หลัง redirect
$swal_success = null; // string | null
$swal_error   = null; // string | null

/* ===== ถ้าเป็นการแก้ไข ดึงข้อมูลเดิมมาแสดง ===== */
if ($item_id > 0) {
    $is_edit = true;
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
            $item_number   = $row['item_number'];
            $serial_number = isset($row['serial_number']) ? $row['serial_number'] : '';
            $brand         = $row['brand'];
            $brand_name_display = isset($row['brand_name']) ? $row['brand_name'] : $row['brand'];
            $description   = $row['description'];
            $category_id   = $row['category_id'];
            $total_quantity= $row['total_quantity'];
            $location      = $row['location'];
            $purchase_date = $row['purchase_date'];
            $budget_year   = $row['budget_year'];
            $price_per_unit= isset($row['price_per_unit']) ? $row['price_per_unit'] : '';
            $total_price   = isset($row['total_price']) ? $row['total_price'] : '';
            $image         = isset($row['image']) ? $row['image'] : '';
            $note          = isset($row['note']) ? $row['note'] : '';
            $model_id      = isset($row['model_id']) ? $row['model_id'] : '';
            $is_disposed   = isset($row['is_disposed']) ? (int)$row['is_disposed'] : 0;

            // load additional images
            $images = [];
            $sql_imgs = "SELECT image_id, image_path, is_primary, sort_order, uploaded_at FROM item_images WHERE item_id = ? ORDER BY sort_order, uploaded_at";
            if ($stmt_imgs = @mysqli_prepare($link, $sql_imgs)) {
                mysqli_stmt_bind_param($stmt_imgs, "i", $item_id);
                mysqli_stmt_execute($stmt_imgs);
                $res_imgs = mysqli_stmt_get_result($stmt_imgs);
                while ($imgRow = mysqli_fetch_assoc($res_imgs)) {
                    $images[] = $imgRow;
                }
                mysqli_stmt_close($stmt_imgs);
            }
        } else {
            $swal_error = 'ไม่พบข้อมูลครุภัณฑ์ที่ต้องการแก้ไข';
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing select item for edit: " . mysqli_error($link));
        $swal_error = 'เกิดข้อผิดพลาดในการดึงข้อมูล (DB Error)';
    }
}

/* ===== Success/Error จาก query param ===== */
if (isset($_GET['success'])) {
    if ($_GET['success'] == '1') {
        $swal_success = 'บันทึกข้อมูลสำเร็จ!';
    } elseif ($_GET['success'] == '0') {
        $swal_error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
    }
}

/* ===== เมื่อมีการส่งฟอร์ม ===== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_number = trim($_POST['item_number']);
    $serial_number = trim($_POST['serial_number']);

    // ดึงค่า model_id
    $selected_model_id_for_post = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
    $model_id = $selected_model_id_for_post;

    $brand_from_model = '';
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
    // brand: จากรุ่นหรือจาก POST (แต่ฟิลด์ในฟอร์มถูก lock ให้ readonly)
    $brand = isset($_POST['brand']) && trim($_POST['brand']) !== '' ? trim($_POST['brand']) : $brand_from_model;

    $description = trim($_POST['description']);
    $note = trim($_POST['note']);
    $category_id = intval($_POST['category_id']);
    $total_quantity = intval($_POST['total_quantity']);
    $location = trim($_POST['location']);
    $purchase_date = $_POST['purchase_date'];
    $budget_year = trim($_POST['budget_year']);
    $price_per_unit = trim($_POST['price_per_unit']);

    // สถานะจำหน่าย
    $is_disposed = isset($_POST['is_disposed']) ? 1 : 0;

    // Validate
    if (empty($serial_number)) $serial_number_err = "กรุณากรอกซีเรียลนัมเบอร์";
    if ($model_id <= 0) $model_id_err = "กรุณาเลือกชื่อรุ่น";
    if (empty($brand)) $brand_err = "ไม่สามารถระบุยี่ห้อได้ กรุณาเลือกชื่อรุ่นที่มียี่ห้อ หรือเพิ่มยี่ห้อ/รุ่นก่อน";
    if ($category_id <= 0) $category_id_err = "กรุณาเลือกหมวดหมู่";
    if ($total_quantity < 0) $total_quantity_err = "จำนวนรวมต้องไม่ติดลบ";
    if (empty($budget_year) || !preg_match('/^[0-9]{4}$/', $budget_year)) $budget_year_err = "กรุณากรอกปีงบประมาณ 4 หลัก";
    if ($price_per_unit === '' || !is_numeric($price_per_unit) || $price_per_unit < 0) $price_per_unit_err = "กรุณากรอกราคาต่อหน่วย (ตัวเลขไม่ติดลบ)";
    else $price_per_unit = floatval($price_per_unit);

    $serial_len = function_exists('mb_strlen') ? mb_strlen($serial_number, 'UTF-8') : strlen($serial_number);
    if ($serial_len > 100) $serial_number_err = "Serial Number ต้องไม่เกิน 100 ตัวอักษร";
    $item_len = function_exists('mb_strlen') ? mb_strlen($item_number, 'UTF-8') : strlen($item_number);
    if (!empty($item_number) && $item_len > 100) {
        $item_number_err = "เลขครุภัณฑ์ต้องไม่เกิน 100 ตัวอักษร";
        if (empty($swal_error)) { $swal_error = 'บันทึกเลขครุภัณฑ์ไม่ได้: ' . $item_number_err; }
    }
    if (!empty($serial_number) && !preg_match('/^[a-zA-Z0-9\-\_\.\s]+$/', $serial_number)) {
        $serial_number_err = "Serial Number ต้องประกอบด้วยตัวอักษร ตัวเลข และเครื่องหมาย - _ . เท่านั้น";
    }

    // คำนวณราคารวม
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
            if ($is_edit) $sql_check_serial .= " AND item_id != ?";
            $stmt_check = mysqli_prepare($link, $sql_check_serial);
            if ($is_edit) mysqli_stmt_bind_param($stmt_check, "si", $serial_number, $item_id);
            else mysqli_stmt_bind_param($stmt_check, "s", $serial_number);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            $row_check = mysqli_fetch_assoc($result_check);
            if ($row_check['cnt'] > 0) $serial_number_err = "Serial Number นี้ถูกใช้ไปแล้วในระบบ";
            mysqli_stmt_close($stmt_check);
        }
    }

    // ตรวจสอบ Item Number ซ้ำ
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
            if ($is_edit) $sql_check_item_num .= " AND item_id != ?";
            $stmt_check_item_num = mysqli_prepare($link, $sql_check_item_num);
            if ($is_edit) mysqli_stmt_bind_param($stmt_check_item_num, "si", $item_number, $item_id);
            else mysqli_stmt_bind_param($stmt_check_item_num, "s", $item_number);
            mysqli_stmt_execute($stmt_check_item_num);
            $result_check_item_num = mysqli_stmt_get_result($stmt_check_item_num);
            $row_check_item_num = mysqli_fetch_assoc($result_check_item_num);
            if ($row_check_item_num['cnt'] > 0) {
                $item_number_err = "เลขครุภัณฑ์นี้ถูกใช้ไปแล้วในระบบ";
                $swal_error = 'บันทึกเลขครุภัณฑ์ไม่ได้: ' . $item_number_err;
            }
            mysqli_stmt_close($stmt_check_item_num);
        }
    }

    // ลบรูปที่ผู้ใช้ติ๊ก
    if ($is_edit && isset($_POST['remove_images']) && is_array($_POST['remove_images'])) {
        $toRemove = array_map('intval', $_POST['remove_images']);
        if (!empty($toRemove)) {
            $in = implode(',', $toRemove);
            $q = "SELECT image_id, image_path FROM item_images WHERE image_id IN ($in) AND item_id = " . intval($item_id);
            $res = mysqli_query($link, $q);
            while ($r = mysqli_fetch_assoc($res)) {
                if (!empty($r['image_path']) && file_exists($r['image_path'])) {
                    @unlink($r['image_path']);
                }
            }
            mysqli_query($link, "DELETE FROM item_images WHERE image_id IN ($in) AND item_id = " . intval($item_id));
            // ถ้ารูปหลักโดนลบ เคลียร์ฟิลด์ image
            if (!empty($image)) {
                $chk = mysqli_query($link, "SELECT COUNT(*) AS c FROM item_images WHERE item_id=" . intval($item_id) . " AND image_path='" . mysqli_real_escape_string($link, $image) . "'");
                $rowc = $chk ? mysqli_fetch_assoc($chk) : null;
                if (!$rowc || intval($rowc['c']) === 0) {
                    mysqli_query($link, "UPDATE items SET image = '' WHERE item_id = " . intval($item_id));
                    $image = '';
                }
            }
        }
    }

    // อัปโหลดไฟล์รูป (รองรับหลายรูป: images[])
    $uploaded_images = [];
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $allowed = ['jpg','jpeg','png','gif'];
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        foreach ($_FILES['images']['name'] as $idx => $origName) {
            if (empty($origName)) continue;
            $error = $_FILES['images']['error'][$idx];
            if ($error !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;
            $newname = 'uploads/item_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['images']['tmp_name'][$idx], $newname)) {
                $uploaded_images[] = $newname;
            }
        }
    }
    if (isset($_POST['old_image']) && empty($uploaded_images)) {
        $image = $_POST['old_image'];
    }

    // หากไม่มี error -> บันทึก
    if (empty($serial_number_err) && empty($item_number_err) && empty($brand_err) && empty($category_id_err) && empty($total_quantity_err) && empty($budget_year_err) && empty($price_per_unit_err) && empty($image_err) && empty($model_id_err)) {

        if ($is_edit) {
            $sql = "UPDATE items 
                    SET model_name=?, item_number=?, serial_number=?, brand=?, description=?, note=?, 
                        category_id=?, total_quantity=?, image=?, location=?, purchase_date=?, budget_year=?, 
                        price_per_unit=?, total_price=?, is_disposed=? 
                    WHERE item_id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssssiissssddii",
                    $model_name, $item_number, $serial_number, $brand, $description, $note,
                    $category_id, $total_quantity, $image, $location, $purchase_date, $budget_year,
                    $price_per_unit, $total_price, $is_disposed, $item_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    // แทรกรูปใหม่
                    if (!empty($uploaded_images)) {
                        $ins_sql = "INSERT INTO item_images (item_id, image_path, is_primary, sort_order) VALUES (?, ?, 0, 0)";
                        if ($ins_stmt = @mysqli_prepare($link, $ins_sql)) {
                            foreach ($uploaded_images as $imgPath) {
                                mysqli_stmt_bind_param($ins_stmt, "is", $item_id, $imgPath);
                                mysqli_stmt_execute($ins_stmt);
                            }
                            mysqli_stmt_close($ins_stmt);
                        }
                        if (empty($image) && !empty($uploaded_images)) {
                            $first = $uploaded_images[0];
                            mysqli_query($link, "UPDATE items SET image = '" . mysqli_real_escape_string($link, $first) . "' WHERE item_id = " . intval($item_id));
                        }
                    }
                    echo "<script>window.location = 'item_form.php?id=" . $item_id . "&success=1';</script>";
                    exit;
                } else {
                    $errno = mysqli_errno($link);
                    $err   = mysqli_error($link);
                    error_log("Error executing UPDATE ($errno): " . $err);
                    if ($errno == 1062 && stripos($err, 'item_number') !== false) {
                        $item_number_err = "เลขครุภัณฑ์นี้ถูกใช้ไปแล้วในระบบ";
                        $swal_error = 'บันทึกเลขครุภัณฑ์ไม่ได้: ' . $item_number_err;
                    } else {
                        $swal_error = 'เกิดข้อผิดพลาดในการบันทึกการแก้ไข';
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Error preparing UPDATE: " . mysqli_error($link));
                $swal_error = 'เกิดข้อผิดพลาดในการเตรียมการอัปเดตข้อมูล';
            }
        } else {
            $sql = "INSERT INTO items (model_name, item_number, serial_number, brand, description, note, category_id, total_quantity, image, location, purchase_date, budget_year, price_per_unit, total_price, is_disposed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssssiissssddi",
                    $model_name, $item_number, $serial_number, $brand, $description, $note,
                    $category_id, $total_quantity, $image, $location, $purchase_date, $budget_year,
                    $price_per_unit, $total_price, $is_disposed
                );
                if (mysqli_stmt_execute($stmt)) {
                    $new_item_id = mysqli_insert_id($link);
                    if (!empty($uploaded_images)) {
                        $ins_sql = "INSERT INTO item_images (item_id, image_path, is_primary, sort_order) VALUES (?, ?, 0, 0)";
                        if ($ins_stmt = @mysqli_prepare($link, $ins_sql)) {
                            foreach ($uploaded_images as $imgPath) {
                                mysqli_stmt_bind_param($ins_stmt, "is", $new_item_id, $imgPath);
                                mysqli_stmt_execute($ins_stmt);
                            }
                            mysqli_stmt_close($ins_stmt);
                        }
                        $first = $uploaded_images[0];
                        mysqli_query($link, "UPDATE items SET image = '" . mysqli_real_escape_string($link, $first) . "' WHERE item_id = " . intval($new_item_id));
                    }
                    echo "<script>window.location = 'item_form.php?success=1';</script>";
                    exit;
                } else {
                    $errno = mysqli_errno($link);
                    $err   = mysqli_error($link);
                    error_log("Error executing INSERT ($errno): " . $err);
                    if ($errno == 1062 && stripos($err, 'item_number') !== false) {
                        $item_number_err = "เลขครุภัณฑ์นี้ถูกใช้ไปแล้วในระบบ";
                        $swal_error = 'บันทึกเลขครุภัณฑ์ไม่ได้: ' . $item_number_err;
                    } else {
                        $swal_error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลใหม่';
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Error preparing INSERT: " . mysqli_error($link));
                $swal_error = 'เกิดข้อผิดพลาดในการเตรียมการบันทึกข้อมูลใหม่';
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
        :root{ --brand-green:#41B143; --brand-green-dark:#2f8f33; }
        body { background:#e8f5e9; font-family:'Prompt','Kanit','Arial',sans-serif; color:#333; }
        .container { max-width:700px; margin-top:40px; padding:20px; background:#fff; border-radius:8px; box-shadow:0 0 15px rgba(0,0,0,0.1); }
        .form-label { font-weight:500; color:#2e7d32; }
        .btn-main { background:linear-gradient(135deg,var(--brand-green) 0%,#8BC34A 100%); color:#fff; border:none; padding:10px 20px; border-radius:5px; transition:.3s; }
        .btn-main:hover { background:linear-gradient(135deg,#8BC34A 0%,var(--brand-green) 100%); box-shadow:0 4px 8px rgba(0,0,0,.2); color:#fff; }
        .btn-secondary { background:#6c757d; color:#fff; border:none; padding:10px 20px; border-radius:5px; transition:.3s; }
        .btn-secondary:hover{ background:#5a6268; color:#fff; }
        .form-control.is-invalid,.form-select.is-invalid{ border-color:#dc3545; }
        .invalid-feedback{ color:#dc3545; }
        .mb-3 img{ border:1px solid #ddd; border-radius:4px; padding:5px; }
        /* Scanner overlay */
        #qr-reader { position:relative; width:100%; max-width:400px; margin:auto; height:320px; background:#000; }
        #qr-reader video { width:100%; height:100%; object-fit:cover; }
        .scan-overlay { position:absolute; inset:0; pointer-events:none; }
        .scan-frame { position:absolute; top:50%; left:50%; width:80%; height:80px; transform:translate(-50%,-50%); border-radius:18px; box-sizing:border-box; }
        .scan-corner { position:absolute; width:28px; height:28px; }
        .scan-corner.tl { top:0; left:0; border-top:4px solid #00ff66; border-left:4px solid #00ff66; border-top-left-radius:18px; }
        .scan-corner.tr { top:0; right:0; border-top:4px solid #00ff66; border-right:4px solid #00ff66; border-top-right-radius:18px; }
        .scan-corner.bl { bottom:0; left:0; border-bottom:4px solid #00ff66; border-left:4px solid #00ff66; border-bottom-left-radius:18px; }
        .scan-corner.br { bottom:0; right:0; border-bottom:4px solid #00ff66; border-right:4px solid #00ff66; border-bottom-right-radius:18px; }
        .scan-dim { position:absolute; background:rgba(0,0,0,.55); }
        .scan-dim.top{ left:0; right:0; top:0; height:32%; }
        .scan-dim.bottom{ left:0; right:0; bottom:0; height:32%; }
        .scan-dim.left{ left:0; top:32%; bottom:32%; width:10%; }
        .scan-dim.right{ right:0; top:32%; bottom:32%; width:10%; }
        .scan-instruction { position:absolute; left:50%; top:calc(50% + 60px); transform:translateX(-50%); color:#fff; font-size:1.1rem; text-shadow:0 1px 4px #000; width:100%; text-align:center; z-index:2; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-center text-success"><i class="fas fa-box me-2"></i><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>ครุภัณฑ์</h2>
    <form action="" method="post" enctype="multipart/form-data">

        <!-- เลขครุภัณฑ์: ใส่ datalist + ghost hint + ปุ่มสแกน -->
        <div class="mb-3">
            <label for="item_number" class="form-label">เลขครุภัณฑ์</label>

            <!-- ชั้นซ้อนเพื่อทำ ghost hint -->
            <div class="position-relative">
                <input type="text"
                        class="form-control <?php echo !empty($item_number_err) ? 'is-invalid' : ''; ?>"
                        id="item_number" name="item_number"
                        value="<?php echo htmlspecialchars($item_number); ?>"
                        maxlength="100"
                        list="item_number_list"
                        autocomplete="off"
                        style="background: transparent; position: relative; z-index: 2;">
                <!-- ghost hint -->
                <input type="text" id="item_number_hint" tabindex="-1" aria-hidden="true"
                        class="form-control"
                        style="position:absolute; inset:0; color:#9aa0a6; pointer-events:none; z-index:1;"
                        value="" />
            </div>

            <div class="d-flex justify-content-end mt-1"><small id="item_number_counter" class="text-muted">0/100</small></div>

            <datalist id="item_number_list">
                <?php foreach ($prev_item_numbers as $num): ?>
                    <option value="<?php echo htmlspecialchars($num); ?>"></option>
                <?php endforeach; ?>
            </datalist>


            <div class="invalid-feedback"><?php echo $item_number_err; ?></div>
            <small class="form-text text-muted">ระบบจะเดาและแสดงต่อท้ายอัตโนมัติและตรวจสอบความซ้ำให้ทันที</small>
        </div>

        <div class="mb-3">
            <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="text" class="form-control <?php echo !empty($serial_number_err) ? 'is-invalid' : ''; ?>" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($serial_number); ?>" required maxlength="100">
                <button type="button" class="btn btn-outline-secondary" onclick="openScannerQuagga('serial_number')"><i class="fas fa-qrcode me-1"></i> สแกน</button>
            </div>
            <div class="d-flex justify-content-end mt-1"><small id="serial_number_counter" class="text-muted">0/100</small></div>
            <div class="invalid-feedback"><?php echo $serial_number_err; ?></div>
            <small class="form-text text-muted">Serial Number จะถูกตรวจสอบความซ้ำซ้อนอัตโนมัติ</small>
        </div>

        <div class="mb-3">
            <label for="model_id" class="form-label">ชื่อรุ่น <span class="text-danger">*</span></label>
            <div class="input-group">
                <div class="me-2" style="flex:1;">
                    <input type="text" id="modelSearch" class="form-control mb-1" placeholder="ค้นหาชื่อรุ่น..." oninput="filterSelectOptions('modelSearch','model_id')">
                    <select class="form-select <?php echo !empty($model_id_err) ? 'is-invalid' : ''; ?>" id="model_id" name="model_id" required onchange="updateBrand()">
                        <option value="">-- เลือกรุ่น --</option>
                        <?php foreach ($models as $m): ?>
                            <option value="<?php echo $m['model_id']; ?>" data-brand="<?php echo htmlspecialchars($m['brand_name']); ?>" <?php if ($model_id == $m['model_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($m['model_name']) . (isset($m['brand_name']) && $m['brand_name'] ? ' (' . htmlspecialchars($m['brand_name']) . ')' : ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-outline-primary" onclick="openModelModal()"><i class="fas fa-plus me-1"></i> จัดการ</button>
            </div>
            <div class="invalid-feedback"><?php echo $model_id_err; ?></div>
        </div>

        <div class="mb-3">
            <label for="brand_name" class="form-label">ยี่ห้อ</label>
            <div class="input-group">
                <input type="text" class="form-control <?php echo !empty($brand_err) ? 'is-invalid' : ''; ?>" id="brand_name" name="brand" value="<?php echo htmlspecialchars($brand_name_display ? $brand_name_display : $brand); ?>" placeholder="กำหนดจากรุ่นอัตโนมัติ" readonly>
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
                <div class="me-2" style="flex:1;">
                    <input type="text" id="categorySearch" class="form-control mb-1" placeholder="ค้นหาหมวดหมู่..." oninput="filterSelectOptions('categorySearch','category_id')">
                    <select class="form-select <?php echo !empty($category_id_err) ? 'is-invalid' : ''; ?>" id="category_id" name="category_id" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php if ($category_id == $cat['category_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
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

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_disposed" name="is_disposed" <?php echo $is_disposed ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_disposed">จำหน่าย (ส่งคืนพัสดุ)</label>
        </div>

        <!-- ตำแหน่งที่ติดตั้ง: datalist -->
        <div class="mb-3">
            <label for="location" class="form-label">ตำแหน่งที่ติดตั้ง</label>
            <input
                type="text"
                class="form-control"
                id="location"
                name="location"
                list="location_list"
                value="<?php echo htmlspecialchars($location); ?>"
                placeholder="เลือกจากรายการ หรือพิมพ์เพิ่มใหม่"
            >
            <datalist id="location_list">
                <?php foreach ($locations_list as $loc): ?>
                    <option value="<?php echo htmlspecialchars($loc); ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <small class="text-muted">เลือกจากรายการที่มี หรือพิมพ์ตำแหน่งใหม่ได้</small>
        </div>

        <!-- ปีงบประมาณ: datalist + ตรวจเลข 4 หลัก -->
        <div class="mb-3">
            <label for="budget_year" class="form-label">ปีงบประมาณ <span class="text-danger">*</span></label>
            <input
                type="text"
                class="form-control <?php echo !empty($budget_year_err) ? 'is-invalid' : ''; ?>"
                id="budget_year"
                name="budget_year"
                list="budget_year_list"
                value="<?php echo htmlspecialchars($budget_year); ?>"
                maxlength="4"
                inputmode="numeric"
                pattern="[0-9]{4}"
                placeholder="เช่น 2567 หรือ 2024"
                required
            >
            <datalist id="budget_year_list">
                <?php foreach ($budget_years as $by): ?>
                    <option value="<?php echo htmlspecialchars($by); ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <div class="invalid-feedback"><?php echo $budget_year_err; ?></div>
            <small class="text-muted">เลือกปีจากฐานข้อมูล หรือพิมพ์ปีใหม่ (ตัวเลข 4 หลัก)</small>
        </div>

        <div class="mb-4">
            <label for="purchase_date" class="form-label">วันที่จัดซื้อ</label>
            <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?php echo htmlspecialchars($purchase_date); ?>">
        </div>

        <!-- รูปภาพ: เลือกหลายไฟล์ + ถ่ายจากกล้องหลายรูป -->
        <div class="mb-3">
            <label for="images" class="form-label">รูปภาพ (หลายไฟล์)</label>

            <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-success" onclick="openCameraModal()">
                    <i class="fa-solid fa-camera me-1"></i> ถ่ายภาพด้วยกล้อง
                </button>
                <small class="text-muted align-self-center">หรือเลือกจากคลังภาพ</small>
            </div>

            <input
                type="file"
                class="form-control <?php echo !empty($image_err) ? 'is-invalid' : ''; ?>"
                id="images"
                name="images[]"
                accept="image/*"
                multiple
            >
            <small class="form-text text-muted">
                รองรับหลายรูป (jpg, png, gif) — รูปที่ถ่ายในโมดัลจะถูกแนบที่นี่อัตโนมัติ
            </small>
        </div>

        <!-- พรีวิวไฟล์ที่แนบ -->
        <div id="cameraPreviewList" class="mt-2 d-flex flex-wrap gap-2"></div>

        <div class="mt-2" id="existingImages">
            <?php if (!empty($images)): ?>
                <?php foreach ($images as $img): ?>
                    <div class="d-inline-block me-2 mb-2 text-center" style="max-width:120px;">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="img" style="max-width:110px; height:auto; display:block; border:1px solid #ddd; padding:4px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_images[]" value="<?php echo intval($img['image_id']); ?>" id="rm<?php echo intval($img['image_id']); ?>">
                            <label class="form-check-label small" for="rm<?php echo intval($img['image_id']); ?>">ลบ</label>
                        </div>
                    </div>
                <?php endforeach; ?>
                <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($image); ?>">
            <?php elseif ($image): ?>
                <div class="mt-2"><img src="<?php echo htmlspecialchars($image); ?>" alt="รูปภาพ" style="max-width:150px; height: auto;"></div>
                <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($image); ?>">
            <?php endif; ?>
        </div>
        <div class="invalid-feedback"><?php echo $image_err; ?></div>

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

<!-- Modals: Scanner / Category / Brand / Model / Camera -->
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
        <div id="qr-reader-results" class="mt-2 small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: จัดการหมวดหมู่ -->
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
            <div id="categoryList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button></div>
    </div>
  </div>
</div>

<!-- Modal: จัดการยี่ห้อ -->
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
            <div id="brandList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button></div>
    </div>
  </div>
</div>

<!-- Modal: จัดการชื่อรุ่น -->
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
                </select>
              </div>
              <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i> เพิ่ม</button>
            </form>
          </div>
          <div class="col-md-6">
            <h6>รายการรุ่น</h6>
            <div id="modelList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button></div>
    </div>
  </div>
</div>

<!-- Modal: Camera Capture (หลายภาพ) -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="cameraModalLabel"><i class="fa-solid fa-camera me-2"></i> ถ่ายภาพด้วยกล้อง (หลายภาพ)</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="ratio ratio-16x9 bg-dark rounded">
          <video id="camStream" autoplay playsinline muted style="object-fit:cover;"></video>
        </div>
        <div class="d-flex justify-content-center gap-2 my-3">
          <button type="button" class="btn btn-outline-light text-dark border" id="switchCameraBtn" title="สลับกล้อง">
            <i class="fa-solid fa-camera-rotate me-1"></i> สลับกล้อง
          </button>
          <button type="button" class="btn btn-primary" id="takePhotoBtn">
            <i class="fa-solid fa-circle-dot me-1"></i> ถ่ายภาพ
          </button>
        </div>
        <div>
          <h6 class="mb-2">ภาพที่ถ่ายไว้</h6>
          <div id="shotList" class="d-flex flex-wrap gap-2"></div>
          <small class="text-muted d-block mt-1">สามารถลบแต่ละรูปก่อนบันทึกได้</small>
        </div>
      </div>
      <div class="modal-footer">
        <div class="me-auto text-muted small" id="cameraHint">ระบบจะบีบอัดและปรับขนาดภาพอัตโนมัติก่อนแนบ</div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        <button type="button" class="btn btn-success" id="attachShotsBtn"><i class="fa-solid fa-paperclip me-1"></i> แนบภาพที่ถ่ายเข้าฟอร์ม</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const BRAND_GREEN = '#41B143';

function filterSelectOptions(searchInputId, selectId) {
    var term = document.getElementById(searchInputId).value.toLowerCase();
    var sel = document.getElementById(selectId);
    var firstMatchIndex = -1;
    for (var i = 0; i < sel.options.length; i++) {
        var txt = sel.options[i].text.toLowerCase();
        var visible = txt.indexOf(term) !== -1;
        sel.options[i].style.display = visible ? '' : 'none';
        if (visible && firstMatchIndex === -1) firstMatchIndex = i;
    }
    if (firstMatchIndex !== -1) sel.selectedIndex = firstMatchIndex;
    else {
        for (var j = 0; j < sel.options.length; j++) if (sel.options[j].value === '') { sel.selectedIndex = j; break; }
    }
    try { if (selectId === 'model_id') updateBrand(); } catch(e){}
}

// ล็อกช่องยี่ห้อให้อ่านอย่างเดียว
document.addEventListener('DOMContentLoaded', function () {
    var brandInput = document.getElementById('brand_name');
    if (!brandInput) return;
    brandInput.readOnly = true;
    brandInput.style.background = '#e9ecef';
    brandInput.style.cursor = 'not-allowed';
    brandInput.style.pointerEvents = 'none';
});

document.addEventListener('DOMContentLoaded', function(){ updateBrand(); });

// บังคับปีงบประมาณเป็นเลข 4 หลัก
document.addEventListener('DOMContentLoaded', function(){
  const by = document.getElementById('budget_year');
  if (by) by.addEventListener('input', function(){ this.value = this.value.replace(/\D/g,'').slice(0,4); });
});

// realtime char counters
function bindCharCounter(inputId, counterId, max) {
  const el = document.getElementById(inputId);
  const counter = document.getElementById(counterId);
  if (!el || !counter) return;
  const update = () => {
    const len = el.value.length;
    counter.textContent = max ? `${len}/${max}` : `${len}`;
    if (max) {
      if (len > max) {
        counter.classList.add('text-danger');
        counter.classList.remove('text-warning');
      } else if (len > max - 10) {
        counter.classList.add('text-warning');
        counter.classList.remove('text-danger');
      } else {
        counter.classList.remove('text-danger');
        counter.classList.remove('text-warning');
      }
    }
  };
  el.addEventListener('input', update);
  el.addEventListener('change', update);
  update();
}

document.addEventListener('DOMContentLoaded', function(){
  bindCharCounter('item_number','item_number_counter',100);
  bindCharCounter('serial_number','serial_number_counter',100);
});
</script>

<script>
// คำนวณราคารวม
function calculateTotalPrice() {
    const quantity = parseFloat(document.getElementById('total_quantity').value) || 0;
    const pricePerUnit = parseFloat(document.getElementById('price_per_unit').value) || 0;
    const totalPrice = quantity * pricePerUnit;
    document.getElementById('total_price').value = totalPrice.toFixed(2);
}
document.getElementById('total_quantity').addEventListener('input', calculateTotalPrice);
document.getElementById('price_per_unit').addEventListener('input', calculateTotalPrice);
document.addEventListener('DOMContentLoaded', calculateTotalPrice);

// อัปเดตยี่ห้อตามรุ่น
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
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'field=' + fieldName + '&value=' + encodeURIComponent(value) + '&item_id=' + currentItemId
    })
    .then(response => response.json())
    .then(data => {
        const inputField = document.getElementById(fieldName);
        const feedbackDiv = inputField.parentNode.querySelector('.invalid-feedback') || inputField.closest('.mb-3')?.querySelector('.invalid-feedback');
        if (data.duplicate) {
            inputField.classList.add('is-invalid');
            if (feedbackDiv) feedbackDiv.textContent = data.message;
        } else {
            inputField.classList.remove('is-invalid');
            if (feedbackDiv) feedbackDiv.textContent = '';
        }
    })
    .catch(error => {
        console.error('Error checking duplicate:', error);
        Swal.fire({icon:'error',title:'ผิดพลาด',text:'ตรวจสอบข้อมูลซ้ำไม่สำเร็จ',confirmButtonColor:BRAND_GREEN});
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const serialNumberInput = document.getElementById('serial_number');
    const itemNumberInput = document.getElementById('item_number');
    const currentItemId = '<?php echo $item_id; ?>';

    if (serialNumberInput) {
        serialNumberInput.addEventListener('blur', function() { checkDuplicateData('serial_number', this.value, currentItemId); });
        serialNumberInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const feedbackDiv = this.parentNode.querySelector('.invalid-feedback') || this.closest('.mb-3')?.querySelector('.invalid-feedback');
            if (feedbackDiv) feedbackDiv.textContent = '';
        });
    }
    if (itemNumberInput) {
        itemNumberInput.addEventListener('blur', function() { checkDuplicateData('item_number', this.value, currentItemId); });
        itemNumberInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const feedbackDiv = this.parentNode.querySelector('.invalid-feedback') || this.closest('.mb-3')?.querySelector('.invalid-feedback');
            if (feedbackDiv) feedbackDiv.textContent = '';
        });
    }

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            if (serialNumberInput && serialNumberInput.classList.contains('is-invalid')) hasError = true;
            if (itemNumberInput && itemNumberInput.classList.contains('is-invalid')) hasError = true;
            if (hasError) {
                e.preventDefault();
                Swal.fire({icon:'warning',title:'กรุณาแก้ไขข้อผิดพลาดก่อนส่งฟอร์ม',confirmButtonColor:BRAND_GREEN});
                return false;
            }
        });
    }
});

// --- Barcode Scanner (Quagga) ---
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
            constraints: { facingMode: "environment", width: { ideal: 1280 }, height: { ideal: 720 } }
        },
        decoder: { readers: ["code_128_reader","ean_reader","ean_8_reader","code_39_reader","upc_reader","upc_e_reader"] },
        locate: true
    }, function(err) {
        if (err) {
            document.getElementById('qr-reader-results').innerHTML = '<div class="text-danger">ไม่สามารถเปิดกล้องได้</div>';
            Swal.fire({icon:'error',title:'เปิดกล้องไม่สำเร็จ',text:err,confirmButtonColor:BRAND_GREEN});
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

    let lastCode = '';
    let sameCodeCount = 0;
    const confirmThreshold = 2;

    Quagga.onDetected(function(result) {
        if (result && result.codeResult && result.codeResult.code) {
            let code = result.codeResult.code.trim();
            if (code === lastCode) { sameCodeCount++; } else { lastCode = code; sameCodeCount = 1; }
            if (sameCodeCount >= confirmThreshold) {
                document.getElementById('qr-reader-results').innerHTML = '<div class="text-success">สแกนสำเร็จ: ' + code + '</div>';
                setTimeout(() => {
                    if (scanTargetInputId) document.getElementById(scanTargetInputId).value = code;
                    Quagga.stop();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('scanModal'));
                    modal.hide();
                    document.getElementById('qr-reader-results').innerHTML = '';
                    lastCode = ''; sameCodeCount = 0;
                }, 800);
            } else {
                document.getElementById('qr-reader-results').innerHTML = '<div class="text-warning">กำลังจับโฟกัส... (' + sameCodeCount + '/' + confirmThreshold + ')</div>';
            }
        }
    });
}
document.getElementById('scanModal').addEventListener('hidden.bs.modal', function () {
    if (Quagga) Quagga.stop();
    document.getElementById('qr-reader-results').innerHTML = '';
    const overlay = document.querySelector('.scan-overlay');
    if (overlay) overlay.remove();
    scanTargetInputId = null;
});

// --- Modal Management: categories / brands / models ---
function openCategoryModal() { loadCategories(); new bootstrap.Modal(document.getElementById('categoryModal')).show(); }
function openBrandModal()    { loadBrands();    new bootstrap.Modal(document.getElementById('brandModal')).show(); }
function openModelModal()    { loadModels(); loadBrandsForModel(); new bootstrap.Modal(document.getElementById('modelModal')).show(); }

function loadCategories() {
    fetch('get_categories.php').then(r=>r.json()).then(data=>{
        const box = document.getElementById('categoryList'); box.innerHTML='';
        if (!data.length) { box.innerHTML = '<p class="text-muted">ไม่มีหมวดหมู่</p>'; return; }
        data.forEach(c=>{
            const div=document.createElement('div');
            div.className='d-flex justify-content-between align-items-center mb-2 p-2 border-bottom';
            div.innerHTML = `<span>${c.category_name}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(${c.category_id})"><i class="fas fa-trash"></i></button>`;
            box.appendChild(div);
        });
    }).catch(_=>{
        document.getElementById('categoryList').innerHTML='<p class="text-danger">เกิดข้อผิดพลาด</p>';
        Swal.fire({icon:'error',title:'ผิดพลาด',text:'โหลดรายการหมวดหมู่ไม่สำเร็จ',confirmButtonColor:BRAND_GREEN});
    });
}
function loadBrands() {
    fetch('get_brands.php').then(r=>r.json()).then(data=>{
        const box=document.getElementById('brandList'); box.innerHTML='';
        if (!data.length) { box.innerHTML = '<p class="text-muted">ไม่มียี่ห้อ</p>'; return; }
        data.forEach(b=>{
            const div=document.createElement('div');
            div.className='d-flex justify-content-between align-items-center mb-2 p-2 border-bottom';
            div.innerHTML = `<span>${b.brand_name}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteBrand(${b.brand_id})"><i class="fas fa-trash"></i></button>`;
            box.appendChild(div);
        });
    }).catch(_=>{
        document.getElementById('brandList').innerHTML='<p class="text-danger">เกิดข้อผิดพลาด</p>';
        Swal.fire({icon:'error',title:'ผิดพลาด',text:'โหลดรายการยี่ห้อไม่สำเร็จ',confirmButtonColor:BRAND_GREEN});
    });
}
function loadBrandsForModel() {
    fetch('get_brands.php').then(r=>r.json()).then(data=>{
        const sel=document.getElementById('newModelBrand'); sel.innerHTML = '<option value="">-- เลือกยี่ห้อ --</option>';
        data.forEach(b=>{
            const op=document.createElement('option'); op.value=b.brand_id; op.textContent=b.brand_name; sel.appendChild(op);
        });
    });
}
function loadModels() {
    fetch('get_models.php').then(r=>r.json()).then(data=>{
        const box=document.getElementById('modelList'); box.innerHTML='';
        if (!data.length) { box.innerHTML = '<p class="text-muted">ไม่มีรุ่น</p>'; return; }
        data.forEach(m=>{
            const div=document.createElement('div');
            div.className='d-flex justify-content-between align-items-center mb-2 p-2 border-bottom';
            div.innerHTML = `<div><strong>${m.model_name}</strong>${m.brand_name ? `<br><small class="text-muted">${m.brand_name}</small>`:''}</div>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteModel(${m.model_id})"><i class="fas fa-trash"></i></button>`;
            box.appendChild(div);
        });
    }).catch(_=>{
        document.getElementById('modelList').innerHTML='<p class="text-danger">เกิดข้อผิดพลาด</p>';
        Swal.fire({icon:'error',title:'ผิดพลาด',text:'โหลดรายการรุ่นไม่สำเร็จ',confirmButtonColor:BRAND_GREEN});
    });
}

// เพิ่ม/ลบ ในม็อดัล + SweetAlert
document.getElementById('addCategoryForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    const name = document.getElementById('newCategoryName').value.trim(); if (!name) return;
    fetch('add_category.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'category_name='+encodeURIComponent(name)})
    .then(r=>r.json()).then(d=>{
        if (d.success){
            document.getElementById('newCategoryName').value='';
            loadCategories(); refreshCategorySelect();
            Swal.fire({icon:'success',title:'สำเร็จ',text:'เพิ่มหมวดหมู่สำเร็จ!',timer:1200,showConfirmButton:false});
        } else {
            Swal.fire({icon:'error',title:'ผิดพลาด',text:d.message || 'เพิ่มหมวดหมู่ไม่สำเร็จ',confirmButtonColor:BRAND_GREEN});
        }
    }).catch(_=>Swal.fire({icon:'error',title:'ผิดพลาด',text:'เพิ่มหมวดหมู่ไม่สำเร็จ',confirmButtonColor:BRAND_GREEN}));
});

document.getElementById('addBrandForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    const name = document.getElementById('newBrandName').value.trim(); if (!name) return;
    fetch('add_brand.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'brand_name='+encodeURIComponent(name)})
    .then(r=>r.json()).then(d=>{
        if (d.success){
            document.getElementById('newBrandName').value='';
            loadBrands(); loadBrandsForModel();
            Swal.fire({icon:'success',title:'สำเร็จ',text:'เพิ่มยี่ห้อสำเร็จ!',timer:1200,showConfirmButton:false});
        } else {
            Swal.fire({icon:'error',title:'ผิดพลาด',text:d.message || 'เพิ่มยี่ห้อไม่สำเร็จ',confirmButtonColor:BRAND_GREEN});
        }
    }).catch(_=>Swal.fire({icon:'error',title:'ผิดพลาด',text:'เพิ่มยี่ห้อไม่สำเร็จ',confirmButtonColor:BRAND_GREEN}));
});

document.getElementById('addModelForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    const mname = document.getElementById('newModelName').value.trim();
    const bid = document.getElementById('newModelBrand').value;
    if (!mname || !bid) return;
    fetch('add_model.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'model_name='+encodeURIComponent(mname)+'&brand_id='+bid})
    .then(r=>r.json()).then(d=>{
        if (d.success){
            document.getElementById('newModelName').value=''; document.getElementById('newModelBrand').value='';
            loadModels(); refreshModelSelect();
            Swal.fire({icon:'success',title:'สำเร็จ',text:'เพิ่มรุ่นสำเร็จ!',timer:1200,showConfirmButton:false});
        } else {
            Swal.fire({icon:'error',title:'ผิดพลาด',text:d.message || 'เพิ่มรุ่นไม่สำเร็จ',confirmButtonColor:BRAND_GREEN});
        }
    }).catch(_=>Swal.fire({icon:'error',title:'ผิดพลาด',text:'เพิ่มรุ่นไม่สำเร็จ',confirmButtonColor:BRAND_GREEN}));
});

function confirmDeleteSwal(text, onConfirm){
    Swal.fire({
        icon:'question', title:'ยืนยันการลบ?', text:text || 'เมื่อลบแล้วจะไม่สามารถกู้คืนได้',
        showCancelButton:true, confirmButtonText:'ลบ', cancelButtonText:'ยกเลิก',
        confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d', reverseButtons:true
    }).then(res=>{ if(res.isConfirmed) onConfirm && onConfirm(); });
}
function deleteCategory(id){
    confirmDeleteSwal('ต้องการลบหมวดหมู่นี้หรือไม่?', function(){
        fetch('delete_category.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'category_id='+id})
        .then(r=>r.json()).then(d=>{
            if (d.success){ loadCategories(); refreshCategorySelect(); Swal.fire({icon:'success',title:'ลบสำเร็จ',timer:1100,showConfirmButton:false}); }
            else Swal.fire({icon:'warning',title:'ลบไม่ได้',text:d.message||'หมวดหมู่นี้ถูกใช้งานอยู่',confirmButtonColor:BRAND_GREEN});
        }).catch(_=>Swal.fire({icon:'error',title:'ผิดพลาด',text:'ลบหมวดหมู่ไม่สำเร็จ',confirmButtonColor:BRAND_GREEN}));
    });
}
function deleteBrand(id){
    confirmDeleteSwal('ต้องการลบยี่ห้อนี้หรือไม่?', function(){
        fetch('delete_brand.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'brand_id='+id})
        .then(r=>r.json()).then(d=>{
            if (d.success){ loadBrands(); loadBrandsForModel(); Swal.fire({icon:'success',title:'ลบสำเร็จ',timer:1100,showConfirmButton:false}); }
            else Swal.fire({icon:'warning',title:'ลบไม่ได้',text:d.message||'ยี่ห้อนี้ถูกใช้งานอยู่',confirmButtonColor:BRAND_GREEN});
        }).catch(_=>Swal.fire({icon:'error',title:'ผิดพลาด',text:'ลบยี่ห้อไม่สำเร็จ',confirmButtonColor:BRAND_GREEN}));
    });
}
function deleteModel(id){
    confirmDeleteSwal('ต้องการลบรุ่นนี้หรือไม่?', function(){
        fetch('delete_model.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'model_id='+id})
        .then(r=>r.json()).then(d=>{
            if (d.success){ loadModels(); refreshModelSelect(); Swal.fire({icon:'success',title:'ลบสำเร็จ',timer:1100,showConfirmButton:false}); }
            else Swal.fire({icon:'warning',title:'ลบไม่ได้',text:d.message||'รุ่นนี้ถูกใช้งานอยู่',confirmButtonColor:BRAND_GREEN});
        }).catch(_=>Swal.fire({icon:'error',title:'ผิดพลาด',text:'ลบรุ่นไม่สำเร็จ',confirmButtonColor:BRAND_GREEN}));
    });
}
function refreshCategorySelect(){
    fetch('get_categories.php').then(r=>r.json()).then(data=>{
        const select = document.getElementById('category_id'); const currentValue = select.value;
        select.innerHTML = '<option value="">-- เลือกหมวดหมู่ --</option>';
        data.forEach(c=>{
            const op=document.createElement('option'); op.value=c.category_id; op.textContent=c.category_name;
            if (c.category_id == currentValue) op.selected = true;
            select.appendChild(op);
        });
    });
}
function refreshModelSelect(){
    fetch('get_models.php').then(r=>r.json()).then(data=>{
        const select = document.getElementById('model_id'); const currentValue = select.value;
        select.innerHTML = '<option value="">-- เลือกรุ่น --</option>';
        data.forEach(m=>{
            const op=document.createElement('option');
            op.value=m.model_id; op.setAttribute('data-brand', m.brand_name || '');
            op.textContent = m.model_name + (m.brand_name ? ' ('+m.brand_name+')' : '');
            if (m.model_id == currentValue) op.selected = true;
            select.appendChild(op);
        });
        updateBrand();
    });
}

/* ====== Camera Capture (Multi-shot) : ผลลัพธ์ 3024×4032 พิกเซล ====== */
let camStream = null;
let usingFacingMode = 'environment';
const TARGET_W = 3024, TARGET_H = 4032; // แนวตั้ง
const maxShots = 15;
const jpegQuality = 0.9;

const elsCam = { modal:null, video:null, shotList:null, takeBtn:null, attachBtn:null, switchBtn:null, fileInput:null, formPreview:null };
const shotBlobs = [];

function qs(id){ return document.getElementById(id); }

document.addEventListener('DOMContentLoaded', () => {
    elsCam.modal      = qs('cameraModal');
    elsCam.video      = qs('camStream');
    elsCam.shotList   = qs('shotList');
    elsCam.takeBtn    = qs('takePhotoBtn');
    elsCam.attachBtn  = qs('attachShotsBtn');
    elsCam.switchBtn  = qs('switchCameraBtn');
    elsCam.fileInput  = qs('images');
    elsCam.formPreview= qs('cameraPreviewList');

    elsCam.takeBtn.addEventListener('click', takePhoto);
    elsCam.attachBtn.addEventListener('click', attachShotsToForm);
    elsCam.switchBtn.addEventListener('click', switchCamera);
    elsCam.modal.addEventListener('hidden.bs.modal', stopCamera);

    elsCam.fileInput.addEventListener('change', renderFormCameraPreview);
});

function openCameraModal(){
    new bootstrap.Modal(qs('cameraModal')).show();
    startCameraStream(usingFacingMode);
}
async function startCameraStream(facingMode){
    try{
        stopCamera();
        camStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: facingMode },
                width:  { ideal: 4032, max: 4096 },
                height: { ideal: 3024, max: 4096 },
                frameRate: { ideal: 30 }
            },
            audio: false
        });
        elsCam.video.srcObject = camStream;
    }catch(err){
        Swal.fire({icon:'error',title:'เปิดกล้องไม่สำเร็จ',text:err.message,confirmButtonColor:BRAND_GREEN});
    }
}
function stopCamera(){ if (camStream){ camStream.getTracks().forEach(t=>t.stop()); camStream=null; } }
function switchCamera(){ usingFacingMode = (usingFacingMode === 'environment') ? 'user' : 'environment'; startCameraStream(usingFacingMode); }

function drawCoverToCanvas(video, targetW, targetH) {
    const vw = video.videoWidth  || 1280;
    const vh = video.videoHeight || 720;
    const rotate = vw > vh; // ส่วนใหญ่กล้องคืน landscape

    const canvas = document.createElement('canvas');
    canvas.width = targetW; canvas.height = targetH;
    const ctx = canvas.getContext('2d');

    ctx.save();
    if (rotate) {
        ctx.translate(targetW, 0);
        ctx.rotate(Math.PI / 2);

        const TW = targetH, TH = targetW; // กรอบหลังหมุน
        const scale = Math.max(TW / vw, TH / vh);
        const dw = vw * scale, dh = vh * scale;
        const dx = (TW - dw) / 2, dy = (TH - dh) / 2;
        ctx.drawImage(video, dx, dy, dw, dh);
    } else {
        const scale = Math.max(targetW / vw, targetH / vh);
        const dw = vw * scale, dh = vh * scale;
        const dx = (targetW - dw) / 2, dy = (targetH - dh) / 2;
        ctx.drawImage(video, dx, dy, dw, dh);
    }
    ctx.restore();

    return canvas;
}
async function takePhoto(){
    if (!camStream) return;
    if (shotBlobs.length >= maxShots)
        return Swal.fire({icon:'info',title:'จำกัดจำนวนภาพ',text:'ถ่ายได้ไม่เกิน '+maxShots+' ภาพต่อครั้ง',confirmButtonColor:BRAND_GREEN});

    try {
        const canvas = drawCoverToCanvas(elsCam.video, TARGET_W, TARGET_H);
        canvas.toBlob((blob) => {
            if (!blob) return;
            shotBlobs.push(blob);
            addShotThumb(blob);
        }, 'image/jpeg', jpegQuality);
    } catch (e) {
        console.error(e);
        Swal.fire({icon:'error',title:'จับภาพไม่สำเร็จ',text:e.message,confirmButtonColor:BRAND_GREEN});
    }
}
function addShotThumb(blob){
    const url = URL.createObjectURL(blob);
    const wrap = document.createElement('div');
    wrap.className = 'position-relative';
    wrap.style.width='140px'; wrap.style.height='100px';

    const img = document.createElement('img');
    img.src=url; img.alt='shot';
    img.style.width='100%'; img.style.height='100%'; img.style.objectFit='cover';
    img.className='rounded border';

    const del = document.createElement('button');
    del.type='button'; del.className='btn btn-sm btn-danger position-absolute';
    del.style.top='4px'; del.style.right='4px';
    del.innerHTML='<i class="fa-solid fa-xmark"></i>';
    del.addEventListener('click', ()=>{ const i=shotBlobs.indexOf(blob); if(i>-1) shotBlobs.splice(i,1); wrap.remove(); URL.revokeObjectURL(url); });

    wrap.appendChild(img); wrap.appendChild(del);
    elsCam.shotList.appendChild(wrap);
}
function attachShotsToForm(){
    if (shotBlobs.length === 0) return Swal.fire({icon:'info',title:'ยังไม่มีภาพที่ถ่าย',confirmButtonColor:BRAND_GREEN});
    const dt = new DataTransfer();
    if (elsCam.fileInput.files && elsCam.fileInput.files.length){
        Array.from(elsCam.fileInput.files).forEach(f => dt.items.add(f));
    }
    const ts = Date.now();
    shotBlobs.forEach((blob,i)=> dt.items.add(new File([blob], `camera_${ts}_${i+1}.jpg`, {type:'image/jpeg'})));
    elsCam.fileInput.files = dt.files;
    renderFormCameraPreview();
    shotBlobs.splice(0,shotBlobs.length);
    elsCam.shotList.innerHTML='';
    bootstrap.Modal.getInstance(elsCam.modal).hide();
}
function renderFormCameraPreview(){
    elsCam.formPreview.innerHTML = '';
    if (!elsCam.fileInput.files || !elsCam.fileInput.files.length) return;
    Array.from(elsCam.fileInput.files).forEach((file)=>{
        if (!file.type.startsWith('image/')) return;
        const url = URL.createObjectURL(file);
        const wrap = document.createElement('div');
        wrap.className='d-inline-block position-relative';
        wrap.style.width='110px'; wrap.style.height='90px';
        const img = document.createElement('img');
        img.src=url; img.alt=file.name;
        img.style.width='100%'; img.style.height='100%'; img.style.objectFit='cover';
        img.className='rounded border';
        wrap.appendChild(img);
        elsCam.formPreview.appendChild(wrap);
    });
}

// SweetAlert for PHP-side messages
(function(){
    const successMsg = <?php echo json_encode($swal_success, JSON_UNESCAPED_UNICODE); ?>;
    const errorMsg   = <?php echo json_encode($swal_error,   JSON_UNESCAPED_UNICODE); ?>;
    if (errorMsg) Swal.fire({icon:'warning',title:'ไม่สามารถดำเนินการได้',text:errorMsg,confirmButtonColor:BRAND_GREEN});
    if (successMsg) {
        Swal.fire({icon:'success',title:'สำเร็จ',text:successMsg,confirmButtonText:'ตกลง',confirmButtonColor:BRAND_GREEN})
        .then(()=>{ window.location.href='items.php'; });
    }
})();
</script>

<!-- ====== Item Number: ghost hint + history (localStorage) ====== -->
<script>
(function(){
  const input = document.getElementById('item_number');
  const hint  = document.getElementById('item_number_hint');
  const datalist = document.getElementById('item_number_list');

  if (!input || !hint || !datalist) return;

  const LS_KEY = 'item_number_history';
  const MAX_HISTORY = 100;

  function loadHistory() {
    try {
      const raw = localStorage.getItem(LS_KEY);
      const arr = raw ? JSON.parse(raw) : [];
      return Array.isArray(arr) ? arr : [];
    } catch { return []; }
  }
  function saveHistory(val){
    if (!val) return;
    const arr = loadHistory().filter(v => v !== val);
    arr.unshift(val);
    while (arr.length > MAX_HISTORY) arr.pop();
    localStorage.setItem(LS_KEY, JSON.stringify(arr));
  }
  function mergeSuggestions() {
    const history = loadHistory();
    const current = Array.from(datalist.options).map(op => op.value);
    const merged = [...history, ...current].filter((v,i,a)=>v && a.indexOf(v)===i).slice(0, 200);

    datalist.innerHTML = '';
    merged.forEach(v=>{
      const op = document.createElement('option');
      op.value = v;
      datalist.appendChild(op);
    });
  }

  function findStartsWith(prefix){
    if (!prefix) return '';
    prefix = prefix.toLowerCase();
    const opts = Array.from(datalist.options).map(op => op.value);
    for (let i=0;i<opts.length;i++){
      const v = (opts[i]||'')+'';
      if (v.toLowerCase().startsWith(prefix)) return v;
    }
    return '';
  }

  function updateGhost(){
    const v = input.value || '';
    const sug = findStartsWith(v);
    if (v && sug && sug.toLowerCase() !== v.toLowerCase()){
      hint.value = v + sug.slice(v.length);
    } else {
      hint.value = '';
    }
  }

  function acceptHintIfAny(e){
    const haveHint = hint.value && hint.value.toLowerCase().startsWith((input.value||'').toLowerCase());
    if (!haveHint) return;
    const keys = ['ArrowRight','End','Tab'];
    if (keys.includes(e.key)){
      input.value = hint.value;
      hint.value = '';
      if (e.key !== 'Tab') e.preventDefault();
      input.dispatchEvent(new Event('blur'));
    }
  }

  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', ()=>{
      const val = (input.value||'').trim();
      if (val) saveHistory(val);
    });
  }

  input.addEventListener('input', updateGhost);
  input.addEventListener('focus',  ()=>{ mergeSuggestions(); updateGhost(); });
  input.addEventListener('keyup',  acceptHintIfAny);
  input.addEventListener('change', updateGhost);

  mergeSuggestions();
  updateGhost();
})();
</script>
</body>
</html>
