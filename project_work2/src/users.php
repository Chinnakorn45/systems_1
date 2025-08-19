<?php
require_once 'config.php';
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

// ป้องกัน admin ลบตัวเอง
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $user_id = intval($_GET['id']);
    if ($user_id !== $_SESSION['user_id']) {
        $stmt = $link->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        header("location: users.php");
        exit;
    }
}

// ค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE username LIKE ? OR full_name LIKE ? OR department LIKE ? OR position LIKE ? OR role LIKE ?";
    $like = "%" . $search . "%";
    $params = [$like, $like, $like, $like, $like];
}

$sql = "SELECT * FROM users $where ORDER BY user_id DESC";
$stmt = $link->prepare($sql);
if ($where !== '') {
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="common-ui.css">
</head>
<body>

<!-- Navbar มือถือ -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
<div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
    <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">จัดการผู้ใช้</span>
</div>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 px-0">
            <div class="main-content mt-4 mt-md-5">

                <!-- ส่วนหัว -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <h2 class="mb-0"><i class="fas fa-users"></i> จัดการผู้ใช้</h2>
                    <input type="text" id="userSearch" class="form-control mb-2 mb-md-0" style="max-width:350px;" placeholder="ค้นหาผู้ใช้...">
                    <div>
                        <a href="departments.php" class="btn btn-secondary me-2"><i class="fas fa-sitemap"></i> จัดการแผนก</a>
                        <a href="add_user.php" class="btn btn-add"><i class="fas fa-user-plus"></i> เพิ่มผู้ใช้</a>
                    </div>
                </div>

                <!-- ตาราง -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="sticky-top bg-white" style="z-index: 1020;">
                                    <tr>
                                        <th>ชื่อผู้ใช้</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>แผนก/ฝ่าย</th>
                                        <th>ตำแหน่ง</th>
                                        <th>บทบาท</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                                        <td><?= htmlspecialchars($row['department'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['position'] ?? '') ?></td>
                                        <td>
                                            <?php
                                                switch ($row['role']) {
                                                    case 'admin':
                                                        echo '<span class="badge bg-primary">ผู้ดูแล</span>';
                                                        break;
                                                    case 'procurement':
                                                        echo '<span class="badge bg-warning text-dark">เจ้าหน้าที่พัสดุ</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-success">เจ้าหน้าที่</span>';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="profile.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                            <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?action=delete&id=<?= $row['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบผู้ใช้นี้?');"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('userSearch').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>

<!-- Footer -->
<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
        <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
        พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
        | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
        | © 2025
</footer>

</body>
</html>
<?php
$stmt->close();
$link->close();
?>
