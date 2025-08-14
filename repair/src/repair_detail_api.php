<?php
require_once 'db.php';
$id = intval($_GET['repair_id'] ?? 0);
$sql = "SELECT r.*, u.full_name, i.model_name, i.location, c.category_name
        FROM repairs r
        JOIN users u ON r.reported_by = u.user_id
        JOIN items i ON r.item_id = i.item_id
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE r.repair_id = $id";
$row = $conn->query($sql)->fetch_assoc();
header('Content-Type: application/json');
echo json_encode($row);