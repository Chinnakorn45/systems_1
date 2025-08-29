<?php
session_start();
require_once 'db.php';

// ===== ฟังก์ชันแสดง Badge สถานะ (อัปเดต in_progress) =====
function status_badge($status) {
    $map = [
        'received'               => ['รับเรื่อง', 'info'],
        'evaluate_it'            => ['ประเมิน (โดย IT)', 'warning'],
        'evaluate_repairable'    => ['ประเมิน: ซ่อมได้โดย IT', 'success'],
        'in_progress'            => ['กำลังซ่อมโดย IT', 'primary'], // <- ปรับข้อความ/สี
        'evaluate_external'      => ['ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'evaluate_disposal'      => ['ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย', 'dark'],
        'external_repair'        => ['ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
        'procurement_managing'   => ['พัสดุจัดการส่งซ่อม', 'info'],
        'procurement_returned'   => ['พัสดุซ่อมเสร็จส่งคืน IT', 'success'],
        'repair_completed'       => ['ซ่อมเสร็จ', 'success'],
        'waiting_delivery'       => ['รอส่งมอบ', 'warning'],
        'delivered'              => ['ส่งมอบ', 'success'],
        'cancelled'              => ['ยกเลิก', 'secondary'],
        // สถานะเก่า/สำรอง
        'pending'                => ['รอดำเนินการ', 'secondary'],
        'done'                   => ['ซ่อมเสร็จ', 'success'],
        ''                       => ['รอดำเนินการ', 'secondary'],
    ];
    if (!isset($map[$status])) return '<span class="badge bg-secondary">ไม่ระบุสถานะ</span>';
    [$th, $color] = $map[$status];
    return "<span class='badge bg-$color'>$th</span>";
}

// ===== ฟังก์ชันวันที่แบบไทย =====
function thaidate($date, $format = 'd/m/Y H:i') {
    $ts = strtotime($date);
    if ($ts === false) return '-';
    $result = date($format, $ts);
    $year_th = date('Y', $ts) + 543;
    return str_replace(date('Y', $ts), $year_th, $result);
}

// ===== ตรวจสิทธิ์ =====
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: login.php'); exit;
}

// ===== ยกเลิกรายการ (เฉพาะที่ยังรับเรื่อง/รอดำเนินการ) =====
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $rid = intval($_GET['cancel']);
    $uid = intval($_SESSION['user_id']);
    $conn->query("UPDATE repairs 
                  SET status='cancelled' 
                  WHERE repair_id=$rid 
                    AND reported_by=$uid 
                    AND status IN ('received','pending')");
}

// ===== ดึงรายการของฉัน (อิงสถานะล่าสุดจาก repair_logs) =====
$sql = "SELECT r.*, COALESCE(latest.status, r.status) AS current_status 
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
        WHERE r.reported_by = ".intval($_SESSION['user_id'])."
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


    <style>
                                /* Timeline — เว้นระยะให้จุดไม่ทับตัวหนังสือ */
    .timeline{
    position:relative;
    padding-left:0;         /* ไม่ต้องดันทั้งบล็อกแล้ว */
    margin:0;
    }
    .timeline::before{
    content:"";
    position:absolute;
    left:16px;              /* ตำแหน่งเส้นตั้ง */
    top:0; bottom:0;
    width:2px; background:#e9ecef;
    }
    .timeline-item{
    position:relative;
    margin-bottom:14px;
    padding-left:36px;      /* ดันข้อความให้เลยจุดไป */
    }
    .timeline-item .dot{
        position:absolute;
    left:10px;              /* จุดอยู่บนเส้น */
    top:4px;
    width:12px; height:12px;
    border-radius:50%;
    background:#ced4da;
    border:2px solid #fff;
    box-shadow:0 0 0 2px #e9ecef;
    }
    .timeline-item.active .dot{
    background:#198754;     /* จุดของรายการล่าสุด */
    box-shadow:0 0 0 2px rgba(25,135,84,.25);
    }
    .timeline-item .time{
    font-size:.85rem;
    color:#6c757d;
    margin-bottom:2px;
    }
    .timeline-item .title{ font-weight:600; margin-bottom:2px; }
    .timeline-item .desc{ color:#6c757d; font-size:.9rem; }
    .timeline-item.muted{ opacity:.75; }

    /* เผื่อหน้าจอแคบ เพิ่มระยะอีกนิด */
    @media (max-width:480px){
    .timeline::before{ left:20px; }
    .timeline-item{ padding-left:44px; }
    .timeline-item .dot{ left:14px; }
    }
    </style>
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

                      <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?= $row['repair_id'] ?>">
                        ตรวจสอบสถานะ
                      </button>

                      <!-- Modal -->
                      <div class="modal fade" id="statusModal<?= $row['repair_id'] ?>" tabindex="-1" aria-labelledby="statusModalLabel<?= $row['repair_id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="statusModalLabel<?= $row['repair_id'] ?>">สถานะการซ่อม</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <!-- ไทม์ไลน์: ล่าสุดอยู่บนสุด แบบรูปตัวอย่างที่ 2 -->
                            <div class="modal-body">
                              <?php
                                $logs = $conn->query(
                                  "SELECT l.*, u.full_name 
                                   FROM repair_logs l 
                                   LEFT JOIN users u ON l.updated_by = u.user_id 
                                   WHERE l.repair_id = ".intval($row['repair_id'])." 
                                   ORDER BY l.updated_at DESC"
                                );
                                $status_map = [
                                  'received'               => ['รับเรื่อง', 'info'],
                                  'evaluate_it'            => ['ประเมิน (โดย IT)', 'warning'],
                                  'evaluate_repairable'    => ['ประเมิน: ซ่อมได้โดย IT', 'success'],
                                  'in_progress'            => ['กำลังซ่อมโดย IT', 'primary'],
                                  'evaluate_external'      => ['ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
                                  'evaluate_disposal'      => ['ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย', 'dark'],
                                  'external_repair'        => ['ซ่อมไม่ได้ - ส่งซ่อมภายนอก', 'danger'],
                                  'procurement_managing'   => ['พัสดุจัดการส่งซ่อม', 'info'],
                                  'procurement_returned'   => ['พัสดุซ่อมเสร็จส่งคืน IT', 'success'],
                                  'repair_completed'       => ['ซ่อมเสร็จ', 'success'],
                                  'waiting_delivery'       => ['รอส่งมอบ', 'warning'],
                                  'delivered'              => ['ส่งมอบ', 'success'],
                                  'cancelled'              => ['ยกเลิก', 'secondary'],
                                  'pending'                => ['รอดำเนินการ', 'secondary'],
                                  'done'                   => ['ซ่อมเสร็จ', 'success'],
                                  ''                       => ['รอดำเนินการ', 'secondary'],
                                ];
                              ?>
                              <div class="timeline">
                                <?php $is_first = true; ?>
                                <?php while ($log = $logs->fetch_assoc()): ?>
                                  <?php [$th,$color] = $status_map[$log['status']] ?? ['ไม่ระบุสถานะ','secondary']; ?>
                                  <div class="timeline-item <?= $is_first ? 'active' : 'muted' ?>">
                                    <span class="dot"></span>
                                    <div class="time"><?= thaidate($log['updated_at']) ?></div>
                                    <div class="title">
                                      <span class="badge bg-<?= $color ?> me-1"><?= $th ?></span>
                                      <small class="text-muted">โดย <?= htmlspecialchars($log['full_name'] ?? '-') ?></small>
                                    </div>
                                    <?php if (!empty($log['detail'])): ?>
                                      <div class="desc"><?= nl2br(htmlspecialchars($log['detail'])) ?></div>
                                    <?php endif; ?>
                                  </div>
                                  <?php $is_first = false; ?>
                                <?php endwhile; ?>

                                <!-- เหตุการณ์แรก: แจ้งและบันทึก -->
                                <div class="timeline-item muted">
                                  <span class="dot"></span>
                                  <div class="time"><?= thaidate($row['created_at']) ?></div>
                                  <div class="title">แจ้งและบันทึกเรื่องเข้าสู่ระบบ</div>
                                  <div class="desc">รายละเอียดที่แจ้ง: <?= htmlspecialchars($row['issue_description']) ?></div>
                                </div>
                              </div>
                            </div>
                            <!-- /modal-body -->
                          </div>
                        </div>
                      </div>
                      <!-- /Modal -->
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
// เลือก/ยกเลิกเลือกทั้งหมด
document.getElementById('select-all').addEventListener('click', function() {
  document.querySelectorAll('input[name="selected[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
</body>
</html>
