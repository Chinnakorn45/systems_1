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

$popup = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username']);
    $password        = $_POST['password'];
    $full_name       = trim($_POST['full_name']);
    $email           = trim($_POST['email']);
    $main_department = trim($_POST['main_department']);
    $department      = trim($_POST['department']);
    $position        = trim($_POST['position']);
    $role            = $_POST['role'] ?? 'staff';

    if ($username && $password && $full_name && $main_department && $department) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email, department, position, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssss', $username, $password_hash, $full_name, $email, $department, $position, $role);
        if ($stmt->execute()) {
            // Log add user
            if (isset($_SESSION['user_id'])) {
                $actor = $_SESSION['username'] ?? ($_SESSION['full_name'] ?? 'system');
                $detail = 'เพิ่มผู้ใช้: ' . $full_name . ' (' . $username . ')';
                $conn->query(
                    "INSERT INTO user_logs (user_id, username, event_type, event_detail)
                     VALUES (".intval($_SESSION['user_id']).", '".$conn->real_escape_string($actor)."', 'add_user', '".$conn->real_escape_string($detail)."')"
                );
            }
            $popup = [
                'icon' => 'fa-circle-check',
                'color' => '#16a34a',
                'msg' => 'เพิ่มพนักงานสำเร็จ',
                'redirect' => 'users.php?success=เพิ่มพนักงานสำเร็จ'
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
            'msg' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
            'redirect' => ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มพนักงานใหม่</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="user-crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --bg:#f6f8fb; --card:#ffffff; --ink:#111827; --muted:#6b7280; --stroke:#e5e7eb;
            --brand:#c9a227; --brand-2:#e4cf88; --danger:#dc3545; --success:#16a34a;
            --shadow:0 10px 30px rgba(17,24,39,.06);
            --input-radius:12px;
        }
        html,body{height:100%;}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial;}

        .shell{
            min-height:100%;
            display:flex;align-items:center;justify-content:center;
            padding:24px 14px;
        }
        .card{
            width:100%; max-width: 880px; /* การ์ดกว้างแบบดูแพง */
            background:var(--card);
            border:1px solid var(--stroke);
            border-radius:14px;
            box-shadow:var(--shadow);
            overflow:hidden;
        }
        .card-header{
            display:flex;align-items:center;justify-content:space-between;gap:10px;
            padding:18px 20px;border-bottom:1px solid var(--stroke);
            background: linear-gradient(180deg,#fff, #fafafa);
        }
        .title{
            margin:0;font-size:1.15rem;font-weight:700;letter-spacing:.2px;
        }
        .title i{ color:var(--brand); margin-right:8px; }
        .back-link{
            text-decoration:none;display:inline-flex;align-items:center;gap:8px;
            padding:10px 12px;border-radius:10px;background:#fff;border:1px solid var(--stroke);color:#111;
        }
        .back-link:hover{ filter:brightness(.98); }

        /* ฟอร์ม */
        .crud-form{
            padding:18px 20px 20px 20px;
        }
        .grid{
            display:grid; grid-template-columns: 1fr 1fr; gap:14px;
        }
        .full-row{ grid-column: 1 / -1; }
        @media (max-width: 767.98px){
            .grid{ grid-template-columns: 1fr; }
        }
        label{
            display:block; margin:4px 0 6px; font-weight:600; color:#111;
        }
        .desc{ margin:0 0 12px; color:var(--muted); font-size:.95rem; }

        input[type="text"], input[type="password"], input[type="email"], select{
            width:100%; padding:12px 14px; border:1px solid var(--stroke); border-radius:var(--input-radius); outline:none;
            background:#fff;
        }
        input::placeholder{ color:#9ca3af; }

        .input-group{
            position:relative;
        }
        .input-append{
            position:absolute; right:8px; top:50%; transform:translateY(-50%);
            display:flex; gap:6px; align-items:center;
        }
        .icon-btn{
            border:none; background:transparent; cursor:pointer; padding:6px; border-radius:8px; color:#374151;
        }
        .icon-btn:hover{ background:#f3f4f6; }

        /* Password strength bar */
        .strength{
            height:6px; border-radius:999px; background:#f3f4f6; overflow:hidden; margin-top:8px;
        }
        .strength > span{
            display:block; height:100%; width:0%; transition:width .25s ease;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #16a34a);
        }
        .hint{ font-size:.85rem; color:var(--muted); margin-top:6px; }

        .actions{
            margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;
        }
        button[type="submit"]{
            padding:12px 16px; border:none; border-radius:12px; cursor:pointer;
            background: linear-gradient(135deg, var(--brand), var(--brand-2)); color:#111; font-weight:700;
            box-shadow: 0 8px 18px rgba(201,162,39,.18);
        }
        .cancel-btn{
            display:inline-flex; align-items:center; justify-content:center;
            padding:12px 16px; border-radius:12px; text-decoration:none; color:#111;
            background:#f3f4f6; border:1px solid var(--stroke);
        }
        .cancel-btn:hover{ filter:brightness(.98); }

        /* Popup */
        .popup-modal{
            position:fixed; inset:0; background:rgba(0,0,0,.45);
            display:flex; justify-content:center; align-items:center; z-index:1000; padding:16px;
        }
        .popup-content{
            background:var(--card); padding:22px; border-radius:14px; box-shadow:var(--shadow);
            border:1px solid var(--stroke); max-width:560px; width:100%; text-align:center;
            display:flex; flex-direction:column; gap:12px; align-items:center;
        }
        .popup-content .msg{ font-size:18px; font-weight:600; }
        .hidden{ display:none !important; }

        /* ป้องกันเคาะ enter เผลอกด submit ในบาง input */
        input, select { line-height: 1.2; }
    </style>
</head>
<body>
    <?php if ($popup): ?>
    <div class="popup-modal" id="resultPopup">
        <div class="popup-content">
            <i class="fas <?= htmlspecialchars($popup['icon']) ?>" style="color:<?= htmlspecialchars($popup['color']) ?>;font-size:42px;"></i>
            <div class="msg" style="color:<?= htmlspecialchars($popup['color']) ?>; font-size:20px;">
                <?= htmlspecialchars($popup['msg']) ?>
            </div>
            <?php if (!empty($popup['redirect'])): ?>
            <div style="font-size:14px; color:#888;">กำลังกลับไปหน้ารายชื่อพนักงาน...</div>
            <script>setTimeout(()=>{ location.href = <?= json_encode($popup['redirect'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>; }, 1500);</script>
            <?php else: ?>
            <div style="font-size:14px; color:#888;">เกิดข้อผิดพลาด กำลังรีเฟรชหน้าเพื่อให้กรอกใหม่...</div>
            <script>
            (function(){
                // เก็บร่างข้อมูลที่กรอกไว้ เพื่อเติมกลับหลังรีเฟรช
                const draft = {
                  username:        <?= json_encode($username ?? '', JSON_UNESCAPED_UNICODE) ?>,
                  full_name:       <?= json_encode($full_name ?? '', JSON_UNESCAPED_UNICODE) ?>,
                  email:           <?= json_encode($email ?? '', JSON_UNESCAPED_UNICODE) ?>,
                  position:        <?= json_encode($position ?? '', JSON_UNESCAPED_UNICODE) ?>,
                  main_department: <?= json_encode($main_department ?? '', JSON_UNESCAPED_UNICODE) ?>,
                  department:      <?= json_encode($department ?? '', JSON_UNESCAPED_UNICODE) ?>,
                  role:            <?= json_encode($role ?? 'staff', JSON_UNESCAPED_UNICODE) ?>
                };
                try { localStorage.setItem('add_user_draft', JSON.stringify(draft)); } catch(e){}
                // ใช้ replace เพื่อล้าง POST และไม่ค้างอยู่หน้าปัจจุบัน
                setTimeout(()=>{ location.replace('add_user.php?draft=1'); }, 600);
            })();
            </script>
            <noscript><meta http-equiv="refresh" content="1;url=add_user.php?draft=1"></noscript>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="shell" <?= $popup ? 'style="filter:blur(2px);pointer-events:none;"' : '' ?>>
        <div class="card">
            <div class="card-header">
                <h3 class="title"><i class="fas fa-user-plus"></i> เพิ่มพนักงานใหม่</h3>
                <a href="users.php" class="back-link"><i class="fas fa-arrow-left"></i> กลับรายชื่อพนักงาน</a>
            </div>

            <form class="crud-form" method="post" autocomplete="off" novalidate>
                <p class="desc">กรอกข้อมูลให้ครบถ้วน โดยเฉพาะฟิลด์ที่มีเครื่องหมาย * ระบบจะแสดงตัวชี้วัดความแข็งแรงของรหัสผ่านแบบเรียลไทม์</p>

                <div class="grid">
                    <div>
                        <label>Username *</label>
                        <input type="text" name="username" required placeholder="เช่น somchai.w">
                    </div>

                    <div class="full-row input-group">
                        <label>รหัสผ่าน *</label>
                        <input type="password" name="password" id="password" required placeholder="อย่างน้อย 10 ตัว รวม a-z, A-Z, 0-9 และอักขระพิเศษ">
                        <div class="input-append">
                            <button class="icon-btn" type="button" id="togglePw" title="แสดง/ซ่อนรหัสผ่าน"><i class="fas fa-eye-slash"></i></button>
                        </div>
                        <div class="strength"><span id="strengthBar"></span></div>
                        <div class="hint" id="pwHint">รหัสผ่านยิ่งหลากหลายและยาว จะยิ่งปลอดภัย</div>
                    </div>

                    <div class="full-row">
                        <label>ชื่อ-นามสกุล *</label>
                        <input type="text" name="full_name" required placeholder="เช่น สมชาย ใจดี">
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="email" name="email" placeholder="you@example.com">
                    </div>

                    <div>
                        <label>ตำแหน่ง</label>
                        <input type="text" name="position" placeholder="เช่น เจ้าหน้าที่ธุรการ">
                    </div>

                    <div>
                        <label>แผนกหลัก *</label>
                        <select name="main_department" id="main_department" required>
                            <option value="">-- เลือกแผนกหลัก --</option>
                            <?php
                            $main_result = $conn->query("SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
                            while ($row = $main_result->fetch_assoc()): ?>
                                <option value="<?= (int)$row['department_id'] ?>"><?= htmlspecialchars($row['department_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label>แผนกย่อย *</label>
                        <select name="department" id="department" required>
                            <option value="">-- เลือกแผนกย่อย --</option>
                        </select>
                    </div>

                    <div class="full-row">
                        <label>บทบาท</label>
                        <select name="role">
                            <option value="admin">admin</option>
                            <option value="staff" selected>staff</option>
                            <option value="procurement">procurement</option>
                        </select>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit"><i class="fas fa-check"></i> เพิ่ม</button>
                    <a href="users.php" class="cancel-btn">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    // เมื่อเกิด error ฝั่ง client ให้เด้งแจ้งแล้วรีเฟรชหน้า (กันลูปด้วย sessionStorage)
    (function(){
        const FLAG = 'add_user_last_crash';
        const hasReloaded = sessionStorage.getItem(FLAG) === '1';
        function showFatalAndReload(text){
            try {
                if (!document.getElementById('fatalOverlay')){
                    const o = document.createElement('div');
                    o.id = 'fatalOverlay';
                    o.setAttribute('role','alert');
                    o.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:2000;';
                    o.innerHTML = '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 20px;min-width:280px;box-shadow:0 10px 30px rgba(0,0,0,.2);text-align:center">'
                        + '<div style="font-weight:700;color:#dc2626;margin-bottom:6px">เกิดข้อผิดพลาด</div>'
                        + '<div style="font-size:14px;color:#6b7280;margin-bottom:10px">'+ (text||'ไม่ทราบสาเหตุ') +'</div>'
                        + '<div style="font-size:13px;color:#888">กำลังรีเฟรชหน้าเพื่อให้กรอกใหม่...</div>'
                        + '</div>';
                    document.body.appendChild(o);
                }
            } catch(_){}
            if (!hasReloaded) {
                sessionStorage.setItem(FLAG, '1');
                setTimeout(()=> location.reload(), 1000);
            }
        }
        window.addEventListener('error', function(e){ showFatalAndReload(e?.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'); });
        window.addEventListener('unhandledrejection', function(e){ showFatalAndReload('เกิดข้อผิดพลาดไม่ทราบสาเหตุ'); });
        window.addEventListener('load', function(){ sessionStorage.removeItem(FLAG); });
    })();

    // โหลดแผนกย่อยตามแผนกหลัก
    document.addEventListener('DOMContentLoaded', function() {
        // เติมค่าที่กรอกค้างไว้ (ถ้ามี) แล้วล้าง draft ทิ้ง
        try {
            const draftStr = localStorage.getItem('add_user_draft');
            if (draftStr) {
                const d = JSON.parse(draftStr);
                const f = document.querySelector('.crud-form');
                if (f) {
                    f.querySelector('input[name="username"]').value   = d.username || '';
                    f.querySelector('input[name="full_name"]').value  = d.full_name || '';
                    f.querySelector('input[name="email"]').value      = d.email || '';
                    f.querySelector('input[name="position"]').value   = d.position || '';
                    const roleSel = f.querySelector('select[name="role"]');
                    if (roleSel) roleSel.value = d.role || roleSel.value;
                }
                const mainSelect = document.getElementById('main_department');
                const subSelect  = document.getElementById('department');
                if (mainSelect) mainSelect.value = d.main_department || '';
                if (d.main_department) {
                    fetch('get_departments_children.php?parent_id=' + encodeURIComponent(d.main_department))
                      .then(r=>r.json())
                      .then(data=>{
                        if (!subSelect) return;
                        subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
                        data.forEach(dep=>{
                            const opt = document.createElement('option');
                            opt.value = dep.department_name;
                            opt.textContent = dep.department_name;
                            subSelect.appendChild(opt);
                        });
                        if (d.department) subSelect.value = d.department;
                      })
                      .catch(()=>{});
                }
                localStorage.removeItem('add_user_draft');
                // ลบ ?draft ออกจาก URL ให้สะอาด
                try { const url = new URL(location.href); url.searchParams.delete('draft'); history.replaceState({}, document.title, url.toString()); } catch(_){ }
            }
        } catch(_){}
        const mainSelect = document.getElementById('main_department');
        const subSelect  = document.getElementById('department');

        mainSelect.addEventListener('change', function() {
            const parentId = this.value;
            subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
            if (!parentId) return;
            fetch('get_departments_children.php?parent_id=' + encodeURIComponent(parentId))
                .then(res => res.json())
                .then(data => {
                    data.forEach(dep => {
                        const opt = document.createElement('option');
                        opt.value = dep.department_name; // เก็บเป็นชื่อ (สอดคล้อง users.department)
                        opt.textContent = dep.department_name;
                        subSelect.appendChild(opt);
                    });
                })
                .catch(()=>{ /* เงียบ ๆ */ });
        });

        // Toggle password visibility
        const pw = document.getElementById('password');
        const toggle = document.getElementById('togglePw');
        toggle.addEventListener('click', ()=>{
            const isText = pw.type === 'text';
            pw.type = isText ? 'password' : 'text';
            toggle.firstElementChild.className = isText ? 'fas fa-eye-slash' : 'fas fa-eye';
        });

        // Password strength meter (เบา ๆ)
        const bar  = document.getElementById('strengthBar');
        const hint = document.getElementById('pwHint');
        pw.addEventListener('input', ()=>{
            const v = pw.value || '';
            let score = 0;
            if (v.length >= 10) score++;
            if (/[a-z]/.test(v)) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[\W_]/.test(v)) score++;
            const pct = Math.min(100, score * 20);
            bar.style.width = pct + '%';
            if (pct < 40) { bar.style.filter = 'hue-rotate(0deg)'; hint.textContent = 'รหัสผ่านสั้น/รูปแบบยังอ่อน ลองเพิ่มความยาวและผสมตัวอักษร'; }
            else if (pct < 80) { bar.style.filter = 'hue-rotate(40deg)'; hint.textContent = 'ดีแล้ว เพิ่มตัวพิมพ์ใหญ่/ตัวเลข/อักขระพิเศษอีกนิด'; }
            else { bar.style.filter = 'hue-rotate(100deg)'; hint.textContent = 'แข็งแรงมาก พร้อมใช้งาน'; }
        });

        // ป้องกันกด Enter เผลอ submit ขณะอยู่ใน input (ยกเว้นปุ่ม)
        document.querySelector('.crud-form').addEventListener('keydown', (e)=>{
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                // อนุญาตถ้าคอร์สอยู่ที่ปุ่ม submit
                const isSubmitBtn = e.target.getAttribute('type') === 'submit';
                if (!isSubmitBtn) e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>
