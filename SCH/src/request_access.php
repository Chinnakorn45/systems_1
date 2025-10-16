<?php
session_start();

/* ===== Database Connection ===== */
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'borrowing_db';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

/* ===== ดึงรายชื่อแผนกหลัก ===== */
$main_departments = [];
$res = $conn->query("SELECT department_id, department_name FROM departments WHERE parent_id IS NULL ORDER BY department_name");
while ($row = $res->fetch_assoc()) $main_departments[] = $row;

/* ===== เมื่อส่งฟอร์ม ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $main_dept  = isset($_POST['main_department']) ? intval($_POST['main_department']) : 0;
    $sub_dept   = isset($_POST['sub_department']) ? intval($_POST['sub_department']) : 0;
    $position   = trim($_POST['position'] ?? '');
    $note       = trim($_POST['note'] ?? ''); // ✅ บทบาทที่เลือกจาก select

    if ($username && $password && $full_name && $email && $main_dept && $sub_dept && $position) {

        /* ===== ตรวจสอบซ้ำ: Username / Email ===== */
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "❌ ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว กรุณาเลือกใหม่";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'staff'; // ค่าเริ่มต้นในฐานข้อมูล

            // ดึงชื่อแผนกหลักและย่อย
            $main_name = $conn->query("SELECT department_name FROM departments WHERE department_id=$main_dept")->fetch_assoc()['department_name'] ?? 'ไม่ระบุ';
            $sub_name  = $conn->query("SELECT department_name FROM departments WHERE department_id=$sub_dept")->fetch_assoc()['department_name'] ?? 'ไม่ระบุ';
            $dept_fullname = $main_name . ' / ' . $sub_name;

            /* ===== บันทึกข้อมูลผู้ใช้ ===== */
            $stmt = $conn->prepare("
                INSERT INTO users (username, password_hash, full_name, email, department, position, role)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssss", $username, $password_hash, $full_name, $email, $dept_fullname, $position, $role);

            if ($stmt->execute()) {
                $success = true;

                /* ===== แจ้งเตือนไปยัง Discord ===== */
                $webhook_url = "https://discordapp.com/api/webhooks/1341720703434489856/heBAzahluGlbQIuVQqTyIq4YqzofZ5Jo8D9EZrEfn4hQ-Z6rBPTh3IhOMKUM7JTmfouw"; // 🔹 ใส่ URL ของคุณ

                $msg = [
                    "username" => "SCH System Bot 🤖",
                    "avatar_url" => "https://i.imgur.com/5M3qY3R.png",
                    "embeds" => [[
                        "title" => "🆕 มีผู้ใช้ใหม่ส่งคำขอใช้งานระบบ",
                        "color" => hexdec("007bff"),
                        "fields" => [
                            ["name" => "👤 ชื่อ-นามสกุล", "value" => $full_name, "inline" => false],
                            ["name" => "📧 อีเมล", "value" => $email, "inline" => true],
                            ["name" => "🏢 หน่วยงาน", "value" => $dept_fullname, "inline" => true],
                            ["name" => "💼 ตำแหน่ง", "value" => $position, "inline" => true],
                            ["name" => "🎯 บทบาทที่ต้องการ", "value" => $note, "inline" => false],
                            ["name" => "🕒 เวลา", "value" => date('d/m/Y H:i:s'), "inline" => false],
                        ],
                        "footer" => ["text" => "Suratthani Cancer Hospital"]
                    ]]
                ];

                $ch = curl_init($webhook_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msg, JSON_UNESCAPED_UNICODE));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_exec($ch);
                curl_close($ch);
            } else {
                $error = "เกิดข้อผิดพลาด: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

/* ===== Handle AJAX sub-department ===== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_sub' && isset($_GET['parent_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid = intval($_GET['parent_id']);
    $sub = [];
    $q = $conn->query("SELECT department_id, department_name FROM departments WHERE parent_id = $pid ORDER BY department_name");
    while ($r = $q->fetch_assoc()) $sub[] = $r;
    echo json_encode($sub);
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แบบฟอร์มขอใช้งานระบบ - โรงพยาบาลมะเร็งสุราษฎร์ธานี</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Sarabun', sans-serif;
  background: #f2f6fc;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 20px;
}
.form-container {
  background: #fff;
  padding: 35px 40px;
  border-radius: 14px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  width: 100%;
  max-width: 450px;
}
h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #007bff;
  font-weight: 700;
}
label {
  font-weight: 600;
  display: block;
  margin-top: 10px;
}
input, select {
  width: 100%;
  padding: 9px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-sizing: border-box;
  font-size: 1em;
}
button {
  width: 100%;
  margin-top: 20px;
  padding: 10px;
  background: #007bff;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1em;
  transition: background 0.2s ease, transform 0.1s ease;
}
button:hover {
  background: #0056b3;
  transform: translateY(-1px);
}
.success, .error {
  padding: 10px;
  border-radius: 6px;
  margin-bottom: 10px;
  text-align: center;
  font-weight: 600;
}
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
.note {
  font-size: 0.85em;
  color: #555;
  text-align: center;
  margin-top: 15px;
}
@media (max-width: 480px) {
  .form-container { padding: 25px 20px; }
}
</style>
</head>
<body>
<div class="form-container">
  <h2>📋 แบบฟอร์มขอใช้งานระบบ</h2>

  <?php if (!empty($success)): ?>
      <div class="success">✅ ส่งคำขอสำเร็จ! แจ้งเตือนไปยัง Discord แล้ว</div>
  <?php elseif (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <label>ชื่อผู้ใช้งาน (Username)</label>
    <input type="text" name="username" required>

    <label>รหัสผ่าน</label>
    <input type="password" name="password" required>

    <label>ชื่อ-นามสกุล</label>
    <input type="text" name="full_name" required>

    <label>อีเมล (ราชการ)</label>
    <input type="email" name="email" required>

    <label>แผนกหลัก</label>
    <select id="main_department" name="main_department" required>
      <option value="">-- เลือกแผนกหลัก --</option>
      <?php foreach ($main_departments as $md): ?>
        <option value="<?= $md['department_id'] ?>"><?= htmlspecialchars($md['department_name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>แผนกย่อย</label>
    <select id="sub_department" name="sub_department" required>
      <option value="">-- เลือกแผนกย่อย --</option>
    </select>

    <label>ตำแหน่ง</label>
    <input type="text" name="position" required>

    <label>บทบาทที่ต้องการ</label>
    <select name="note" required>
      <option value="">-- เลือกบทบาทที่ต้องการ --</option>
      <option value="ผู้ดูแลระบบ">ผู้ดูแลระบบ</option>
      <option value="เจ้าหน้าที่พัสดุ">เจ้าหน้าที่พัสดุ</option>
      <option value="ผู้ใช้งานทั่วไป">ผู้ใช้งานทั่วไป</option>
    </select>

    <button type="submit">ส่งคำขอใช้งาน</button>
  </form>

  <div class="note">
    ระบบจะกำหนดสิทธิ์เริ่มต้นเป็น <b>Staff</b><br>
    เจ้าหน้าที่ฝ่าย IT จะตรวจสอบและอนุมัติสิทธิ์ต่อไป
  </div>
</div>

<script>
document.getElementById('main_department').addEventListener('change', function() {
  const pid = this.value;
  const subSelect = document.getElementById('sub_department');
  subSelect.innerHTML = '<option value="">-- กำลังโหลด --</option>';

  if (pid) {
    fetch('?ajax=get_sub&parent_id=' + pid)
      .then(res => res.json())
      .then(data => {
        subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
        if (data.length > 0) {
          data.forEach(d => {
            subSelect.innerHTML += `<option value="${d.department_id}">${d.department_name}</option>`;
          });
        } else {
          subSelect.innerHTML = '<option value="">(ไม่มีแผนกย่อย)</option>';
        }
      })
      .catch(() => subSelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>');
  } else {
    subSelect.innerHTML = '<option value="">-- เลือกแผนกย่อย --</option>';
  }
});
</script>
</body>
</html>
