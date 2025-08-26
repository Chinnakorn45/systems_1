<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Database connection settings
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'borrowing_db'; // <-- เปลี่ยนชื่อตามฐานข้อมูลของคุณ

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle AJAX POST ลบ user (ต้องอยู่ก่อน output HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param('i', $id);
    $success = false;
    $msg = '';
    if ($stmt->execute()) {
        $success = true;
        $msg = 'ลบข้อมูลสำเร็จ';
        // Log delete user
        if (isset($_SESSION['user_id'])) {
            $detail = 'ลบผู้ใช้ user_id: ' . $id;
            $conn->query("INSERT INTO user_logs (user_id, username, event_type, event_detail) VALUES (".intval($_SESSION['user_id']).", '".$conn->real_escape_string($_SESSION['username'])."', 'delete_user', '".$conn->real_escape_string($detail)."')");
        }
    } else {
        $msg = 'เกิดข้อผิดพลาด';
    }
    $stmt->close();
    echo json_encode(['success'=>$success, 'msg'=>$msg]);
    $conn->close();
    exit;
}

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = $search ? "WHERE full_name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%'" : '';

// Fetch users
$sql = "SELECT * FROM users $where ORDER BY created_at DESC";
$result = $conn->query($sql);

// Dummy role for demo (replace with session role in real use)
$current_role = 'admin'; // เปลี่ยนเป็นระบบ session จริงในอนาคต

