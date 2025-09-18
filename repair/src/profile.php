<?php
require_once 'db.php';
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// รองรับ admin แก้ไข user อื่น
$is_admin_edit = false;
if (isset($_GET['id']) && $_SESSION['role'] === 'admin') {
    $user_id = intval($_GET['id']);
    $is_admin_edit = true;
} else {
    $user_id = $_SESSION["user_id"];
}

$username = $full_name = $email = $department = $position = '';
$username_err = $full_name_err = $email_err = $password_err = $confirm_password_err = $update_err = $department_err = $position_err = '';
$success = false;

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$sql = "SELECT username, full_name, email, department, position FROM users WHERE user_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $username, $full_name, $email, $department, $position);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// ดึงแผนกหลัก (parent_id IS NULL)
$main_departments = [];
$main_result = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
while ($row = mysqli_fetch_assoc($main_result)) {
    $main_departments[] = $row;
}

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
    if (empty($full_name)) {
        $full_name_err = "กรุณากรอกชื่อ-นามสกุล";
    }
    
    // Validate email
    if (empty($email)) {
        $email_err = "กรุณากรอกอีเมล";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "รูปแบบอีเมลไม่ถูกต้อง";
    } else {
        // ตรวจสอบอีเมลซ้ำ (ยกเว้นของตัวเอง)
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
    
    // Validate department
    if (empty($department)) {
        $department_err = "กรุณากรอกแผนก/ฝ่าย";
    }
    
    // Validate position
    if (empty($position)) {
        $position_err = "กรุณากรอกตำแหน่ง";
    }
    
    // Validate password (ถ้ามีการกรอก)
    if (!empty($password)) {
        if (
            strlen($password) < 10 ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[\W_]/', $password)
        ) {
            $password_err = "รหัสผ่านต้องมีอย่างน้อย 10 ตัว และประกอบด้วยตัวพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และอักขระพิเศษ";
        } elseif ($password !== $confirm_password) {
            $confirm_password_err = "รหัสผ่านไม่ตรงกัน";
        }
    }
    
    // ถ้าไม่มี error
    if (empty($username_err) && empty($full_name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($department_err) && empty($position_err)) {
        // อัปเดตข้อมูล
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            // ถ้ามีการเปลี่ยนรหัสผ่าน ให้รีเซ็ต force_password_change = 0 (ยกเว้น admin edit user อื่น)
            if (!$is_admin_edit || $_SESSION['role'] !== 'admin') {
                $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, department = ?, position = ?, password_hash = ?, force_password_change = 0 WHERE user_id = ?";
            } else {
                $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, department = ?, position = ?, password_hash = ? WHERE user_id = ?";
            }
            if ($stmt = mysqli_prepare($conn, $sql)) {
                if (!$is_admin_edit || $_SESSION['role'] !== 'admin') {
                    mysqli_stmt_bind_param($stmt, "ssssssi", $username, $full_name, $email, $department, $position, $password_hash, $user_id);
                } else {
                    mysqli_stmt_bind_param($stmt, "ssssssi", $username, $full_name, $email, $department, $position, $password_hash, $user_id);
                }
                if (mysqli_stmt_execute($stmt)) {
                    $success = true;
                    // sync session ถ้าเป็น user ตัวเอง
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
    <!-- Google Fonts for Thai (Prompt & Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600&family=Kanit:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body, .main-content, .form-label, .form-control, .btn, .nav-link, .alert, .profile-header h2, .profile-header p {
            font-family: 'Prompt', 'Kanit', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    Swal.fire({
        title: 'โปรดกรอกข้อมูลตามความจริง',
        html: 'เพื่อความถูกต้องของข้อมูลและการติดต่อ โปรดกรอกข้อมูลให้ครบถ้วนและเป็นความจริงทุกประการ<br>มิฉะนั้นระบบจะไม่สามารถดำเนินการขั้นต่อไปได้',
        icon: 'info',
        confirmButtonText: 'รับทราบ',
        heightAuto: false
    });
});
</script>
<div class="main-content">
    <div class="container" style="max-width: 700px;">
        <div class="profile-header mb-4">
            <h2>แก้ไขโปรไฟล์</h2>
            <p class="text-muted">ปรับปรุงข้อมูลส่วนตัวของคุณ</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                บันทึกข้อมูลสำเร็จ!
            </div>
        <?php endif; ?>
        
        <?php if (!empty($update_err)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $update_err; ?>
            </div>
        <?php endif; ?>
        
        <form action="profile.php<?php echo $is_admin_edit ? '?id=' . $user_id : ''; ?>" method="post" class="mb-3">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                    <input type="text" class="form-control <?php echo !empty($username_err) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required <?php echo ($is_admin_edit && $user_id == 1) ? 'readonly' : ''; ?>>
                    <div class="invalid-feedback"><?php echo $username_err; ?></div>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control <?php echo !empty($full_name_err) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    <div class="invalid-feedback"><?php echo $full_name_err; ?></div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" class="form-control <?php echo !empty($email_err) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="position" class="form-label">ตำแหน่ง</label>
                    <input type="text" class="form-control <?php echo !empty($position_err) ? 'is-invalid' : ''; ?>" id="position" name="position" value="<?php echo htmlspecialchars($position ?? ''); ?>" required>
                    <div class="invalid-feedback"><?php echo $position_err; ?></div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label for="main_department" class="form-label">แผนกหลัก</label>
                    <select class="form-select" id="main_department" name="main_department" required>
                        <option value="">-- เลือกแผนกหลัก --</option>
                        <?php foreach ($main_departments as $dep): ?>
                            <option value="<?php echo $dep['department_id']; ?>" <?php if (isset($main_department) && $main_department == $dep['department_id']) echo 'selected'; ?>><?php echo htmlspecialchars($dep['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="department" class="form-label">แผนกย่อย</label>
                    <select class="form-select <?php echo !empty($department_err) ? 'is-invalid' : ''; ?>" id="department" name="department" required>
                        <option value="">-- เลือกแผนกย่อย --</option>
                    </select>
                    <div class="invalid-feedback"><?php echo $department_err; ?></div>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label for="password" class="form-label">รหัสผ่านใหม่ (ถ้าเปลี่ยน)</label>
                    <input type="password" class="form-control <?php echo !empty($password_err) ? 'is-invalid' : ''; ?>" id="password" name="password">
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" class="form-control <?php echo !empty($confirm_password_err) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                    <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success mt-3">บันทึกข้อมูล</button>
            <a href="dashboard.php" class="btn btn-link mt-3">ยกเลิก</a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainSelect = document.getElementById('main_department');
    const subSelect = document.getElementById('department');
    
    function loadSubDepartments(parentId, selected) {
        subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
        if (!parentId) return;
        
        fetch('get_departments_children.php?parent_id=' + parentId)
            .then(res => res.json())
            .then(data => {
                data.forEach(dep => {
                    const opt = document.createElement('option');
                    opt.value = dep.department_name;
                    opt.textContent = dep.department_name;
                    if (selected && selected === dep.department_name) opt.selected = true;
                    subSelect.appendChild(opt);
                });
            })
            .catch(error => {
                console.error('Error loading departments:', error);
            });
    }
    
    // โหลดแผนกย่อยเดิมถ้ามี (edit profile)
    <?php if (!empty($main_department) && !empty($department)): ?>
    loadSubDepartments('<?php echo $main_department; ?>', '<?php echo $department; ?>');
    <?php endif; ?>
    
    mainSelect.addEventListener('change', function() {
        loadSubDepartments(this.value);
    });
});
</script>
</body>
</html> 