-- Migration: create item_images table
-- Creates a table to store multiple images per item

CREATE TABLE IF NOT EXISTS `item_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  INDEX (`item_id`),
  CONSTRAINT `fk_item_images_item` FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
