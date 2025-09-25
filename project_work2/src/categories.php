<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

/* ----- ลบหมวดหมู่ (แบบระมัดระวัง) ----- */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $category_id = intval($_GET['id']);

    // ตรวจสอบการอ้างอิงใน items ก่อนลบ
    $checkSql = "SELECT COUNT(*) AS cnt FROM items WHERE category_id = ?";
    if ($checkStmt = mysqli_prepare($link, $checkSql)) {
        mysqli_stmt_bind_param($checkStmt, "i", $category_id);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($checkStmt);

        if (!empty($row['cnt']) && intval($row['cnt']) > 0) {
            $_SESSION['error_message'] = 'ไม่สามารถลบได้: หมวดหมู่นี้ถูกใช้งานอยู่ในข้อมูลครุภัณฑ์';
            header("location: categories.php");
            exit;
        }
    }

    $sql = "DELETE FROM categories WHERE category_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['success_message'] = 'ลบหมวดหมู่สำเร็จ';
        header("location: categories.php");
        exit;
    }
}

/* ----- ดึงข้อมูลหมวดหมู่ ----- */
$sql = "SELECT category_id, category_name FROM categories ORDER BY category_id DESC";
$result = mysqli_query($link, $sql);

/* ----- เตรียม Flash message สำหรับ JS (ใช้ json_encode เพื่อความปลอดภัย) ----- */
$flash_success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$flash_error   = isset($_SESSION['error_message'])   ? $_SESSION['error_message']   : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
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
        :root{
            --bg-page: #f5f6f8;
            --card-border: #e5e7eb;
            --brand-green: #41B143;      /* สีหลักให้เหมือนรูป */
            --brand-green-dark: #2f8f33;
            --thead-text: #ffffff;
        }
        html, body { height: 100%; }
        body {
            background: var(--bg-page);
            overflow: hidden; /* เลื่อนเฉพาะตาราง */
            font-size: 0.95rem;
        }
        .page-wrap { min-height: 100%; display: flex; flex-direction: column; }
        .topbar { border-bottom: 1px solid var(--card-border); background: #ffffff; }
        .main-container { flex:1; padding:16px; display:flex; justify-content:center; align-items:stretch; }
        .card-clean { border:1px solid var(--card-border); box-shadow:0 1px 2px rgba(0,0,0,0.03); }
        .card-header { background:#ffffff; border-bottom:1px solid var(--card-border); }

        /* ตาราง */
        .table-responsive.table-scroll { max-height: calc(100vh - 220px); overflow:auto; }
        .table thead th {
            background: var(--brand-green) !important;
            color: var(--thead-text) !important;
            position: sticky; top: 0; z-index: 2;
            border-color: var(--brand-green) !important;
        }
        .table-hover tbody tr:hover { background:#f9fff9; }

        /* ปุ่มบนขวา */
        .btn-add-pill{
            background: var(--brand-green); color:#fff; border:0;
            border-radius:9999px; padding:.5rem 1rem; font-weight:600;
        }
        .btn-add-pill:hover{ background: var(--brand-green-dark); color:#fff; }
        .btn-back {
            background:#fff; border:1px solid var(--card-border);
            border-radius:9999px; padding:.5rem 1rem; color:#111827;
        }
        .btn-back:hover { background:#f3f4f6; color:#111827; }

        /* ปุ่มจัดการ (เหลือง/แดง) */
        .btn-icon { padding:.4rem .6rem; }
        .btn-edit { background:#ffc107; border:0; color:#212529; }
        .btn-edit:hover { filter:brightness(.95); color:#212529; }
        .btn-del  { background:#dc3545; border:0; color:#fff; }
        .btn-del:hover { filter:brightness(.92); color:#fff; }

        .footer-actions { border-top:1px solid var(--card-border); background:#fafafa; }

        @media (max-width: 576px) {
            .table-responsive.table-scroll { max-height: calc(100vh - 260px); }
            .top-actions { flex-direction: column; gap:.5rem!important; }
            .top-actions .btn { width:100%; }
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <!-- Topbar -->
    <div class="topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between py-3">
                <h5 class="mb-0 text-dark">
                    <i class="fas fa-layer-group me-2"></i>จัดการหมวดหมู่
                </h5>
                <div class="d-flex gap-2 top-actions">
                    <a href="items.php" class="btn btn-back">
                        <i class="fa-solid fa-arrow-left-long me-1"></i> กลับไปหน้าครุภัณฑ์
                    </a>
                    <a href="category_form.php" class="btn btn-add-pill">
                        <i class="fas fa-plus me-1"></i> เพิ่มหมวดหมู่
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- เนื้อหา -->
    <div class="main-container">
        <div class="w-100" style="max-width: 880px;">
            <div class="card card-clean h-100 d-flex flex-column">
                <div class="card-header py-3">
                    <div class="small text-muted">รายการหมวดหมู่ทั้งหมด</div>
                </div>

                <div class="card-body p-0 d-flex flex-column">
                    <div class="table-responsive table-scroll">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead>
                                <tr class="text-nowrap">
                                    <th style="width:70%;">ชื่อหมวดหมู่</th>
                                    <th class="text-center" style="width:30%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['category_name']); ?></td>
                                        <td class="text-center">
                                            <a href="category_form.php?id=<?= (int)$row['category_id']; ?>"
                                               class="btn btn-edit btn-sm btn-icon" title="แก้ไข">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button class="btn btn-del btn-sm btn-icon"
                                                    title="ลบ"
                                                    onclick="confirmDelete(<?= (int)$row['category_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">
                                        <i class="far fa-folder-open me-1"></i>ยังไม่มีหมวดหมู่
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="footer-actions d-flex justify-content-end align-items-center p-3">
                        <div class="text-muted small">แสดงผลแบบเลื่อนเฉพาะตาราง</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.page-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // ใช้สีธีมเดียวกัน
  const BRAND_GREEN = '#41B143';
  const BRAND_GREEN_DARK = '#2f8f33';

  // Flash messages จาก PHP
  const flashSuccess = <?php echo json_encode($flash_success, JSON_UNESCAPED_UNICODE); ?>;
  const flashError   = <?php echo json_encode($flash_error,   JSON_UNESCAPED_UNICODE); ?>;

  // แสดงป๊อปอัปเมื่อมีข้อความ
  if (flashError) {
    Swal.fire({
      icon: 'warning',
      title: 'ไม่สามารถดำเนินการได้',
      text: flashError,
      confirmButtonText: 'ตกลง',
      confirmButtonColor: BRAND_GREEN
    });
  }
  if (flashSuccess) {
    Swal.fire({
      icon: 'success',
      title: 'สำเร็จ',
      text: flashSuccess,
      timer: 1600,
      showConfirmButton: false
    });
  }

  // กล่องยืนยันลบ (แทน confirm() แบบเดิม)
  function confirmDelete(id){
    Swal.fire({
      icon: 'question',
      title: 'ยืนยันการลบ?',
      text: 'เมื่อลบแล้วจะไม่สามารถกู้คืนได้',
      showCancelButton: true,
      confirmButtonText: 'ลบ',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'categories.php?action=delete&id=' + encodeURIComponent(id);
      }
    });
  }
</script>
</body>
</html>
