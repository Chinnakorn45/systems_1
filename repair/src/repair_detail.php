<?php
session_start();
require_once 'db.php';

// ฟังก์ชันสำหรับแสดง Badge สถานะ
function status_badge($status) {
    $map = [
        'received' => ['รับเรื่อง', 'info'],
        'evaluate_it' => ['ประเมิน (โดย IT)', 'warning'],
        'evaluate_repairable' => ['ประเมิน: ซ่อมได้โดย IT', 'success'],
        'evaluate_external' => ['ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'evaluate_disposal' => ['ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย', 'dark'],
        'external_repair' => ['ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'procurement_managing' => ['พัสดุจัดการส่งซ่อม', 'info'],
        'procurement_returned' => ['พัสดุซ่อมเสร็จส่งคืน IT', 'success'],
        'repair_completed' => ['ซ่อมเสร็จ', 'success'],
        'waiting_delivery' => ['รอส่งมอบ', 'warning'],
        'delivered' => ['ส่งมอบ', 'success'],
        'cancelled' => ['ยกเลิก', 'secondary'],
    ];
    if (!isset($map[$status])) return '';
    [$th, $color] = $map[$status];
    return "<span class='badge bg-$color'>$th</span>";
}

// ฟังก์ชันสำหรับแปลงวันที่เป็นภาษาไทย
function thaidate($date, $format = 'd/m/Y H:i') {
    $ts = strtotime($date);
    $result = date($format, $ts);
    $year_th = date('Y', $ts) + 543;
    return str_replace(date('Y', $ts), $year_th, $result);
}

// ฟังก์ชันตรวจสอบสถานะที่ล็อคสำหรับ IT/Admin (คือสถานะที่ Procurement เป็นคนจัดการ)
function is_status_locked_for_it_admin($current_status) {
    $locked_statuses = [
        'external_repair',          // IT ส่งให้พัสดุจัดการแล้ว
        'procurement_managing',     // พัสดุกำลังจัดการ
        // ลบ 'procurement_returned' ออก เพราะเมื่อพัสดุส่งคืนแล้ว IT/Admin ควรสามารถดำเนินการต่อได้
    ];
    return in_array($current_status, $locked_statuses);
}

// ฟังก์ชันสำหรับหาสถานะที่ผ่านมาแล้ว (ไม่สามารถย้อนกลับได้)
function get_past_statuses($current_status) {
    $status_order = [
        'received', 'evaluate_it', 'evaluate_repairable',
        'evaluate_external', 'evaluate_disposal', 'external_repair',
        'procurement_managing', 'procurement_returned', 'repair_completed',
        'waiting_delivery', 'delivered'
    ];
    
    $current_index = array_search($current_status, $status_order);
    if ($current_index === false) return [];
    
    // สถานะที่ผ่านมาแล้ว (ก่อนหน้าสถานะปัจจุบัน)
    return array_slice($status_order, 0, $current_index);
}

// ฟังก์ชันตรวจสอบว่าสถานะปัจจุบันอยู่ในช่วงที่ผู้ใช้สามารถจัดการได้หรือไม่
function is_status_in_user_responsibility($user_role, $current_status) {
    switch ($user_role) {
        case 'procurement':
            // พัสดุจัดการได้เฉพาะสถานะที่อยู่ในความรับผิดชอบ
            // และต้องเป็นสถานะที่พัสดุสามารถดำเนินการต่อได้
            if ($current_status === 'external_repair') {
                return true; // พัสดุสามารถเปลี่ยนเป็น procurement_managing ได้
            } elseif ($current_status === 'procurement_managing') {
                return true; // พัสดุสามารถเปลี่ยนเป็น procurement_returned ได้
            } elseif ($current_status === 'procurement_returned') {
                return false; // ขั้นตอนสุดท้ายของพัสดุ - โดนบล็อค
            }
            return false; // สถานะอื่นๆ พัสดุไม่สามารถจัดการได้
            
        case 'admin':
        case 'it':
            // Admin/IT จัดการได้ทุกสถานะ ยกเว้นที่ถูกล็อค
            // และสามารถจัดการสถานะ repair_completed ได้
            if ($current_status === 'repair_completed') {
                return true;
            }
            return !is_status_locked_for_it_admin($current_status);
            
        default:
            return false;
    }
}

// ฟังก์ชันกำหนดสถานะที่แต่ละ Role สามารถเลือกได้
function get_allowed_statuses($user_role, $current_status) {
    $all_statuses = [ // All possible statuses in the system
        'received', 'evaluate_it', 'evaluate_repairable',
        'evaluate_external', 'evaluate_disposal', 'external_repair',
        'procurement_managing', 'procurement_returned', 'repair_completed',
        'waiting_delivery', 'delivered', 'cancelled'
    ];

    switch ($user_role) {
        case 'procurement':
            // ฝ่ายพัสดุสามารถจัดการสถานะที่อยู่ในความรับผิดชอบ
            if ($current_status === 'external_repair') {
                return ['external_repair', 'procurement_managing'];
            } elseif ($current_status === 'procurement_managing') {
                return ['procurement_managing', 'procurement_returned'];
            } elseif ($current_status === 'procurement_returned') {
                return ['procurement_returned']; // ขั้นตอนสุดท้ายของพัสดุ
            }
            // สถานะอื่นๆ ที่ไม่ใช่ความรับผิดชอบของพัสดุ
            return [];
            
        case 'admin':
        case 'it':
            // ถ้าสถานะปัจจุบันถูกล็อค (ฝ่ายพัสดุจัดการ)
            if (is_status_locked_for_it_admin($current_status)) {
                return [$current_status]; // เห็นสถานะปัจจุบันเท่านั้น
            }
            
            // หาสถานะที่ผ่านมาแล้ว
            $past_statuses = get_past_statuses($current_status);
            
            // ถ้าสถานะไม่ล็อค สามารถเลือกสถานะที่เกี่ยวข้องได้
            // แต่ต้องให้แอดมิน/IT สามารถเปลี่ยนจาก evaluate_external เป็น external_repair ได้
            if ($current_status === 'evaluate_external') {
                return ['evaluate_external', 'external_repair'];
            }
            // เมื่อสถานะเป็น procurement_returned ให้ Admin/IT สามารถดำเนินการต่อได้
            if ($current_status === 'procurement_returned') {
                return ['repair_completed', 'waiting_delivery', 'delivered']; // เพิ่ม repair_completed
            }
            // เมื่อสถานะเป็น evaluate_repairable ให้ Admin/IT สามารถเปลี่ยนเป็น repair_completed ได้
            if ($current_status === 'evaluate_repairable') {
                return ['evaluate_repairable', 'repair_completed'];
            }
            // เมื่อสถานะเป็น repair_completed ให้ Admin/IT สามารถเปลี่ยนเป็น waiting_delivery หรือ delivered ได้
            if ($current_status === 'repair_completed') {
                return ['repair_completed', 'waiting_delivery', 'delivered'];
            }
            // เมื่อสถานะเป็น waiting_delivery ให้ Admin/IT สามารถเปลี่ยนเป็น delivered ได้
            if ($current_status === 'waiting_delivery') {
                return ['waiting_delivery', 'delivered'];
            }
            
            // สำหรับสถานะอื่นๆ ให้เลือกได้เฉพาะสถานะปัจจุบันและสถานะที่อยู่ข้างหน้า
            $available_statuses = array_diff($all_statuses, ['procurement_managing', 'procurement_returned']);
            $available_statuses = array_diff($available_statuses, $past_statuses);
            return array_values($available_statuses);
            
        default:
            return []; // Role อื่นๆ ไม่มีสิทธิ์เปลี่ยน
    }
}


// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['procurement', 'admin', 'it'])) {
    header('Location: login.php'); exit;
}

if (!isset($_GET['id'])) { echo 'ไม่พบข้อมูล'; exit; }
$id = intval($_GET['id']);

// ดึงข้อมูลรายการซ่อมก่อนการประมวลผล POST เพื่อให้ได้สถานะปัจจุบันที่ถูกต้อง
$sql_current_repair = "SELECT status FROM repairs WHERE repair_id = $id";
$current_repair_data = $conn->query($sql_current_repair)->fetch_assoc();
$current_status = $current_repair_data['status'];
$user_role = $_SESSION['role'];

// ดึงสถานะปัจจุบันจาก repair_logs (ล่าสุด)
$current_status_sql = "SELECT status FROM repair_logs WHERE repair_id = $id ORDER BY updated_at DESC LIMIT 1";
$current_status_result = $conn->query($current_status_sql);
$current_status_data = $current_status_result->fetch_assoc();
$current_status = $current_status_data['status'] ?? 'reported'; // ใช้ 'reported' เป็นค่าเริ่มต้น

// ส่วนจัดการการอัปเดตสถานะ (เมื่อมีการ POST form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_to_update = $_POST['status'];
    $fix_desc = $_POST['fix_description'];
    $img = '';
    if (!empty($_FILES['fix_image']['name'])) {
        $img = 'uploads/' . uniqid() . '_' . basename($_FILES['fix_image']['name']);
        move_uploaded_file($_FILES['fix_image']['tmp_name'], $img);
    }
    
    // ดึงสถานะที่ผู้ใช้ Role ปัจจุบันได้รับอนุญาตให้เปลี่ยนได้
    $allowed_to_change_to = get_allowed_statuses($user_role, $current_status);

    $error = null; // Reset error message

    // ตรวจสอบว่าสถานะที่จะบันทึกไม่ใช่สถานะปัจจุบัน (ป้องกันการบันทึกซ้ำ)
    if ($status_to_update === $current_status) {
        $error = "ไม่สามารถบันทึกสถานะซ้ำได้ สถานะปัจจุบันคือ " . status_badge($current_status);
    }
    // ตรวจสอบเงื่อนไขการล็อกและสิทธิ์การเปลี่ยนสถานะ
    elseif ($user_role === 'procurement') {
        // Procurement สามารถเปลี่ยนสถานะได้เฉพาะเมื่อสถานะปัจจุบันอยู่ในช่วงที่พัสดุจัดการ
        if (!in_array($current_status, ['external_repair', 'procurement_managing', 'procurement_returned'])) {
             $error = "ฝ่ายพัสดุไม่สามารถดำเนินการกับสถานะนี้ได้ เนื่องจากไม่ใช่สถานะในความรับผิดชอบ";
        } elseif (!in_array($status_to_update, $allowed_to_change_to)) {
             $error = "ฝ่ายพัสดุไม่ได้รับอนุญาตให้เปลี่ยนสถานะเป็น '" . status_badge($status_to_update) . "'.";
        }
    } elseif (in_array($user_role, ['admin', 'it'])) {
        // Admin/IT ถูกล็อคหากสถานะปัจจุบันเป็น 'evaluate_external', 'procurement_managing', 'procurement_returned'
        if (is_status_locked_for_it_admin($current_status)) {
            $error = "สถานะนี้ถูกล็อคไว้ ต้องให้ฝ่ายพัสดุจัดการเท่านั้น หรือรอการดำเนินการจากฝ่ายพัสดุ";
        } elseif (!in_array($status_to_update, $allowed_to_change_to)) {
            $error = "คุณไม่ได้รับอนุญาตให้เปลี่ยนสถานะเป็น '" . status_badge($status_to_update) . "'.";
        }
    } else {
        // Role อื่นๆ ไม่มีสิทธิ์เปลี่ยน
        $error = "คุณไม่มีสิทธิ์ในการเปลี่ยนแปลงสถานะ";
    }

    if ($error === null) { // ถ้าไม่มี error ก็ทำการอัปเดต
        $sql = "UPDATE repairs SET status=?, fix_description=?, image=IF(?, ?, image), assigned_to=?, updated_at=NOW() WHERE repair_id=?";
        $stmt = $conn->prepare($sql);
        $has_img = $img ? 1 : 0;
        $img_val = $img ?: '';
        $stmt->bind_param('sssiii', $status_to_update, $fix_desc, $has_img, $img_val, $_SESSION['user_id'], $id);
        $stmt->execute();

        // เพิ่ม log ลง repair_logs เพื่อบันทึกประวัติการเปลี่ยนแปลงสถานะ
        $log_stmt = $conn->prepare("INSERT INTO repair_logs (repair_id, status, detail, updated_at, updated_by) VALUES (?, ?, ?, NOW(), ?)");
        $log_stmt->bind_param('isss', $id, $status_to_update, $fix_desc, $_SESSION['user_id']);
        $log_stmt->execute();

        // หลังจากอัปเดตข้อมูลสำเร็จ ให้ Redirect ไปยังหน้าเดิมด้วยเมธอด GET
        header("Location: repair_detail.php?id=$id&status_updated=true");
        exit();
    }
}

