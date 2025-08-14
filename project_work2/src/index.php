<?php
// borrowing-system/src/index.php

session_start();
require_once 'config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    // ถ้ายังไม่ได้ล็อกอิน ให้แสดงหน้า Login
    include 'login.php';
    exit();
}

// ถ้าล็อกอินแล้ว ให้แสดงหน้า Dashboard
include 'dashboard.php';