// Handle alert from query string
$alert = '';
if (isset($_GET['success'])) {
    $alert = '<div class="alert" style="background:#d4edda; color:#155724; border-color:#c3e6cb;">' . htmlspecialchars($_GET['success']) . '</div>'; // Success alert colors
} elseif (isset($_GET['error'])) {
    $alert = '<div class="alert" style="background:#f8d7da; color:#721c24; border-color:#f5c6cb;">' . htmlspecialchars($_GET['error']) . '</div>'; // Error alert colors
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบฐานข้อมูลพนักงาน</title>
    <link rel="stylesheet" href="user-crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ===== ล็อกทั้งหน้าและจัดเลย์เอาต์ให้เลื่อนเฉพาะตาราง ===== */
        html, body {
            height: 100%;
            overflow: hidden; /* ล็อกหน้า */
        }
        body {
            margin: 0;
        }
        main {
            height: 100%;
            display: flex;
        }
        .user-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 85%;
            min-height: 0; /* สำคัญกับ flex + overflow */
        }
        .user-header {
            flex: 0 0 auto;
            background: #fff;
            position: relative;
            z-index: 3;
            box-shadow: 0 1px 0 rgba(0,0,0,.06);
        }
        /* พื้นที่ตารางที่สกอลล์ได้ */
        .table-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto; /* เลื่อนเฉพาะส่วนนี้ */
            border-top: 1px solid #e5e7eb;
            background: #fff;
        }
        /* กำหนดความสูงสูงสุด (ถ้าต้องการ) เช่น กันโต๊ะชนขอบล่างจอ
           .table-scroll { max-height: calc(100vh - 220px); } */

        /* ===== ตาราง + sticky header ===== */
        .user-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .user-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #ffffff;
        }
        .user-table th,
        .user-table td {
            padding: 10px 12px;
            line-height: 1.4;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        /* toolbar และส่วนค้นหา */
        .user-header .user-toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .user-search-box {
            display: flex;
            gap: 8px;
        }
        .user-search-box input[type="text"] {
            padding: 8px 10px;
        }
        .user-search-box button,
        .user-manage-btn,
        .user-add-btn,
        .dep-btn {
            cursor: pointer;
        }

        /* โมดัล (เดิมของคุณ) */
        .popup-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none; /* เริ่มปิดไว้ */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .popup-content {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        .popup-content .msg {
            font-size: 20px;
            font-weight: 500;
        }

        /* แถบแจ้งเตือน */
        .alert {
            margin: 12px 0;
            padding: 10px 12px;
            border: 1px solid transparent;
            border-radius: 6px;
        }
        /* ปุ่มในคอลัมน์จัดการ */
        .user-actions .dep-btn.edit,
        .user-actions .dep-btn.delete {
            margin: 2px 4px;
        }
        /* ช่วยให้ข้อความยาวตัดบรรทัดได้ */
        .full_name, .username, .email, .department, .position, .role {
            word-break: break-word;
        }
    </style>
</head>
<body>
    <main>
        <div class="user-card">
            <div class="user-header">
                <div class="user-toolbar" style="padding:16px;">
                    <div>
                        <h2 class="user-title" style="margin:0;"><i class="fas fa-address-book"></i> ระบบฐานข้อมูลพนักงาน</h2>
                        <p class="user-desc" style="margin:6px 0 0;">จัดการข้อมูลพนักงาน เพิ่ม แก้ไข ลบ และค้นหาข้อมูล พร้อมกำหนดสิทธิ์การเข้าถึง</p>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <form class="user-search-box" method="get">
                            <input type="text" name="search" placeholder="ค้นหาชื่อ, username, email" value="<?= htmlspecialchars($search) ?>">
                            <button type="submit">ค้นหา</button>
                        </form>
                        <div style="display:flex; gap:10px;">
                            <a href="departments.php" class="user-manage-btn">จัดการแผนก/ฝ่าย</a>
                            <?php if ($current_role === 'admin'): ?>
                                <a href="add_user.php" class="user-add-btn">+ เพิ่มพนักงาน</a>
                            <?php endif; ?>
                            <a href="logout.php" class="user-manage-btn" style="background-color: #dc3545; color:#fff; padding:8px 10px; border-radius:4px;" onclick="return confirm('คุณต้องการออกจากระบบหรือไม่?')">
                                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                            </a>
                        </div>
                    </div>
                </div>
                <?php if ($alert) echo $alert; ?>
            </div>

            <!-- พื้นที่ตารางแบบเลื่อน -->
            <div class="table-scroll">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ชื่อ-นามสกุล</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>แผนก/ฝ่าย</th>
                            <th>ตำแหน่ง</th>
                            <th>บทบาท</th>
                            <th>วันที่สร้าง</th>
                            <?php if ($current_role === 'admin'): ?><th>การจัดการ</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="full_name"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="username"><?= htmlspecialchars($row['username']) ?></td>
                            <td class="email"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="department"><?= htmlspecialchars($row['department']) ?></td>
                            <td class="position"><?= htmlspecialchars($row['position']) ?></td>
                            <td class="role"><?= htmlspecialchars($row['role']) ?></td>
                            <td class="created_at">
                                <?php
                                    $dt = strtotime($row['created_at']);
                                    echo date('d/m/', $dt) . (date('Y', $dt) + 543);
                                ?>
                            </td>
                            <?php if ($current_role === 'admin'): ?>
                            <td class="user-actions">
                                <form method="get" action="edit_user.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>">
                                    <button type="submit" class="dep-btn edit" title="แก้ไข"><i class="fas fa-pen"></i> แก้ไข</button>
                                </form>
                                <form method="get" action="#" style="display:inline;" class="delete-user-form">
                                    <input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>">
                                    <button type="button" class="dep-btn delete" title="ลบ" onclick="openDeleteModal(<?= (int)$row['user_id'] ?>, '<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal: ยืนยันลบ -->
    <div id="deleteModal" class="popup-modal">
        <div class="popup-content">
            <i class="fas fa-exclamation-triangle" style="color:#d32f2f;font-size:38px;"></i>
            <div class="msg" style="color:#222; font-size:18px;">ยืนยันการลบพนักงาน</div>
            <div id="deleteName" style="font-size:16px; margin-bottom:12px;"></div>
            <div style="display:flex; gap:10px;">
                <button id="confirmDeleteBtn" class="dep-btn delete" style="min-width:80px;">ลบ</button>
                <button onclick="closeDeleteModal()" class="dep-btn" style="background:#6c757d;color:#fff;min-width:80px;">ยกเลิก</button>
            </div>
        </div>
    </div>

    <!-- Modal: แสดงผลลัพธ์ -->
    <div id="resultPopup" class="popup-modal">
        <div class="popup-content">
            <i id="resultIcon" class="fas" style="font-size:48px;"></i>
            <div id="resultMsg" class="msg"></div>
            <div style="font-size:14px; color:#888;">กำลังโหลดข้อมูลใหม่...</div>
        </div>
    </div>

    <script>
        let deleteUserId = null;

        function openDeleteModal(id, name) {
            deleteUserId = id;
            document.getElementById('deleteName').innerText = name || '';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteUserId = null;
        }

        // ใช้ modal แทน confirm เดิม
        document.querySelectorAll('.user-actions .dep-btn.delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const tr = btn.closest('tr');
                const name = tr.querySelector('.full_name')?.innerText || '';
                const id = tr.querySelector('input[name="id"]')?.value || '';
                if (id) openDeleteModal(parseInt(id), name);
            });
        });

        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (!deleteUserId) return;

            // AJAX POST ลบ user
            fetch('users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'delete_user=1&id=' + encodeURIComponent(deleteUserId)
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                showResultPopup(data.success, data.msg);
                setTimeout(() => { window.location.reload(); }, 1300);
            })
            .catch(error => {
                console.error('Error:', error);
                closeDeleteModal();
                showResultPopup(false, 'เกิดข้อผิดพลาดในการส่งคำขอ');
                setTimeout(() => { window.location.reload(); }, 1300);
            });
        };

        function showResultPopup(success, msg) {
            const popup = document.getElementById('resultPopup');
            const icon = document.getElementById('resultIcon');
            const msgDiv = document.getElementById('resultMsg');

            if (success) {
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#28a745';
                msgDiv.innerText = msg || 'ลบข้อมูลสำเร็จ';
                msgDiv.style.color = '#28a745';
            } else {
                icon.className = 'fas fa-times-circle';
                icon.style.color = '#dc3545';
                msgDiv.innerText = msg || 'เกิดข้อผิดพลาด';
                msgDiv.style.color = '#dc3545';
            }
            popup.style.display = 'flex';
        }

        // แสดง popup ตาม query string
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success') || urlParams.has('error')) {
                const message = urlParams.has('success') ? urlParams.get('success') : urlParams.get('error');
                const isSuccess = urlParams.has('success');
                showResultPopup(isSuccess, message);
                setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('success');
                    url.searchParams.delete('error');
                    window.history.replaceState({}, document.title, url.toString());
                }, 1300);
            }
        };
    </script>
</body>
</html>
<?php $conn->close(); ?>
