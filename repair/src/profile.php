<?php
require_once 'db.php';
session_start();

/* ------------ ตรวจสอบการล็อกอิน ------------ */
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

/* ------------ รองรับ admin แก้ไข user อื่น ------------ */
$is_admin_edit = false;
if (isset($_GET['id']) && $_SESSION['role'] === 'admin') {
    $user_id = intval($_GET['id']);
    $is_admin_edit = true;
} else {
    $user_id = $_SESSION["user_id"];
}

/* ------------ ตัวแปรเริ่มต้น ------------ */
$username = $full_name = $email = $department = $position = '';
$username_err = $full_name_err = $email_err = $password_err = $confirm_password_err = $update_err = $department_err = $position_err = '';
$success = false;

/* ------------ ดึงข้อมูลผู้ใช้ปัจจุบัน ------------ */
$sql = "SELECT username, full_name, email, department, position FROM users WHERE user_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $username, $full_name, $email, $department, $position);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

/* ------------ ดึงแผนกหลัก (parent_id IS NULL) ------------ */
$main_departments = [];
$main_result = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
while ($row = mysqli_fetch_assoc($main_result)) {
    $main_departments[] = $row;
}

/* ------------ หาแผนกหลักของผู้ใช้จากแผนกย่อยเดิม (map อัตโนมัติ) ------------ */
$main_department = null;
if (!empty($department)) {
    $q = $conn->prepare("SELECT parent_id FROM departments WHERE department_name = ? LIMIT 1");
    $q->bind_param("s", $department);
    $q->execute();
    $q->bind_result($parent_id);
    if ($q->fetch() && $parent_id !== null) {
        $main_department = (string)$parent_id;
    }
    $q->close();
}

