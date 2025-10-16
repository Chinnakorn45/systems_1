<?php
/* ================= CONFIG ================= */
$host = 'localhost';
$user = 'root';
$pass = ''; // ถ้ามีรหัสผ่าน MySQL ให้ใส่ตรงนี้
$db   = 'borrowing_db';

$link = new mysqli($host, $user, $pass, $db);
if ($link->connect_error) die("Database connection failed: " . $link->connect_error);

mysqli_set_charset($link, 'utf8mb4');
mysqli_query($link, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($link, "SET collation_connection = 'utf8mb4_unicode_ci'");

session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

/* ================= FUNCTIONS ================= */
function get_departments_tree($link, $parent_id = null, $level = 0) {
    $sql = "SELECT * FROM departments WHERE parent_id " . ($parent_id === null ? 'IS NULL' : '= ' . intval($parent_id)) . " ORDER BY department_id";
    $result = mysqli_query($link, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['level'] = $level;
        $rows[] = $row;
        $rows = array_merge($rows, get_departments_tree($link, $row['department_id'], $level + 1));
    }
    return $rows;
}

/* ================= ADD ================= */
if (isset($_POST['add_department'])) {
    $name = trim($_POST['department_name']);
    $parent_id = $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;
    $type_service_id = $_POST['type_service_id'] !== '' ? intval($_POST['type_service_id']) : null;

    if ($name !== '') {
        $stmt = mysqli_prepare($link, "INSERT INTO departments (department_name, parent_id, type_service_id) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sii", $name, $parent_id, $type_service_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: departments.php');
    exit;
}

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($link, "UPDATE departments SET parent_id = NULL WHERE parent_id = $id");
    mysqli_query($link, "DELETE FROM departments WHERE department_id = $id");
    header('Location: departments.php');
    exit;
}

/* ================= EDIT ================= */
if (isset($_POST['edit_department'])) {
    $id = intval($_POST['edit_id']);
    $name = trim($_POST['edit_name']);
    $type_service_id = $_POST['edit_type_service_id'] !== '' ? intval($_POST['edit_type_service_id']) : null;

    if ($name !== '') {
        $stmt = mysqli_prepare($link, "UPDATE departments SET department_name = ?, type_service_id = ? WHERE department_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $name, $type_service_id, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: departments.php');
    exit;
}

/* ================= LOAD DATA ================= */
$departments = get_departments_tree($link);
$all_departments = mysqli_query($link, "SELECT * FROM departments ORDER BY department_id");
$type_services = mysqli_query($link, "SELECT * FROM type_service_clinic ORDER BY type_name, service_status");

// หาแผนกย่อยที่ยังไม่กำหนดประเภทบริการ (ยกเว้นแผนกหัว parent_id IS NULL)
$missing_deps = [];
$miss_res = @mysqli_query(
    $link,
    "SELECT department_name FROM departments WHERE parent_id IS NOT NULL AND (type_service_id IS NULL OR type_service_id = 0) ORDER BY department_name"
);
if ($miss_res) {
    while ($mr = mysqli_fetch_assoc($miss_res)) {
        $missing_deps[] = $mr['department_name'];
    }
}

// ถ้าอยู่ในโหมดแก้ไข (กดปุ่มแก้ไขเปิดด้วยพารามิเตอร์ ?edit=) ให้ปิดการเด้งแจ้งเตือน
$suppress_missing_popup = isset($_GET['edit']) && $_GET['edit'] !== '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการแผนก/ฝ่าย</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #e8f5e9; font-family: 'Prompt', 'Kanit', sans-serif; }
.container { max-width: 950px; margin-top: 40px; }
.card { border-radius: 18px; box-shadow: 0 4px 24px rgba(44,62,80,0.08); border: none; }
.card-header { background: linear-gradient(90deg, #388e3c 0%, #66bb6a 100%); color: #fff; border-radius: 18px 18px 0 0; }
.form-label { color: #388e3c; font-weight: 500; }
.table { background: #fff; border-radius: 12px; overflow: hidden; }
.table th, .table td { vertical-align: middle; }
.table th { background: #c8e6c9; color: #2e7d32; }
.btn-success, .btn-warning, .btn-danger { border-radius: 8px; }
.btn-success { background: linear-gradient(90deg, #43a047 0%, #66bb6a 100%); border: none; }
.btn-warning { background: linear-gradient(90deg, #ffa726 0%, #fb8c00 100%); border: none; color: #fff; }
.btn-danger { background: linear-gradient(90deg, #e53935 0%, #d32f2f 100%); border: none; }
.tree-indent { padding-left: 2em; }
</style>
</head>
<body>
<div class="container py-4">
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-sitemap me-2"></i> จัดการแผนก/ฝ่าย</span>
            <a href="users.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าจัดการผู้ใช้</a>
        </div>
        <div class="card-body">

            <!-- ================= ADD FORM ================= -->
            <form method="post" class="row g-2 mb-4 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">ชื่อแผนก/ฝ่าย</label>
                    <input type="text" name="department_name" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">อยู่ภายใต้ (ถ้ามี)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">- ไม่มี -</option>
                        <?php mysqli_data_seek($all_departments, 0); while($d = mysqli_fetch_assoc($all_departments)): ?>
                            <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">ประเภทบริการ</label>
                    <select name="type_service_id" class="form-select" required>
                        <option value="">-- เลือกประเภทบริการ --</option>
                        <?php mysqli_data_seek($type_services, 0); while($ts = mysqli_fetch_assoc($type_services)): ?>
                            <option value="<?= $ts['id'] ?>">
                                <?= $ts['type_name'] . ' - ' . $ts['service_status'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" name="add_department" class="btn btn-success w-100"><i class="fas fa-plus"></i> เพิ่ม</button>
                </div>
            </form>

            <!-- ================= TABLE ================= -->
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ชื่อแผนก/ฝ่าย</th>
                            <th>อยู่ภายใต้</th>
                            <th>ประเภทบริการ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($departments as $dep): ?>
                        <tr>
                            <td style="padding-left: <?= $dep['level']*2 ?>em;">
                                <?php if(isset($_GET['edit']) && $_GET['edit'] == $dep['department_id']): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="edit_id" value="<?= $dep['department_id'] ?>">
                                        <input type="text" name="edit_name" value="<?= htmlspecialchars($dep['department_name']) ?>" required style="max-width:160px;display:inline-block;">
                                <?php else: ?>
                                    <?= htmlspecialchars($dep['department_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($dep['parent_id']) {
                                    $q = mysqli_query($link, "SELECT department_name FROM departments WHERE department_id = " . intval($dep['parent_id']));
                                    $p = mysqli_fetch_assoc($q);
                                    echo htmlspecialchars($p['department_name']);
                                } else echo '-';
                                ?>
                            </td>
                            <td>
                                <?php if(isset($_GET['edit']) && $_GET['edit'] == $dep['department_id']): ?>
                                    <select name="edit_type_service_id" class="form-select form-select-sm" style="width:auto;display:inline-block;">
                                        <option value="">-- เลือก --</option>
                                        <?php
                                        mysqli_data_seek($type_services, 0);
                                        while($ts = mysqli_fetch_assoc($type_services)):
                                            $selected = ($dep['type_service_id'] == $ts['id']) ? 'selected' : '';
                                            echo "<option value='{$ts['id']}' {$selected}>{$ts['type_name']} - {$ts['service_status']}</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                <?php else:
                                    if ($dep['type_service_id']) {
                                        $tq = mysqli_query($link, "SELECT type_name, service_status FROM type_service_clinic WHERE id = " . intval($dep['type_service_id']));
                                        $ts = mysqli_fetch_assoc($tq);
                                        echo htmlspecialchars($ts['type_name'] . ' - ' . $ts['service_status']);
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if(isset($_GET['edit']) && $_GET['edit'] == $dep['department_id']): ?>
                                    <button type="submit" name="edit_department" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                                    <a href="departments.php" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
                                    </form>
                                <?php else: ?>
                                    <a href="departments.php?edit=<?= $dep['department_id'] ?>" class="btn btn-sm btn-warning me-1"><i class="fas fa-edit"></i></a>
                                    <a href="departments.php?delete=<?= $dep['department_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?');"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ป็อปอัพแจ้งเตือนแผนกย่อยที่ยังไม่กำหนดประเภทบริการ (ยกเว้นหัวไม่นับ)
document.addEventListener('DOMContentLoaded', function(){
  const missing = <?php echo json_encode($missing_deps, JSON_UNESCAPED_UNICODE); ?>;
  const suppress = <?php echo json_encode((bool)$suppress_missing_popup); ?>;
  if (!suppress && Array.isArray(missing) && missing.length > 0) {
    const list = missing.map(n => `<li>${n}</li>`).join('');
    Swal.fire({
      icon: 'warning',
      title: 'แผนกย่อยยังไม่กำหนดประเภทบริการ',
      html: `<div style="text-align:left;max-height:260px;overflow:auto">`+
            `<p>พบ ${missing.length} แผนกย่อยที่ยังไม่ได้กำหนดประเภทบริการ:</p>`+
            `<ol style="margin-left:16px">${list}</ol>`+
            `</div>`,
      confirmButtonText: 'ตกลง'
    });
  }
});
</script>
</body>
</html>
