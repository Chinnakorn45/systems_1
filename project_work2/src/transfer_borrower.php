<?php
require_once 'config.php';
require_once 'movement_logger.php';
session_start();
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ['admin','procurement'])) {
    http_response_code(403);
    exit;
}
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit;
}
$borrow_id = intval($_GET['id']);
// ดึงข้อมูลการยืม
$q = mysqli_query($link, "SELECT b.*, i.item_number, i.model_name, i.brand, i.location, u.full_name as borrower_name, u.department as borrower_department FROM borrowings b 
                        LEFT JOIN items i ON b.item_id = i.item_id
                        LEFT JOIN users u ON b.user_id = u.user_id
                        WHERE b.borrow_id = $borrow_id");
$borrowing = mysqli_fetch_assoc($q);
if (!$borrowing) {
    http_response_code(404);
    exit;
}
// ดึงรายชื่อผู้ใช้ทั้งหมด
$users = mysqli_query($link, "SELECT user_id, full_name, department FROM users ORDER BY full_name");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_user_id'])) {
    $new_user_id = intval($_POST['new_user_id']);
    $old_user_id = $borrowing['user_id'];
    
    // อัปเดตการยืม
    $stmt = mysqli_prepare($link, "UPDATE borrowings SET user_id=? WHERE borrow_id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $new_user_id, $borrow_id);
    $update_result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($update_result && $new_user_id != $old_user_id) {
        // ดึงข้อมูลผู้ใช้ใหม่
        $new_user_query = mysqli_query($link, "SELECT full_name, department FROM users WHERE user_id = $new_user_id");
        $new_user = mysqli_fetch_assoc($new_user_query);
        
        // บันทึกการเคลื่อนไหว
        $from_location = $borrowing['borrower_department'] ?: 'ไม่ระบุ';
        $to_location = $new_user['department'] ?: 'ไม่ระบุ';
        $notes = "โอนจาก " . $borrowing['borrower_name'] . " ไปยัง " . $new_user['full_name'];
        
        log_equipment_movement(
            $borrowing['item_id'],
            'transfer',
            $from_location,
            $to_location,
            $old_user_id,
            $new_user_id,
            1,
            $notes,
            $borrow_id
        );
    }
    
    echo 'success';
    exit;
}
?>
<form id="transferBorrowerForm" method="post">
    <div class="mb-3">
        <label class="form-label">เลือกผู้ใช้ใหม่</label>
        <input type="text" id="transferUserSearch" class="form-control mb-2" placeholder="พิมพ์ชื่อหรือแผนก เพื่อค้นหา...">
        <select name="new_user_id" class="form-select" required>
            <option value="">-- เลือกผู้ใช้ --</option>
            <?php while($u = mysqli_fetch_assoc($users)): ?>
                <option value="<?= $u['user_id'] ?>" <?= $u['user_id'] == $borrowing['user_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['department'] ?? '') ?>)
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">บันทึก</button>
    </div>
</form> 
