<?php
require_once 'config.php';

echo "<h2>กำลังตรวจสอบและแก้ไขฐานข้อมูล...</h2>";

// ตรวจสอบและเพิ่มคอลัมน์ที่จำเป็นในตาราง items
$alter_queries = [
    "ALTER TABLE items ADD COLUMN IF NOT EXISTS available_quantity INT NOT NULL DEFAULT 0 COMMENT 'จำนวนที่พร้อมให้ยืม' AFTER total_quantity",
    "ALTER TABLE items ADD COLUMN IF NOT EXISTS item_title VARCHAR(255) COMMENT 'รุ่น/โมเดล' AFTER item_name",
    "ALTER TABLE items ADD COLUMN IF NOT EXISTS brand VARCHAR(100) COMMENT 'ยี่ห้อ' AFTER item_title"
];

foreach ($alter_queries as $query) {
    if (mysqli_query($link, $query)) {
        echo "✅ " . $query . " - สำเร็จ<br>";
    } else {
        echo "❌ " . $query . " - " . mysqli_error($link) . "<br>";
    }
}

// อัปเดต available_quantity ให้ตรงกับ total_quantity สำหรับข้อมูลที่มีอยู่
$update_query = "UPDATE items SET available_quantity = total_quantity WHERE available_quantity = 0 OR available_quantity IS NULL";
if (mysqli_query($link, $update_query)) {
    echo "✅ อัปเดต available_quantity สำเร็จ<br>";
} else {
    echo "❌ อัปเดต available_quantity - " . mysqli_error($link) . "<br>";
}

// ตรวจสอบและสร้างตาราง equipment_movements ถ้ายังไม่มี
$check_table = "SHOW TABLES LIKE 'equipment_movements'";
$result = mysqli_query($link, $check_table);

if (mysqli_num_rows($result) == 0) {
    echo "<br><h3>กำลังสร้างตาราง equipment_movements...</h3>";
    
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `equipment_movements` (
        `movement_id` INT AUTO_INCREMENT PRIMARY KEY,
        `item_id` INT NOT NULL COMMENT 'ID ครุภัณฑ์',
        `movement_type` ENUM('borrow', 'return', 'transfer', 'maintenance', 'disposal', 'purchase', 'adjustment') NOT NULL COMMENT 'ประเภทการเคลื่อนไหว',
        `from_location` VARCHAR(100) COMMENT 'ตำแหน่งเดิม',
        `to_location` VARCHAR(100) COMMENT 'ตำแหน่งใหม่',
        `from_user_id` INT COMMENT 'ผู้ใช้เดิม',
        `to_user_id` INT COMMENT 'ผู้ใช้ใหม่',
        `quantity` INT NOT NULL DEFAULT 1 COMMENT 'จำนวนที่เคลื่อนไหว',
        `movement_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่เคลื่อนไหว',
        `notes` TEXT COMMENT 'หมายเหตุ',
        `created_by` INT NOT NULL COMMENT 'ผู้บันทึกการเคลื่อนไหว',
        `borrow_id` INT NULL COMMENT 'ID การยืม (ถ้ามี)',
        FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (`from_user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
        FOREIGN KEY (`to_user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (`borrow_id`) REFERENCES `borrowings`(`borrow_id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการเคลื่อนไหวของครุภัณฑ์'";

    if (mysqli_query($link, $create_table_sql)) {
        echo "✅ สร้างตาราง equipment_movements สำเร็จ<br>";
        
        // สร้าง index
        $indexes = [
            "CREATE INDEX idx_equipment_movements_item_id ON equipment_movements(item_id)",
            "CREATE INDEX idx_equipment_movements_movement_date ON equipment_movements(movement_date)",
            "CREATE INDEX idx_equipment_movements_movement_type ON equipment_movements(movement_type)"
        ];
        
        foreach ($indexes as $index_sql) {
            if (mysqli_query($link, $index_sql)) {
                echo "✅ สร้าง index สำเร็จ<br>";
            } else {
                echo "❌ สร้าง index - " . mysqli_error($link) . "<br>";
            }
        }
    } else {
        echo "❌ สร้างตาราง equipment_movements - " . mysqli_error($link) . "<br>";
    }
} else {
    echo "✅ ตาราง equipment_movements มีอยู่แล้ว<br>";
}

echo "<br><h3>✅ การแก้ไขฐานข้อมูลเสร็จสิ้น!</h3>";
echo "<a href='equipment_history.php'>ไปยังหน้าประวัติการเคลื่อนไหว</a> | ";
echo "<a href='add_borrowing.php'>ไปยังหน้าเพิ่มการยืม</a>";
?>