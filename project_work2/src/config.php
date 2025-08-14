<?php
// src/config.php

// --- 1) โหลดค่าจาก local (ถ้ามี) เพื่อ override ---
$localPath = __DIR__ . '/config.local.php';
if (file_exists($localPath)) {
    require_once $localPath; // ภายในควรใช้ if (!defined(...)) กัน define ซ้ำ
}

// --- 2) ค่าเริ่มต้น/ENV (กำหนดเฉพาะที่ยังไม่ถูก define) ---
if (!defined('DB_SERVER'))     define('DB_SERVER', getenv('DB_SERVER') ?: 'localhost');
if (!defined('DB_USERNAME'))   define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
if (!defined('DB_PASSWORD'))   define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
if (!defined('DB_NAME'))       define('DB_NAME',   getenv('DB_NAME')   ?: 'borrowing_db');

// เพิ่มพอร์ต/ซ็อกเก็ต (ไม่บังคับ)
if (!defined('DB_PORT'))       define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
if (!defined('DB_SOCKET'))     define('DB_SOCKET', getenv('DB_SOCKET') ?: null);

// เปิด/ปิดการสร้าง DB อัตโนมัติ (1 = เปิด, 0 = ปิด)
if (!defined('DB_AUTO_CREATE')) define('DB_AUTO_CREATE', (int)(getenv('DB_AUTO_CREATE') ?: 1));

// --- 3) เชื่อมต่อ MySQL (เชื่อมต่อไปที่เซิร์ฟเวอร์ก่อน ยังไม่เลือก DB) ---
$mysqli = mysqli_init();

$connected = @mysqli_real_connect(
    $mysqli,
    DB_SERVER,
    DB_USERNAME,
    DB_PASSWORD,
    null,           // ยังไม่เลือก DB ตรงนี้
    DB_PORT ?: 3306,
    DB_SOCKET ?: null
);

if (!$connected) {
    die("ERROR: Could not connect to MySQL server. " . mysqli_connect_error());
}

// --- 4) สร้างฐานข้อมูลถ้ายังไม่มี (ถ้าเปิด DB_AUTO_CREATE) แล้วเลือก DB ---
$escapedDb = str_replace('`', '``', DB_NAME);

if (DB_AUTO_CREATE) {
    $createSql = "CREATE DATABASE IF NOT EXISTS `{$escapedDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if (!@mysqli_query($mysqli, $createSql)) {
        die("ERROR: Failed to create database: " . mysqli_error($mysqli));
    }
}

// เลือก DB (ถ้าเลือกไม่ได้ ให้ฟ้องชัดเจน)
if (!@mysqli_select_db($mysqli, DB_NAME)) {
    die("ERROR: Could not select DB " . htmlspecialchars(DB_NAME));
}

// --- 5) ตั้งค่า charset และ timezone ---
if (!@mysqli_set_charset($mysqli, "utf8mb4")) {
    // ไม่ถึงกับ fatal แต่แจ้งไว้
    error_log("WARN: Failed to set charset to utf8mb4: " . mysqli_error($mysqli));
}

date_default_timezone_set('Asia/Bangkok');

// ทำตัวแปร $link ให้เข้ากับโค้ดเดิมที่ใช้ procedural
$link = $mysqli;

// --- 6) ฟังก์ชันแปลงวันที่ไทย ---
function thaidate($format, $datetime_str) {
    if (!$datetime_str) return '-';
    $timestamp = strtotime($datetime_str);
    if ($timestamp === false) return '-';

    $year = date('Y', $timestamp) + 543;

    $thai_months = [
        1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',
        5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',
        9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'
    ];
    $thai_short_months = [
        1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',
        5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',
        9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'
    ];

    $month = date('n', $timestamp);
    $day   = date('j', $timestamp);
    $time  = date('H:i', $timestamp);

    $result = $format;
    $result = str_replace('Y', $year, $result);
    $result = str_replace('y', substr($year, -2), $result);
    $result = str_replace('F', $thai_months[$month], $result);
    $result = str_replace('M', $thai_short_months[$month], $result);
    $result = str_replace('d', sprintf('%02d', $day), $result);
    $result = str_replace('j', $day, $result);
    $result = str_replace('H:i', $time, $result);

    return $result;
}
