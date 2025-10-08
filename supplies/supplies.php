<?php
// -------- Session --------
if (session_status() === PHP_SESSION_NONE) session_start();

// -------- โหลด config.php แบบยืดหยุ่น --------
$configCandidates = [
    __DIR__ . '/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/includes/config.php',
    __DIR__ . '/../includes/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/systems_1/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/systems_1/includes/config.php',
];

$configLoaded = false;
foreach ($configCandidates as $cfg) {
    if ($cfg && is_file($cfg)) {
        require_once $cfg;
        $configLoaded = true;
        break;
    }
}
if (!$configLoaded) {
    http_response_code(500);
    die("ไม่พบไฟล์ config.php\nโปรดตรวจสอบพาธหรือแก้ใน supplies.php");
}

// -------- Helpers: asset URL (กันแคชค้าง) --------
function asset_url(string $rel): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $url  = ($base === '/' ? '' : $base) . '/' . ltrim($rel, '/\\');
    $fs   = __DIR__ . DIRECTORY_SEPARATOR . ltrim($rel, '/\\');
    if (is_file($fs)) {
        $ver = filemtime($fs);
        $url .= (strpos($url, '?') !== false ? '&' : '?') . 'v=' . $ver;
    }
    return $url;
}

// -------- ตรวจสิทธิ์ผู้ใช้ --------
if (!isset($_SESSION["user_id"])) {
    header("location: login.php"); exit;
}

$current_page = 'supplies';

// staff เข้าหน้านี้ไม่ได้ → ส่งไป borrowings
if (isset($_SESSION["role"]) && $_SESSION["role"] === 'staff') {
    header('Location: borrowings.php'); exit;
}

// -------- การลบ (เฉพาะ admin) --------
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'
) {
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

// -------- ดึงข้อมูลวัสดุสำนักงาน --------
$sql = "SELECT * FROM office_supplies ORDER BY supply_id DESC";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>วัสดุสำนักงาน - ระบบบันทึกคลังครุภัณฑ์</title>

    <!-- Vendor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ✅ Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">

    <!-- Project CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('common-ui.css')) ?>">
</head>
<body class="table-lock">
<div class="container-fluid">
    <div class="row">
        <!-- Mobile Navbar (Hamburger) -->
        <nav class="navbar navbar-light bg-light d-md-none mb-3">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar"
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="navbar-brand mb-0 h1">วัสดุสำนักงาน</span>
            </div>
        </nav>

        <!-- Sidebar -->
        <?php
        $sidebarPath = __DIR__ . '/sidebar.php';
        if (!is_file($sidebarPath)) $sidebarPath = __DIR__ . '/../sidebar.php';
        if (is_file($sidebarPath)) include $sidebarPath;
        else echo '<div class="col-md-3 col-lg-2 d-none d-md-block"></div>';
        ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-0">
            <div class="main-content position-relative mt-4 mt-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h2 class="mb-1"><i class="fa-solid fa-paperclip me-2"></i>วัสดุสำนักงาน</h2>
                        <div class="section-subtitle">รายการทั้งหมด <?= number_format(mysqli_num_rows($result)); ?> รายการ</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 w-100 w-md-auto">
                        <div class="input-group" style="max-width:420px;">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="supplySearch" class="form-control" placeholder="ค้นหาวัสดุสำนักงาน...">
                        </div>
                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                            <a href="supply_form.php" class="btn btn-add"><i class="fa-solid fa-plus"></i><span class="d-none d-sm-inline ms-1">เพิ่มวัสดุ</span></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card table-card shadow-soft">
                    <div class="card-body p-0">
                        <!-- เลื่อนเฉพาะตาราง + thead sticky -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped align-middle mb-0">
                                <colgroup>
                                    <col style="width:22%">
                                    <col>
                                    <col style="width:10%">
                                    <col style="width:12%">
                                    <col style="width:12%">
                                    <col style="width:12%">
                                </colgroup>
                                <thead class="sticky-top bg-white" style="z-index: 1020;">
                                    <tr>
                                        <th>ชื่อวัสดุ</th>
                                        <th>รายละเอียด</th>
                                        <th>หน่วยนับ</th>
                                        <th class="text-end">จำนวนคงเหลือ</th>
                                        <th class="text-end">สต็อกขั้นต่ำ</th>
                                        <th class="text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $row_count = 0;
                                    while ($row = mysqli_fetch_assoc($result)): 
                                        $row_count++;
                                        $low = (int)$row['current_stock'] <= (int)$row['min_stock_level'];
                                    ?>
                                    <tr class="<?= $low ? 'table-warning' : '' ?>">
                                        <td><?= htmlspecialchars($row['supply_name']); ?></td>
                                        <td title="<?= htmlspecialchars($row['description']); ?>">
                                            <span class="d-inline-block text-truncate" style="max-width: 520px;"><?= htmlspecialchars($row['description']); ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['unit']); ?></td>
                                        <td class="text-end">
                                            <?= (int)$row['current_stock']; ?>
                                            <?= $low ? '<span class="badge bg-danger ms-2">ต่ำกว่าขั้นต่ำ</span>' : '' ?>
                                        </td>
                                        <td class="text-end"><?= (int)$row['min_stock_level']; ?></td>
                                        <td class="text-center">
                                            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                                                <a href="supply_form.php?id=<?= (int)$row['supply_id']; ?>" class="btn btn-sm btn-warning" title="แก้ไข">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="supplies.php?action=delete&id=<?= (int)$row['supply_id']; ?>" class="btn btn-sm btn-danger"
                                                   onclick="return confirm('ยืนยันการลบ?');" title="ลบ">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled title="เฉพาะผู้ดูแลระบบเท่านั้น">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if($row_count == 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fa-solid fa-boxes-stacked fa-3x mb-3"></i>
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

<!-- Vendor JS -->
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
                    <i class="fa-solid fa-magnifying-glass fa-3x mb-3"></i>
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
</body>
</html>
