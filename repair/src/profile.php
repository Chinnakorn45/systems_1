<?php
require_once 'db.php';
session_start();

/* ===== ตรวจสอบการล็อกอิน ===== */
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

/* ===== รองรับ admin แก้ไข user อื่น ===== */
$is_admin_edit = false;
if (isset($_GET['id']) && (($_SESSION['role'] ?? '') === 'admin')) {
    $user_id = intval($_GET['id']);
    $is_admin_edit = true;
} else {
    $user_id = $_SESSION["user_id"];
}

/* ===== ตัวแปรเริ่มต้น ===== */
$username = $full_name = $email = $department = $position = '';
$username_err = $full_name_err = $email_err = $password_err = $confirm_password_err = $update_err = $department_err = $position_err = '';
$success = false;

/* ===== ดึงข้อมูลผู้ใช้ ===== */
$sql = "SELECT username, full_name, email, department, position FROM users WHERE user_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $username, $full_name, $email, $department, $position);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

/* ===== ดึงแผนกหลัก ===== */
$main_departments = [];
$main_result = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
while ($row = mysqli_fetch_assoc($main_result)) {
    $main_departments[] = $row;
}

/* ===== หาแผนกหลักของผู้ใช้จากแผนกย่อย ===== */
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

/* ===== เมื่อ submit ===== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $department = trim($_POST["department"]);
    $position = trim($_POST["position"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($username)) $username_err = "กรุณากรอกชื่อผู้ใช้";
    if (empty($full_name)) $full_name_err = "กรุณากรอกชื่อ-นามสกุล";
    if (empty($email)) $email_err = "กรุณากรอกอีเมล";
    if (empty($department)) $department_err = "กรุณาเลือกแผนก";
    if (empty($position)) $position_err = "กรุณากรอกตำแหน่ง";

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

    if (empty($username_err) && empty($full_name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($department_err) && empty($position_err)) {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, full_name=?, email=?, department=?, position=?, password_hash=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $username, $full_name, $email, $department, $position, $password_hash, $user_id);
        } else {
            $sql = "UPDATE users SET username=?, full_name=?, email=?, department=?, position=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $username, $full_name, $email, $department, $position, $user_id);
        }
        if ($stmt->execute()) $success = true;
        else $update_err = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แก้ไขโปรไฟล์</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ===== ฟอร์มกลางหน้าหลัง sidebar ===== */
.main-wrapper {
  margin-left: 260px;
  min-height: calc(100vh - 80px);
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--brand-bg);
  padding: 40px 20px;
}
.profile-box {
  background: var(--brand-surface);
  border: 1px solid var(--brand-border);
  border-radius: 16px;
  box-shadow: 0 6px 22px rgba(0,0,0,0.08);
  padding: 36px 40px;
  width: 100%;
  max-width: 760px;
}
h3 {
  font-weight: 650;
  color: var(--brand-primary);
}

