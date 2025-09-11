<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$brand_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$brand_name = '';
$brand_name_err = '';
$is_edit = false;

// ถ้าเป็นการแก้ไข ดึงข้อมูลเดิมมาแสดง
if ($brand_id > 0) {
    $is_edit = true;
    $sql = "SELECT * FROM brands WHERE brand_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $brand_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $brand_name = $row['brand_name'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand_name = trim($_POST['brand_name']);
    if (empty($brand_name)) {
        $brand_name_err = "กรุณากรอกชื่อยี่ห้อ";
    } else {
        // ตรวจสอบชื่อซ้ำ (ไม่สนตัวพิมพ์เล็ก/ใหญ่) และยกเว้นเรคคอร์ดของตัวเองหากเป็นโหมดแก้ไข
        $sql = "SELECT brand_id FROM brands WHERE LOWER(brand_name) = LOWER(?)" . ($is_edit ? " AND brand_id != ?" : "");
        if ($stmt = mysqli_prepare($link, $sql)) {
            if ($is_edit) {
                mysqli_stmt_bind_param($stmt, "si", $brand_name, $brand_id);
            } else {
                mysqli_stmt_bind_param($stmt, "s", $brand_name);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $brand_name_err = "ชื่อยี่ห้อนี้ถูกใช้แล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (empty($brand_name_err)) {
        if ($is_edit) {
            $sql = "UPDATE brands SET brand_name = ? WHERE brand_id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $brand_name, $brand_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("location: brands.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $sql = "INSERT INTO brands (brand_name) VALUES (?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $brand_name);
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
    <title><?php echo $is_edit ? 'แก้ไข' : 'เพิ่ม'; ?>ยี่ห้อ - ระบบบันทึกคลังครุภัณฑ์</title>

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
        .form-control {
            border-radius: 12px;
        }
        .brand-chip {
            display:inline-flex; align-items:center; gap:.5rem;
            padding:.35rem .6rem; border-radius:999px;
            background: #eef2ff; color:#4338ca; font-size:.85rem;
        }
        /* ปรับ backdrop ให้ละมุน */
        .modal-backdrop.show {
            opacity: .4;
        }
    </style>
</head>
<body>

    <!-- Modal -->
    <div class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="brandModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header px-4 pt-4">
                    <h5 class="modal-title" id="brandModalLabel">
                        <?php if ($is_edit): ?>
                            แก้ไขยี่ห้อ
                        <?php else: ?>
                            เพิ่มยี่ห้อใหม่
                        <?php endif; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" title="ปิด"></button>
                </div>
                <div class="modal-body px-4 pb-2">
                    <p class="text-secondary mb-3" style="margin-top:-.25rem">
                        ระบุชื่อยี่ห้อของครุภัณฑ์ <span class="brand-chip">ยี่ห้อ / Brand</span>
                    </p>

                    <form id="brandForm" action="" method="post" novalidate>
                        <div class="mb-3">
                            <label for="brand_name" class="form-label">ชื่อยี่ห้อ</label>
                            <input
                                type="text"
                                class="form-control <?php echo !empty($brand_name_err) ? 'is-invalid' : ''; ?>"
                                id="brand_name"
                                name="brand_name"
                                value="<?php echo htmlspecialchars($brand_name); ?>"
                                required
                                maxlength="100"
                                autocomplete="off"
                                placeholder="เช่น HP, Dell, Lenovo, Canon">
                            <div class="invalid-feedback">
                                <?php echo $brand_name_err ?: 'กรุณากรอกชื่อยี่ห้อ'; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-main" id="submitBtn">
                                <?php echo $is_edit ? 'บันทึกการแก้ไข' : 'เพิ่มยี่ห้อ'; ?>
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
            const modalEl = document.getElementById('brandModal');
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
            modal.show();

            // โฟกัสช่องกรอกเมื่อโมดัลแสดง
            modalEl.addEventListener('shown.bs.modal', function () {
                const input = document.getElementById('brand_name');
                if (input) input.focus();
            });

            // เมื่อปิดโมดัล ให้กลับไปหน้า brands.php
            modalEl.addEventListener('hidden.bs.modal', function () {
                window.location.href = 'brands.php';
            });

            // กันกดปุ่มซ้ำตอน submit + ตรวจความถูกต้องฟอร์ม
            const form = document.getElementById('brandForm');
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