// ดึงข้อมูลรายการซ่อม (อีกครั้งหลังจาก POST เพื่อแสดงข้อมูลล่าสุด)
$sql = "SELECT r.*, u_reported.full_name AS reported_by_name, u_assigned.full_name AS assigned_to_name FROM repairs r
LEFT JOIN users u_reported ON r.reported_by = u_reported.user_id
LEFT JOIN users u_assigned ON r.assigned_to = u_assigned.user_id
WHERE r.repair_id = $id";
$repair = $conn->query($sql)->fetch_assoc();


// ส่วนจัดการการเพิ่มครุภัณฑ์ใหม่ (ถ้ายังไม่ได้เชื่อมโยง)
if (isset($_POST['add_item'])) {
    $model_name = $_POST['model_name'];
    $brand_id = $_POST['brand_id'];
    $item_number = $_POST['item_number'];
    $serial_number = $_POST['serial_number'];
    $category_id = $_POST['category_id'];
    $location = $_POST['location'];
    $desc = $_POST['item_description'];
    $stmt = $conn->prepare("INSERT INTO items (model_name, brand_id, item_number, serial_number, category_id, location, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param('sississ', $model_name, $brand_id, $item_number, $serial_number, $category_id, $location, $desc);
    $stmt->execute();
    $new_item_id = $conn->insert_id;
    // update repair to link new item
    $conn->query("UPDATE repairs SET item_id=$new_item_id WHERE repair_id=$id");
    header("Location: repair_detail.php?id=$id");
    exit;
}

// ดึงข้อมูลหมวดหมู่และยี่ห้อสำหรับฟอร์มเพิ่มครุภัณฑ์
$catres = $conn->query("SELECT * FROM categories");
$brands = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดการแจ้งซ่อม</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
    <h3>รายละเอียดการแจ้งซ่อม</h3>
    <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] === 'true'): ?>
        <div class="alert alert-success">อัปเดตข้อมูลสำเร็จ</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <div class="card mb-3">
        <div class="card-body">
            <b>เลขครุภัณฑ์:</b> <?= htmlspecialchars($repair['asset_number']) ?><br>
            <b>Serial Number:</b> <?= htmlspecialchars($repair['serial_number']) ?><br>
            <b>ยี่ห้อ:</b> <?= htmlspecialchars($repair['brand']) ?><br>
            <b>รุ่น:</b> <?= htmlspecialchars($repair['model_name']) ?><br>
            <b>ตำแหน่ง:</b> <?= htmlspecialchars($repair['location_name']) ?><br>
            <b>ผู้แจ้ง:</b> <?= htmlspecialchars($repair['reported_by_name']) ?><br>
            <b>ผู้รับผิดชอบ:</b> <?= htmlspecialchars($repair['assigned_to_name'] ?? '-') ?><br>
            <b>วันที่แจ้ง:</b> <?= thaidate($repair['created_at']) ?><br>
            <b>รายละเอียดปัญหา:</b> <?= htmlspecialchars($repair['issue_description']) ?><br>
            <?php if ($repair['image']): ?>
                <b>รูปก่อนซ่อม:</b> <img src="<?= $repair['image'] ?>" width="120" class="img-thumbnail mt-2">
            <?php endif; ?>
            <br><b>สถานะปัจจุบัน:</b> <?= status_badge($repair['status']) ?><br>
        </div>
    </div>

    <div class="mb-4">
        <h5 class="mb-3">ไทม์ไลน์สถานะ</h5>
        <?php
        $logs = $conn->query("SELECT l.*, u.full_name FROM repair_logs l LEFT JOIN users u ON l.updated_by = u.user_id WHERE l.repair_id = $id ORDER BY l.updated_at ASC");
        while ($log = $logs->fetch_assoc()) {
            $badge_info = status_badge($log['status']);
            preg_match('/bg-(.*?)"/', $badge_info, $matches_color);
            preg_match('/>(.*?)</', $badge_info, $matches_th);
            $status_th = $matches_th[1] ?? $log['status'];
            $status_color = $matches_color[1] ?? 'secondary';
        ?>
        <div class="mb-2 p-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-<?= $status_color ?>"><?= $status_th ?></span>
                <small class="text-muted"><?= thaidate($log['updated_at']) ?></small>
            </div>
            <?php if (!empty($log['detail'])): ?>
                <p class="small text-muted mb-1"><?= htmlspecialchars($log['detail']) ?></p>
            <?php endif; ?>
            <div class="small text-muted">โดย <?= htmlspecialchars($log['full_name'] ?? '-') ?></div>
        </div>
        <?php } ?>
    </div>

    <?php if (!$repair['item_id']): ?>
    <div class="alert alert-warning">
        <b>แจ้งซ่อมนี้ยังไม่ได้เชื่อมโยงกับครุภัณฑ์ในระบบ</b><br>
        <button class="btn btn-success mt-2" type="button" onclick="document.getElementById('add-item-form').style.display='block'">เพิ่มเข้าคลังครุภัณฑ์</button>
        <form method="post" id="add-item-form" style="display:none;" class="mt-3 border p-3 bg-light rounded">
            <h6 class="mb-3">เพิ่มครุภัณฑ์ใหม่</h6>
            <div class="mb-2"><label class="form-label">ชื่อครุภัณฑ์</label><input type="text" name="model_name" class="form-control" required></div>
            <div class="mb-2">
                <label class="form-label">ยี่ห้อ</label>
                <select name="brand_id" class="form-select" required>
                    <option value="">-- เลือกยี่ห้อ --</option>
                    <?php if ($brands) while($b = $brands->fetch_assoc()): ?>
                        <option value="<?= $b['brand_id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-2"><label class="form-label">หมายเลขครุภัณฑ์</label><input type="text" name="item_number" class="form-control"></div>
            <div class="mb-2"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-control"></div>
            <div class="mb-2">
                <label class="form-label">หมวดหมู่</label>
                <select name="category_id" class="form-select" required>
                    <?php while($cat = $catres->fetch_assoc()): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-2"><label class="form-label">สถานที่ตั้ง</label><input type="text" name="location" class="form-control"></div>
            <div class="mb-2"><label class="form-label">รายละเอียด</label><input type="text" name="item_description" class="form-control"></div>
            <button type="submit" name="add_item" class="btn btn-primary">บันทึกและเชื่อมโยง</button>
        </form>
    </div>
    <?php endif; ?>
    
         <?php 
     // ตรวจสอบว่าสถานะปัจจุบันอยู่ในช่วงที่ผู้ใช้สามารถจัดการได้หรือไม่
     $is_in_responsibility = is_status_in_user_responsibility($user_role, $current_status);
     
     // กำหนดเงื่อนไขในการแสดงฟอร์มอัปเดตสถานะ
     $allowed_statuses = get_allowed_statuses($user_role, $current_status);
     $can_show_form = $is_in_responsibility && !empty($allowed_statuses);
     
     // ถ้าไม่สามารถแสดงฟอร์มได้ ให้แสดงข้อความแจ้งเตือน
     if (!$can_show_form): 
     ?>
     <div class="alert alert-warning mt-3">
         <i class="fas fa-lock me-2"></i>
         <strong>ไม่สามารถเปลี่ยนสถานะได้</strong><br>
         <?php 
         // แสดงข้อความที่เหมาะสมตามบทบาทและสถานะ
         if ($user_role === 'procurement') {
             if ($is_in_responsibility) {
                 echo "ฝ่ายพัสดุสามารถจัดการสถานะนี้ได้ กรุณาเลือกสถานะที่ต้องการ";
             } else {
                 echo "ฝ่ายพัสดุไม่สามารถดำเนินการกับสถานะนี้ได้ เนื่องจากไม่ใช่สถานะในความรับผิดชอบของคุณ";
             }
         } elseif (in_array($user_role, ['admin', 'it'])) {
             if ($is_in_responsibility) {
                 echo "คุณสามารถจัดการสถานะนี้ได้ กรุณาเลือกสถานะที่ต้องการ";
             } else {
                 echo "สถานะปัจจุบัน " . status_badge($current_status) . " ถูกล็อคไว้ ต้องให้ฝ่ายพัสดุจัดการเท่านั้น";
             }
         } else {
             echo "คุณไม่มีสิทธิ์ในการเปลี่ยนแปลงสถานะ";
         }
         ?>
     </div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data" class="mt-3 p-3 border rounded bg-light">
        <h5 class="mb-3">อัปเดตสถานะและข้อมูลการซ่อม</h5>
        <div class="mb-3">
            <label class="form-label">เลือกสถานะ</label>
            <select name="status" class="form-select" required>
                                 <?php 
                 foreach ($allowed_statuses as $s): 
                     $badge_info = status_badge($s);
                     preg_match('/>(.*?)</', $badge_info, $matches_th);
                     $status_th_option = $matches_th[1] ?? $s;
                 ?>
                     <option value="<?= $s ?>" <?= $current_status==$s?'selected':'' ?>>
                         <?= $status_th_option ?>
                     </option>
                 <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">รายละเอียดการซ่อม/การดำเนินการ</label>
            <textarea name="fix_description" class="form-control" rows="3"><?= htmlspecialchars($repair['fix_description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">แนบรูปหลังซ่อม (ถ้ามี)</label>
            <input type="file" name="fix_image" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">บันทึกการอัปเดต</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>