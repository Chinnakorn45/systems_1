<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

// แจ้งเตือน
$alert = '';

// เพิ่มแผนก/ฝ่าย
$popup = null;
if (isset($_POST['add_department'])) {
    $name = trim($_POST['department_name']);
    $parent = $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO departments (department_name, parent_id) VALUES (?, ?)");
        $stmt->bind_param('si', $name, $parent);
        if ($stmt->execute()) {
            $popup = [
                'icon' => 'fa-circle-check',
                'color' => '#16a34a',
                'msg' => 'เพิ่มแผนก/ฝ่ายสำเร็จ',
                'redirect' => 'departments.php?success=เพิ่มแผนก/ฝ่ายสำเร็จ'
            ];
        } else {
            $popup = [
                'icon' => 'fa-circle-xmark',
                'color' => '#dc2626',
                'msg' => 'เกิดข้อผิดพลาด: ' . $conn->error,
                'redirect' => ''
            ];
        }
        $stmt->close();
    } else {
        $popup = [
            'icon' => 'fa-circle-xmark',
            'color' => '#dc2626',
            'msg' => 'กรุณากรอกชื่อแผนก/ฝ่าย',
            'redirect' => ''
        ];
    }
}

// แก้ไขแผนก/ฝ่าย
if (isset($_POST['edit_department'])) {
    $id = intval($_POST['edit_id']);
    $name = trim($_POST['edit_department_name']);
    $parent = $_POST['edit_parent_id'] !== '' ? intval($_POST['edit_parent_id']) : null;
    if ($name) {
        $stmt = $conn->prepare("UPDATE departments SET department_name=?, parent_id=? WHERE department_id=?");
        $stmt->bind_param('sii', $name, $parent, $id);
        if ($stmt->execute()) {
            $popup = [
                'icon' => 'fa-circle-check',
                'color' => '#16a34a',
                'msg' => 'แก้ไขแผนก/ฝ่ายสำเร็จ',
                'redirect' => 'departments.php?success=แก้ไขแผนก/ฝ่ายสำเร็จ'
            ];
        } else {
            $popup = [
                'icon' => 'fa-circle-xmark',
                'color' => '#dc2626',
                'msg' => 'เกิดข้อผิดพลาด: ' . $conn->error,
                'redirect' => ''
            ];
        }
        $stmt->close();
    } else {
        $popup = [
            'icon' => 'fa-circle-xmark',
            'color' => '#dc2626',
            'msg' => 'กรุณากรอกชื่อแผนก/ฝ่าย',
            'redirect' => ''
        ];
    }
}

// ลบแผนก/ฝ่าย
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // ตรวจว่ามีลูกไหม
    $chk = $conn->query("SELECT COUNT(*) AS cnt FROM departments WHERE parent_id=$id");
    $has_child = $chk->fetch_assoc()['cnt'] > 0;
    if ($has_child) {
        $popup = [
            'icon' => 'fa-circle-xmark',
            'color' => '#dc2626',
            'msg' => 'ไม่สามารถลบแผนก/ฝ่ายที่มีแผนกย่อยได้',
            'redirect' => ''
        ];
    } else {
        $stmt = $conn->prepare("DELETE FROM departments WHERE department_id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $popup = [
                'icon' => 'fa-circle-check',
                'color' => '#16a34a',
                'msg' => 'ลบแผนก/ฝ่ายสำเร็จ',
                'redirect' => 'departments.php?success=ลบแผนก/ฝ่ายสำเร็จ'
            ];
        } else {
            $popup = [
                'icon' => 'fa-circle-xmark',
                'color' => '#dc2626',
                'msg' => 'เกิดข้อผิดพลาด: ' . $conn->error,
                'redirect' => ''
            ];
        }
        $stmt->close();
    }
}

// ดึง department ทั้งหมดสำหรับ dropdown
$departments = [];
$res = $conn->query("SELECT department_id, department_name FROM departments");
while($row = $res->fetch_assoc()) $departments[$row['department_id']] = $row['department_name'];

// ดึงข้อมูลหลัก
$sql = "SELECT * FROM departments ORDER BY department_id";
$result = $conn->query($sql);

