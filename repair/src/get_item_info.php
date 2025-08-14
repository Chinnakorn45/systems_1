<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_GET['item_id'])) {
    echo json_encode(['asset_number' => '', 'serial_number' => '', 'location_name' => '', 'brand' => '', 'model_name' => '']);
    exit;
}

$item_id = intval($_GET['item_id']);
$res = $conn->query("SELECT item_number, serial_number, location, brand, model_name FROM items WHERE item_id = $item_id LIMIT 1");

if ($res && $row = $res->fetch_assoc()) {
    echo json_encode([
        'asset_number'   => $row['item_number'],
        'serial_number'  => $row['serial_number'],
        'location_name'  => $row['location'],
        'brand'          => $row['brand'],
        'model_name'     => $row['model_name']
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['asset_number' => '', 'serial_number' => '', 'location_name' => '', 'brand' => '', 'model_name' => '']);
}
exit;
?>