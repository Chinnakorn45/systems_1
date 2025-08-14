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

// ลบการเบิก (ถ้ามี action=delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $dispense_id = intval($_GET['id']);
    $sql = "DELETE FROM dispensations WHERE dispense_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $dispense_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("location: dispensations.php");
        exit;
    }
}

// ดึงข้อมูลการเบิกวัสดุ
$sql = "SELECT d.*, s.supply_name, u.full_name FROM dispensations d
        LEFT JOIN office_supplies s ON d.supply_id = s.supply_id
        LEFT JOIN users u ON d.user_id = u.user_id
        ORDER BY d.dispense_date DESC, d.dispense_id DESC";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การเบิกวัสดุ - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Thai (Prompt & Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="common-ui.css">
</head>
<body>
<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">การเบิกวัสดุ</span>
    <!-- ลบ user dropdown ออก -->
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar (Desktop Only) และ Offcanvas (Mobile) -->
    <?php include 'sidebar.php'; ?>
    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 px-0">
      <div class="main-content mt-4 mt-md-5">
        <!-- User Dropdown (Desktop Only) -->
        <!-- ลบ user dropdown (desktop) ออก -->
        <!-- Heading & Add Button Row -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
          <h2 class="mb-0"><i class="fas fa-dolly"></i> การเบิกวัสดุ</h2>
          <a href="dispensation_form.php" class="btn btn-add align-self-md-end"><i class="fas fa-plus"></i> เพิ่มการเบิกวัสดุ</a>
        </div>
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead>
                  <tr>
                    <th>ชื่อวัสดุ</th>
                    <th>ชื่อผู้เบิก</th>
                    <th>วันที่เบิก</th>
                    <th>จำนวนที่เบิก</th>
                    <th>หมายเหตุ</th>
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
                    <td><?php echo htmlspecialchars($row['supply_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo thaidate('j M Y', $row['dispense_date']); ?></td>
                    <td><?php echo $row['quantity_dispensed']; ?></td>
                    <td><?php echo htmlspecialchars($row['notes']); ?></td>
                    <td>
                      <a href="dispensation_form.php?id=<?php echo $row['dispense_id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                      <a href="dispensations.php?action=delete&id=<?php echo $row['dispense_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?');"><i class="fas fa-trash"></i></a>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                  
                  <?php if($row_count == 0): ?>
                      <tr>
                          <td colspan="6" class="text-center py-4">
                              <div class="text-muted">
                                  <i class="fas fa-dolly fa-3x mb-3"></i>
                                  <h5>ไม่พบข้อมูลการเบิกวัสดุ</h5>
                                  <p class="mb-0">ยังไม่มีรายการเบิกวัสดุในระบบ</p>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
    <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
    พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
    | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
    | © 2025
</footer>
</body>
</html> 