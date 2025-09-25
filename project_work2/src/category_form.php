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

$save_status = '';   // 'added' | 'updated'
$save_message = '';  // ข้อความสำหรับ Swal

// ดึงข้อมูลเดิมเมื่อแก้ไข
if ($category_id > 0) {
    $is_edit = true;
    $sql = "SELECT category_id, category_name FROM categories WHERE category_id = ?";
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

// Submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name']);

    if ($category_name === '') {
        $category_name_err = "กรุณากรอกชื่อหมวดหมู่";
    } else {
        // ตรวจชื่อซ้ำ (ยกเว้นตัวเอง)
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

    if ($category_name_err === '') {
        if ($is_edit) {
            $sql = "UPDATE categories SET category_name = ? WHERE category_id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $category_name, $category_id);
                if (mysqli_stmt_execute($stmt)) {
                    $save_status = 'updated';
                    $save_message = 'บันทึกการแก้ไขหมวดหมู่เรียบร้อยแล้ว';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "INSERT INTO categories (category_name) VALUES (?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $category_name);
                if (mysqli_stmt_execute($stmt)) {
                    $save_status = 'added';
                    $save_message = 'เพิ่มหมวดหมู่เรียบร้อยแล้ว';
                }
                mysqli_stmt_close($stmt);
            }
        }
        // ไม่ใช้ header redirect เพื่อให้แสดง Swal ได้ก่อน
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
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
  /* ===== โทนเดียวกับหน้ารายการ ===== */
  :root{
    --bg-page: #f5f6f8;
    --card-border: #e5e7eb;
    --brand-green: #41B143;
    --brand-green-dark: #2f8f33;
  }
  html, body { height: 100%; }
  body{
    background: var(--bg-page);
    font-size: .95rem;
    overflow: hidden; /* ให้ความรู้สึกเป็น overlay เหลือแต่โมดัล */
  }

  /* แถบหัวโมดัลให้เป็นสีเขียว */
  .modal-header.brand {
    background: var(--brand-green);
    color: #fff;
    border-bottom: none;
  }
  .modal-content.clean {
    border: 1px solid var(--card-border);
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    border-radius: 16px;
  }

  /* ปุ่มสไตล์เดียวกับหน้า list */
  .btn-add-pill{
    background: var(--brand-green);
    color:#fff;
    border:0;
    border-radius: 9999px;
    padding: .5rem 1rem;
    font-weight: 600;
  }
  .btn-add-pill:hover{ background: var(--brand-green-dark); color:#fff; }

  .btn-cancel {
    background:#fff;
    border:1px solid var(--card-border);
    border-radius: 9999px;
    padding:.5rem 1rem;
    color:#111827;
  }
  .btn-cancel:hover{ background:#f3f4f6; color:#111827; }

  /* ขนาดโมดัล */
  .modal-dialog { max-width: 520px; }
</style>
</head>
<body>

<!-- Modal: เปิดอัตโนมัติ -->
<div class="modal fade" id="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content clean">
      <div class="modal-header brand">
        <h5 class="modal-title">
          <i class="fa-solid fa-layer-group me-2"></i>
          <?php echo $is_edit ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่'; ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                onclick="window.location.href='categories.php'"></button>
      </div>
      <div class="modal-body">
        <form action="" method="post" id="categoryForm" novalidate>
          <div class="mb-3">
            <label for="category_name" class="form-label fw-semibold">ชื่อหมวดหมู่</label>
            <input
              type="text"
              class="form-control <?php echo $category_name_err !== '' ? 'is-invalid' : ''; ?>"
              id="category_name" name="category_name"
              value="<?php echo htmlspecialchars($category_name); ?>" required
              placeholder="เช่น คอมพิวเตอร์, เครื่องพิมพ์">
            <div class="invalid-feedback"><?php echo $category_name_err; ?></div>
          </div>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <a href="categories.php" class="btn btn-cancel">
          <i class="fa-solid fa-arrow-left-long me-1"></i> กลับไปหน้าหมวดหมู่
        </a>
        <button type="submit" form="categoryForm" class="btn btn-add-pill">
          <?php echo $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มหมวดหมู่'; ?>
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // เปิดโมดัลทันทีเมื่อโหลดหน้า
  const modalEl = document.getElementById('categoryModal');
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  // ตรวจ required ของ Bootstrap
  (function () {
    const form = document.getElementById('categoryForm');
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  })();

  // ---- SweetAlert2 สำหรับผลลัพธ์ ----
  const BRAND_GREEN = '#41B143';

  // ข้อความจาก PHP (success / error)
  const saveStatus  = <?php echo json_encode($save_status,  JSON_UNESCAPED_UNICODE); ?>; // '' | 'added' | 'updated'
  const saveMessage = <?php echo json_encode($save_message, JSON_UNESCAPED_UNICODE); ?>;
  const fieldError  = <?php echo json_encode($category_name_err, JSON_UNESCAPED_UNICODE); ?>;

  // กรณีฟอร์ม error (เช่น ชื่อซ้ำ) -> แจ้งเตือนสวยๆ
  if (fieldError && !saveStatus) {
    Swal.fire({
      icon: 'warning',
      title: 'ข้อมูลไม่ถูกต้อง',
      text: fieldError,
      confirmButtonText: 'ตกลง',
      confirmButtonColor: BRAND_GREEN
    });
  }

  // เพิ่ม/แก้ไขสำเร็จ -> เด้งสำเร็จแล้วพากลับหน้ารายการ
  if (saveStatus) {
    Swal.fire({
      icon: 'success',
      title: 'สำเร็จ',
      text: saveMessage || 'บันทึกข้อมูลเรียบร้อยแล้ว',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: BRAND_GREEN
      // ถ้าอยากให้ปิดเอง: ใส่ timer: 1500, showConfirmButton: false
    }).then(() => {
      window.location.href = 'categories.php';
    });
  }
</script>
</body>
</html>
