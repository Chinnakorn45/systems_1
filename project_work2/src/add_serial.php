<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    http_response_code(403);
    echo 'Permission denied';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $serial_number = trim($_POST['serial_number'] ?? '');
    $total_quantity = intval($_POST['total_quantity'] ?? 1);
    $price_per_unit = floatval($_POST['price_per_unit'] ?? 0);
    $total_price = $total_quantity * $price_per_unit;

    if ($item_id <= 0 || $serial_number === '') {
        echo 'กรุณากรอก Serial Number';
        exit;
    }

    // ตรวจสอบ serial ซ้ำ
    $sql_check = "SELECT COUNT(*) AS cnt FROM items WHERE serial_number = ?";
    $stmt = mysqli_prepare($link, $sql_check);
    mysqli_stmt_bind_param($stmt, "s", $serial_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    if ($row['cnt'] > 0) {
        echo 'Serial Number นี้ถูกใช้ไปแล้วในระบบ';
        exit;
    }
    mysqli_stmt_close($stmt);

    // ดึงข้อมูลเดิม
    $sql = "SELECT * FROM items WHERE item_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$item) {
        echo 'ไม่พบครุภัณฑ์เดิม';
        exit;
    }

    // เพิ่มแถวใหม่ (copy ข้อมูลเดิม เปลี่ยน serial, จำนวน, ราคา)
    $sql_insert = "INSERT INTO items (item_title, item_name, serial_number, brand, description, note, category_id, total_quantity, image, status, location, purchase_date, budget_year, price_per_unit, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql_insert);
    mysqli_stmt_bind_param($stmt, "sssssssissssssd",
        $item['item_title'],
        $item['item_name'],
        $serial_number,
        $item['brand'],
        $item['description'],
        $item['note'],
        $item['category_id'],
        $total_quantity,
        $item['image'],
        $item['status'],
        $item['location'],
        $item['purchase_date'],
        $item['budget_year'],
        $price_per_unit,
        $total_price
    );
    if (mysqli_stmt_execute($stmt)) {
        echo 'success';
    } else {
        echo 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล';
    }
    mysqli_stmt_close($stmt);
    exit;
}
echo 'Invalid request'; 