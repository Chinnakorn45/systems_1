<?php
session_start();
require_once 'db.php';
function status_badge($status) {
    $map = [
        'received' => ['รับเรื่อง', 'info'],
        'evaluate_it' => ['ประเมิน (โดย IT)', 'warning'],
        'evaluate_repairable' => ['ประเมิน: ซ่อมได้โดย IT', 'success'],
        'evaluate_external' => ['ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'evaluate_disposal' => ['ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย', 'dark'],
        'external_repair' => ['ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'procurement_managing' => ['พัสดุจัดการส่งซ่อม', 'info'],
        'procurement_returned' => ['พัสดุซ่อมเสร็จส่งคืน IT', 'success'],
        'repair_completed' => ['ซ่อมเสร็จ', 'success'],
        'waiting_delivery' => ['รอส่งมอบ', 'warning'],
        'delivered' => ['ส่งมอบ', 'success'],
        'cancelled' => ['ยกเลิก', 'secondary'],
        // สถานะเก่า (เพื่อความเข้ากันได้)
        'pending' => ['รอดำเนินการ', 'secondary'],
        'in_progress' => ['กำลังซ่อม', 'info'],
        'done' => ['ซ่อมเสร็จ', 'success'],
        // สถานะค่าว่าง
        '' => ['รอดำเนินการ', 'secondary'],
    ];
    if (!isset($map[$status])) return '<span class="badge bg-secondary">ไม่ระบุสถานะ</span>';
    [$th, $color] = $map[$status];
    return "<span class='badge bg-$color'>$th</span>";
}
function thaidate($date, $format = 'd/m/Y H:i') {
    $ts = strtotime($date);
    $result = date($format, $ts);
    $year_th = date('Y', $ts) + 543;
    return str_replace(date('Y', $ts), $year_th, $result);
}
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['procurement', 'admin'])) {
    header('Location: login.php'); exit;
}
// ฟิลเตอร์
$where = [];
if (!empty($_GET['status'])) {
    // แก้ไขการฟิลเตอร์ให้ใช้สถานะล่าสุดจาก repair_logs
    $status_filter = $conn->real_escape_string($_GET['status']);
    $where[] = "COALESCE(latest.status, r.status) = '$status_filter'";
}
if (!empty($_GET['date'])) $where[] = "DATE(r.created_at)='".$conn->real_escape_string($_GET['date'])."'";
if (!empty($_GET['category'])) $where[] = "i.category_id='".$conn->real_escape_string($_GET['category'])."'";
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';

// แก้ไข SQL ให้ดึงสถานะล่าสุดจาก repair_logs
$sql = "SELECT r.*, u_reported.full_name AS reported_by_name, u_assigned.full_name AS assigned_to_name,
        COALESCE(latest.status, r.status) as current_status
        FROM repairs r
        LEFT JOIN users u_reported ON r.reported_by = u_reported.user_id
        LEFT JOIN users u_assigned ON r.assigned_to = u_assigned.user_id
        LEFT JOIN (
            SELECT repair_id, status 
            FROM repair_logs 
            WHERE (repair_id, updated_at) IN (
                SELECT repair_id, MAX(updated_at) 
                FROM repair_logs 
                GROUP BY repair_id
            )
        ) latest ON r.repair_id = latest.repair_id
        $where_sql ORDER BY r.created_at DESC";

$repairs = $conn->query($sql);
// get categories
$cats = $conn->query("SELECT * FROM categories");
// handle delete
if (isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    // ลบ log ที่เกี่ยวข้องก่อน
    $conn->query("DELETE FROM repair_logs WHERE repair_id = $del_id");
    $conn->query("DELETE FROM repairs WHERE repair_id = $del_id");
    echo "<script>location.href=location.href;</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายการแจ้งซ่อมทั้งหมด</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <style>
#repairs-table {
  font-size: 0.95rem;
}
#repairs-table th, #repairs-table td {
  padding: 0.45rem 0.5rem;
}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
    <form method="post" action="../print/print_repairs.php" target="_blank" id="printForm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>รายการแจ้งซ่อมทั้งหมด</h3>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-print me-1"></i> พิมพ์ใบรายการแจ้งซ่อม</button>
    </div>
    <div class="table-responsive">
    <table id="repairs-table" class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th></th>
                <th>วันที่แจ้ง</th>
                <th>เลขครุภัณฑ์</th>
                <th>Serial</th>
                <th>ยี่ห้อ</th>
                <th>รุ่น</th>
                <th>ตำแหน่ง</th>
                <th>ผู้แจ้ง</th>
                <th>ผู้รับผิดชอบ</th>
                <th>สถานะ</th>
                <th>รายละเอียด</th>
                <th>ลบ</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $repairs->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="selected[]" value="<?= $row['repair_id'] ?>"></td>
                <td><?= thaidate($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['asset_number']) ?></td>
                <td><?= htmlspecialchars($row['serial_number']) ?></td>
                <td><?= htmlspecialchars($row['brand']) ?></td>
                <td><?= htmlspecialchars($row['model_name']) ?></td>
                <td><?= htmlspecialchars($row['location_name']) ?></td>
                <td><?= htmlspecialchars($row['reported_by_name']) ?></td>
                <td><?= htmlspecialchars($row['assigned_to_name'] ?? '-') ?></td>
                <td><?= status_badge($row['current_status']) ?></td>
                <td><a href="repair_detail.php?id=<?= $row['repair_id'] ?>" class="btn btn-info btn-sm">ดู/อัปเดต</a></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteRepair(<?= $row['repair_id'] ?>)">ลบ</button>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if ($repairs->num_rows == 0): ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    </form>
</div>
<script>
$(document).ready(function() {
    $('#repairs-table').DataTable({
        "language": {
            "lengthMenu": "แสดงข้อมูล _MENU_ จำนวน",
            "zeroRecords": "<div class='text-center py-4'><i class='fas fa-inbox fa-3x mb-3 text-muted'></i><p class='mb-0'>ไม่พบข้อมูลรายการแจ้งซ่อม</p><small>ไม่มีรายการแจ้งซ่อมในระบบ</small></div>",
            "info": "แสดงข้อมูลหน้า _START_ ถึง _END_ (จำนวน _TOTAL_ ข้อมูล)",
            "infoEmpty": "ไม่มีข้อมูล",
            "infoFiltered": "(กรองจากทั้งหมด _MAX_ ข้อมูล)",
            "search": "ค้นหาข้อมูล",
            "paginate": {
                "first": "หน้าแรก",
                "last": "หน้าสุดท้าย",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });
});

function deleteRepair(repairId) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบรายการแจ้งซ่อมนี้หรือไม่',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            // ส่งข้อมูลไปลบ
            $.post('', { delete_id: repairId }, function() {
                Swal.fire({
                    title: 'ลบสำเร็จ!',
                    text: 'ลบรายการแจ้งซ่อมเรียบร้อยแล้ว',
                    icon: 'success',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    location.reload();
                });
            });
        }
    });
}

</script>
<?php include 'toast.php'; ?>
</body>
</html>
