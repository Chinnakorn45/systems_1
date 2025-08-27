<?php
/**
 * config.php — Global configuration for Supplies module
 * วางไฟล์นี้ไว้ที่: C:\xampp\htdocs\systems_1\supplies\config.php
 * (หรือที่อื่นแล้วปรับพาธ require_once ให้ตรง)
 */

/* =========================
   Error & Timezone
   ========================= */
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Bangkok');

/* =========================
   Database Credentials
   =========================
   แก้ค่าตามเครื่องของคุณถ้าจำเป็น
*/
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'borrowing_db'); // ใช้ฐานเดียวกับระบบหลักของคุณ

/* =========================
   MySQLi Connection ($link)
   ========================= */
$link = mysqli_init();
if (!$link) {
    http_response_code(500);
    die('ไม่สามารถเริ่มต้น mysqli ได้');
}

// ตัวเลือกช่วย parse int/float เป็นชนิดจริง (ถ้าเป็นไปได้)
mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

// เชื่อมต่อ
if (!@mysqli_real_connect($link, DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
    http_response_code(500);
    die('เชื่อมต่อฐานข้อมูลล้มเหลว: ' . mysqli_connect_error());
}

// บังคับใช้ utf8mb4
if (!mysqli_set_charset($link, 'utf8mb4')) {
    // ถ้าตั้ง charset ไม่ได้ ก็ยังทำงานต่อได้ แต่แจ้งเตือน
    // echo 'คำเตือน: ตั้งค่า charset ไม่สำเร็จ: ' . mysqli_error($link);
}

/* =========================
   Helpers (optional)
   ========================= */

/** ตรวจว่ามี session แล้วหรือยัง (บางหน้าจะ start เองอยู่แล้ว) */
if (session_status() === PHP_SESSION_NONE) {
    // ไม่ start ซ้ำ เพื่อให้หน้าแต่ละหน้าคุมเองตามที่ออกแบบไว้
}

/** ฟังก์ชัน escape ข้อความเวลาแสดงผล HTML */
function h(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
