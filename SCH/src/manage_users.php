<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

/* ===== Database Connection ===== */
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'borrowing_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

/* ===== Actions ===== */
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    if ($_GET['action'] === 'delete') {
        $conn->query("DELETE FROM users WHERE user_id = $user_id");
    } elseif ($_GET['action'] === 'make_admin') {
        $conn->query("UPDATE users SET role = 'admin' WHERE user_id = $user_id");
    } elseif ($_GET['action'] === 'make_procurement') {
        $conn->query("UPDATE users SET role = 'procurement' WHERE user_id = $user_id");
    } elseif ($_GET['action'] === 'make_staff') {
        $conn->query("UPDATE users SET role = 'staff' WHERE user_id = $user_id");
    }
    header("Location: manage_users.php");
    exit;
}

/* ===== Fetch Users ===== */
$result = $conn->query("SELECT * FROM users ORDER BY user_id ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการผู้ใช้งานระบบ</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<style>
:root {
  --brand: #007bff;
  --proc: #17a2b8;
  --staff: #6c757d;
  --danger: #dc3545;
  --bg: #f8f9fb;
  --card: #ffffff;
}

body {
  font-family: 'Sarabun', sans-serif;
  background: var(--bg);
  margin: 0;
  padding: 20px;
  color: #333;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  background: var(--card);
  padding: 25px;
  border-radius: 16px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

h2 {
  text-align: center;
  margin-bottom: 25px;
  color: var(--brand);
  font-weight: 700;
  letter-spacing: 0.5px;
}

/* ตาราง */
.table-wrapper {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  min-width: 800px;
}
th, td {
  border-bottom: 1px solid #e9ecef;
  padding: 10px 12px;
  text-align: left;
  font-size: 0.9em;
  white-space: nowrap;
}
th {
  background: #f1f4f9;
  font-weight: 600;
  text-align: center;
}
tr:hover { background: #f8fbff; }

/* ป้าย role */
.role-badge {
  padding: 5px 10px;
  border-radius: 20px;
  color: #fff;
  font-size: 0.8em;
  text-transform: capitalize;
  display: inline-block;
}
.role-admin { background: var(--brand); }
.role-procurement { background: var(--proc); }
.role-staff { background: var(--staff); }

/* ปุ่มการจัดการ */
.actions a {
  text-decoration: none;
  padding: 6px 12px;
  border-radius: 25px;
  font-size: 0.8em;
  margin: 3px;
  display: inline-block;
  transition: all 0.2s ease;
  color: #fff;
  font-weight: 500;
}
.make-admin { background: var(--brand); }
.make-proc { background: var(--proc); }
.make-staff { background: var(--staff); }
.delete { background: var(--danger); }

.actions a:hover {
  opacity: 0.85;
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
  body { padding: 10px; }
  .container {
    padding: 15px;
    border-radius: 12px;
  }
  table {
    font-size: 0.85em;
    min-width: 600px;
  }
  th, td {
    padding: 8px;
  }
}
@media (max-width: 480px) {
  h2 {
    font-size: 1.2rem;
  }
  .actions a {
    display: block;
    margin: 5px auto;
    width: 90%;
    text-align: center;
  }
}
</style>
</head>
<body>
<div class="container">
  <h2>📋 จัดการผู้ใช้งานระบบ</h2>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>ชื่อผู้ใช้</th>
          <th>ชื่อ-นามสกุล</th>
          <th>อีเมล</th>
          <th>หน่วยงาน</th>
          <th>ตำแหน่ง</th>
          <th>สิทธิ์ใช้งาน</th>
          <th>การจัดการ</th>
        </tr>
      </thead>
      <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['user_id'] ?></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td><?= htmlspecialchars($row['full_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['position'] ?? '-') ?></td>
          <td style="text-align:center;">
            <span class="role-badge role-<?= htmlspecialchars($row['role']) ?>">
              <?= htmlspecialchars($row['role']) ?>
            </span>
          </td>
          <td class="actions" style="text-align:center;">
            <a class="make-admin" href="?action=make_admin&id=<?= $row['user_id'] ?>">ตั้งเป็น Admin</a>
            <a class="make-proc" href="?action=make_procurement&id=<?= $row['user_id'] ?>">ตั้งเป็น พัสดุ</a>
            <a class="make-staff" href="?action=make_staff&id=<?= $row['user_id'] ?>">ตั้งเป็น Staff</a>
            <a class="delete" href="?action=delete&id=<?= $row['user_id'] ?>" onclick="return confirm('ลบผู้ใช้นี้หรือไม่?')">ลบ</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
