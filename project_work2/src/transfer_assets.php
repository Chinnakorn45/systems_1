<?php
// transfer_assets.php — full_name ทุกจุด + แก้ collation + ตารางเลื่อน 80vh + ค้นหา (Select2) + SweetAlert2
require_once 'config.php';
if (!isset($_SESSION)) session_start();

/* Charset/Collation */
mysqli_set_charset($link, 'utf8mb4');
mysqli_query($link, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($link, "SET collation_connection = 'utf8mb4_unicode_ci'");

/* Auth */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php'); exit;
}

/* POST: โอนสิทธิ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id      = (int)($_POST['item_id'] ?? 0);
    $new_owner_id = (int)($_POST['new_owner_id'] ?? 0);
    $remarks      = trim($_POST['remarks'] ?? '');

    if ($item_id <= 0 || $new_owner_id <= 0) {
        $_SESSION['error_message'] = 'กรุณาเลือกครุภัณฑ์และผู้ถือสิทธิใหม่ให้ครบถ้วน';
        header('Location: transfer_assets.php'); exit;
    }

    // ตรวจสอบบทบาทของผู้รับสิทธิ
    $new_role = null;
    if ($stmtRole = mysqli_prepare($link, "SELECT role FROM users WHERE user_id = ?")) {
        mysqli_stmt_bind_param($stmtRole, "i", $new_owner_id);
        mysqli_stmt_execute($stmtRole);
        mysqli_stmt_bind_result($stmtRole, $new_role);
        mysqli_stmt_fetch($stmtRole);
        mysqli_stmt_close($stmtRole);
    }

    if (!in_array($new_role, ['staff','procurement','admin'], true)) {
        $_SESSION['error_message'] = 'อนุญาตให้โอนสิทธิให้เฉพาะผู้ใช้ที่มีบทบาทที่กำหนด';
        header('Location: transfer_assets.php'); exit;
    }

    mysqli_begin_transaction($link);
    try {
        // ดึงเจ้าของเดิมและล็อกแถว
        $old_owner_id = null;
        if ($stmtOld = mysqli_prepare($link, "SELECT owner_user_id FROM items WHERE item_id = ? FOR UPDATE")) {
            mysqli_stmt_bind_param($stmtOld, "i", $item_id);
            mysqli_stmt_execute($stmtOld);
            mysqli_stmt_bind_result($stmtOld, $old_owner_id);
            mysqli_stmt_fetch($stmtOld);
            mysqli_stmt_close($stmtOld);
        }

        $old_owner_int = is_null($old_owner_id) ? null : (int)$old_owner_id;
        if ($old_owner_int === $new_owner_id) {
            throw new Exception('ผู้ถือสิทธิใหม่ซ้ำกับเจ้าของเดิม');
        }

        // อัปเดตเจ้าของ
        if ($stmtUpd = mysqli_prepare($link, "UPDATE items SET owner_user_id = ? WHERE item_id = ?")) {
            mysqli_stmt_bind_param($stmtUpd, "ii", $new_owner_id, $item_id);
            mysqli_stmt_execute($stmtUpd);
            mysqli_stmt_close($stmtUpd);
        }

        // บันทึกประวัติ
        if ($stmtHis = mysqli_prepare($link, "INSERT INTO equipment_history
            (item_id, action_type, old_value, new_value, changed_by, remarks)
            VALUES (?, 'transfer_ownership', ?, ?, ?, ?)")) {
            $old_str    = is_null($old_owner_int) ? null : (string)$old_owner_int;
            $new_str    = (string)$new_owner_id;
            $changed_by = (int)$_SESSION['user_id'];
            mysqli_stmt_bind_param($stmtHis, "issis", $item_id, $old_str, $new_str, $changed_by, $remarks);
            mysqli_stmt_execute($stmtHis);
            mysqli_stmt_close($stmtHis);
        }

        mysqli_commit($link);
        $_SESSION['success_message'] = 'โอนสิทธิครุภัณฑ์เรียบร้อยแล้ว';
    } catch (Throwable $e) {
        mysqli_rollback($link);
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาด: '.$e->getMessage();
    }
    header('Location: transfer_assets.php'); exit;
}

/* Data for form */
$sqlItems = "
    SELECT i.item_id, i.item_number, i.model_name, i.brand,
           i.owner_user_id,
           u.full_name AS owner_name, u.department AS owner_dept
    FROM items i
    LEFT JOIN users u ON u.user_id = i.owner_user_id
    WHERE i.item_number IS NOT NULL AND i.item_number <> ''
    ORDER BY i.item_number
";
$rsItems = mysqli_query($link, $sqlItems);

$sqlUsers = "
    SELECT user_id, full_name, department
    FROM users
    WHERE role IN ('staff','procurement','admin')
    ORDER BY (full_name IS NULL) ASC, (full_name='') ASC, full_name ASC, user_id ASC
";
$rsUsers = mysqli_query($link, $sqlUsers);

