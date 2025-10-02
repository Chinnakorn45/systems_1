<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

/* ---------------- Reusable deletion ---------------- */
function deleteRecord($link, $table, $id_column, $id, $redirect_page) {
    $sql = "DELETE FROM $table WHERE $id_column = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Error deleting record from $table: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
        header("location: $redirect_page");
        exit;
    } else {
        error_log("Error preparing delete statement for $table: " . mysqli_error($link));
        header("location: $redirect_page?error=sql_prepare_failed");
        exit;
    }
}

/* ---------------- Actions ---------------- */
if (isset($_GET['action'], $_GET['id'])) {
    if ($_GET['action'] === 'delete') {
        deleteRecord($link, 'brands', 'brand_id', intval($_GET['id']), 'brands.php');
    }
    if ($_GET['action'] === 'delete_model') {
        deleteRecord($link, 'models', 'model_id', intval($_GET['id']), 'brands.php');
    }
}

/* ---------------- Data ---------------- */
$sql_brands = "SELECT * FROM brands ORDER BY brand_id DESC";
$result_brands = mysqli_query($link, $sql_brands);

$sql_models = "SELECT m.model_id, m.model_name, b.brand_name
               FROM models m
               LEFT JOIN brands b ON m.brand_id = b.brand_id
               ORDER BY m.model_id DESC";
$result_models = mysqli_query($link, $sql_models);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการยี่ห้อและชื่อรุ่น - ระบบบันทึกคลังครุภัณฑ์</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap');

/* ====== Desktop: ล็อกทั้งหน้า แล้วให้สกรอลเฉพาะตาราง ====== */
html, body { height: 100%; }
body {
    font-family: 'Prompt', sans-serif;
    background-color: #f0f2f5;
    overflow: hidden; /* ล็อกทั้งหน้า (เดสก์ท็อป/แท็บเล็ตแนวนอน) */
}

