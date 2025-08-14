<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ดึงข้อมูลการยืมทั้งหมด
if (in_array($_SESSION['role'], ['admin','procurement'])) {
    $sql = "SELECT b.borrow_id, b.item_id, b.user_id, b.borrow_date, b.due_date, b.return_date, b.status,
                i.item_number, i.model_name, i.brand, u.full_name, u.department
            FROM borrowings b
            LEFT JOIN items i ON b.item_id = i.item_id
            LEFT JOIN users u ON b.user_id = u.user_id
            ORDER BY b.borrow_id DESC";
} else {
$sql = "SELECT b.borrow_id, b.item_id, b.user_id, b.borrow_date, b.due_date, b.return_date, b.status,
                i.item_number, i.model_name, i.brand, u.full_name, u.department
        FROM borrowings b
        LEFT JOIN items i ON b.item_id = i.item_id
        LEFT JOIN users u ON b.user_id = u.user_id
            WHERE b.user_id = " . intval($_SESSION['user_id']) . "
            ORDER BY b.borrow_id DESC";
}
$result = mysqli_query($link, $sql);

// ดึงข้อมูลผู้ใช้ปัจจุบันเพื่อตรวจสอบความครบถ้วน
$current_user_data = [];
if (isset($_SESSION['user_id'])) {
    $user_sql = "SELECT username, full_name, email, department, position FROM users WHERE user_id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_user_data['username'], $current_user_data['full_name'], $current_user_data['email'], $current_user_data['department'], $current_user_data['position']);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
}

function status_text($status, $due, $return) {
    if ($status === 'pending') return '<span class="badge bg-secondary">รออนุมัติ</span>';
    if ($status === 'approved') return '<span class="badge bg-info text-dark">อนุมัติแล้ว</span>';
    if ($status === 'returned') return '<span class="badge bg-success">คืนแล้ว</span>';
    if ($status === 'return_pending') return '<span class="badge bg-warning text-dark">รอยืนยันการคืน</span>';
    if ($status === 'borrowed' && $due < date('Y-m-d') && !$return) return '<span class="badge bg-danger">เกินกำหนด</span>';
    if ($status === 'borrowed') return '<span class="badge bg-warning text-dark">กำลังยืม</span>';
    if ($status === 'cancelled') return '<span class="badge bg-dark">ถูกปฏิเสธ</span>';
    return '<span class="badge bg-secondary">ไม่ทราบสถานะ</span>';
}

if ($_SESSION["role"] === 'staff' && !in_array(basename($_SERVER['PHP_SELF']), ['borrowings.php', 'user_guide.php'])) {
    header('Location: borrowings.php');
    exit;
}

