<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Reusable function to handle deletion
function deleteRecord($link, $table, $id_column, $id, $redirect_page) {
    // Check for related data before deletion to prevent orphan records
    // In a real-world scenario, this should be handled by ON DELETE RESTRICT foreign key constraints
    // For this example, we will proceed with deletion.
    
    $sql = "DELETE FROM $table WHERE $id_column = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            // Deletion successful
        } else {
            // Log the error
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

// Handle Brand Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $brand_id = intval($_GET['id']);
    deleteRecord($link, 'brands', 'brand_id', $brand_id, 'brands.php');
}

// Handle Model Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_model' && isset($_GET['id'])) {
    $model_id = intval($_GET['id']);
    deleteRecord($link, 'models', 'model_id', $model_id, 'brands.php');
}

// Fetch all brands
$sql_brands = "SELECT * FROM brands ORDER BY brand_id DESC";
$result_brands = mysqli_query($link, $sql_brands);

// Fetch all models, joining with brands for the brand name
$sql_models = "SELECT m.model_id, m.model_name, b.brand_name FROM models m LEFT JOIN brands b ON m.brand_id = b.brand_id ORDER BY m.model_id DESC";
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
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f0f2f5;
        }
        .main-container {
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .section-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        .section-card:hover {
            transform: translateY(-5px);
        }
        .table thead th {
            background-color: #4CAF50; /* A pleasant green */
            color: #fff;
            vertical-align: middle;
            text-align: center;
            border: none;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .table tbody td {
            vertical-align: middle;
            text-align: center;
        }
        .table th.text-start,
        .table td.text-start {
            text-align: start !important;
        }
        .btn-add {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 50px; /* Pill-shaped button */
            transition: all 0.3s ease;
        }
        .btn-add:hover {
            background: linear-gradient(135deg, #8BC34A 0%, #4CAF50 100%);
            color: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .btn-sm {
            padding: .3rem .75rem;
            font-size: .85rem;
            border-radius: .3rem;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .table-responsive {
            flex-grow: 1;
        }
        .flex-grow-1 {
            flex-grow: 1;
        }
        .d-flex.flex-column {
            display: flex;
            flex-direction: column;
        }
        .table-fixed-layout {
            table-layout: fixed;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="row align-items-stretch">
            
            <div class="col-md-6 d-flex flex-column">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h4 text-primary"><i class="fas fa-trademark me-2"></i> จัดการยี่ห้อ</h2>
                        <a href="brand_form.php" class="btn btn-add"><i class="fas fa-plus me-1"></i> เพิ่มยี่ห้อ</a>
                    </div>
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-bordered table-hover align-middle mb-0 table-fixed-layout">
                            <thead>
                                <tr>
                                    <th style="width: 65%;" class="text-start">ชื่อยี่ห้อ</th>
                                    <th style="width: 35%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result_brands) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result_brands)): ?>
                                    <tr>
                                        <td class="text-start"><?php echo htmlspecialchars($row['brand_name']); ?></td>
                                        <td>
                                            <a href="brand_form.php?id=<?php echo $row['brand_id']; ?>" class="btn btn-sm btn-warning me-1" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                            <a href="brands.php?action=delete&id=<?php echo $row['brand_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบยี่ห้อ <?php echo htmlspecialchars($row['brand_name']); ?>? การลบยี่ห้ออาจส่งผลต่อข้อมูลรุ่นที่เกี่ยวข้อง');" title="ลบ"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">ไม่พบข้อมูลยี่ห้อ</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 d-flex flex-column">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h4 text-primary"><i class="fas fa-box me-2"></i> จัดการชื่อรุ่น</h2>
                        <a href="model_form.php" class="btn btn-add"><i class="fas fa-plus me-1"></i> เพิ่มชื่อรุ่น</a>
                    </div>
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-bordered table-hover align-middle mb-0 table-fixed-layout">
                            <thead>
                                <tr>
                                    <th style="width: 40%;" class="text-start">ชื่อรุ่น</th>
                                    <th style="width: 30%;">ยี่ห้อ</th>
                                    <th style="width: 30%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result_models) > 0): ?>
                                    <?php while ($model = mysqli_fetch_assoc($result_models)): ?>
                                    <tr>
                                        <td class="text-start"><?php echo htmlspecialchars($model['model_name']); ?></td>
                                        <td><?php echo htmlspecialchars($model['brand_name'] ?: '-'); ?></td> 
                                        <td>
                                            <a href="model_form.php?id=<?php echo $model['model_id']; ?>" class="btn btn-sm btn-warning me-1" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                            <a href="brands.php?action=delete_model&id=<?php echo $model['model_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบชื่อรุ่น <?php echo htmlspecialchars($model['model_name']); ?>? การลบชื่อรุ่นอาจส่งผลต่อข้อมูลครุภัณฑ์ที่เกี่ยวข้อง');" title="ลบ"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">ไม่พบข้อมูลชื่อรุ่น</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="items.php" class="btn btn-secondary px-4 py-2 rounded-pill">
                    <i class="fas fa-arrow-alt-circle-left me-1"></i> กลับหน้าหลักครุภัณฑ์
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>