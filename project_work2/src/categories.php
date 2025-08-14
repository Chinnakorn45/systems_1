<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ลบหมวดหมู่ (ถ้ามี action=delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    $sql = "DELETE FROM categories WHERE category_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("location: categories.php");
        exit;
    }
}

// ดึงข้อมูลหมวดหมู่
$sql = "SELECT * FROM categories ORDER BY category_id DESC";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 700px; margin-top: 40px; }
        .table thead th { background: #667eea; color: #fff; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .btn-add:hover { background: #764ba2; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-layer-group"></i> จัดการหมวดหมู่</h2>
            <a href="category_form.php" class="btn btn-add"><i class="fas fa-plus"></i> เพิ่มหมวดหมู่</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>ชื่อหมวดหมู่</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td>
                            <a href="category_form.php?id=<?php echo $row['category_id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                            <a href="categories.php?action=delete&id=<?php echo $row['category_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?\nหากลบหมวดหมู่นี้ ครุภัณฑ์ที่เกี่ยวข้องจะไม่แสดงหมวดหมู่');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <a href="items.php" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 