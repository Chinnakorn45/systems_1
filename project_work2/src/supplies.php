<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

if ($_SESSION["role"] === 'staff') {
    header('Location: borrowings.php');
    exit;
}

// ลบวัสดุสำนักงาน (ถ้ามี action=delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $supply_id = intval($_GET['id']);
    $sql = "DELETE FROM office_supplies WHERE supply_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $supply_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("location: supplies.php");
        exit;
    }
}

// ดึงข้อมูลวัสดุสำนักงาน
$sql = "SELECT * FROM office_supplies ORDER BY supply_id DESC";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>วัสดุสำนักงาน - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Thai (Prompt & Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="common-ui.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Mobile Navbar (Hamburger) -->
        <nav class="navbar navbar-light bg-light d-md-none mb-3">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="navbar-brand mb-0 h1">วัสดุสำนักงาน</span>
            </div>
        </nav>

        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-0">
            <div class="main-content position-relative mt-4 mt-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <h2 class="mb-0"><i class="fas fa-paperclip me-2"></i>วัสดุสำนักงาน</h2>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="supplySearch" class="form-control" style="max-width:350px;" placeholder="ค้นหาวัสดุสำนักงาน...">
                        <?php if ($_SESSION["role"] === "admin"): ?>
                            <a href="supply_form.php" class="btn btn-add"><i class="fas fa-plus"></i> เพิ่มวัสดุสำนักงาน</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <!-- เลื่อนเฉพาะตาราง + thead sticky -->
                        <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="sticky-top bg-white" style="z-index: 1020;">
                                    <tr>
                                        <th>ชื่อวัสดุ</th>
                                        <th>รายละเอียด</th>
                                        <th>หน่วยนับ</th>
                                        <th>จำนวนคงเหลือ</th>
                                        <th>สต็อกขั้นต่ำ</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $row_count = 0;
                                    while ($row = mysqli_fetch_assoc($result)): 
                                        $row_count++;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['supply_name']); ?></td>
                                        <td><?= htmlspecialchars($row['description']); ?></td>
                                        <td><?= htmlspecialchars($row['unit']); ?></td>
                                        <td><?= (int)$row['current_stock']; ?></td>
                                        <td><?= (int)$row['min_stock_level']; ?></td>
                                        <td>
                                            <?php if ($_SESSION["role"] === "admin"): ?>
                                                <a href="supply_form.php?id=<?= $row['supply_id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                <a href="supplies.php?action=delete&id=<?= $row['supply_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?');"><i class="fas fa-trash"></i></a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled title="เฉพาะผู้ดูแลระบบเท่านั้น"><i class="fas fa-ban"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if($row_count == 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-boxes fa-3x mb-3"></i>
                                                    <h5>ไม่พบข้อมูลวัสดุสำนักงาน</h5>
                                                    <p class="mb-0">ยังไม่มีรายการวัสดุสำนักงานในระบบ</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div><!-- /.table-responsive -->
                    </div><!-- /.card-body -->
                </div><!-- /.card -->

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ค้นหาแบบเรียลไทม์ในตารางวัสดุสำนักงาน
document.getElementById('supplySearch').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const show = text.includes(filter);
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    // แสดง/ซ่อนแถว "ไม่พบผลลัพธ์"
    let noResultsRow = document.querySelector('.no-results-row');
    if (visibleCount === 0 && !noResultsRow) {
        const tbody = document.querySelector('table tbody');
        const newRow = document.createElement('tr');
        newRow.className = 'no-results-row';
        newRow.innerHTML = `
            <td colspan="6" class="text-center py-4">
                <div class="text-muted">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h5>ไม่พบผลลัพธ์</h5>
                    <p class="mb-0">ลองค้นหาด้วยคำอื่น</p>
                </div>
            </td>`;
        tbody.appendChild(newRow);
    } else if (visibleCount > 0 && noResultsRow) {
        noResultsRow.remove();
    }
});

// คลิกหัวตารางให้เลื่อนกลับบนสุดของกรอบตาราง
document.querySelectorAll('table thead th').forEach(th => {
    th.style.cursor = 'pointer';
    th.title = 'คลิกเพื่อเลื่อนขึ้นด้านบน';
    th.addEventListener('click', function() {
        const container = document.querySelector('.table-responsive');
        container.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>

<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
    <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
    | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
    | © 2025
</footer>
</body>
</html>