// สำหรับแก้ไข
$edit = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM departments WHERE department_id=$eid");
    $edit = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการแผนก/ฝ่าย</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="user-crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --bg:#f6f8fb; --card:#fff; --ink:#111827; --muted:#6b7280; --stroke:#e5e7eb;
            --brand:#c9a227; --brand-2:#e4cf88; --danger:#dc3545; --success:#16a34a;
            --thead-bg:#111827; --thead-fg:#fff; --shadow:0 10px 30px rgba(17,24,39,.06);
        }
        html,body{height:100%;}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial;}

        .department-card{
            max-width: 1100px; /* กว้างขึ้นแบบดูแพง */
            margin: 20px auto;
            background: var(--card);
            border: 1px solid var(--stroke);
            border-radius: 14px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .department-header{
            display:flex;align-items:center;justify-content:space-between;
            gap:12px;padding:18px 20px;border-bottom:1px solid var(--stroke);
            background: linear-gradient(180deg,#fff, #fafafa);
        }
        .department-title{font-size:1.15rem;font-weight:700;letter-spacing:.2px}
        .department-title i{color:var(--brand);margin-right:6px}
        .user-manage-btn{
            text-decoration:none;display:inline-flex;align-items:center;gap:8px;
            padding:10px 12px;border-radius:10px;background:#fff;border:1px solid var(--stroke);color:#111;
        }
        .user-manage-btn:hover{filter:brightness(.98)}

        .department-form{
            display:grid;grid-template-columns: 1fr 1fr auto;gap:10px;padding:16px 20px;border-bottom:1px solid var(--stroke);
        }
        .department-form input[type="text"], .department-form select{
            padding:10px 12px;border:1px solid var(--stroke);border-radius:10px;outline:none;
        }
        .dep-btn{
            padding:10px 12px;border-radius:10px;border:1px solid var(--stroke);background:#fff;color:#111;
            text-decoration:none;display:inline-flex;align-items:center;gap:8px;cursor:pointer;
        }
        .dep-btn.add{background: linear-gradient(135deg,var(--brand),var(--brand-2));border:none;font-weight:700;color:#111;}
        .dep-btn.edit{background:#eef2ff;border-color:#2563eb;color:#2563eb;}
        .dep-btn.delete{background:#fff5f5;border-color:#ef4444;color:#b91c1c;}
        .dep-btn.cancel-btn{background:#f3f4f6;border-color:#e5e7eb;color:#374151;}

        /* ตาราง */
        .table-wrap{padding: 0 0 6px 0; overflow:auto;}
        .department-table{width:100%;border-collapse:separate;border-spacing:0;min-width:720px;}
        .department-table thead th{
            position:sticky;top:0;z-index:2;background:var(--thead-bg);color:var(--thead-fg);
            font-weight:700;border-bottom:1px solid rgba(255,255,255,.12);
        }
        .department-table th,.department-table td{
            padding:12px;border-bottom:1px solid var(--stroke);vertical-align:top;line-height:1.5;
        }
        .department-table tbody tr:hover{background:#fcfdff;}
        .department-actions{white-space:nowrap;display:flex;gap:8px;flex-wrap:wrap}

        /* ป๊อปอัป */
        .popup-modal{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;justify-content:center;align-items:center;z-index:1000;padding:16px;}
        .popup-content{
            background:var(--card);padding:22px;border-radius:14px;box-shadow:var(--shadow);
            border:1px solid var(--stroke);max-width:560px;width:100%;text-align:center;display:flex;flex-direction:column;gap:12px;align-items:center;
        }
        .popup-content .msg{font-size:18px;font-weight:600;}
        .hidden{display:none !important;}

        /* มือถือ */
        @media (max-width: 991.98px){
            .department-form{grid-template-columns:1fr;gap:8px}
            .department-card{margin:12px}
            .department-header{padding:14px}
        }
        @media (max-width: 767.98px){
            .department-title{font-size:1.05rem}
            .department-table{min-width:640px;}
        }
    </style>
</head>
<body>
    <?php if (isset($popup) && $popup): ?>
    <div class="popup-modal" id="instantPopup">
        <div class="popup-content">
            <i class="fas <?= htmlspecialchars($popup['icon']) ?>" style="color:<?= htmlspecialchars($popup['color']) ?>;font-size:42px;"></i>
            <div class="msg" style="color:<?= htmlspecialchars($popup['color']) ?>;font-size:20px;"><?= htmlspecialchars($popup['msg']) ?></div>
            <?php if ($popup['redirect']): ?>
                <div style="font-size:14px;color:#888;">กำลังโหลดข้อมูลใหม่...</div>
                <script>setTimeout(()=>{ location.href = <?= json_encode($popup['redirect'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>; },1500);</script>
            <?php else: ?>
                <button class="dep-btn" onclick="document.getElementById('instantPopup').classList.add('hidden')">ปิด</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal ยืนยันลบ (สวย ๆ แทน confirm) -->
    <div class="popup-modal hidden" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="popup-content">
            <i class="fas fa-triangle-exclamation" style="color:#d32f2f;font-size:34px;"></i>
            <div id="confirmTitle" class="msg">ยืนยันการลบหน่วยงาน/ฝ่าย</div>
            <div id="confirmName" style="font-size:15px;color:#444;margin-bottom:8px;"></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
                <a id="confirmDeleteLink" class="dep-btn delete"><i class="fas fa-trash"></i> ลบ</a>
                <button type="button" class="dep-btn cancel-btn" onclick="closeConfirm()">ยกเลิก</button>
            </div>
        </div>
    </div>

    <div class="department-card">
        <div class="department-header">
            <div><span class="department-title"><i class="fas fa-sitemap"></i> จัดการหน่วยงาน/ฝ่าย</span></div>
            <a href="users.php" class="user-manage-btn"><i class="fas fa-arrow-left"></i> กลับหน้าผู้ใช้งาน</a>
        </div>

        <?php if ($alert) echo $alert; ?>

        <form class="department-form" method="post">
            <?php if ($edit): ?>
                <input type="hidden" name="edit_id" value="<?= (int)$edit['department_id'] ?>">
                <input type="text" name="edit_department_name" value="<?= htmlspecialchars($edit['department_name']) ?>" required placeholder="ชื่อหน่วยงาน/ฝ่าย">
                <select name="edit_parent_id">
                    <option value="">- ไม่มีหน่วยงานแม่ -</option>
                    <?php foreach($departments as $id=>$name): if ($id == $edit['department_id']) continue; ?>
                        <option value="<?= (int)$id ?>" <?= ((int)$edit['parent_id']===$id)?'selected':'' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="edit_department" class="dep-btn edit"><i class="fas fa-save"></i> บันทึก</button>
                <a href="departments.php" class="dep-btn cancel-btn"><i class="fas fa-times"></i> ยกเลิก</a>
            <?php else: ?>
                <input type="text" name="department_name" required placeholder="ชื่อหน่วยงาน/ฝ่าย">
                <select name="parent_id">
                    <option value="">- ไม่มีหน่วยงานแม่ -</option>
                    <?php foreach($departments as $id=>$name): ?>
                        <option value="<?= (int)$id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_department" class="dep-btn add"><i class="fas fa-plus"></i> เพิ่มหน่วยงานใหม่</button>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table class="department-table">
                <thead>
                    <tr>
                        <th>ชื่อหน่วยงาน/ฝ่าย</th>
                        <th>สังกัด</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td><?= $row['parent_id'] ? htmlspecialchars($departments[$row['parent_id']] ?? '-') : '-' ?></td>
                        <td class="department-actions">
                            <a href="departments.php?edit=<?= (int)$row['department_id'] ?>" class="dep-btn edit" title="แก้ไขข้อมูล"><i class="fas fa-pen"></i> แก้ไข</a>

                            <!-- ปุ่มลบใช้ modal แทน confirm -->
                            <button
                                type="button"
                                class="dep-btn delete btn-open-confirm"
                                data-id="<?= (int)$row['department_id'] ?>"
                                data-name="<?= htmlspecialchars($row['department_name'], ENT_QUOTES) ?>"
                                title="ลบข้อมูล">
                                <i class="fas fa-trash"></i> ลบ
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // เปิดป๊อปอัปยืนยันลบ
        document.querySelectorAll('.btn-open-confirm').forEach(btn=>{
            btn.addEventListener('click', ()=>{
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name') || '';
                document.getElementById('confirmName').textContent = name;
                document.getElementById('confirmDeleteLink').setAttribute('href', 'departments.php?delete=' + encodeURIComponent(id));
                document.getElementById('confirmModal').classList.remove('hidden');
            });
        });
        function closeConfirm(){
            document.getElementById('confirmModal').classList.add('hidden');
        }
        // ปิด modal เมื่อคลิกฉากหลัง
        document.getElementById('confirmModal').addEventListener('click', (e)=>{
            if (e.target.id === 'confirmModal') closeConfirm();
        });
        // กด ESC เพื่อปิด
        document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') closeConfirm(); });
    </script>
</body>
</html>
<?php $conn->close(); ?>
