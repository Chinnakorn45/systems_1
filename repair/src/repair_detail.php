<?php
session_start();
require_once 'db.php';

/* ---------- สร้างคอลัมน์ fix_image อัตโนมัติถ้ายังไม่มี ---------- */
function ensure_fix_image_column(mysqli $conn) {
    $col = $conn->query("SHOW COLUMNS FROM repairs LIKE 'fix_image'");
    if (!$col || $col->num_rows === 0) {
        @$conn->query("ALTER TABLE repairs ADD COLUMN fix_image VARCHAR(255) NULL AFTER image");
    }
}
ensure_fix_image_column($conn);

/* ---------------- Badge แสดงสถานะ ---------------- */
function status_badge($status) {
    $map = [
        'received'               => ['รับเรื่อง', 'info'],
        'evaluate_it'            => ['ประเมิน (โดย IT)', 'warning'],
        'evaluate_repairable'    => ['ประเมิน: ซ่อมได้โดย IT', 'success'],
        'in_progress'            => ['กำลังซ่อมโดย IT', 'primary'],
        'evaluate_external'      => ['ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'evaluate_disposal'      => ['ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย', 'dark'],
        'external_repair'        => ['ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'procurement_managing'   => ['พัสดุจัดการส่งซ่อม', 'info'],
        'procurement_returned'   => ['พัสดุซ่อมเสร็จส่งคืน IT', 'success'],
        'repair_completed'       => ['ซ่อมเสร็จ', 'success'],
        'waiting_delivery'       => ['รอส่งมอบ', 'warning'],
        'delivered'              => ['ส่งมอบ', 'success'],
        'cancelled'              => ['ยกเลิก', 'secondary'],
    ];
    if (!isset($map[$status])) return '';
    [$th, $color] = $map[$status];
    return "<span class='badge bg-$color'>$th</span>";
}

function thaidate($date, $format = 'd/m/Y H:i') {
    $ts = strtotime($date);
    if ($ts === false) return '-';
    $result = date($format, $ts);
    $year_th = date('Y', $ts) + 543;
    return str_replace(date('Y', $ts), $year_th, $result);
}

/* ---------------- สิทธิ์ & ลำดับสถานะ ---------------- */
function is_status_locked_for_it_admin($current_status) {
    return in_array($current_status, ['external_repair','procurement_managing']);
}
function get_past_statuses($current_status) {
    $order = ['received','evaluate_it','evaluate_repairable','in_progress','evaluate_external','evaluate_disposal','external_repair','procurement_managing','procurement_returned','repair_completed','waiting_delivery','delivered'];
    $i = array_search($current_status, $order);
    if ($i === false) return [];
    return array_slice($order, 0, $i);
}
function is_status_in_user_responsibility($role, $current_status) {
    if ($role === 'procurement') {
        return in_array($current_status, ['external_repair','procurement_managing']);
    }
    if (in_array($role, ['admin','it'])) {
        return $current_status === 'repair_completed' || !is_status_locked_for_it_admin($current_status);
    }
    return false;
}
function get_allowed_statuses($role, $current_status) {
    $all = ['received','evaluate_it','evaluate_repairable','in_progress','evaluate_external','evaluate_disposal','external_repair','procurement_managing','procurement_returned','repair_completed','waiting_delivery','delivered','cancelled'];

    if ($role === 'procurement') {
        if ($current_status === 'external_repair')      return ['external_repair','procurement_managing'];
        if ($current_status === 'procurement_managing') return ['procurement_managing','procurement_returned'];
        if ($current_status === 'procurement_returned') return ['procurement_returned'];
        return [];
    }

    if (in_array($role, ['admin','it'])) {
        if (is_status_locked_for_it_admin($current_status)) return [$current_status];
        if ($current_status === 'evaluate_external')    return ['evaluate_external','external_repair'];
        if ($current_status === 'procurement_returned') return ['procurement_returned','in_progress','repair_completed','waiting_delivery','delivered'];
        if ($current_status === 'evaluate_repairable')  return ['evaluate_repairable','in_progress','repair_completed'];
        if ($current_status === 'in_progress')          return ['in_progress','repair_completed'];
        if ($current_status === 'repair_completed')     return ['repair_completed','waiting_delivery','delivered'];
        if ($current_status === 'waiting_delivery')     return ['waiting_delivery','delivered'];

        $avail = array_diff($all, ['procurement_managing','procurement_returned']);
        $avail = array_diff($avail, get_past_statuses($current_status));
        array_unshift($avail, $current_status);
        return array_values(array_unique($avail));
    }
    return [];
}

/* ---------------- ตรวจสิทธิ์เข้าเพจ ---------------- */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['procurement','admin','it'])) {
    header('Location: login.php'); exit;
}
if (!isset($_GET['id'])) { echo 'ไม่พบข้อมูล'; exit; }
$id = intval($_GET['id']);
$user_role = $_SESSION['role'];

