<?php
require_once 'config.php';

$username = $password = $confirm_password = $full_name = $email = $role = $department = $position = '';
$username_err = $password_err = $confirm_password_err = $full_name_err = $email_err = $register_err = $role_err = $department_err = $position_err = '';
$success = false;

// ดึงแผนกหลัก (parent_id IS NULL)
$main_departments = [];
$main_result = mysqli_query($link, "SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
while ($row = mysqli_fetch_assoc($main_result)) {
    $main_departments[] = $row;
}

// ดึงรายชื่อแผนก/ฝ่าย
$department_options = [];
$dep_result = mysqli_query($link, "SELECT department_name FROM departments ORDER BY department_name");
while ($row = mysqli_fetch_assoc($dep_result)) {
    $department_options[] = $row['department_name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $department = trim($_POST["department"]);
    $position = trim($_POST["position"]);
    $role = isset($_POST['role']) ? $_POST['role'] : '';

    // Validate username
    if (empty($username)) {
        $username_err = "กรุณากรอกชื่อผู้ใช้";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $username_err = "ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, A-Z, 0-9, _ เท่านั้น";
    } else {
        $sql = "SELECT user_id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $username_err = "ชื่อผู้ใช้นี้ถูกใช้แล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate email
    if (empty($email)) {
        $email_err = "กรุณากรอกอีเมล";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "รูปแบบอีเมลไม่ถูกต้อง";
    } else {
        $sql = "SELECT user_id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $email_err = "อีเมลนี้ถูกใช้แล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate full name
    if (empty($full_name)) {
        $full_name_err = "กรุณากรอกชื่อ-นามสกุล";
    }

    // Validate department
    if (empty($department)) {
        $department_err = "กรุณากรอกแผนก/ฝ่าย";
    }

    // Validate position
    if (empty($position)) {
        $position_err = "กรุณากรอกตำแหน่ง";
    }

    // Validate password
    if (empty($password)) {
        $password_err = "กรุณากรอกรหัสผ่าน";
    } elseif (strlen($password) < 6) {
        $password_err = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    }

    // Validate confirm password
    if (empty($confirm_password)) {
        $confirm_password_err = "กรุณายืนยันรหัสผ่าน";
    } elseif ($password !== $confirm_password) {
        $confirm_password_err = "รหัสผ่านไม่ตรงกัน";
    }

    // Validate role
    if (empty($role) || !in_array($role, ['admin', 'staff', 'procurement'])) {
        $role_err = "กรุณาเลือกบทบาท";
    }

    // ถ้าไม่มี error
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($full_name_err) && empty($email_err) && empty($role_err) && empty($department_err) && empty($position_err)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password_hash, full_name, email, department, position, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssss", $username, $password_hash, $full_name, $email, $department, $position, $role);
            if (mysqli_stmt_execute($stmt)) {
                $success = true;
            } else {
                $register_err = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ใช้ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #e8f5e9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 10px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 32px;
            font-weight: 600;
            min-width: 120px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #8BC34A 0%, #4CAF50 100%);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: #fff;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 32px;
            font-weight: 600;
            min-width: 120px;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
            color: #fff;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>เพิ่มผู้ใช้</h2>
            <p class="text-muted">กรอกข้อมูลเพื่อเพิ่มผู้ใช้ใหม่</p>
        </div>
        <form action="add_user.php" method="post">
                <div class="mb-2">
                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                    <input type="text" class="form-control <?php echo !empty($username_err) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    <div class="invalid-feedback"><?php echo $username_err; ?></div>
                </div>
                <div class="mb-2">
                    <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control <?php echo !empty($full_name_err) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    <div class="invalid-feedback"><?php echo $full_name_err; ?></div>
                </div>
                <div class="mb-2">
                    <label for="main_department" class="form-label">แผนกหลัก</label>
                    <select class="form-select" id="main_department" name="main_department" required>
                        <option value="">-- เลือกแผนกหลัก --</option>
                        <?php foreach ($main_departments as $dep): ?>
                            <option value="<?php echo $dep['department_id']; ?>" <?php if (isset($main_department) && $main_department == $dep['department_id']) echo 'selected'; ?>><?php echo htmlspecialchars($dep['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="department" class="form-label">แผนกย่อย</label>
                    <select class="form-select <?php echo !empty($department_err) ? 'is-invalid' : ''; ?>" id="department" name="department" required>
                        <option value="">-- เลือกแผนกย่อย --</option>
                    </select>
                    <div class="invalid-feedback"><?php echo $department_err; ?></div>
                </div>
                <div class="mb-2">
                    <label for="position" class="form-label">ตำแหน่ง</label>
                    <input type="text" class="form-control <?php echo !empty($position_err) ? 'is-invalid' : ''; ?>" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>" required>
                    <div class="invalid-feedback"><?php echo $position_err; ?></div>
                </div>
                <div class="mb-2">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" class="form-control <?php echo !empty($email_err) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                </div>
                <div class="mb-2">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control <?php echo !empty($password_err) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" class="form-control <?php echo !empty($confirm_password_err) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                    <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                </div>
                <div class="mb-2">
                    <label for="role" class="form-label">บทบาท</label>
                    <select class="form-select <?php echo !empty($role_err) ? 'is-invalid' : ''; ?>" id="role" name="role" required>
                        <option value="">-- เลือกบทบาท --</option>
                        <option value="staff" <?php if ($role == 'staff') echo 'selected'; ?>>เจ้าหน้าที่ (Staff)</option>
                        <option value="procurement" <?php if ($role == 'procurement') echo 'selected'; ?>>เจ้าหน้าที่พัสดุ (Procurement)</option>
                        <option value="admin" <?php if ($role == 'admin') echo 'selected'; ?>>ผู้ดูแลระบบ (Admin)</option>
                    </select>
                    <div class="invalid-feedback"><?php echo $role_err; ?></div>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    <button type="submit" class="btn btn-register me-2">เพิ่มผู้ใช้</button>
                    <a href="users.php" class="btn btn-cancel">ยกเลิก</a>
                </div>
            </form>
        </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainSelect = document.getElementById('main_department');
        const subSelect = document.getElementById('department');
        mainSelect.addEventListener('change', function() {
            const parentId = this.value;
            subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
            if (!parentId) return;
            fetch('get_departments_children.php?parent_id=' + parentId)
                .then(res => res.json())
                .then(data => {
                    data.forEach(dep => {
                        const opt = document.createElement('option');
                        opt.value = dep.department_name;
                        opt.textContent = dep.department_name;
                        subSelect.appendChild(opt);
                    });
                });
        });

        // SweetAlert2 popup for success/error
        <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'เพิ่มผู้ใช้สำเร็จ!',
            showConfirmButton: false,
            timer: 1800
        }).then(() => {
            window.location = 'users.php';
        });
        setTimeout(function(){ window.location = 'users.php'; }, 1850);
        <?php elseif (!empty($register_err)): ?>
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: '<?php echo addslashes($register_err); ?>',
            confirmButtonText: 'ตกลง'
        });
        <?php endif; ?>
    });
    </script>
</body>
</html> 