/* History (แสดงชื่อเดิม/ใหม่ด้วย full_name ทั้งกรณีเก็บเป็น id หรือ string) */
$sqlHis = "
    SELECT
      h.history_id, h.item_id, h.action_type, h.old_value, h.new_value,
      h.changed_by, h.change_date, h.remarks,
      i.item_number, i.model_name,
      COALESCE(uo_id.full_name, uo_name.full_name) AS old_owner,
      COALESCE(un_id.full_name, un_name.full_name) AS new_owner,
      uc.full_name AS changed_by_name
    FROM equipment_history h
    LEFT JOIN items i ON i.item_id = h.item_id
    LEFT JOIN users uo_id
           ON (h.old_value REGEXP '^[0-9]+$' AND uo_id.user_id = CAST(h.old_value AS UNSIGNED))
    LEFT JOIN users uo_name
           ON (uo_name.full_name COLLATE utf8mb4_unicode_ci = h.old_value COLLATE utf8mb4_unicode_ci)
    LEFT JOIN users un_id
           ON (h.new_value REGEXP '^[0-9]+$' AND un_id.user_id = CAST(h.new_value AS UNSIGNED))
    LEFT JOIN users un_name
           ON (un_name.full_name COLLATE utf8mb4_unicode_ci = h.new_value COLLATE utf8mb4_unicode_ci)
    LEFT JOIN users uc ON uc.user_id = h.changed_by
    WHERE h.action_type = 'transfer_ownership'
    ORDER BY h.change_date DESC, h.history_id DESC
    LIMIT 100
";
$rsHis = mysqli_query($link, $sqlHis);