/* Header บาง ๆ + ปุ่มกลับแบบไม่เกะกะ */
.header-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding-top: 16px; padding-bottom: 8px;
}
.back-link {
    text-decoration: none; font-size: .95rem;
    color: #6c757d;
}
.back-link:hover { color: #495057; text-decoration: underline; }

/* โครงหน้าหลักสูงเต็มจอ */
.main-container {
    min-height: 100vh;
    padding-bottom: 12px;
}

/* แถวหลักกินความสูงที่เหลือ เพื่อคุม max-height ของตารางได้ */
.row-stretch {
    height: calc(100vh - 100px); /* เผื่อพื้นที่ header บนสุด */
}

/* Card */
.section-card {
    background: #ffffff;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease;
}
.section-card:hover { transform: translateY(-2px); }

/* ส่วนหัวของการ์ด */
.card-head { flex: 0 0 auto; margin-bottom: 10px; }

/* พื้นที่สกรอล (เดสก์ท็อป) */
.scroll-area {
    flex: 1 1 auto;
    max-height: calc(100vh - 100px - 80px); /* = viewport - header - head ของการ์ด */
    overflow: auto;
    border-radius: 8px;
    -webkit-overflow-scrolling: touch;
}

/* ตาราง + sticky header */
.table { margin: 0; }
.table thead th {
    position: sticky; top: 0; z-index: 2;
    background-color: #4CAF50; color: #fff;
    vertical-align: middle; text-align: center; border: none;
}
.table tbody td { vertical-align: middle; text-align: center; }
.table tbody tr:hover { background-color: #f5f5f5; }
.table th.text-start, .table td.text-start { text-align: start !important; }
.table-fixed-layout { table-layout: fixed; width: 100%; }

/* ปุ่ม */
.btn-add {
    background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
    color: #fff; border: none; padding: 10px 20px; border-radius: 50px;
    transition: all 0.3s ease;
}
.btn-add:hover {
    background: linear-gradient(135deg, #8BC34A 0%, #4CAF50 100%);
    color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
.btn-sm { padding: .3rem .75rem; font-size: .85rem; border-radius: .3rem; }
.btn-warning { background-color: #ffc107; border-color: #ffc107; color: #212529; }
.btn-warning:hover { background-color: #e0a800; border-color: #d39e00; }
.btn-danger { background-color: #dc3545; border-color: #dc3545; }
.btn-danger:hover { background-color: #c82333; border-color: #bd2130; }

/* ให้คอลัมน์สูงเต็มและไม่ดันกัน */
.col-md-6 { display: flex; flex-direction: column; height: 100%; min-height: 0; }

/* Scrollbar ให้เรียบร้อย */
.scroll-area::-webkit-scrollbar { width: 10px; height: 10px; }
.scroll-area::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 8px; }
.scroll-area::-webkit-scrollbar-track { background: #f0f0f0; }

/* ====== มือถือ (<= 767.98px): ให้เลื่อนทั้งหน้า และยกเลิกสกรอลในกล่อง ====== */
@media (max-width: 767.98px) {
    body { overflow: auto; }              /* ปล่อยให้เพจเลื่อนตามปกติ */
    .row-stretch { height: auto; }
    .section-card { height: auto; }
    .scroll-area {
        max-height: none;                 /* ยกเลิกสกรอลซ้อน */
        overflow: visible;                /* ให้คอนเทนต์ไหลตามเพจ */
    }
    .header-bar { padding-top: 12px; padding-bottom: 0; }
}

/* ====== แท็บเล็ตแนวตั้ง (768 - 991.98px): เผื่อพื้นที่มากขึ้น ====== */
@media (min-width: 768px) and (max-width: 991.98px) {
    .row-stretch { height: calc(100vh - 110px); }
    .scroll-area { max-height: calc(100vh - 110px - 80px); }
}
</style>
</head>
<body>
<div class="container main-container">
    <!-- Header บาง ๆ พร้อมปุ่มกลับไม่เกะกะ -->
    <div class="header-bar">
        <a href="items.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> กลับหน้าหลักครุภัณฑ์</a>
        <div class="text-muted small">จัดการยี่ห้อและชื่อรุ่น</div>
    </div>

    <!-- แถวหลัก: เดสก์ท็อปสองคอลัมน์ / มือถือจะเรียงลง -->
    <div class="row row-stretch g-3">
        <!-- Brands -->
        <div class="col-md-6">
            <div class="section-card">
                <div class="card-head d-flex justify-content-between align-items-center">
                    <h2 class="h6 text-primary mb-0"><i class="fas fa-trademark me-2"></i> จัดการยี่ห้อ</h2>
                    <a href="brand_form.php" class="btn btn-add"><i class="fas fa-plus me-1"></i> เพิ่มยี่ห้อ</a>
                </div>

                <div class="scroll-area">
                    <table class="table table-bordered table-hover align-middle mb-0 table-fixed-layout">
                        <thead>
                            <tr>
                                <th style="width: 65%;" class="text-start">ชื่อยี่ห้อ</th>
                                <th style="width: 35%;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_brands && mysqli_num_rows($result_brands) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result_brands)): ?>
                                    <tr>
                                        <td class="text-start"><?php echo htmlspecialchars($row['brand_name']); ?></td>
                                        <td>
                                            <a href="brand_form.php?id=<?php echo $row['brand_id']; ?>" class="btn btn-sm btn-warning me-1" title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="brands.php?action=delete&id=<?php echo $row['brand_id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('ยืนยันการลบยี่ห้อ <?php echo htmlspecialchars($row['brand_name']); ?>? การลบยี่ห้ออาจส่งผลต่อข้อมูลรุ่นที่เกี่ยวข้อง');"
                                               title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted">ไม่พบข้อมูลยี่ห้อ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Models -->
        <div class="col-md-6">
            <div class="section-card">
                <div class="card-head d-flex justify-content-between align-items-center">
                    <h2 class="h6 text-primary mb-0"><i class="fas fa-box me-2"></i> จัดการชื่อรุ่น</h2>
                    <a href="model_form.php" class="btn btn-add"><i class="fas fa-plus me-1"></i> เพิ่มชื่อรุ่น</a>
                </div>

                <div class="scroll-area">
                    <table class="table table-bordered table-hover align-middle mb-0 table-fixed-layout">
                        <thead>
                            <tr>
                                <th style="width: 40%;" class="text-start">ชื่อรุ่น</th>
                                <th style="width: 30%;">ยี่ห้อ</th>
                                <th style="width: 30%;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_models && mysqli_num_rows($result_models) > 0): ?>
                                <?php while ($model = mysqli_fetch_assoc($result_models)): ?>
                                    <tr>
                                        <td class="text-start"><?php echo htmlspecialchars($model['model_name']); ?></td>
                                        <td><?php echo htmlspecialchars($model['brand_name'] ?: '-'); ?></td>
                                        <td>
                                            <a href="model_form.php?id=<?php echo $model['model_id']; ?>" class="btn btn-sm btn-warning me-1" title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="brands.php?action=delete_model&id=<?php echo $model['model_id']; ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('ยืนยันการลบชื่อรุ่น <?php echo htmlspecialchars($model['model_name']); ?>? การลบชื่อรุ่นอาจส่งผลต่อข้อมูลครุภัณฑ์ที่เกี่ยวข้อง');"
                                                title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted">ไม่พบข้อมูลชื่อรุ่น</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
