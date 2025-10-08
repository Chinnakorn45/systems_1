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
$db = 'borrowing_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

/* ---------- AJAX: ลบผู้ใช้ ---------- */
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
            $actor  = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'system');
            $conn->query("INSERT INTO user_logs (user_id, username, event_type, event_detail) VALUES ("
                . intval($_SESSION['user_id']) . ", '"
                . $conn->real_escape_string($actor) . "', 'delete_user', '"
                . $conn->real_escape_string($detail) . "')");
        }
    } else {
        $msg = 'เกิดข้อผิดพลาด';
    }
    $stmt->close();
    echo json_encode(['success'=>$success, 'msg'=>$msg]);
    $conn->close();
    exit;
}

/* ---------- ค้นหา ---------- */
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = $search ? "WHERE full_name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%'" : '';

/* ---------- ดึงผู้ใช้ ---------- */
$sql = "SELECT * FROM users $where ORDER BY created_at DESC";
$result = $conn->query($sql);

/* ---------- role ปัจจุบัน (เดโม) ---------- */
$current_role = 'admin';

/* ---------- Alert จาก query string ---------- */
$alert = '';
if (isset($_GET['success'])) {
    $alert = '<div class="alert alert-success">'.htmlspecialchars($_GET['success']).'</div>';
} elseif (isset($_GET['error'])) {
    $alert = '<div class="alert alert-danger">'.htmlspecialchars($_GET['error']).'</div>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบฐานข้อมูลพนักงาน</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- External -->
    <link rel="stylesheet" href="user-crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --bg: #f6f8fb;
            --card: #ffffff;
            --ink: #111827;
            --muted: #6b7280;
            --stroke: #e5e7eb;
            --row: #fafbff;
            --brand: #c9a227; /* gold accent */
            --brand-2: #e4cf88;
            --danger: #dc3545;
            --success: #16a34a;
            --shadow: 0 8px 30px rgba(17,24,39,.06);
        }

        /* ===== Layout base ===== */
        html, body { height: 100%; }
        body {
            margin: 0; background: var(--bg); color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
        }
        main { height: 100%; display: flex; }

        /* lock page scroll + scroll ในกรอบตาราง (เดสก์ท็อป) */
        html, body { overflow: hidden; }
        @media (max-width: 991.98px){
            /* บนมือถือ ให้สกรอล์ทั้งหน้าแทน เพื่อความคุ้นมือ */
            html, body { overflow: auto; }
        }

        .user-card {
            display: flex; flex-direction: column;
            width: 100%; height: 100%; min-height: 0;
        }
        /* ขยายความกว้างของ user-card ให้กินเต็มความกว้างของหน้าจอ */
        .user-card.user-card--wide {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
        }

        /* ===== Header / Toolbar ===== */
        .user-header {
            flex: 0 0 auto; background: var(--card); position: relative; z-index: 3;
            box-shadow: var(--shadow); border-bottom: 1px solid var(--stroke);
        }
        .user-toolbar {
            display: flex; gap: 12px; align-items: flex-start; justify-content: space-between;
            padding: 16px 18px; flex-wrap: wrap;
        }
        .left-group{
            display: flex; flex-direction: column; gap: 8px;
            flex: 1 1 auto; min-width: 280px;
        }
        .actions-inline{
            margin-left: auto;
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            justify-content: flex-end;
        }
        /* ปรับปุ่มให้สม่ำเสมอ และจัดชิดขวา */
        .actions-inline .user-manage-btn, .actions-inline .user-add-btn{
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
            padding: 10px 14px; border-radius: 10px; border: 1px solid var(--stroke);
            white-space: nowrap;
        }
        .actions-inline .logout-btn{ background:#dc3545; color:#fff; border-color:#dc3545; }
        .actions-inline .logout-btn:hover{ filter:brightness(.95); }
        /* มือถือ: ให้ช่องค้นหาเต็มแถว และปุ่มแบ่งครึ่งบรรทัด */
        @media (max-width: 767.98px){
            .left-group{ width: 100%; }
            .user-search-box{ width: 100%; }
            .user-search-box input[type="text"]{ min-width: 0; flex: 1 1 auto; width: 100%; }
            .actions-inline{ width: 100%; margin-top: 6px; justify-content: flex-end; }
            .actions-inline .user-manage-btn, .actions-inline .user-add-btn, .actions-inline .logout-btn{
                flex: 0 0 auto; /* ชิดขวา เกาะตามขนาดปุ่ม ไม่ยืดเต็มครึ่งบรรทัด */
            }
        }
        .user-title { margin: 0; font-weight: 700; letter-spacing: .2px; }
        .user-title i { color: var(--brand); margin-right: 8px; }
        .user-desc { margin: 6px 0 0; color: var(--muted); font-size: .95rem; }

        /* ===== Search + Actions ===== */
        .user-search-box { display: flex; gap: 8px; align-items: center; }
        .user-search-box input[type="text"]{
            padding: 10px 12px; border: 1px solid var(--stroke); border-radius: 10px; min-width: 220px;
            outline: none;
        }
        .user-search-box button{
            padding: 10px 14px; border: none; border-radius: 10px; cursor: pointer;
            background: linear-gradient(135deg, var(--brand), var(--brand-2)); color: #111;
            font-weight: 600; box-shadow: 0 8px 18px rgba(201,162,39,.18);
        }

        .actions-inline{ display:flex; gap:10px; flex-wrap: wrap; align-items:center; }

        .user-manage-btn, .user-add-btn, .dep-btn{
            padding: 10px 12px; border-radius: 10px; border: 1px solid var(--stroke);
            background: #fff; color: #111; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .user-add-btn{
            background: linear-gradient(135deg, var(--brand), var(--brand-2)); color: #111; border: none;
            font-weight: 700;
        }
        .user-manage-btn:hover, .dep-btn:hover { filter: brightness(0.98); }
        .dep-btn.edit{ border-color:#2563eb; color:#2563eb; background:#eef2ff; }
        .dep-btn.delete{ border-color:#ef4444; color:#b91c1c; background:#fff5f5; }

        /* ===== Table area (scroll only table on desktop) ===== */
        .table-scroll {
            flex: 1 1 auto; min-height: 0; overflow: auto;
            border-top: 1px solid var(--stroke); background: var(--card);
        }
        .user-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        /* ทำให้ตารางกว้างขึ้นบนจอใหญ่ + ไม่ตัดบรรทัดง่าย ๆ เพื่ออ่านได้เต็มตา */
        @media (min-width: 768px){
            .user-table { min-width: 1280px; }
        }
        /* กำหนดความกว้างขั้นต่ำของแต่ละคอลัมน์ให้กว้างขึ้น */
        .user-table th:nth-child(1), .user-table td.full_name   { min-width: 260px; }
        .user-table th:nth-child(2), .user-table td.username    { min-width: 180px; }
        .user-table th:nth-child(3), .user-table td.email       { min-width: 260px; }
        .user-table th:nth-child(4), .user-table td.department  { min-width: 200px; }
        .user-table th:nth-child(5), .user-table td.position    { min-width: 200px; }
        .user-table th:nth-child(6), .user-table td.role        { min-width: 140px; }
        .user-table th:nth-child(7), .user-table td.created_at  { min-width: 120px; white-space: nowrap; }
        .user-table th:last-child, .user-table td.user-actions  { min-width: 160px; }
        .user-table thead th {
            position: sticky; top: 0; z-index: 2;
            background: var(--row); border-bottom: 1px solid var(--stroke);
        }
        .user-table th, .user-table td {
            padding: 12px; line-height: 1.5; border-bottom: 1px solid var(--stroke); vertical-align: top;
        }
        .user-table tbody tr:hover { background: #fcfdff; }

        /* มือถือ: แปลงตารางเป็นแถวการ์ด อ่านง่าย ไม่ต้องเลื่อนแนวนอน */
        @media (max-width: 767.98px){
            .user-search-box input[type="text"]{ min-width: 160px; }
            .table-scroll { overflow: visible; padding: 0 8px; }
            .user-table { width: 100%; border: 0; border-collapse: separate; border-spacing: 0; }
            .user-table thead { display: none; }
            .user-table tbody tr {
                display: block;
                background: var(--card);
                border: 1px solid var(--stroke);
                border-radius: 12px;
                margin: 10px 0;
                box-shadow: var(--shadow);
                overflow: hidden;
            }
            .user-table td {
                display: grid;
                grid-template-columns: 42% 58%;
                padding: 10px 12px;
                border: 0;
                border-bottom: 1px solid var(--stroke);
                white-space: normal;
            }
            .user-table td:last-child { border-bottom: 0; }
            .user-table td::before {
                content: attr(data-label);
                font-weight: 600; color: var(--muted);
                padding-right: 8px;
            }
            .user-actions { display: flex; gap: 6px; justify-content: flex-start; }
            .dep-btn.edit, .dep-btn.delete { padding: 8px 12px; }
            .user-desc { display:none; }
            .full_name, .username, .email, .department, .position, .role {
                white-space: normal; word-break: break-word;
            }
        }

        /* ===== Alerts ===== */
        .alert{
            margin: 12px 16px; padding: 10px 12px; border-radius: 10px; border:1px solid var(--stroke);
        }
        .alert-success{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
        .alert-danger{  background:#fef2f2; color:#991b1b; border-color:#fecaca; }

        /* ===== Modals (custom) ===== */
        .popup-modal {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45);
            display: none; justify-content: center; align-items: center; z-index: 1000;
            padding: 16px;
        }
        .popup-content {
            background: var(--card); padding: 22px 22px; border-radius: 14px;
            box-shadow: var(--shadow); text-align: center; max-width: 420px; width: 100%;
            display: flex; flex-direction: column; gap: 14px; align-items: center;
            border: 1px solid var(--stroke);
            animation: pop .14s ease-out;
        }
        .popup-content .msg { font-size: 18px; font-weight: 600; color:#111; }
        @keyframes pop { from{ transform: scale(.98); opacity: 0;} to{ transform: scale(1); opacity:1;} }

        /* Cells wrap nicely */
        .full_name, .username, .email, .department, .position, .role {
            word-break: normal;           /* อย่าตัดคำกลางประโยค */
            white-space: nowrap;          /* แสดงในบรรทัดเดียวเพื่อกว้างขึ้น */
        }

        /* Helper buttons in modal */
        .btn-modal-danger{
            background: #ef4444; color:#fff; border:none; border-radius:10px; padding:10px 14px; min-width:88px; cursor:pointer;
        }
        .btn-modal-secondary{
            background: #6b7280; color:#fff; border:none; border-radius:10px; padding:10px 14px; min-width:88px; cursor:pointer;
        }
        
        /* ===== FIX: ทำให้หัวตารางชัด (เข้ม อ่านง่าย) ===== */
        :root{  
        --thead-bg: linear-gradient(180deg, #f3f4f6, #e5e7eb);
        --thead-fg: #111827;
        }

        
        .user-card .table-scroll .user-table thead th{
        background: var(--thead-bg) !important;
        color: var(--thead-fg) !important;
        font-weight: 700;
        border-bottom: 1px solid rgba(255,255,255,.12);
        /* เส้นเงาด้านล่างให้แยกจากข้อมูล */
        box-shadow: 0 1px 0 rgba(0,0,0,.25) inset;
        }

        /* ไอคอน/ลิงก์ในหัวตารางให้เป็นสีเดียวกัน */
        .user-card .table-scroll .user-table thead th i,
        .user-card .table-scroll .user-table thead th a{
  color: var(--thead-fg) !important;
        }

/* มุมมนเล็ก ๆ (ถ้าไม่ตรงใจ ลบได้) */
        .user-card .table-scroll .user-table thead th:first-child{
        border-top-left-radius: 10px;
        }
        .user-card .table-scroll .user-table thead th:last-child{
        border-top-right-radius: 10px;
        }

        /* มือถือ: ตัวอักษรหัวตารางไม่เล็กเกิน และหัวตารางเด่น */
        @media (max-width: 767.98px){
        .user-card .table-scroll .user-table thead th{
            font-size: .95rem;
            letter-spacing: .2px;
        }
        }
    </style>
</head>
<body>
    <main>
        <div class="user-card user-card--wide">
            <!-- Header -->
            <div class="user-header">
                <div class="user-toolbar">
                    <div class="left-group">
                        <div>
                            <h2 class="user-title"><i class="fas fa-address-book"></i> ระบบฐานข้อมูลพนักงาน</h2>
                            <p class="user-desc">จัดการข้อมูลพนักงาน เพิ่ม แก้ไข ลบ และค้นหา พร้อมกำหนดสิทธิ์การเข้าถึง</p>
                        </div>
                        <form class="user-search-box" method="get">
                            <input type="text" name="search" placeholder="ค้นหาชื่อ, username, email" value="<?= htmlspecialchars($search) ?>">
                            <button type="submit"><i class="fas fa-search"></i> ค้นหา</button>
                        </form>
                    </div>

                    <div class="actions-inline">
                        <a href="departments.php" class="user-manage-btn"><i class="fas fa-sitemap"></i> จัดการแผนก/ฝ่าย</a>
                        <?php if ($current_role === 'admin'): ?>
                            <a href="add_user.php" class="user-add-btn"><i class="fas fa-user-plus"></i> เพิ่มพนักงาน</a>
                        <?php endif; ?>
                        <a href="logout.php?confirm=1" class="user-manage-btn logout-btn">
                            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
                <?php if ($alert) echo $alert; ?>
            </div>

            <!-- Table -->
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
                            <td class="full_name" data-label="ชื่อ-นามสกุล"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="username" data-label="Username"><?= htmlspecialchars($row['username']) ?></td>
                            <td class="email" data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="department" data-label="แผนก/ฝ่าย"><?= htmlspecialchars($row['department']) ?></td>
                            <td class="position" data-label="ตำแหน่ง"><?= htmlspecialchars($row['position']) ?></td>
                            <td class="role" data-label="บทบาท"><?= htmlspecialchars($row['role']) ?></td>
                            <td class="created_at" data-label="วันที่สร้าง">
                                <?php
                                    $dt = strtotime($row['created_at']);
                                    echo date('d/m/', $dt) . (date('Y', $dt) + 543);
                                ?>
                            </td>
                            <?php if ($current_role === 'admin'): ?>
                            <td class="user-actions" data-label="การจัดการ" style="white-space:nowrap;">
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
    <div id="deleteModal" class="popup-modal" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
        <div class="popup-content">
            <i class="fas fa-triangle-exclamation" style="color:#d32f2f;font-size:34px;"></i>
            <div id="deleteTitle" class="msg">ยืนยันการลบพนักงาน</div>
            <div id="deleteName" style="font-size:15px; color:#444; margin-bottom:8px;"></div>
            <div style="display:flex; gap:10px;">
                <button id="confirmDeleteBtn" class="btn-modal-danger"><i class="fas fa-trash"></i> ลบ</button>
                <button onclick="closeDeleteModal()" class="btn-modal-secondary">ยกเลิก</button>
            </div>
        </div>
    </div>

    <!-- Modal: ผลลัพธ์ -->
    <div id="resultPopup" class="popup-modal" role="dialog" aria-modal="true" aria-labelledby="resultMsg">
        <div class="popup-content">
            <i id="resultIcon" class="fas" style="font-size:42px;"></i>
            <div id="resultMsg" class="msg"></div>
            <div style="font-size:13px; color:#888;">กำลังโหลดข้อมูลใหม่...</div>
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

        // ปุ่มลบ (เปิด modal)
        document.querySelectorAll('.user-actions .dep-btn.delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const tr = btn.closest('tr');
                const name = tr.querySelector('.full_name')?.innerText || '';
                const id = tr.querySelector('input[name="id"]')?.value || '';
                if (id) openDeleteModal(parseInt(id), name);
            });
        });

        // Confirm ลบ (AJAX)
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (!deleteUserId) return;
            fetch('users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'delete_user=1&id=' + encodeURIComponent(deleteUserId)
            })
            .then(res => res.json())
            .then(data => {
                closeDeleteModal();
                showResultPopup(data.success, data.msg);
                setTimeout(() => { window.location.reload(); }, 1200);
            })
            .catch(() => {
                closeDeleteModal();
                showResultPopup(false, 'เกิดข้อผิดพลาดในการส่งคำขอ');
                setTimeout(() => { window.location.reload(); }, 1200);
            });
        };

        function showResultPopup(success, msg) {
            const popup = document.getElementById('resultPopup');
            const icon = document.getElementById('resultIcon');
            const msgDiv = document.getElementById('resultMsg');

            if (success) {
                icon.className = 'fas fa-circle-check';
                icon.style.color = 'var(--success)';
                msgDiv.innerText = msg || 'ลบข้อมูลสำเร็จ';
                msgDiv.style.color = '#111';
            } else {
                icon.className = 'fas fa-circle-xmark';
                icon.style.color = 'var(--danger)';
                msgDiv.innerText = msg || 'เกิดข้อผิดพลาด';
                msgDiv.style.color = '#111';
            }
            popup.style.display = 'flex';
        }

        // แสดง popup ตาม query string (ถ้ามี)
        window.addEventListener('load', () => {
            const p = new URLSearchParams(location.search);
            if (p.has('success') || p.has('error')) {
                const message = p.get('success') || p.get('error');
                const ok = p.has('success');
                showResultPopup(ok, message);
                setTimeout(() => {
                    const url = new URL(location.href);
                    url.searchParams.delete('success');
                    url.searchParams.delete('error');
                    history.replaceState({}, document.title, url.toString());
                }, 1200);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
