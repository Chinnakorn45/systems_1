<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

/* ----------------- เตรียมตัวแปร ----------------- */
$model_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$model_name = '';
$brand_id = 0;

$model_name_err = '';
$brand_id_err = '';
$is_edit = $model_id > 0;

/* ----------------- ดึงรายชื่อยี่ห้อ ----------------- */
mysqli_set_charset($link, 'utf8mb4');
mysqli_query($link, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$brands = [];
if ($res = mysqli_query($link, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name")) {
    while ($row = mysqli_fetch_assoc($res)) $brands[] = $row;
}

/* ----------------- ถ้าเป็นโหมดแก้ไข โหลดข้อมูลเดิม ----------------- */
if ($is_edit) {
    $sql = "SELECT model_name, brand_id FROM models WHERE model_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $model_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $model_name = $row['model_name'];
            $brand_id   = (int)$row['brand_id'];
        } else {
            // ไม่พบข้อมูล -> กลับไปหน้า brands
            mysqli_stmt_close($stmt);
            header("location: brands.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

/* ----------------- เมื่อโพสต์ฟอร์ม ----------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model_name = trim($_POST['model_name'] ?? '');
    $brand_id   = intval($_POST['brand_id'] ?? 0);

    if ($model_name === '') {
        $model_name_err = 'กรุณากรอกชื่อรุ่น';
    } elseif (mb_strlen($model_name) > 150) {
        $model_name_err = 'ชื่อรุ่นต้องไม่เกิน 150 ตัวอักษร';
    }

    if ($brand_id <= 0) {
        $brand_id_err = 'กรุณาเลือกยี่ห้อ';
    }

    // ตรวจชื่อรุ่นซ้ำในยี่ห้อเดียวกัน (ไม่สนตัวพิมพ์) ยกเว้นเรคคอร์ดตัวเอง
    if ($model_name_err === '' && $brand_id_err === '') {
        $sql = "SELECT model_id FROM models 
                WHERE LOWER(model_name) = LOWER(?) AND brand_id = ?" . ($is_edit ? " AND model_id != ?" : "");
        if ($stmt = mysqli_prepare($link, $sql)) {
            if ($is_edit) {
                mysqli_stmt_bind_param($stmt, "sii", $model_name, $brand_id, $model_id);
            } else {
                mysqli_stmt_bind_param($stmt, "si", $model_name, $brand_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $model_name_err = 'ชื่อรุ่นนี้มีอยู่แล้วในยี่ห้อเดียวกัน';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // บันทึก
    if ($model_name_err === '' && $brand_id_err === '') {
        if ($is_edit) {
            $sql = "UPDATE models SET model_name = ?, brand_id = ? WHERE model_id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sii", $model_name, $brand_id, $model_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: brands.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "INSERT INTO models (model_name, brand_id) VALUES (?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $model_name, $brand_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: brands.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>ชื่อรุ่น - ระบบบันทึกคลังครุภัณฑ์</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root{
            --gradient-start:#5b7cfa; /* อินดิโก้-ฟ้า สุภาพ */
            --gradient-end:#6d4fc2;   /* ม่วงนุ่ม */
            --card-bg:#ffffff;
            --text-main:#1f2937;
            --muted:#6b7280;
        }
        html, body {
            height: 100%;
            background: radial-gradient(1200px 600px at 20% -10%, #eef2ff 0%, #f8fafc 40%, #f1f5f9 100%) fixed;
            color: var(--text-main);
        }
        /* ปุ่มหลักโทนไล่สี */
        .btn-main {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(93, 76, 194, 0.28);
        }
        .btn-main:hover { filter: brightness(0.98); color:#fff; }
        .btn-secondary {
            border-radius: 12px;
        }
        /* โมดัลสวย ๆ */
        .modal-content {
            border: 0;
            border-radius: 16px;
            background: var(--card-bg);
            box-shadow: 0 20px 60px rgba(17, 24, 39, .15);
        }
        .modal-header {
            border: 0;
            padding-bottom: 0.25rem;
        }
        .modal-title {
            font-weight: 700;
            letter-spacing: .2px;
        }
        .form-label { font-weight: 600; }
        .form-control, .form-select {
            border-radius: 12px;
        }
        .brand-chip {
            display:inline-flex; align-items:center; gap:.5rem;
            padding:.35rem .6rem; border-radius:999px;
            background: #eef2ff; color:#4338ca; font-size:.85rem;
        }
        /* ปรับ backdrop ให้ละมุน */
        .modal-backdrop.show { opacity: .4; }
    </style>
</head>
<body>

    <!-- Modal -->
    <div class="modal fade" id="modelModal" tabindex="-1" aria-labelledby="modelModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header px-4 pt-4">
                    <h5 class="modal-title" id="modelModalLabel">
                        <?php echo $is_edit ? 'แก้ไขชื่อรุ่น' : 'เพิ่มชื่อรุ่นใหม่'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" title="ปิด"></button>
                </div>
                <div class="modal-body px-4 pb-2">
                    <p class="text-secondary mb-3" style="margin-top:-.25rem">
                        ระบุชื่อรุ่นและเลือกยี่ห้อ <span class="brand-chip">รุ่น / Model</span>
                    </p>

                    <form id="modelForm" action="" method="post" novalidate>
                        <div class="mb-3">
                            <label for="model_name" class="form-label">ชื่อรุ่น</label>
                            <input
                                type="text"
                                class="form-control <?php echo $model_name_err ? 'is-invalid' : ''; ?>"
                                id="model_name"
                                name="model_name"
                                value="<?php echo htmlspecialchars($model_name); ?>"
                                required
                                maxlength="150"
                                autocomplete="off"
                                placeholder="เช่น ThinkPad T14, LaserJet Pro, Latitude 5440">
                            <div class="invalid-feedback">
                                <?php echo $model_name_err ?: 'กรุณากรอกชื่อรุ่น'; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="brand_id" class="form-label">ยี่ห้อ</label>
                            <select
                                class="form-select <?php echo $brand_id_err ? 'is-invalid' : ''; ?>"
                                id="brand_id"
                                name="brand_id"
                                required>
                                <option value="">-- เลือกยี่ห้อ --</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo (int)$b['brand_id']; ?>" <?php echo ((int)$brand_id === (int)$b['brand_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['brand_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                <?php echo $brand_id_err ?: 'กรุณาเลือกยี่ห้อ'; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-main" id="submitBtn">
                                <?php echo $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มชื่อรุ่น'; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="px-4 pb-4">
                    <small class="text-muted">ระบบบันทึกคลังครุภัณฑ์</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (bundle) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // เปิดโมดัลทันทีเมื่อหน้าโหลด
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('modelModal');
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
            modal.show();

            // โฟกัสช่องกรอกเมื่อโมดัลแสดง
            modalEl.addEventListener('shown.bs.modal', function () {
                const input = document.getElementById('model_name');
                if (input) input.focus();
            });

            // เมื่อปิดโมดัล ให้กลับไปหน้า brands.php
            modalEl.addEventListener('hidden.bs.modal', function () {
                window.location.href = 'brands.php';
            });

            // กันกดปุ่มซ้ำตอน submit + ตรวจความถูกต้องฟอร์ม
            const form = document.getElementById('modelForm');
            const submitBtn = document.getElementById('submitBtn');
            form.addEventListener('submit', function(e){
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                } else {
                    submitBtn.disabled = true;
                    submitBtn.innerText = '<?php echo $is_edit ? 'กำลังบันทึก...' : 'กำลังเพิ่ม...'; ?>';
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>
