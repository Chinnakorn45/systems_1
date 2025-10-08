<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'procurement', 'staff'])) {
    header('Location: login.php');
    exit;
}

/* ===== ส่วนของการดึงข้อมูลจากฐานข้อมูล ===== */
// ดึงข้อมูลจำนวนการแจ้งซ่อมทั้งหมด
$total_repairs = $conn->query("SELECT COUNT(*) FROM repairs")->fetch_row()[0];

// ดึงสถานะล่าสุดจาก repair_logs (ปรับปรุงประสิทธิภาพ)
$latest_statuses_query = $conn->query("
    SELECT r.repair_id, COALESCE(latest.status, r.status) AS current_status
    FROM repairs r
    LEFT JOIN (
        SELECT repair_id, status, updated_at
        FROM repair_logs
        WHERE (repair_id, updated_at) IN (
            SELECT repair_id, MAX(updated_at)
            FROM repair_logs
            GROUP BY repair_id
        )
    ) latest ON r.repair_id = latest.repair_id
");
$latest_statuses = [];
while ($row = $latest_statuses_query->fetch_assoc()) {
    $latest_statuses[$row['repair_id']] = $row['current_status'];
}

// นับจำนวนตามสถานะ
$pending_repairs = 0;
$inprogress_repairs = 0;
$done_repairs = 0;
$cancelled_repairs = 0;

foreach ($latest_statuses as $status) {
    switch ($status) {
        case 'pending':
        case '':
            $pending_repairs++;
            break;
        case 'received':
        case 'evaluate_it':
        case 'evaluate_repairable':
        case 'evaluate_external':
        case 'evaluate_disposal':
        case 'external_repair':
        case 'procurement_managing':
        case 'procurement_returned':
        case 'waiting_delivery':
        case 'in_progress':
            $inprogress_repairs++;
            break;
        case 'done':
        case 'delivered':
        case 'repair_completed':
            $done_repairs++;
            break;
        case 'cancelled':
            $cancelled_repairs++;
            break;
    }
}

$done_percent = $total_repairs > 0 ? round(($done_repairs / $total_repairs) * 100, 1) : 0;

/* ===== ดึงรายการล่าสุด 5 รายการ ===== */
$latest_repairs = [];
$sql_latest = "
    SELECT r.repair_id, r.created_at, r.asset_number, r.model_name, r.location_name,
           u_reported.full_name AS reporter, u_reported.department
    FROM repairs r
    LEFT JOIN users u_reported ON r.reported_by = u_reported.user_id
    ORDER BY r.created_at DESC
    LIMIT 5
";
$res_latest = $conn->query($sql_latest);
while ($row = $res_latest->fetch_assoc()) {
    $row['current_status'] = $latest_statuses[$row['repair_id']] ?? '';
    $latest_repairs[] = $row;
}

/* ===== ดึงรายการแจ้งซ่อมใหม่ที่ค้าง 5 รายการ ===== */
// แก้ไขปัญหา 'repair_id' is ambiguous ที่นี่
$alerts = [];
$sql_pending_list = "
    SELECT r.repair_id, r.model_name, r.asset_number, r.location_name, r.created_at,
           u.full_name AS reporter, u.department
    FROM repairs r
    LEFT JOIN users u ON r.reported_by = u.user_id
    LEFT JOIN (
        SELECT repair_id, status 
        FROM repair_logs 
        WHERE (repair_id, updated_at) IN (
            SELECT repair_id, MAX(updated_at)
            FROM repair_logs
            GROUP BY repair_id
        )
    ) latest ON r.repair_id = latest.repair_id
    WHERE COALESCE(latest.status, r.status) IN ('pending', '')
    ORDER BY r.created_at DESC
    LIMIT 5
";
$res_pending_list = $conn->query($sql_pending_list);
while ($row = $res_pending_list->fetch_assoc()) {
    $alerts[] = $row;
}

/* ===== ดึงข้อมูล Top 5 สำหรับกราฟ ===== */
$top5_labels = [];
$top5_counts = [];
$top5_title = 'Top 5 หมวดหมู่ที่ส่งซ่อมบ่อยสุด';

$use_items_category = false;
$has_items_table = $conn->query("SHOW TABLES LIKE 'items'");
if ($has_items_table && $has_items_table->num_rows > 0) {
    $has_category_id_col = $conn->query("SHOW COLUMNS FROM items LIKE 'category_id'");
    if ($has_category_id_col && $has_category_id_col->num_rows > 0) {
        $use_items_category = true;
    }
}
$sql_top5 = "";
if ($use_items_category) {
    $sql_top5 = "
        SELECT COALESCE(c.category_name, 'ไม่ระบุ') AS cat_name, COUNT(*) AS total_cnt
        FROM repairs r
        LEFT JOIN items i ON i.item_id = r.item_id
        LEFT JOIN categories c ON c.category_id = i.category_id
        GROUP BY cat_name
        ORDER BY total_cnt DESC
        LIMIT 5
    ";
} else {
    $sql_top5 = "
        SELECT COALESCE(r.brand, 'ไม่ระบุ') AS cat_name, COUNT(*) AS total_cnt
        FROM repairs r
        GROUP BY cat_name
        ORDER BY total_cnt DESC
        LIMIT 5
    ";
    $top5_title = 'Top 5 หมวดหมู่ที่ส่งซ่อมบ่อยสุด';
}
$res_top5 = $conn->query($sql_top5);
if ($res_top5) {
    while ($row = $res_top5->fetch_assoc()) {
        $top5_labels[] = $row['cat_name'];
        $top5_counts[] = (int)$row['total_cnt'];
    }
}

/* ===== helpers ===== */
function status_badge($status)
{
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
        'pending' => ['รอดำเนินการ', 'secondary'],
        'in_progress' => ['กำลังซ่อม', 'info'],
        'done' => ['ซ่อมเสร็จ', 'success'],
        '' => ['รอดำเนินการ', 'secondary'],
    ];
    if (!isset($map[$status])) return "<span class='badge bg-secondary'>ไม่ระบุสถานะ</span>";
    [$th, $color] = $map[$status];
    return "<span class='badge bg-$color'>$th</span>";
}
function thaidate($date, $format = 'j F Y')
{
    $ts = strtotime($date);
    $thai_months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $day = date('j', $ts);
    $month = (int)date('n', $ts);
    $year = date('Y', $ts) + 543;
    return "$day {$thai_months[$month]} $year";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        #top5CategoriesChart {
            height: 240px !important;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="container mt-5">
        <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h2>

        <div class="row g-4 dashboard-section align-items-stretch">
            <div class="col-md-4 d-flex">
                <div class="dashboard-card p-4 bg-white w-100 d-flex flex-column">
                    <div class="d-flex align-items-center mb-2 flex-shrink-0">
                        <span class="icon text-warning"><i class="fas fa-tools"></i></span>
                        <span class="ms-auto text-muted">การแจ้งซ่อมทั้งหมด</span>
                    </div>
                    <div class="number text-warning"><?= $total_repairs ?></div>
                    <div class="small text-danger"><i class="fas fa-exclamation-circle"></i> <?= $pending_repairs ?> รอดำเนินการ</div>
                    <div class="small text-info"><i class="fas fa-cog"></i> <?= $inprogress_repairs ?> กำลังซ่อม</div>
                    <div class="small text-secondary"><i class="fas fa-times"></i> <?= $cancelled_repairs ?> ยกเลิก</div>
                </div>
            </div>
            <div class="col-md-4 d-flex">
                <div class="dashboard-card p-4 bg-white w-100 d-flex flex-column">
                    <div class="d-flex align-items-center mb-2 flex-shrink-0">
                        <span class="icon text-success"><i class="fas fa-check"></i></span>
                        <span class="ms-auto text-muted">การซ่อมเสร็จสิ้น</span>
                    </div>
                    <div class="number text-success"><?= $done_repairs ?></div>
                    <div class="small text-success"><i class="fas fa-arrow-up"></i> <?= $done_percent ?>% อัตราเสร็จสิ้น</div>
                </div>
            </div>
            <div class="col-md-4 d-flex">
                <div class="dashboard-card p-4 bg-white w-100 d-flex flex-column">
                    <div class="mb-2 fw-bold text-warning flex-shrink-0"><i class="fas fa-exclamation-triangle me-2"></i> รายการแจ้งซ่อมใหม่ที่รอดำเนินการ</div>
                    <div class="dashboard-alert-list flex-grow-1" style="max-height: 150px; overflow-y: auto;">
                        <?php foreach ($alerts as $a) : ?>
                            <div class="dashboard-alert">
                                <div class="fw-bold"><?= htmlspecialchars($a['model_name'] ?: 'ไม่ระบุ') ?></div>
                                <div class="small text-muted">
                                    <?= htmlspecialchars(($a['asset_number'] ? $a['asset_number'] . ' - ' : '') . ($a['location_name'] ?: '-')) ?><br>
                                    <span class="text-muted"><?= htmlspecialchars($a['reporter'] ?: '-') ?></span><br>
                                    <span class="text-danger"><i class="fas fa-exclamation-circle"></i> แจ้งใหม่ ยังไม่ดำเนินการ</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-0 align-items-stretch">
            <div class="col-lg-8 d-flex">
                <div class="dashboard-card p-4 bg-white w-100">
                    <div class="mb-3 fw-bold"><i class="fas fa-list me-2"></i> การแจ้งซ่อมล่าสุด</div>
                    <div class="table-responsive">
                        <table class="table dashboard-table table-sm">
                            <thead>
                                <tr>
                                    <th>รหัส</th>
                                    <th>ครุภัณฑ์</th>
                                    <th>ผู้แจ้ง</th>
                                    <th>แผนก</th>
                                    <th>สถานะ</th>
                                    <th>วันที่แจ้ง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_repairs as $r) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['repair_id']) ?></td>
                                        <td><?= htmlspecialchars($r['model_name'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($r['reporter'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($r['department'] ?: '-') ?></td>
                                        <td><?= status_badge($r['current_status']) ?></td>
                                        <td><?= thaidate($r['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 d-flex">
                <div class="dashboard-card p-4 bg-white w-100">
                    <div class="mb-3 fw-bold">
                        <i class="fas fa-chart-bar me-2"></i> <?= htmlspecialchars($top5_title) ?>
                    </div>
                    <canvas id="top5CategoriesChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            const labels = <?= json_encode($top5_labels, JSON_UNESCAPED_UNICODE) ?>;
            const data = <?= json_encode($top5_counts, JSON_UNESCAPED_UNICODE) ?>;
            const el = document.getElementById('top5CategoriesChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            const palette = [
                [220, 53, 69], [255, 193, 7], [25, 135, 84], [13, 110, 253], [108, 117, 125],
            ];
            const bgColors = labels.map((_, i) => {
                const [r, g, b] = palette[i % palette.length];
                return `rgba(${r}, ${g}, ${b}, 0.25)`;
            });
            const borderColors = labels.map((_, i) => {
                const [r, g, b] = palette[i % palette.length];
                return `rgba(${r}, ${g}, ${b}, 1)`;
            });

            const valueLabelPlugin = {
                id: 'valueLabel',
                afterDatasetsDraw(chart) {
                    const {
                        ctx
                    } = chart;
                    const meta = chart.getDatasetMeta(0);
                    const ds = chart.data.datasets[0];
                    if (!meta || !ds) return;
                    ctx.save();
                    ctx.font = '12px sans-serif';
                    ctx.fillStyle = '#333';
                    meta.data.forEach((bar, i) => {
                        const val = ds.data?.[i];
                        if (val == null) return;
                        ctx.fillText(val, bar.x + 8, bar.y + 4);
                    });
                    ctx.restore();
                }
            };

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'จำนวนแจ้งซ่อม',
                        data,
                        backgroundColor: bgColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 22,
                        maxBarThickness: 26
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,

                    interaction: {
                        mode: 'nearest',
                        intersect: true,
                    },

                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            displayColors: true,
                            callbacks: {
                                title: (items) => items?.[0]?.label ?? '',
                                label: (ctx) => `  ${ctx.parsed.x} ครั้ง`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                callback(v) {
                                    const lbl = this.getLabelForValue(v) || '';
                                    return lbl.length > 26 ? lbl.slice(0, 24) + '…' : lbl;
                                }
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 8,
                            right: 8,
                            bottom: 8,
                            left: 8
                        }
                    }
                },
                plugins: [valueLabelPlugin]
            });
        })();
    </script>
<?php include 'toast.php'; ?>
</body>

</html>
