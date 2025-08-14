<?php
// ตัวอย่างโครงสร้าง export excel (ยังไม่ใช้งานจริง)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="repair_report.xls"');
echo "หมวดหมู่\tจำนวนแจ้งซ่อม\tซ่อมเสร็จ\tยังไม่เสร็จ\tยกเลิก\t% สำเร็จ\n";
echo "ตัวอย่างข้อมูล\t10\t8\t1\t1\t80%\n";
// TODO: ใช้ PhpSpreadsheet ดึงข้อมูลจริงและส่งออก Excel 