/* ===== Responsive for mobile ===== */
@media (max-width: 991.98px) {
  .main-wrapper {
    margin-left: 0;
    padding: 20px;
    align-items: flex-start;
  }
  .profile-box {
    padding: 24px 20px;
    border-radius: 12px;
  }
  footer {
    margin-left: 0;
    font-size: 13px;
  }
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
  <div class="profile-box">
      <h3 class="mb-3 text-primary text-center">แก้ไขโปรไฟล์</h3>
      <hr>
      <?php if (!empty($update_err)): ?>
          <div class="alert alert-danger"><?php echo $update_err; ?></div>
      <?php endif; ?>

      <form method="post" action="profile.php<?php echo $is_admin_edit ? '?id='.$user_id : ''; ?>">
          <div class="row g-3">
              <div class="col-md-6">
                  <label class="form-label">ชื่อผู้ใช้</label>
                  <input type="text" class="form-control <?php echo $username_err?'is-invalid':'';?>" name="username" value="<?php echo htmlspecialchars($username); ?>">
                  <div class="invalid-feedback"><?php echo $username_err; ?></div>
              </div>
              <div class="col-md-6">
                  <label class="form-label">ชื่อ-นามสกุล</label>
                  <input type="text" class="form-control <?php echo $full_name_err?'is-invalid':'';?>" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>">
                  <div class="invalid-feedback"><?php echo $full_name_err; ?></div>
              </div>

              <div class="col-md-6">
                  <label class="form-label">อีเมล</label>
                  <input type="email" class="form-control <?php echo $email_err?'is-invalid':'';?>" name="email" value="<?php echo htmlspecialchars($email); ?>">
                  <div class="invalid-feedback"><?php echo $email_err; ?></div>
              </div>
              <div class="col-md-6">
                  <label class="form-label">ตำแหน่ง</label>
                  <input type="text" class="form-control <?php echo $position_err?'is-invalid':'';?>" name="position" value="<?php echo htmlspecialchars($position); ?>">
                  <div class="invalid-feedback"><?php echo $position_err; ?></div>
              </div>

              <div class="col-md-6">
                  <label class="form-label">แผนกหลัก</label>
                  <select class="form-select" id="main_department" name="main_department">
                      <option value="">-- เลือกแผนกหลัก --</option>
                      <?php foreach ($main_departments as $dep): ?>
                          <option value="<?php echo $dep['department_id']; ?>"
                            <?php echo ($main_department == $dep['department_id']) ? 'selected':''; ?>>
                            <?php echo htmlspecialchars($dep['department_name']); ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
              </div>
              <div class="col-md-6">
                  <label class="form-label">แผนกย่อย</label>
                  <select class="form-select <?php echo $department_err?'is-invalid':'';?>" id="department" name="department">
                      <option value="">-- เลือกแผนกย่อย --</option>
                  </select>
                  <div class="invalid-feedback"><?php echo $department_err; ?></div>
              </div>

              <div class="col-12"><hr></div>
              <div class="col-md-6">
                  <label class="form-label">รหัสผ่านใหม่ (ถ้าเปลี่ยน)</label>
                  <input type="password" class="form-control <?php echo $password_err?'is-invalid':'';?>" name="password">
                  <div class="invalid-feedback"><?php echo $password_err; ?></div>
              </div>
              <div class="col-md-6">
                  <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                  <input type="password" class="form-control <?php echo $confirm_password_err?'is-invalid':'';?>" name="confirm_password">
                  <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
              </div>
          </div>

          <div class="mt-4 text-center">
              <button type="submit" class="btn btn-primary px-4">บันทึกข้อมูล</button>
              <a href="dashboard.php" class="btn btn-outline-secondary px-4 ms-2">ยกเลิก</a>
          </div>
      </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const mainSelect=document.getElementById('main_department');
    const subSelect=document.getElementById('department');
    const currentSub=<?php echo json_encode($department,JSON_UNESCAPED_UNICODE);?>;
    const currentMain=<?php echo json_encode($main_department);?>;
    function loadSub(pid,sel){
        subSelect.innerHTML='<option value="">-- เลือกแผนกย่อย --</option>';
        if(!pid)return;
        fetch('get_departments_children.php?parent_id='+pid)
          .then(r=>r.json()).then(data=>{
            data.forEach(dep=>{
                const opt=document.createElement('option');
                opt.value=dep.department_name;
                opt.textContent=dep.department_name;
                if(sel===dep.department_name)opt.selected=true;
                subSelect.appendChild(opt);
            });
        });
    }
    if(currentMain){ mainSelect.value=currentMain; loadSub(currentMain,currentSub); }
    mainSelect.addEventListener('change',()=>loadSub(mainSelect.value,''));

    <?php if($success): ?>
    Swal.fire({
        icon:'success', title:'บันทึกข้อมูลสำเร็จ',
        text:'ระบบบันทึกโปรไฟล์ของคุณเรียบร้อยแล้ว'
    }).then(()=>window.location='profile.php<?php echo $is_admin_edit?'?id='.$user_id:'';?>');
    <?php endif; ?>
});
</script>
</body>
</html>
