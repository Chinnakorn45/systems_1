<?php

$host = 'localhost';
$user = 'root';
$pass = ''; // ใส่รหัสผ่านถ้ามี
$db = 'borrowing_db'; // แก้ไขตรงนี้
$link = new mysqli($host, $user, $pass, $db);
// ...
if ($link->connect_error) die('Database connection failed: ' . $link->connect_error);

mysqli_set_charset($link, 'utf8mb4');

if (isset($_POST['add_type'])) {
    $type_name = $_POST['type_name'] ?? '';
    $service_status = $_POST['service_status'] ?? '';
    $description = trim($_POST['description'] ?? '');
    if ($type_name && $service_status) {
        // ใช้ NOW() ใน SQL สำหรับ created_at และ updated_at (ถ้ามีคอลัมน์ created_at)
        $stmt = $link->prepare("INSERT INTO type_service_clinic (type_name, service_status, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $type_name, $service_status, $description);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: type_service_clinic.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // เปลี่ยนมาใช้ Prepared Statement เพื่อความปลอดภัย
    $stmt = $link->prepare("DELETE FROM type_service_clinic WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: type_service_clinic.php");
    exit;
}

/* ================= EDIT ================= */
if (isset($_POST['edit_type'])) {
    $id = intval($_POST['edit_id']);
    $type_name = $_POST['edit_type_name'] ?? '';
    $service_status = $_POST['edit_service_status'] ?? '';
    $description = trim($_POST['edit_description'] ?? '');
    if ($type_name && $service_status) {
        $stmt = $link->prepare("UPDATE type_service_clinic SET type_name=?, service_status=?, description=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssi", $type_name, $service_status, $description, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: type_service_clinic.php");
    exit;
}

/* ================= LOAD DATA ================= */
// เปลี่ยนการดึงข้อมูลเพื่อดึง created_at มาแสดงด้วย (ถ้ามี)
$result = mysqli_query($link, "SELECT id, type_name, service_status, description, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') as updated_at_fmt FROM type_service_clinic ORDER BY type_name, service_status");
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>จัดการประเภทบริการคลินิก</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
<style>
body { background-color: #f8f9fa; font-family: 'Prompt', sans-serif; }
.card { border-radius: 1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
th, td { vertical-align: middle; }
.btn { border-radius: 8px; }
.btn-success { background: linear-gradient(90deg,#198754,#28a745); border: none; }
.btn-warning { background: linear-gradient(90deg,#ffb300,#ff9800); border: none; color:#fff; }
.btn-danger { background: linear-gradient(90deg,#dc3545,#b71c1c); border: none; }

/* ปรับปรุงฟอร์มสำหรับมือถือ */
@media (max-width: 767.98px) {
    .card-header h5 {
        font-size: 1.1rem;
    }
    .col-md-4 {
        margin-bottom: 0.5rem; /* เพิ่มระยะห่างระหว่างฟอร์มบนมือถือ */
    }
    .col-12.text-end {
        text-align: left !important; /* จัดปุ่ม "เพิ่มข้อมูล" ชิดซ้ายบนมือถือ */
    }
}
</style>
</head>
<body>

<div class="container py-3 py-md-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <h5 class="mb-2 mb-md-0"><i class="fas fa-clinic-medical me-2"></i>จัดการประเภทบริการคลินิก (Clinic / Non-Clinic)</h5>
            <a href="users.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> กลับ</a>
        </div>
        <div class="card-body">

            <form method="post" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">ประเภทหน่วยงาน</label>
                    <select name="type_name" class="form-select" required>
                        <option value="">-- เลือกประเภท --</option>
                        <option value="Clinic">Clinic</option>
                        <option value="Non Clinic">Non Clinic</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">สถานะบริการ</label>
                    <select name="service_status" class="form-select" required>
                        <option value="">-- เลือกสถานะบริการ --</option>
                        <option value="บริการ">บริการ</option>
                        <option value="ไม่บริการ">ไม่บริการ</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">รายละเอียดเพิ่มเติม</label>
                    <input type="text" name="description" class="form-control" placeholder="ระบุรายละเอียด (ถ้ามี)">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="add_type" class="btn btn-success w-100">
                        <i class="fas fa-plus"></i> เพิ่มข้อมูล
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-primary">
                        <tr>
                            <th style="min-width:50px;">#</th>
                            <th style="min-width:120px;">ประเภทหน่วยงาน</th>
                            <th style="min-width:120px;">สถานะบริการ</th>
                            <th style="min-width:200px;">รายละเอียด</th>
                            <th style="min-width:150px;">วันที่ปรับปรุง</th>
                            <th style="min-width:120px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    if ($result && mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $i++ ?></td>

                            <?php if (isset($_GET['edit']) && $_GET['edit'] == $row['id']): ?>
                            <form method="post">
                                <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">

                                <td>
                                    <select name="edit_type_name" class="form-select form-select-sm" required>
                                        <option value="">-- เลือกประเภท --</option>
                                        <option value="Clinic" <?= $row['type_name']=='Clinic'?'selected':'' ?>>Clinic</option>
                                        <option value="Non Clinic" <?= $row['type_name']=='Non Clinic'?'selected':'' ?>>Non Clinic</option>
                                    </select>
                                </td>

                                <td>
                                    <select name="edit_service_status" class="form-select form-select-sm" required>
                                        <option value="">-- เลือกสถานะบริการ --</option>
                                        <option value="บริการ" <?= $row['service_status']=='บริการ'?'selected':'' ?>>บริการ</option>
                                        <option value="ไม่บริการ" <?= $row['service_status']=='ไม่บริการ'?'selected':'' ?>>ไม่บริการ</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text" name="edit_description" class="form-control form-control-sm" 
                                        value="<?= htmlspecialchars($row['description']) ?>" placeholder="รายละเอียดเพิ่มเติม">
                                </td>

                                <td><?= htmlspecialchars($row['updated_at_fmt']) ?></td>

                                <td>
                                    <button type="submit" name="edit_type" class="btn btn-sm btn-primary">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <a href="type_service_clinic.php" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            </form>

                            <?php else: ?>
                            <td><?= htmlspecialchars($row['type_name']) ?></td>
                            <td><?= htmlspecialchars($row['service_status']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= htmlspecialchars($row['updated_at_fmt']) ?></td>
                            <td>
                                <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning mb-1 mb-md-0"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบรายการนี้?');"><i class="fas fa-trash"></i></a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="6" class="text-muted">ยังไม่มีข้อมูล</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>