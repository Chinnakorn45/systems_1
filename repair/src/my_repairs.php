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
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: login.php'); exit;
}
// handle cancel
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $rid = $_GET['cancel'];
    $conn->query("UPDATE repairs SET status='cancelled' WHERE repair_id=$rid AND reported_by={$_SESSION['user_id']} AND status IN ('received','pending')");
}
// get repairs - แก้ไขให้ดึงสถานะล่าสุดจาก repair_logs
$sql = "SELECT r.*, COALESCE(latest.status, r.status) as current_status 
        FROM repairs r 
        LEFT JOIN (
            SELECT repair_id, status 
            FROM repair_logs 
            WHERE (repair_id, updated_at) IN (
                SELECT repair_id, MAX(updated_at) 
                FROM repair_logs 
                GROUP BY repair_id
            )
        ) latest ON r.repair_id = latest.repair_id
        WHERE r.reported_by={$_SESSION['user_id']} 
        ORDER BY r.created_at DESC";
$repairs = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการแจ้งซ่อมของฉัน</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
    <form method="post" action="../print/print_repairs.php" target="_blank" id="printForm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>รายการแจ้งซ่อมของฉัน</h3>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-print me-1"></i> พิมพ์ใบรายการแจ้งซ่อม</button>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><input type="checkbox" id="select-all"></th>
                <th>วันที่แจ้ง</th>
                <th>ครุภัณฑ์</th>
                <th>ปัญหา</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $repairs->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="selected[]" value="<?= $row['repair_id'] ?>"></td>
                <td><?= thaidate($row['created_at']) ?></td>
                <td><?= $row['model_name'] ? htmlspecialchars($row['model_name']) : '<span class="text-muted">-</span>' ?></td>
                <td><?= htmlspecialchars($row['issue_description']) ?></td>
                <td><?= status_badge($row['current_status']) ?></td>
                <td>
                    <?php if (in_array($row['current_status'], ['received', 'pending'])): ?>
                        <a href="?cancel=<?= $row['repair_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันยกเลิก?')">ยกเลิก</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?= $row['repair_id'] ?>">ตรวจสอบสถานะ</button>
                    <!-- Modal -->
                    <div class="modal fade" id="statusModal<?= $row['repair_id'] ?>" tabindex="-1" aria-labelledby="statusModalLabel<?= $row['repair_id'] ?>" aria-hidden="true">
                      <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="statusModalLabel<?= $row['repair_id'] ?>">สถานะการซ่อม</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <!-- ไทม์ไลน์สถานะจริง -->
                            <div class="mb-3 p-3 bg-light rounded">
                              <b>แจ้งและบันทึกเรื่องเข้าสู่ระบบ</b><br>
                              รายละเอียดที่แจ้ง: <?= htmlspecialchars($row['issue_description']) ?><br>
                              <span class="text-muted"><?= thaidate($row['created_at']) ?></span>
                            </div>
                            <?php if (!empty($row['assigned_to'])): ?>
                            <div class="mb-3 p-3" style="background:#c8f4ff;">
                              <b>เจ้าหน้าที่ที่รับเรื่องดำเนินการ</b><br>
                              ผู้รับเรื่อง: <?php
                                $user_q = $conn->query("SELECT full_name FROM users WHERE user_id=".intval($row['assigned_to']));
                                $user = $user_q ? $user_q->fetch_assoc() : null;
                                echo $user ? htmlspecialchars($user['full_name']) : '-';
                              ?><br>
                              <span class="badge bg-info">
                                <?php
                                  if ($row['current_status'] === 'received') echo 'รับเรื่อง';
                                  elseif ($row['current_status'] === 'evaluate_it') echo 'ประเมิน (โดย IT)';
                                  elseif ($row['current_status'] === 'evaluate_repairable') echo 'ประเมิน: ซ่อมได้โดย IT';
                                  elseif ($row['current_status'] === 'evaluate_external') echo 'ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก';
                                  elseif ($row['current_status'] === 'evaluate_disposal') echo 'ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย';
                                  elseif ($row['current_status'] === 'external_repair') echo 'ซ่อมไม่ได้ - ส่งซ่อมภายนอก';
                                  elseif ($row['current_status'] === 'procurement_managing') echo 'พัสดุจัดการส่งซ่อม';
                                  elseif ($row['current_status'] === 'procurement_returned') echo 'พัสดุซ่อมเสร็จส่งคืน IT';
                                  elseif ($row['current_status'] === 'repair_completed') echo 'ซ่อมเสร็จ';
                                  elseif ($row['current_status'] === 'waiting_delivery') echo 'รอส่งมอบ';
                                  elseif ($row['current_status'] === 'delivered') echo 'ส่งมอบ';
                                  elseif ($row['current_status'] === 'cancelled') echo 'ยกเลิก';
                                  elseif ($row['current_status'] === 'pending') echo 'รอดำเนินการ';
                                  elseif ($row['current_status'] === 'in_progress') echo 'กำลังซ่อม';
                                  elseif ($row['current_status'] === 'done') echo 'ซ่อมเสร็จ';
                                  else echo 'ไม่ระบุสถานะ';
                                ?>
                              </span><br>
                              <span class="text-muted">อัปเดตล่าสุด: <?= thaidate($row['updated_at']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($row['fix_description'])): ?>
                            <div class="mb-3 p-3 bg-secondary bg-opacity-10 rounded">
                              <b>รายละเอียดจากเจ้าหน้าที่</b><br>
                              <?= nl2br(htmlspecialchars($row['fix_description'])) ?><br>
                              <span class="badge bg-info">
                                <?php
                                  if ($row['current_status'] === 'received') echo 'รับเรื่อง';
                                  elseif ($row['current_status'] === 'evaluate_it') echo 'ประเมิน (โดย IT)';
                                  elseif ($row['current_status'] === 'evaluate_repairable') echo 'ประเมิน: ซ่อมได้โดย IT';
                                  elseif ($row['current_status'] === 'evaluate_external') echo 'ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก';
                                  elseif ($row['current_status'] === 'evaluate_disposal') echo 'ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย';
                                  elseif ($row['current_status'] === 'external_repair') echo 'ซ่อมไม่ได้ - ส่งซ่อมภายนอก';
                                  elseif ($row['current_status'] === 'procurement_managing') echo 'พัสดุจัดการส่งซ่อม';
                                  elseif ($row['current_status'] === 'procurement_returned') echo 'พัสดุซ่อมเสร็จส่งคืน IT';
                                  elseif ($row['current_status'] === 'repair_completed') echo 'ซ่อมเสร็จ';
                                  elseif ($row['current_status'] === 'waiting_delivery') echo 'รอส่งมอบ';
                                  elseif ($row['current_status'] === 'delivered') echo 'ส่งมอบ';
                                  elseif ($row['current_status'] === 'cancelled') echo 'ยกเลิก';
                                  elseif ($row['current_status'] === 'pending') echo 'รอดำเนินการ';
                                  elseif ($row['current_status'] === 'in_progress') echo 'กำลังซ่อม';
                                  elseif ($row['current_status'] === 'done') echo 'ซ่อมเสร็จ';
                                  else echo 'ไม่ระบุสถานะ';
                                ?>
                              </span><br>
                              <span class="text-muted">อัปเดตล่าสุด: <?= thaidate($row['updated_at']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php
                            $logs = $conn->query("SELECT l.*, u.full_name FROM repair_logs l LEFT JOIN users u ON l.updated_by = u.user_id WHERE l.repair_id = {$row['repair_id']} ORDER BY l.updated_at ASC");
                            while ($log = $logs->fetch_assoc()) {
                                // แสดงแต่ละสถานะใน timeline
                                $status_th = '';
                                $status_color = '';
                                switch ($log['status']) {
                                    case 'received':
                                        $status_th = 'รับเรื่อง';
                                        $status_color = 'info';
                                        break;
                                    case 'evaluate_it':
                                        $status_th = 'ประเมิน (โดย IT)';
                                        $status_color = 'warning';
                                        break;
                                    case 'evaluate_repairable':
                                        $status_th = 'ประเมิน: ซ่อมได้โดย IT';
                                        $status_color = 'success';
                                        break;
                                    case 'evaluate_external':
                                        $status_th = 'ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก';
                                        $status_color = 'danger';
                                        break;
                                    case 'evaluate_disposal':
                                        $status_th = 'ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย';
                                        $status_color = 'dark';
                                        break;
                                    case 'external_repair':
                                        $status_th = 'ซ่อมไม่ได้ - ส่งซ่อมภายนอก';
                                        $status_color = 'danger';
                                        break;
                                    case 'procurement_managing':
                                        $status_th = 'พัสดุจัดการส่งซ่อม';
                                        $status_color = 'info';
                                        break;
                                    case 'procurement_returned':
                                        $status_th = 'พัสดุซ่อมเสร็จส่งคืน IT';
                                        $status_color = 'success';
                                        break;
                                    case 'repair_completed':
                                        $status_th = 'ซ่อมเสร็จ';
                                        $status_color = 'success';
                                        break;
                                    case 'waiting_delivery':
                                        $status_th = 'รอส่งมอบ';
                                        $status_color = 'warning';
                                        break;
                                    case 'delivered':
                                        $status_th = 'ส่งมอบ';
                                        $status_color = 'success';
                                        break;
                                    case 'cancelled':
                                        $status_th = 'ยกเลิก';
                                        $status_color = 'secondary';
                                        break;
                                    // สถานะเก่า
                                    case 'pending':
                                        $status_th = 'รอดำเนินการ';
                                        $status_color = 'secondary';
                                        break;
                                    case 'in_progress':
                                        $status_th = 'กำลังซ่อม';
                                        $status_color = 'info';
                                        break;
                                    case 'done':
                                        $status_th = 'ซ่อมเสร็จ';
                                        $status_color = 'success';
                                        break;
                                    default:
                                        $status_th = 'ไม่ระบุสถานะ';
                                        $status_color = 'secondary';
                                        break;
                                }
                                ?>
                                <div class="mb-2 p-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= $status_color ?>"><?= $status_th ?></span>
                                        <small class="text-muted"><?= thaidate($log['updated_at']) ?></small>
                                    </div>
                                    <?php if (!empty($log['detail'])): ?>
                                        <p class="small text-muted"><?= htmlspecialchars($log['detail']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if ($repairs->num_rows == 0): ?>
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p class="mb-0">ไม่พบข้อมูลรายการแจ้งซ่อม</p>
                        <small>คุณยังไม่เคยแจ้งซ่อมอุปกรณ์ใดๆ</small>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Select all checkboxes
document.getElementById('select-all').addEventListener('click', function() {
    var checkboxes = document.querySelectorAll('input[name="selected[]"]');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = this.checked;
    }
});
</script>
</body>
</html> 