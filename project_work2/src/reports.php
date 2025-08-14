<?php
require_once 'config.php';
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}
if ($_SESSION["role"] === 'staff') {
    header('Location: borrowings.php');
    exit;
}
// --- รายงานสถานะครุภัณฑ์ ---
$equipment_stats = [];
$sql_stats = "SELECT COUNT(*) as total_items, SUM(total_quantity) as total_quantity FROM items";
$result_stats = mysqli_query($link, $sql_stats);
$equipment_stats = mysqli_fetch_assoc($result_stats);
$sql_borrowed = "SELECT IFNULL(SUM(quantity_borrowed), 0) as borrowed_quantity FROM borrowings WHERE status IN ('borrowed', 'return_pending')";
$result_borrowed = mysqli_query($link, $sql_borrowed);
$borrowed_data = mysqli_fetch_assoc($result_borrowed);
$equipment_stats['borrowed_quantity'] = $borrowed_data['borrowed_quantity'];
$equipment_stats['available_quantity'] = $equipment_stats['total_quantity'] - $equipment_stats['borrowed_quantity'];
// --- กราฟ ---
// สถานะการยืม-คืน
$sql_status_chart = "SELECT status, COUNT(*) as count FROM borrowings GROUP BY status";
$result_status_chart = mysqli_query($link, $sql_status_chart);
$status_chart_data = [];
while ($row = mysqli_fetch_assoc($result_status_chart)) {
    $status_chart_data[] = $row;
}
// การยืมตามเดือน
$sql_monthly_chart = "SELECT DATE_FORMAT(borrow_date, '%Y-%m') as month, COUNT(*) as borrow_count FROM borrowings WHERE borrow_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month";
$result_monthly_chart = mysqli_query($link, $sql_monthly_chart);
$monthly_chart_data = [];
while ($row = mysqli_fetch_assoc($result_monthly_chart)) {
    $monthly_chart_data[] = $row;
}
// มูลค่าตามปีงบประมาณ
$sql_budget_chart = "SELECT budget_year, SUM(total_price) as total_value FROM items WHERE budget_year IS NOT NULL AND budget_year != '' GROUP BY budget_year ORDER BY budget_year";
$result_budget_chart = mysqli_query($link, $sql_budget_chart);
$budget_chart_data = [];
while ($row = mysqli_fetch_assoc($result_budget_chart)) {
    $budget_chart_data[] = $row;
}
// --- รายงานการยืม-คืน ---
$sql_borrowings = "SELECT b.*, i.model_name, i.item_number, u.full_name FROM borrowings b LEFT JOIN items i ON b.item_id = i.item_id LEFT JOIN users u ON b.user_id = u.user_id ORDER BY b.borrow_date DESC LIMIT 10";
$result_borrowings = mysqli_query($link, $sql_borrowings);
// --- รายงานการเคลื่อนไหว ---
$sql_movements = "SELECT m.*, i.model_name, i.item_number, u.full_name FROM equipment_movements m LEFT JOIN items i ON m.item_id = i.item_id LEFT JOIN users u ON m.created_by = u.user_id ORDER BY m.movement_date DESC LIMIT 10";
$result_movements = mysqli_query($link, $sql_movements);
// --- รายงานมูลค่าครุภัณฑ์ ---
$sql_category_value = "SELECT c.category_name, SUM(i.total_price) as category_value, COUNT(i.item_id) as item_count FROM categories c LEFT JOIN items i ON c.category_id = i.category_id WHERE i.total_price IS NOT NULL AND i.total_price > 0 GROUP BY c.category_id ORDER BY category_value DESC";
$result_category_value = mysqli_query($link, $sql_category_value);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน - ระบบบันทึกคลังครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
</head>
<body>
<!-- Navbar (Mobile Only) -->
<nav class="navbar navbar-light bg-white d-md-none shadow-sm sticky-top">
  <div class="container-fluid px-2">
    <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <span class="fw-bold">รายงาน</span>
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
                    <div class="d-flex align-items-center mb-4">
                        <h2 class="me-auto"><i class="fas fa-chart-bar"></i> รายงานและสรุป</h2>
                        <button class="btn btn-primary ms-2" id="btnPrintReport"><i class="fas fa-print"></i> พิมพ์รายงาน</button>
                    </div>
                    <!-- Print Modal -->
                    <div class="modal fade" id="printReportModal" tabindex="-1" aria-labelledby="printReportModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="printReportModalLabel"><i class="fas fa-print"></i> เลือกรายงานที่ต้องการพิมพ์</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="printSection" id="printSummary" value="summary" checked>
                            <label class="form-check-label" for="printSummary">สรุปสถานะครุภัณฑ์</label>
                            </div>
                            <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="printSection" id="printBorrowings" value="borrowings">
                            <label class="form-check-label" for="printBorrowings">รายงานการยืม-คืน</label>
                            </div>
                            <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="printSection" id="printMovements" value="movements">
                            <label class="form-check-label" for="printMovements">รายงานการเคลื่อนไหว</label>
                            </div>
                            <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="printSection" id="printValue" value="value">
                            <label class="form-check-label" for="printValue">รายงานมูลค่าครุภัณฑ์</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            <button type="button" class="btn btn-primary" id="printSelectedReport">พิมพ์</button>
                        </div>
                        </div>
                    </div>
                    </div>
                    <!-- สถิติสรุป -->
                    <div id="report-summary">
                    <h5 class="mb-3"><i class="fas fa-boxes me-2"></i> รายงานสถานะครุภัณฑ์</h5>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary-gradient me-3">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo number_format($equipment_stats['total_quantity']); ?></h3>
                                        <p class="text-muted mb-0">ครุภัณฑ์ทั้งหมด</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success-gradient me-3">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo number_format($equipment_stats['available_quantity']); ?></h3>
                                        <p class="text-muted mb-0">จำนวนที่ว่าง</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning-gradient me-3">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo number_format($equipment_stats['borrowed_quantity']); ?></h3>
                                        <p class="text-muted mb-0">จำนวนที่ยืมอยู่</p>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- กราฟและแผนภูมิ -->
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i> กราฟและแผนภูมิ</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>สถานะการยืม-คืน</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height:260px;">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>การยืมตามเดือน</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height:260px;">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>มูลค่าครุภัณฑ์ตามปีงบประมาณ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height:260px;">
                                        <canvas id="budgetChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- รายงานการยืม-คืน -->
                    <div id="report-borrowings">
                    <h5 class="mb-3"><i class="fas fa-exchange-alt me-2"></i> รายงานการยืม-คืน</h5>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>วันที่ยืม</th>
                                            <th>ผู้ยืม</th>
                                            <th>ครุภัณฑ์</th>
                                            <th>เลขครุภัณฑ์</th>
                                            <th>จำนวน</th>
                                            <th>วันที่คืน</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result_borrowings)): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['model_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_number']); ?></td>
                                            <td><?php echo $row['quantity_borrowed']; ?></td>
                                            <td><?php echo $row['return_date'] ? date('d/m/Y', strtotime($row['return_date'])) : '-'; ?></td>
                                            <td><?php
                                                $status_labels = [
                                                    'pending' => '<span class="badge bg-warning">รออนุมัติ</span>',
                                                    'borrowed' => '<span class="badge bg-primary">กำลังยืม</span>',
                                                    'return_pending' => '<span class="badge bg-info">รอยืนยันการคืน</span>',
                                                    'returned' => '<span class="badge bg-success">คืนแล้ว</span>',
                                                    'cancelled' => '<span class="badge bg-secondary">ยกเลิก</span>',
                                                    'overdue' => '<span class="badge bg-danger">เกินกำหนด</span>'
                                                ];
                                                echo $status_labels[$row['status']] ?? $row['status'];
                                            ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    </div>
                    <!-- รายงานการเคลื่อนไหว -->
                    <div id="report-movements">
                    <h5 class="mb-3"><i class="fas fa-route me-2"></i> รายงานการเคลื่อนไหว (โอนย้าย)</h5>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>วันที่</th>
                                            <th>ครุภัณฑ์</th>
                                            <th>เลขครุภัณฑ์</th>
                                            <th>จาก</th>
                                            <th>ไปยัง</th>
                                            <th>ผู้ดำเนินการ</th>
                                            <th>หมายเหตุ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result_movements)): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['movement_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['model_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['from_location']); ?></td>
                                            <td><?php echo htmlspecialchars($row['to_location']); ?></td>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    </div>
                    <!-- รายงานมูลค่าครุภัณฑ์ -->
                    <div id="report-value">
                    <h5 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i> รายงานมูลค่าครุภัณฑ์</h5>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>หมวดหมู่</th>
                                            <th>จำนวนครุภัณฑ์</th>
                                            <th>มูลค่ารวม (บาท)</th>
                                            <th>มูลค่าเฉลี่ย (บาท)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result_category_value)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <td><?php echo number_format($row['item_count']); ?></td>
                                            <td><?php echo number_format($row['category_value'], 2); ?></td>
                                            <td><?php echo number_format($row['category_value'] / $row['item_count'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    </div>
                    <!-- กราฟ Chart.js -->
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                    // กราฟสถานะการยืม-คืน
                    new Chart(document.getElementById('statusChart'), {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo json_encode(array_column($status_chart_data, 'status')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($status_chart_data, 'count')); ?>,
                                backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#6c757d','#17a2b8']
                            }]
                        },
                        options: {responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}
                    });
                    // กราฟการยืมตามเดือน
                    new Chart(document.getElementById('monthlyChart'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_column($monthly_chart_data, 'month')); ?>,
                            datasets: [{
                                label: 'จำนวนการยืม',
                                data: <?php echo json_encode(array_column($monthly_chart_data, 'borrow_count')); ?>,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.1
                            }]
                        },
                        options: {responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}
                    });
                    // กราฟมูลค่าตามปีงบประมาณ
                    new Chart(document.getElementById('budgetChart'), {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_column($budget_chart_data, 'budget_year')); ?>,
                            datasets: [{
                                label: 'มูลค่าครุภัณฑ์ (บาท)',
                                data: <?php echo json_encode(array_map('floatval', array_column($budget_chart_data, 'total_value'))); ?>,
                                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}
                    });
                    </script>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
                    <script>
                    document.getElementById('btnPrintReport').addEventListener('click', function() {
                    var modal = new bootstrap.Modal(document.getElementById('printReportModal'));
                    modal.show();
                    });
                    document.getElementById('printSelectedReport').addEventListener('click', function() {
                    var selected = document.querySelector('input[name="printSection"]:checked').value;
                    var url = '';
                    if(selected === 'summary') url = 'print/report_summary.php';
                    if(selected === 'borrowings') url = 'print/report_borrowings.php';
                    if(selected === 'movements') url = 'print/report_movements.php';
                    if(selected === 'value') url = 'print/report_value.php';
                    window.open(url, '_blank', 'width=1100,height=900');
                    var modal = bootstrap.Modal.getInstance(document.getElementById('printReportModal'));
                    modal.hide();
                    });
                    </script>
                </div>
            </div>
        </div>
    </div>
    <footer style="text-align: center; padding: 5px; font-size: 14px; color: #555; background-color: #f9f9f9;">
        <img src="img/logo3.png" alt="โลโก้มหาวิทยาลัย" style="height: 40px; vertical-align: middle; margin-right: 10px;">
        พัฒนาโดย นายชินกร ทองสอาด และ นางสาวซากีหนะต์ ปรังเจะ
        | สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี
        | © 2025
    </footer>
</body>
</html>