$pending = 0;
if (in_array($_SESSION['role'], ['admin','procurement'])) {
    $pending_q = mysqli_query($link, "SELECT COUNT(*) AS cnt FROM borrowings WHERE status='pending'");
    $pending = mysqli_fetch_assoc($pending_q)['cnt'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การยืม-คืนครุภัณฑ์</title>
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
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar" aria-label="Toggle navigation" tabindex="0">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="navbar-brand mb-0 h1">การยืม-คืน</span>
                <!-- ลบ user dropdown ออก -->
            </div>
        </nav>
        <!-- Sidebar (Desktop Only) และ Offcanvas (Mobile) -->
        <?php include 'sidebar.php'; ?>
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-0">
            <div class="main-content mt-4 mt-md-5">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <h2 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>การยืม-คืนครุภัณฑ์</h2>
                    <div class="d-flex align-items-center">
                        <!-- Mobile: Add Borrowing Button at top right -->
                        <a href="#" class="btn btn-success d-block d-md-none ms-2" onclick="checkUserProfile()"><i class="fas fa-plus"></i> เพิ่มการยืมใหม่</a>
                        <!-- Desktop: User Dropdown and Add Button below -->
                        <div class="d-none d-md-flex flex-column align-items-end">
                            <!-- ลบ user dropdown (desktop) ออก -->
                            <a href="#" class="btn btn-success" onclick="checkUserProfile()"><i class="fas fa-plus"></i> เพิ่มการยืมใหม่</a>
                </div>
                </div>
                </div>
                <input type="text" id="borrowingSearch" class="form-control mb-3" style="max-width:350px;" placeholder="ค้นหารายการยืม-คืน...">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="sticky-top bg-white" style="z-index: 1020;">
                                    <tr>
                                        <!-- <th>ลำดับ</th> -->
                                        <th>เลขครุภัณฑ์</th>
                                        <th>รุ่น</th>
                                        <th>ยี่ห้อ</th>
                                        <th>ชื่อผู้ยืม</th>
                                        <th>แผนก</th>
                                        <th>วันที่ยืม</th>
                                        <th>กำหนดคืน</th>
                                        <th>วันที่คืน</th>
                                        <th>สถานะ</th>
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                $row_count = 0;
                                while($row = mysqli_fetch_assoc($result)): 
                                    $row_count++;
                                ?>
                                    <tr>
                                        <!-- <td><?= $i++; ?></td> -->
                                        <td><?= htmlspecialchars($row['item_number']); ?></td>
                                        <td><?= htmlspecialchars($row['model_name']); ?></td>
                                        <td><?= htmlspecialchars($row['brand']); ?></td>
                                        <td><?= htmlspecialchars($row['full_name']); ?></td>
                                        <td><?= htmlspecialchars($row['department'] ?? '-'); ?></td>
                                        <td><?= thaidate('j M Y', $row['borrow_date']); ?></td>
                                        <td><?= thaidate('j M Y', $row['due_date']); ?></td>
                                        <td><?= $row['return_date'] ? thaidate('j M Y', $row['return_date']) : '-'; ?></td>
                                        <td><?= status_text($row['status'], $row['due_date'], $row['return_date']); ?></td>
                                        <td>
                                            <?php if($row['status']==='borrowed'): ?>
                                                <a href="return_borrowing.php?id=<?= $row['borrow_id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('ยืนยันการส่งคำขอคืนครุภัณฑ์?');"><i class="fas fa-undo"></i> ส่งคำขอคืน</a>
                                            <?php elseif($row['status']==='return_pending' && in_array($_SESSION['role'], ['admin','procurement'])): ?>
                                                <a href="process_return.php?action=confirm_return&id=<?= $row['borrow_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('ยืนยันการคืนครุภัณฑ์นี้?');"><i class="fas fa-check"></i> ยืนยันคืน</a>
                                                <a href="process_return.php?action=reject_return&id=<?= $row['borrow_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันปฏิเสธการคืนนี้?');"><i class="fas fa-times"></i> ปฏิเสธคืน</a>
                                            <?php elseif($row['status']==='pending' && in_array($_SESSION['role'], ['admin','procurement'])): ?>
                                                <a href="process_borrowing.php?action=approve&id=<?= $row['borrow_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('ยืนยันอนุมัติการยืมนี้?');"><i class="fas fa-check"></i> อนุมัติ</a>
                                                <a href="process_borrowing.php?action=cancel&id=<?= $row['borrow_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันปฏิเสธการยืมนี้?');"><i class="fas fa-times"></i> ปฏิเสธ</a>
                                            <?php endif; ?>
                                            <?php if(in_array($_SESSION['role'], ['admin','procurement']) && !in_array($row['status'], ['returned','cancelled'])): ?>
                                                <a href="#" class="btn btn-sm btn-info btn-transfer-borrower" data-borrow-id="<?= $row['borrow_id']; ?>"><i class="fas fa-user-exchange"></i> โอนผู้ยืม</a>
                                            <?php endif; ?>
                                            <?php if(!in_array($row['status'], ['returned','cancelled'])): ?>
                                            <a class="btn btn-sm btn-outline-secondary" href="print/print_borrowing.php?id=<?= $row['borrow_id']; ?>" target="_blank"><i class="fas fa-print"></i> พิมพ์ใบคำขอยืม</a>
                                            <a class="btn btn-sm btn-outline-secondary" href="print/print_transfer.php?id=<?= $row['borrow_id']; ?>" target="_blank"><i class="fas fa-print"></i> พิมพ์ใบขอโอน</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <?php if($row_count == 0): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <h5>ไม่พบข้อมูลการยืมคืน</h5>
                                                <p class="mb-0">ยังไม่มีรายการยืมคืนครุภัณฑ์ในระบบ</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal โอนผู้ยืม -->
<div class="modal fade" id="transferBorrowerModal" tabindex="-1" aria-labelledby="transferBorrowerModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="transferBorrowerModalLabel"><i class="fas fa-user-exchange"></i> โอนผู้ยืม</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body" id="transferBorrowerModalBody">
        <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>
    </div>
    </div>
</div>
</div>

<!-- Modal ใบคำขอยืม/ขอโอน -->
<div class="modal fade" id="printRequestModal" tabindex="-1" aria-labelledby="printRequestModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">
    <div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="printRequestModalLabel"><i class="fas fa-print"></i> พิมพ์ใบคำขอ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body" id="printRequestModalBody">
        <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>
    </div>
    </div>
</div>
</div>

<!-- Modal แจ้งเตือนข้อมูลผู้ใช้ไม่ครบ -->
<div class="modal fade" id="userProfileWarningModal" tabindex="-1" aria-labelledby="userProfileWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="userProfileWarningModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>ข้อมูลผู้ใช้ไม่ครบ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-edit fa-3x text-warning mb-3"></i>
                    <h5>กรุณาแก้ไขข้อมูลส่วนตัวให้ครบถ้วน</h5>
                    <p class="text-muted">ข้อมูลที่จำเป็น: ชื่อผู้ใช้, ชื่อ-นามสกุล, อีเมล, แผนก/ฝ่าย, ตำแหน่ง</p>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>ข้อมูลที่ขาด:</strong>
                    <ul class="mb-0 mt-2" id="missingFieldsList">
                        <!-- จะถูกเติมด้วย JavaScript -->
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <a href="profile.php" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>แก้ไขข้อมูลส่วนตัว
                </a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ฟังก์ชันตรวจสอบข้อมูลผู้ใช้
    window.checkUserProfile = function() {
        // ดึงข้อมูลผู้ใช้จากฐานข้อมูลที่ PHP ดึงมาแล้ว
        const userData = {
            username: '<?php echo htmlspecialchars($current_user_data['username'] ?? ""); ?>',
            full_name: '<?php echo htmlspecialchars($current_user_data['full_name'] ?? ""); ?>',
            email: '<?php echo htmlspecialchars($current_user_data['email'] ?? ""); ?>',
            department: '<?php echo htmlspecialchars($current_user_data['department'] ?? ""); ?>',
            position: '<?php echo htmlspecialchars($current_user_data['position'] ?? ""); ?>'
        };
        
        // ตรวจสอบข้อมูลที่ขาด
        const missingFields = [];
        const fieldNames = {
            username: 'ชื่อผู้ใช้',
            full_name: 'ชื่อ-นามสกุล',
            email: 'อีเมล',
            department: 'แผนก/ฝ่าย',
            position: 'ตำแหน่ง'
        };
        
        Object.keys(userData).forEach(field => {
            if (!userData[field] || userData[field].trim() === '') {
                missingFields.push(fieldNames[field]);
            }
        });
        
        if (missingFields.length > 0) {
            // แสดง Modal แจ้งเตือน
            const modal = new bootstrap.Modal(document.getElementById('userProfileWarningModal'));
            const missingFieldsList = document.getElementById('missingFieldsList');
            
            // สร้างรายการข้อมูลที่ขาด
            missingFieldsList.innerHTML = missingFields.map(field => `<li>${field}</li>`).join('');
            
            modal.show();
        } else {
            // ข้อมูลครบ ให้ไปหน้าเพิ่มการยืมใหม่
            window.location.href = 'add_borrowing.php';
        }
    };
    
    const modal = new bootstrap.Modal(document.getElementById('transferBorrowerModal'));
document.querySelectorAll('.btn-transfer-borrower').forEach(btn => {
    btn.addEventListener('click', function(e) {
    e.preventDefault();
    const borrowId = this.getAttribute('data-borrow-id');
    const body = document.getElementById('transferBorrowerModalBody');
    body.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>';
    modal.show();
    fetch('transfer_borrower.php?id=' + borrowId)
        .then(res => res.text())
        .then(html => {
        body.innerHTML = html;
        const form = document.getElementById('transferBorrowerForm');
        if (form) {
            form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            const formData = new FormData(form);
            fetch('transfer_borrower.php?id=' + borrowId, {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(resp => {
                if (resp.trim() === 'success') {
                modal.hide();
                location.reload();
                } else {
                body.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาด กรุณาลองใหม่</div>' + html;
                }
            });
            });
        }
        });
    });
});

// Print Borrow/Transfer Request
const printModal = new bootstrap.Modal(document.getElementById('printRequestModal'));
document.querySelectorAll('.btn-print-borrow').forEach(btn => {
  btn.addEventListener('click', function() {
    const borrowId = this.getAttribute('data-borrow-id');
    showPrintRequest(borrowId, 'borrow');
  });
});
document.querySelectorAll('.btn-print-transfer').forEach(btn => {
  btn.addEventListener('click', function() {
    const borrowId = this.getAttribute('data-borrow-id');
    showPrintRequest(borrowId, 'transfer');
  });
});
function showPrintRequest(borrowId, type) {
  const body = document.getElementById('printRequestModalBody');
  body.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>';
  printModal.show();
  fetch('print_request_template.php?id=' + borrowId + '&type=' + type)
    .then(res => res.text())
    .then(html => {
      body.innerHTML = html;
    });
}
document.getElementById('btnPrintRequest').addEventListener('click', function() {
  const printContents = document.getElementById('printRequestModalBody').innerHTML;
  const printWindow = window.open('', '', 'height=800,width=900');
  printWindow.document.write('<html><head><title>พิมพ์ใบคำขอ</title>');
  printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">');
  printWindow.document.write('<style>body{font-family:Prompt,Kanit,Arial,sans-serif;}</style>');
  printWindow.document.write('</head><body >');
  printWindow.document.write(printContents);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  printWindow.focus();
  setTimeout(function(){ printWindow.print(); printWindow.close(); }, 500);
});

// Real-time search filter for borrowings table
    document.getElementById('borrowingSearch').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const shouldShow = text.includes(filter);
            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });
        
        // แสดงข้อความเมื่อไม่พบผลลัพธ์
        let noResultsRow = document.querySelector('.no-results-row');
        if (visibleCount === 0 && !noResultsRow) {
            const tbody = document.querySelector('table tbody');
            const newRow = document.createElement('tr');
            newRow.className = 'no-results-row';
            newRow.innerHTML = `
                <td colspan="10" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h5>ไม่พบผลลัพธ์</h5>
                        <p class="mb-0">ลองค้นหาด้วยคำอื่น</p>
                    </div>
                </td>
            `;
            tbody.appendChild(newRow);
        } else if (visibleCount > 0 && noResultsRow) {
            noResultsRow.remove();
        }
    });
    
    // เพิ่มการ scroll to top เมื่อคลิกที่ header
    document.querySelectorAll('table thead th').forEach(th => {
        th.style.cursor = 'pointer';
        th.title = 'คลิกเพื่อเลื่อนขึ้นด้านบน';
        th.addEventListener('click', function() {
            const tableContainer = document.querySelector('.table-responsive');
            tableContainer.scrollTo({ top: 0, behavior: 'smooth' });
        });
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