/* ------------ เมื่อ submit แบบ POST ------------ */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $department = trim($_POST["department"]);
    $position = trim($_POST["position"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Validate username
    if (empty($username)) {
        $username_err = "กรุณากรอกชื่อผู้ใช้";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $username_err = "ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, A-Z, 0-9, _ เท่านั้น";
    } else {
        $sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $username_err = "ชื่อผู้ใช้นี้ถูกใช้แล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate full name
    if (empty($full_name)) $full_name_err = "กรุณากรอกชื่อ-นามสกุล";

    // Validate email
    if (empty($email)) {
        $email_err = "กรุณากรอกอีเมล";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "รูปแบบอีเมลไม่ถูกต้อง";
    } else {
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $email_err = "อีเมลนี้ถูกใช้แล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (empty($department)) $department_err = "กรุณากรอก/เลือกแผนก";
    if (empty($position))   $position_err   = "กรุณากรอกตำแหน่ง";

    // Validate password (ถ้ามี)
    if (!empty($password)) {
        if (
            strlen($password) < 10 ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[\W_]/', $password)
        ) {
            $password_err = "รหัสผ่านอย่างน้อย 10 ตัว (มีตัวพิมพ์เล็ก/ใหญ่ ตัวเลข และอักขระพิเศษ)";
        } elseif ($password !== $confirm_password) {
            $confirm_password_err = "รหัสผ่านไม่ตรงกัน";
        }
    }

    // อัปเดต
    if (empty($username_err) && empty($full_name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($department_err) && empty($position_err)) {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($is_admin_edit && $_SESSION['role'] === 'admin' && $user_id != $_SESSION['user_id']) {
                $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, department = ?, position = ?, password_hash = ? WHERE user_id = ?";
            } else {
                $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, department = ?, position = ?, password_hash = ?, force_password_change = 0 WHERE user_id = ?";
            }
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssssi", $username, $full_name, $email, $department, $position, $password_hash, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success = true;
                    if (!$is_admin_edit || $user_id == $_SESSION['user_id']) {
                        $_SESSION["username"] = $username;
                        $_SESSION["full_name"] = $full_name;
                    }
                } else {
                    $update_err = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, department = ?, position = ? WHERE user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssi", $username, $full_name, $email, $department, $position, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success = true;
                    if (!$is_admin_edit || $user_id == $_SESSION['user_id']) {
                        $_SESSION["username"] = $username;
                        $_SESSION["full_name"] = $full_name;
                    }
                } else {
                    $update_err = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แก้ไขโปรไฟล์ - ระบบแจ้งซ่อมครุภัณฑ์</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
      body { background:#f7f9fc; font-family:'Prompt', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; }
      .main-content { margin-left: 250px; padding: 24px; }
      @media (max-width: 991.98px){ .main-content{ margin-left:0; padding:16px; } }

      .page-header {
        margin-bottom: 16px;
      }
      .page-header h2 {
        margin: 0 0 4px 0;
        font-weight: 600;
        color:#1f2937;
      }
      .page-header .text-muted { color:#6b7280 !important; }

      .card-plain {
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(17,24,39,0.04);
      }
      .section-title {
        font-weight: 600;
        color:#374151;
        margin-bottom:8px;
      }
      .form-control, .form-select {
        border-radius: 10px;
      }
      .form-control:focus, .form-select:focus {
        border-color:#2563eb;
        box-shadow: 0 0 0 .2rem rgba(37,99,235,.15);
      }
      .btn-save { padding:.6rem 1rem; }
      .btn-cancel { color:#6b7280; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
  <div class="container" style="max-width: 880px;">
    <div class="page-header">
      <h2>แก้ไขโปรไฟล์</h2>
      <div class="text-muted">ปรับปรุงข้อมูลส่วนตัวของคุณ</div>
    </div>

    <div class="card-plain p-3 p-md-4">
      <?php if (!empty($update_err)): ?>
        <div class="alert alert-danger py-2 px-3 mb-3"><?php echo $update_err; ?></div>
      <?php endif; ?>

      <form action="profile.php<?php echo $is_admin_edit ? '?id=' . $user_id : ''; ?>" method="post" novalidate>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control <?php echo $username_err ? 'is-invalid' : ''; ?>"
                   name="username" value="<?php echo htmlspecialchars($username); ?>"
                   required <?php echo ($is_admin_edit && $user_id == 1) ? 'readonly' : ''; ?>>
            <div class="invalid-feedback"><?php echo $username_err; ?></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">ชื่อ-นามสกุล</label>
            <input type="text" class="form-control <?php echo $full_name_err ? 'is-invalid' : ''; ?>"
                   name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
            <div class="invalid-feedback"><?php echo $full_name_err; ?></div>
          </div>

          <div class="col-md-6">
            <label class="form-label">อีเมล</label>
            <input type="email" class="form-control <?php echo $email_err ? 'is-invalid' : ''; ?>"
                   name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <div class="invalid-feedback"><?php echo $email_err; ?></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">ตำแหน่ง</label>
            <input type="text" class="form-control <?php echo $position_err ? 'is-invalid' : ''; ?>"
                   name="position" value="<?php echo htmlspecialchars($position ?? ''); ?>" required>
            <div class="invalid-feedback"><?php echo $position_err; ?></div>
          </div>

          <div class="col-md-6">
            <label class="form-label">แผนกหลัก</label>
            <select class="form-select" id="main_department" name="main_department" required>
              <option value="">-- เลือกแผนกหลัก --</option>
              <?php foreach ($main_departments as $dep): ?>
                <option value="<?php echo $dep['department_id']; ?>"
                  <?php echo ($main_department !== null && (string)$main_department === (string)$dep['department_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($dep['department_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">แผนกย่อย</label>
            <select class="form-select <?php echo $department_err ? 'is-invalid' : ''; ?>"
                    id="department" name="department" required>
              <option value="">-- เลือกแผนกย่อย --</option>
            </select>
            <div class="invalid-feedback"><?php echo $department_err; ?></div>
          </div>

          <div class="col-12"><hr class="my-2"></div>

          <div class="col-md-6">
            <label class="form-label">รหัสผ่านใหม่ (ถ้าเปลี่ยน)</label>
            <input type="password" class="form-control <?php echo $password_err ? 'is-invalid' : ''; ?>"
                   name="password" autocomplete="new-password">
            <div class="invalid-feedback"><?php echo $password_err; ?></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" class="form-control <?php echo $confirm_password_err ? 'is-invalid' : ''; ?>"
                   name="confirm_password" autocomplete="new-password">
            <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
          </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mt-4">
          <button type="submit" class="btn btn-primary btn-save"><i class="fa-solid fa-floppy-disk me-2"></i>บันทึกข้อมูล</button>
          <a href="dashboard.php" class="btn btn-outline-secondary btn-save">ยกเลิก</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainSelect = document.getElementById('main_department');
    const subSelect  = document.getElementById('department');
    const currentSubName = <?php echo json_encode($department, JSON_UNESCAPED_UNICODE); ?>;
    const currentMainId  = <?php echo json_encode($main_department); ?>;

    function loadSubDepartments(parentId, selectedName) {
        subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
        if (!parentId) return;

        fetch('get_departments_children.php?parent_id=' + encodeURIComponent(parentId))
            .then(res => res.json())
            .then(data => {
                data.forEach(dep => {
                    const opt = document.createElement('option');
                    opt.value = dep.department_name;
                    opt.textContent = dep.department_name;
                    if (selectedName && selectedName === dep.department_name) opt.selected = true;
                    subSelect.appendChild(opt);
                });
            })
            .catch(() => {});
    }

    if (currentMainId) {
        mainSelect.value = String(currentMainId);
        loadSubDepartments(currentMainId, currentSubName);
    }

    mainSelect.addEventListener('change', function() {
        loadSubDepartments(this.value, '');
    });

    // SweetAlert เฉพาะผลลัพธ์บันทึก
    <?php if ($success): ?>
    Swal.fire({
        icon: 'success',
        title: 'บันทึกข้อมูลสำเร็จ',
        text: 'ระบบบันทึกโปรไฟล์ของคุณเรียบร้อยแล้ว',
        confirmButtonText: 'ตกลง',
        heightAuto: false
    }).then(() => {
        window.location.href = <?php echo json_encode('profile.php' . ($is_admin_edit ? ('?id=' . $user_id) : ''), JSON_UNESCAPED_SLASHES); ?>;
    });
    <?php elseif (!empty($update_err)): ?>
    Swal.fire({
        icon: 'error',
        title: 'บันทึกไม่สำเร็จ',
        text: <?php echo json_encode($update_err, JSON_UNESCAPED_UNICODE); ?>,
        confirmButtonText: 'ปิด',
        heightAuto: false
    });
    <?php endif; ?>
});
</script>
</body>
</html>
