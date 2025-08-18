<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Database connection settings
require_once __DIR__ . '/../db.php';
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
        /* Add specific style for popup modals */
        .popup-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
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
    </style>
</head>
<body>
    <main>
        <div class="user-card">
            <div class="user-header">
                <div>
                    <h2 class="user-title"><i class="fas fa-address-book"></i> ระบบฐานข้อมูลพนักงาน</h2>
                    <p class="user-desc">จัดการข้อมูลพนักงาน เพิ่ม แก้ไข ลบ และค้นหาข้อมูล พร้อมกำหนดสิทธิ์การเข้าถึง</p>
                </div>
                <div class="user-toolbar">
                    <form class="user-search-box" method="get">
                        <input type="text" name="search" placeholder="ค้นหาชื่อ, username, email" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit">ค้นหา</button>
                    </form>
                    <div style="display:flex; gap:10px;">
                        <a href="departments.php" class="user-manage-btn">จัดการแผนก/ฝ่าย</a>
                        <?php if ($current_role === 'admin'): ?>
                            <a href="add_user.php" class="user-add-btn">+ เพิ่มพนักงาน</a>
                        <?php endif; ?>
                        <a href="logout.php" class="user-manage-btn" style="background-color: #dc3545;" onclick="return confirm('คุณต้องการออกจากระบบหรือไม่?')">
                            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
            <?php if ($alert) echo $alert; ?>
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
                        <td class="username"> <?= htmlspecialchars($row['username']) ?></td>
                        <td class="email"> <?= htmlspecialchars($row['email']) ?></td>
                        <td class="department"> <?= htmlspecialchars($row['department']) ?></td>
                        <td class="position"> <?= htmlspecialchars($row['position']) ?></td>
                        <td class="role"> <?= htmlspecialchars($row['role']) ?></td>
                        <td class="created_at"><?php
    $dt = strtotime($row['created_at']);
    echo date('d/m/', $dt) . (date('Y', $dt) + 543);
?></td>
                        <?php if ($current_role === 'admin'): ?>
                        <td class="user-actions">
                            <form method="get" action="edit_user.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['user_id'] ?>">
                                <button type="submit" class="dep-btn edit" title="แก้ไข"><i class="fas fa-pen"></i>แก้ไข</button>
                            </form>
                            <form method="get" action="#" style="display:inline;" class="delete-user-form">
                                <input type="hidden" name="id" value="<?= $row['user_id'] ?>">
                                <button type="button" class="dep-btn delete" title="ลบ" onclick="openDeleteModal(<?= $row['user_id'] ?>, '<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i>ลบ</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
    <div id="deleteModal" class="popup-modal" style="display:none;">
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
    <div id="resultPopup" class="popup-modal" style="display:none;">
        <div class="popup-content">
            <i id="resultIcon" class="fas" style="font-size:48px;"></i>
            <div id="resultMsg" class="msg"></div>
            <div style="font-size:14px; color:#888;">กำลังโหลดข้อมูลใหม่...</div>
        </div>
    </div>
    <script>
        let deleteUserId = null;
        let deleteUserName = '';

        function openDeleteModal(id, name) {
            deleteUserId = id;
            deleteUserName = name;
            document.getElementById('deleteName').innerText = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteUserId = null;
        }

        // Modified to trigger custom modal instead of default confirm
        document.querySelectorAll('.user-actions .dep-btn.delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default form submission
                const tr = btn.closest('tr');
                const name = tr.querySelector('.full_name').innerText;
                // Get user ID from the hidden input within the same form
                const id = tr.querySelector('input[name="id"]').value;
                openDeleteModal(parseInt(id), name);
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
                setTimeout(() => { window.location.reload(); }, 1300); // Reload page after showing result
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
                icon.className = 'fas fa-check-circle'; // Changed to a checkmark icon for success
                icon.style.color = '#28a745'; // Green for success
                msgDiv.innerText = msg || 'ลบข้อมูลสำเร็จ';
                msgDiv.style.color = '#28a745';
            } else {
                icon.className = 'fas fa-times-circle'; // Changed to a cross icon for error
                icon.style.color = '#dc3545'; // Red for error
                msgDiv.innerText = msg || 'เกิดข้อผิดพลาด';
                msgDiv.style.color = '#dc3545';
            }
            popup.style.display = 'flex';
        }

        // Handle alert from query string on page load
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success') || urlParams.has('error')) {
                const message = urlParams.has('success') ? urlParams.get('success') : urlParams.get('error');
                const isSuccess = urlParams.has('success');
                showResultPopup(isSuccess, message);
                // Optionally remove the query parameters from the URL after showing the alert
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