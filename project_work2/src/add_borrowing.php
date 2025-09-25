<?php
require_once 'config.php';
require_once 'movement_logger.php';
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

/* ===== ดึงรายการครุภัณฑ์ + จำนวนคงเหลือให้ยืม ===== */
$sqlItems = "
    SELECT 
        i.item_id, 
        i.item_number, 
        i.model_name, 
        i.brand,
        COALESCE(i.total_quantity,0) AS total_quantity,
        (
          COALESCE(i.total_quantity,0) - COALESCE((
            SELECT SUM(quantity_borrowed) 
            FROM borrowings 
            WHERE item_id = i.item_id AND status IN ('borrowed','approved','return_pending')
          ),0)
        ) AS available_items,
        c.category_name
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.category_id
    ORDER BY i.item_number
";
$items = mysqli_query($link, $sqlItems);

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION["user_id"];
    $item_id     = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $borrow_date = $_POST['borrow_date'] ?? '';
    $due_date    = $_POST['due_date'] ?? '';
    $quantity    = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    if ($item_id <= 0 || $borrow_date === '' || $due_date === '' || $quantity < 1) {
        $err = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        // ตรวจสอบจำนวนคงเหลือจริง
        $qTot = mysqli_query($link, "SELECT COALESCE(total_quantity,0) AS total_quantity FROM items WHERE item_id = ".intval($item_id));
        $itemRow = $qTot ? mysqli_fetch_assoc($qTot) : null;
        $total = $itemRow ? (int)$itemRow['total_quantity'] : 0;

        $qBorrow = mysqli_query(
            $link,
            "SELECT COALESCE(SUM(quantity_borrowed),0) AS borrowed
             FROM borrowings 
             WHERE item_id = ".intval($item_id)." 
               AND status IN ('borrowed','approved','return_pending')"
        );
        $borrowed = $qBorrow ? (int)mysqli_fetch_assoc($qBorrow)['borrowed'] : 0;

        $available = max(0, $total - $borrowed);

        if ($quantity > $available) {
            $err = 'จำนวนที่ยืมเกินจำนวนที่มีอยู่ในระบบ (เหลือให้ยืม '.$available.' ชิ้น)';
        } else {
            $status = ($_SESSION["role"] === "staff") ? 'pending' : 'borrowed';
            $sqlIns = "INSERT INTO borrowings (user_id, item_id, borrow_date, due_date, quantity_borrowed, status)
                       VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sqlIns)) {
                mysqli_stmt_bind_param($stmt, 'iissis', $user_id, $item_id, $borrow_date, $due_date, $quantity, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $borrow_id = mysqli_insert_id($link);

                    if ($_SESSION["role"] !== "staff") {
                        // เผื่อมีคอลัมน์ available_quantity
                        @mysqli_query(
                            $link,
                            "UPDATE items 
                             SET available_quantity = GREATEST(0, COALESCE(available_quantity,0) - ".intval($quantity).") 
                             WHERE item_id = ".intval($item_id)
                        );
                        log_borrow_movement($item_id, $user_id, $quantity, $borrow_id, 'สร้างการยืมโดย ' . ($_SESSION['username'] ?? $_SESSION['full_name'] ?? 'system'));
                    } else {
                        log_equipment_movement(
                            $item_id, 'adjustment',
                            null, null, null, null,
                            $quantity,
                            'ส่งคำขอยืมโดย ' . ($_SESSION['username'] ?? $_SESSION['full_name'] ?? 'staff'),
                            $borrow_id
                        );
                    }

                    /* ===== ป็อปอัปสวย ๆ แล้วค่อยพาไปหน้า borrowings.php ===== */
                    ?>
                    <!DOCTYPE html>
                    <html lang="th">
                    <head>
                      <meta charset="UTF-8">
                      <meta name="viewport" content="width=device-width, initial-scale=1.0">
                      <title>สำเร็จ</title>
                      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    </head>
                    <body>
                      <script>
                        Swal.fire({
                          icon: 'success',
                          title: 'บันทึกการยืมสำเร็จ!',
                          text: 'สถานะ: <?= $_SESSION["role"] === "staff" ? "รออนุมัติ (pending)" : "กำลังยืม (borrowed)" ?>',
                          confirmButtonText: 'ไปหน้าการยืม',
                          confirmButtonColor: '#41B143',
                          timer: 2000,
                          timerProgressBar: true
                        }).then(() => {
                          window.location.href = 'borrowings.php';
                        });
                      </script>
                      <noscript>
                        <meta http-equiv="refresh" content="0;url=borrowings.php">
                      </noscript>
                    </body>
                    </html>
                    <?php
                    exit;
                } else {
                    $err = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                }
                mysqli_stmt_close($stmt);
            } else {
                $err = 'ไม่สามารถเตรียมคำสั่งฐานข้อมูลได้';
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
<!-- Select2 (ทำให้ select ค้นหาได้) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  body { background:#e8f5e9; }
  .btn-main {
    background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
    color: #fff; border: none; padding: 10px 20px; border-radius: 5px;
    font-size: 1.05rem; font-weight: 600; transition: all .25s ease;
  }
  .btn-main:hover { background: linear-gradient(135deg, #8BC34A 0%, #4CAF50 100%); color:#fff; box-shadow:0 4px 10px rgba(0,0,0,.15); }
  .btn-cancel, .btn-secondary {
    background:#6c757d; color:#fff; border:none; padding:10px 20px; border-radius:5px;
    font-size:1.05rem; font-weight:600;
  }
  .btn-cancel:hover, .btn-secondary:hover { background:#5a6268; color:#fff; }
  .form-label { font-weight:600; color:#185a9d; }
  /* ปรับความสูง select2 ให้พอดีกับ bootstrap 5 */
  .select2-container { width:100%!important; }
  .select2-container--default .select2-selection--single {
    height: calc(2.5rem); padding:.375rem .5rem; border:1px solid #ced4da; border-radius:.375rem;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:1.6; padding-left:0; }
  .select2-container--default .select2-selection--single .select2-selection__arrow { height:100%; }
</style>
</head>
<body>
<div class="container py-4">
  <h2 class="mb-4"><i class="fas fa-plus"></i> เพิ่มการยืมครุภัณฑ์</h2>

  <?php if ($err): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($err) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow-sm">
    <div class="mb-3">
      <label class="form-label">ผู้ยืม</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? '-') ?>" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label">เลือกครุภัณฑ์</label>
      <select name="item_id" id="item_id" class="form-select" required>
        <option value="">-- เลือกครุภัณฑ์ --</option>
        <?php if ($items && mysqli_num_rows($items) > 0): ?>
          <?php while ($i = mysqli_fetch_assoc($items)): ?>
            <?php
              $available   = (int)($i['available_items'] ?? 0);
              $status_text = $available > 0 ? 'พร้อมให้ยืม' : 'ไม่พร้อมให้ยืม';
              $is_disabled = $available <= 0;

              $label = trim(
                ($i['item_number'] ?? '') .
                (!empty($i['model_name']) ? ' - '.$i['model_name'] : '') .
                (!empty($i['brand']) ? ' ('.$i['brand'].')' : '')
              );
              $cat = $i['category_name'] ?? '-';
            ?>
            <option
              value="<?= (int)$i['item_id'] ?>"
              <?= $is_disabled ? 'disabled' : '' ?>
              data-available="<?= $available ?>"
              data-search="<?= htmlspecialchars($i['item_number'].' '.$i['model_name'].' '.$i['brand'].' '.$cat) ?>"
            >
              <?= htmlspecialchars($label) ?>
              [หมวดหมู่: <?= htmlspecialchars($cat) ?>]
              - <?= $status_text ?> จำนวน: <?= $available ?>
            </option>
          <?php endwhile; ?>
        <?php else: ?>
          <option value="" disabled>ไม่พบครุภัณฑ์ในระบบ</option>
        <?php endif; ?>
      </select>
      <div class="form-text" id="availHint" hidden>เหลือให้ยืม <span id="availNum">0</span> ชิ้น</div>
    </div>

    <div class="mb-3">
      <label class="form-label">จำนวนที่ยืม</label>
      <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1" required>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// ทำให้เลือกครุภัณฑ์ค้นหาได้ (Select2) + อัปเดตจำนวนสูงสุดที่ยืมได้
(function () {
  const sel   = document.getElementById('item_id');
  const qty   = document.getElementById('quantity');
  const hint  = document.getElementById('availHint');
  const avail = document.getElementById('availNum');

  // ใช้ data-search เพื่อช่วยให้ค้นหาจากทุกฟิลด์
  $(sel).select2({
    width: '100%',
    placeholder: '-- เลือกครุภัณฑ์ --',
    language: {
      noResults: () => 'ไม่พบรายการ',
      searching: () => 'กำลังค้นหา...'
    },
    matcher: function(params, data) {
      if ($.trim(params.term) === '') return data;
      if (typeof data.text === 'undefined') return null;

      const term  = params.term.toLowerCase();
      const text  = (data.text || '').toLowerCase();
      const extra = (data.element?.dataset?.search || '').toLowerCase();

      return (text.indexOf(term) > -1 || extra.indexOf(term) > -1) ? data : null;
    }
  });

  function updateLimits() {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) {
      qty.max = '';
      hint.hidden = true;
      return;
    }
    const av = parseInt(opt.getAttribute('data-available') || '0', 10);
    qty.max = av > 0 ? av : 1;
    if (+qty.value > av) qty.value = av || 1;
    avail.textContent = av;
    hint.hidden = false;
  }

  sel.addEventListener('change', updateLimits);
  $(sel).on('select2:select', updateLimits);
  updateLimits();
})();
</script>
</body>
</html>
