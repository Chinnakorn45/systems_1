<?php
require_once 'config.php';

// สร้างตาราง equipment_movements
$sql = "CREATE TABLE IF NOT EXISTS `equipment_movements` (
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

if (mysqli_query($link, $sql)) {
    echo "ตาราง equipment_movements ถูกสร้างเรียบร้อยแล้ว<br>";
    
    // สร้าง index
    $indexes = [
        "CREATE INDEX idx_equipment_movements_item_id ON equipment_movements(item_id)",
        "CREATE INDEX idx_equipment_movements_movement_date ON equipment_movements(movement_date)",
        "CREATE INDEX idx_equipment_movements_movement_type ON equipment_movements(movement_type)"
    ];
    
    foreach ($indexes as $index_sql) {
        if (mysqli_query($link, $index_sql)) {
            echo "สร้าง index สำเร็จ<br>";
        } else {
            echo "Error creating index: " . mysqli_error($link) . "<br>";
        }
    }
    
    echo "<br><a href='equipment_history.php'>ไปยังหน้าประวัติการเคลื่อนไหว</a>";
} else {
    echo "Error creating table: " . mysqli_error($link);
}
?>