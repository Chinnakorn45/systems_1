<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

// ฟังก์ชันดึง tree
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

// เพิ่มแผนก
if (isset($_POST['add_department'])) {
    $name = trim($_POST['department_name']);
    $parent_id = $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;
    if ($name !== '') {
        $stmt = mysqli_prepare($link, "INSERT INTO departments (department_name, parent_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $name, $parent_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: departments.php');
    exit;
}
// ลบแผนก
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // set parent_id ของลูกเป็น null ก่อน
    mysqli_query($link, "UPDATE departments SET parent_id = NULL WHERE parent_id = $id");
    mysqli_query($link, "DELETE FROM departments WHERE department_id = $id");
    header('Location: departments.php');
    exit;
}
// แก้ไขแผนก
if (isset($_POST['edit_department'])) {
    $id = intval($_POST['edit_id']);
    $name = trim($_POST['edit_name']);
    if ($name !== '') {
        $stmt = mysqli_prepare($link, "UPDATE departments SET department_name = ? WHERE department_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $name, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: departments.php');
    exit;
}
$departments = get_departments_tree($link);
$all_departments = mysqli_query($link, "SELECT * FROM departments ORDER BY department_id");
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
        body { background: #e8f5e9; font-family: 'Prompt', 'Kanit', 'Arial', sans-serif; }
        .container { max-width: 800px; margin-top: 40px; }
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
        .btn-secondary { border-radius: 8px; }
        .tree-indent { padding-left: 2em; }
        @media (max-width: 600px) {
            .container { padding: 0 4px; }
            .card { padding: 0; }
            .table th, .table td { font-size: 0.95em; }
        }
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
            <form method="post" class="row g-2 mb-4 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">ชื่อแผนก/ฝ่าย</label>
                    <input type="text" name="department_name" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">อยู่ภายใต้ (ถ้ามี)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">- ไม่มี -</option>
                        <?php mysqli_data_seek($all_departments, 0); while($d = mysqli_fetch_assoc($all_departments)): ?>
                            <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="add_department" class="btn btn-success w-100"><i class="fas fa-plus"></i> เพิ่ม</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ชื่อแผนก/ฝ่าย</th>
                            <th>อยู่ภายใต้</th>
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
                                        <button type="submit" name="edit_department" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                                        <a href="departments.php" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
                                    </form>
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
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <a href="departments.php?edit=<?= $dep['department_id'] ?>" class="btn btn-sm btn-warning me-1"><i class="fas fa-edit"></i></a>
                                <a href="departments.php?delete=<?= $dep['department_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?');"><i class="fas fa-trash-alt"></i></a>
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
</body>
</html>