/* Flash (สำหรับ SweetAlert2) */
$flash_success = $_SESSION['success_message'] ?? null;
$flash_error   = $_SESSION['error_message']   ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>โอนสิทธิครุภัณฑ์ - ระบบบันทึกคลังครุภัณฑ์</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="common-ui.css">
  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
      height: calc(2.5rem); padding: .375rem .5rem; border: 1px solid #ced4da; border-radius: .375rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 1.6; padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 100%;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">โอนสิทธิครุภัณฑ์</span>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'sidebar.php'; ?>

    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content mt-4 mt-md-5">

        <!-- ฟอร์มโอนสิทธิ -->
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <form id="transferForm" method="post" class="row g-3" novalidate>
              <div class="col-md-6">
                <label class="form-label">เลือกรายการครุภัณฑ์</label>
                <select name="item_id" id="item_id" class="form-select" required>
                  <option value="">— เลือก —</option>
                  <?php if ($rsItems): while ($it = mysqli_fetch_assoc($rsItems)): ?>
                    <?php
                      $label = $it['item_number'];
                      if (!empty($it['model_name'])) $label .= " - ".$it['model_name'];
                      if (!empty($it['brand'])) $label .= " (".$it['brand'].")";
                      $ownerLabel = trim((string)$it['owner_name']);
                      if ($ownerLabel === '') $ownerLabel = "ยังไม่มีผู้ถือสิทธิ";
                    ?>
                    <option
                      value="<?= (int)$it['item_id'] ?>"
                      data-owner-id="<?= (int)$it['owner_user_id'] ?>"
                      data-owner-name="<?= htmlspecialchars($ownerLabel, ENT_QUOTES) ?>"
                    ><?= htmlspecialchars($label) ?></option>
                  <?php endwhile; endif; ?>
                </select>
                <div class="form-text">
                  เจ้าของปัจจุบัน: <span id="currentOwnerText" class="fw-semibold text-primary">—</span>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">ผู้ถือสิทธิใหม่</label>
                <select name="new_owner_id" id="new_owner_id" class="form-select" required>
                  <option value="">— เลือก —</option>
                  <?php if ($rsUsers): while ($u = mysqli_fetch_assoc($rsUsers)):
                        $display = trim((string)$u['full_name']);
                        if ($display === '') $display = 'ไม่ระบุชื่อ';
                  ?>
                    <option value="<?= (int)$u['user_id'] ?>"><?= htmlspecialchars($display) ?></option>
                  <?php endwhile; endif; ?>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">หมายเหตุ (ถ้ามี)</label>
                <textarea name="remarks" rows="2" class="form-control" placeholder="ระบุเหตุผล/รายละเอียดการโอนสิทธิ"></textarea>
              </div>

              <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-check me-1"></i> ยืนยันการโอนสิทธิ
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ตารางประวัติ -->
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 57vh; overflow-y: auto;">
              <table id="historyTable" class="table table-bordered table-hover align-middle mb-0">
                <thead class="sticky-top bg-white" style="z-index: 1020;">
                  <tr>
                    <th>วันที่</th>
                    <th>เลขครุภัณฑ์</th>
                    <th>รุ่น</th>
                    <th>จาก (เดิม)</th>
                    <th>เป็น (ใหม่)</th>
                    <th>ผู้ดำเนินการ</th>
                    <th>หมายเหตุ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($rsHis && mysqli_num_rows($rsHis) > 0): while ($h = mysqli_fetch_assoc($rsHis)): ?>
                    <tr>
                      <td><?= thaidate('j M Y H:i', $h['change_date']) ?></td>
                      <td><?= htmlspecialchars($h['item_number'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($h['model_name'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($h['old_owner'] ?: ($h['old_value'] ?: '-')) ?></td>
                      <td><?= htmlspecialchars($h['new_owner'] ?: ($h['new_value'] ?: '-')) ?></td>
                      <td><?= htmlspecialchars($h['changed_by_name'] ?? '-') ?></td>
                      <td><?= $h['remarks'] ? htmlspecialchars($h['remarks']) : '-' ?></td>
                    </tr>
                  <?php endwhile; else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <div>ยังไม่มีประวัติการโอนสิทธิ</div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div> <!-- /main-content -->
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const BRAND_GREEN = '#41B143';

  const itemSel   = document.getElementById('item_id');
  const ownerText = document.getElementById('currentOwnerText');
  const newOwner  = document.getElementById('new_owner_id');
  const form      = document.getElementById('transferForm');

  // เปิด Select2 ให้ค้นหาได้
  $(itemSel).select2({ width: '100%', placeholder: '— เลือกครุภัณฑ์ —' });
  $(newOwner).select2({ width: '100%', placeholder: '— เลือกผู้ถือสิทธิใหม่ —' });

  function refreshNewOwnerDisabled(curId) {
    Array.from(newOwner.options).forEach(o => { o.disabled = false; });
    if (curId && Number(curId) > 0) {
      Array.from(newOwner.options).forEach(o => {
        if (o.value && Number(o.value) === Number(curId)) { o.disabled = true; }
      });
      if (newOwner.value && Number(newOwner.value) === Number(curId)) { newOwner.value = ''; }
    }
    $(newOwner).trigger('change.select2');
  }

  function updateOwnerInfo() {
    const opt = itemSel.options[itemSel.selectedIndex];
    if (!opt || !opt.value) {
      ownerText.textContent = '—';
      refreshNewOwnerDisabled(null);
      return;
    }
    const curId   = opt.getAttribute('data-owner-id');
    const curName = opt.getAttribute('data-owner-name') || 'ยังไม่มีผู้ถือสิทธิ';
    ownerText.textContent = curName;
    refreshNewOwnerDisabled(curId);
  }

  itemSel.addEventListener('change', updateOwnerInfo);
  $(itemSel).on('select2:select', updateOwnerInfo);
  updateOwnerInfo();

  // Confirm ก่อนส่งฟอร์ม
  form.addEventListener('submit', function(e){
    e.preventDefault();

    const itemOption = itemSel.options[itemSel.selectedIndex];
    const itemText   = itemOption ? itemOption.text : '';
    const curName    = ownerText.textContent || '—';
    const newOpt     = newOwner.options[newOwner.selectedIndex];
    const newText    = newOpt ? newOpt.text : '';

    if (!itemSel.value || !newOwner.value) {
      Swal.fire({icon:'warning', title:'ข้อมูลไม่ครบ', text:'กรุณาเลือกครุภัณฑ์และผู้ถือสิทธิใหม่', confirmButtonColor:BRAND_GREEN});
      return;
    }

    // ป้องกันโอนให้คนเดิม (เผื่อกรณีแก้ DOM)
    const currentOwnerId = itemOption ? Number(itemOption.getAttribute('data-owner-id') || 0) : 0;
    if (currentOwnerId > 0 && Number(newOwner.value) === currentOwnerId) {
      Swal.fire({icon:'error', title:'ไม่สามารถโอนได้', text:'ผู้ถือสิทธิใหม่ซ้ำกับเจ้าของเดิม', confirmButtonColor:BRAND_GREEN});
      return;
    }

    Swal.fire({
      icon: 'question',
      title: 'ยืนยันการโอนสิทธิ?',
      html: `
        <div class="text-start">
          <div><strong>ครุภัณฑ์:</strong> ${itemText ? itemText.replace(/</g,'&lt;') : '-'}</div>
          <div><strong>จาก:</strong> ${curName ? curName.replace(/</g,'&lt;') : '—'}</div>
          <div><strong>เป็น:</strong> ${newText ? newText.replace(/</g,'&lt;') : '-'}</div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'ยืนยัน',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: BRAND_GREEN
    }).then(res => {
      if (res.isConfirmed) {
        form.submit();
      }
    });
  });

  // Flash message จาก PHP (SweetAlert2)
  const flashSuccess = <?php echo json_encode($flash_success, JSON_UNESCAPED_UNICODE); ?>;
  const flashError   = <?php echo json_encode($flash_error,   JSON_UNESCAPED_UNICODE); ?>;
  if (flashError) {
    Swal.fire({ icon:'error', title:'ไม่สามารถดำเนินการได้', text: flashError, confirmButtonColor: BRAND_GREEN });
  }
  if (flashSuccess) {
    Swal.fire({ icon:'success', title:'สำเร็จ', text: flashSuccess, confirmButtonColor: BRAND_GREEN });
  }
});
</script>

<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
  <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
  พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี | © 2025
</footer>
</body>
</html>