/* ---------------- หา “สถานะล่าสุด” ของใบงานนี้ ---------------- */
$row_cur = $conn->query("SELECT status FROM repair_logs WHERE repair_id=$id ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
$current_status = $row_cur['status'] ?? 'received';

$error = null;

/* ---------------- บันทึกอัปเดต (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_to_update = $_POST['status'];
    $fix_desc = $_POST['fix_description'];
    $img = '';
    if (!empty($_FILES['fix_image']['name'])) {
        // เก็บรูปหลังซ่อม
        $safeName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($_FILES['fix_image']['name']));
        $img = 'uploads/' . uniqid('fix_') . '_' . $safeName;
        @move_uploaded_file($_FILES['fix_image']['tmp_name'], $img);
    }

    $allowed = get_allowed_statuses($user_role, $current_status);

    // บันทึกสถานะซ้ำได้เฉพาะตอนอยู่ in_progress
    if ($status_to_update === $current_status && $current_status !== 'in_progress') {
        $error = "ไม่สามารถบันทึกสถานะซ้ำได้ สถานะปัจจุบันคือ " . status_badge($current_status);
    } elseif ($user_role === 'procurement') {
        if (!in_array($current_status, ['external_repair','procurement_managing','procurement_returned'])) {
            $error = "ฝ่ายพัสดุไม่สามารถดำเนินการกับสถานะนี้ได้";
        } elseif (!in_array($status_to_update, $allowed)) {
            $error = "ฝ่ายพัสดุไม่ได้รับอนุญาตให้เปลี่ยนเป็น '" . strip_tags(status_badge($status_to_update)) . "'";
        }
    } elseif (in_array($user_role, ['admin','it'])) {
        if (is_status_locked_for_it_admin($current_status)) {
            $error = "สถานะนี้ถูกล็อค ต้องให้ฝ่ายพัสดุจัดการ";
        } elseif (!in_array($status_to_update, $allowed)) {
            $error = "คุณไม่ได้รับอนุญาตให้เปลี่ยนเป็น '" . strip_tags(status_badge($status_to_update)) . "'";
        }
    } else {
        $error = "คุณไม่มีสิทธิ์ในการเปลี่ยนแปลงสถานะ";
    }

    if ($error === null) {
        // อัปเดต fix_image (ไม่ทับ image เดิม)
        $sql = "UPDATE repairs
                SET status=?, fix_description=?, fix_image=IF(?, ?, fix_image), assigned_to=?, updated_at=NOW()
                WHERE repair_id=?";
        $stmt = $conn->prepare($sql);
        $has_img = $img ? 1 : 0;
        $img_val = $img ?: '';
        $stmt->bind_param('ssisii', $status_to_update, $fix_desc, $has_img, $img_val, $_SESSION['user_id'], $id);
        $stmt->execute();

        // log
        $log_stmt = $conn->prepare("INSERT INTO repair_logs (repair_id, status, detail, updated_at, updated_by) VALUES (?, ?, ?, NOW(), ?)");
        $log_stmt->bind_param('isss', $id, $status_to_update, $fix_desc, $_SESSION['user_id']);
        $log_stmt->execute();

        header("Location: repair_detail.php?id=$id&status_updated=true");
        exit();
    }
}

/* ---------------- ดึงข้อมูลแสดงผล (อิงสถานะล่าสุด) ---------------- */
$sql = "SELECT r.*,
               COALESCE(latest.status, r.status) AS current_status,
               u_reported.full_name AS reported_by_name,
               u_assigned.full_name AS assigned_to_name
        FROM repairs r
        LEFT JOIN users u_reported ON r.reported_by = u_reported.user_id
        LEFT JOIN users u_assigned ON r.assigned_to = u_assigned.user_id
        LEFT JOIN (
            SELECT rl.repair_id, rl.status
            FROM repair_logs rl
            INNER JOIN (
                SELECT repair_id, MAX(updated_at) AS max_ts
                FROM repair_logs
                GROUP BY repair_id
            ) t ON rl.repair_id = t.repair_id AND rl.updated_at = t.max_ts
        ) latest ON latest.repair_id = r.repair_id
        WHERE r.repair_id = $id";
$repair = $conn->query($sql)->fetch_assoc();

/* ---------------- สำหรับแสดงฟอร์ม ---------------- */
$is_in_responsibility = is_status_in_user_responsibility($user_role, $current_status);
$allowed_statuses = get_allowed_statuses($user_role, $current_status);
$can_show_form = $is_in_responsibility && !empty($allowed_statuses);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดการแจ้งซ่อม</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
      .img-thumb-click { cursor: zoom-in; }
    </style>
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

            <?php if (!empty($repair['image'])): ?>
              <b>รูปก่อนซ่อม:</b>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($repair['image']) ?>"
                     width="120"
                     class="img-thumbnail img-thumb-click"
                     alt="รูปก่อนซ่อม"
                     onclick="previewImage('<?= htmlspecialchars($repair['image'], ENT_QUOTES, 'UTF-8') ?>')">
                <a href="<?= htmlspecialchars($repair['image']) ?>" target="_blank" rel="noopener" class="ms-2 small">เปิดต้นฉบับ</a>
                <a href="<?= htmlspecialchars($repair['image']) ?>" download class="ms-2 small">ดาวน์โหลด</a>
              </div>
            <?php endif; ?>

            <?php if (!empty($repair['fix_image'])): ?>
              <b class="mt-3 d-inline-block">รูปหลังซ่อม:</b>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($repair['fix_image']) ?>"
                     width="120"
                     class="img-thumbnail img-thumb-click"
                     alt="รูปหลังซ่อม"
                     onclick="previewImage('<?= htmlspecialchars($repair['fix_image'], ENT_QUOTES, 'UTF-8') ?>')">
                <a href="<?= htmlspecialchars($repair['fix_image']) ?>" target="_blank" rel="noopener" class="ms-2 small">เปิดต้นฉบับ</a>
                <a href="<?= htmlspecialchars($repair['fix_image']) ?>" download class="ms-2 small">ดาวน์โหลด</a>
              </div>
            <?php endif; ?>

            <br><b>สถานะปัจจุบัน:</b> <?= status_badge($repair['current_status']) ?><br>
        </div>
    </div>

    <div class="mb-4">
        <h5 class="mb-3">ไทม์ไลน์สถานะ</h5>
        <?php
        $logs = $conn->query("SELECT l.*, u.full_name
                              FROM repair_logs l
                              LEFT JOIN users u ON l.updated_by = u.user_id
                              WHERE l.repair_id = $id
                              ORDER BY l.updated_at ASC");
        while ($log = $logs->fetch_assoc()):
            $badge_info = status_badge($log['status']);
            preg_match('/bg-(.*?)"/', $badge_info, $mcolor);
            preg_match('/>(.*?)</',   $badge_info, $mtext);
            $status_th    = $mtext[1]  ?? $log['status'];
            $status_color = $mcolor[1] ?? 'secondary';
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
        <?php endwhile; ?>
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
                    <?php $brands = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
                    if ($brands) while($b = $brands->fetch_assoc()): ?>
                        <option value="<?= $b['brand_id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-2"><label class="form-label">หมายเลขครุภัณฑ์</label><input type="text" name="item_number" class="form-control"></div>
            <div class="mb-2"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-control"></div>
            <div class="mb-2">
                <label class="form-label">หมวดหมู่</label>
                <select name="category_id" class="form-select" required>
                    <?php $catres = $conn->query("SELECT * FROM categories");
                    while($cat = $catres->fetch_assoc()): ?>
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

    <?php if (!$can_show_form): ?>
        <div class="alert alert-warning mt-3">
            <i class="fas fa-lock me-2"></i><strong>ไม่สามารถเปลี่ยนสถานะได้</strong><br>
            <?php
            if ($user_role === 'procurement') {
                echo $is_in_responsibility
                    ? "ฝ่ายพัสดุสามารถจัดการสถานะนี้ได้ กรุณาเลือกสถานะที่ต้องการ"
                    : "ฝ่ายพัสดุไม่สามารถดำเนินการกับสถานะนี้ได้ เนื่องจากไม่ใช่สถานะในความรับผิดชอบของคุณ";
            } elseif (in_array($user_role, ['admin','it'])) {
                echo $is_in_responsibility
                    ? "คุณสามารถจัดการสถานะนี้ได้ กรุณาเลือกสถานะที่ต้องการ"
                    : "สถานะปัจจุบัน ".status_badge($current_status)." ถูกล็อคไว้ ต้องให้ฝ่ายพัสดุจัดการเท่านั้น";
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
                    <?php foreach ($allowed_statuses as $s):
                        $badge_info = status_badge($s);
                        preg_match('/>(.*?)</', $badge_info, $m);
                        $label_th = $m[1] ?? $s;
                    ?>
                        <option value="<?= $s ?>" <?= $current_status==$s?'selected':'' ?>><?= $label_th ?></option>
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
                <div class="form-text">คลิกที่รูปเพื่อดูขนาดใหญ่ หรือกด “เปิดต้นฉบับ/ดาวน์โหลด” หลังบันทึก</div>
            </div>
            <button type="submit" class="btn btn-primary">บันทึกการอัปเดต</button>
        </form>
    <?php endif; ?>
</div>

<!-- Modal แสดงรูปขนาดใหญ่ -->
<div class="modal fade" id="imgPreviewModal" tabindex="-1" aria-labelledby="imgPreviewLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0">
        <h6 class="modal-title text-white" id="imgPreviewLabel">แสดงรูปภาพ</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body p-0 text-center">
        <img id="imgPreview" src="" alt="preview" class="img-fluid rounded">
      </div>
      <div class="modal-footer border-0">
        <a id="imgOpenNew" href="#" target="_blank" rel="noopener" class="btn btn-outline-light">เปิดต้นฉบับ</a>
        <a id="imgDownload" href="#" download class="btn btn-light">ดาวน์โหลด</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS (ต้องมีสำหรับ Modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// เปิดดูรูปใน Modal
function previewImage(src){
  const img = document.getElementById('imgPreview');
  const openBtn = document.getElementById('imgOpenNew');
  const dlBtn = document.getElementById('imgDownload');
  img.src = src;
  openBtn.href = src;
  dlBtn.href = src;
  new bootstrap.Modal(document.getElementById('imgPreviewModal')).show();
}
</script>
</body>
</html>
