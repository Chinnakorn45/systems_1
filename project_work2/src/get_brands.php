<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT * FROM brands ORDER BY brand_name";
    $result = mysqli_query($link, $sql);
    
    $brands = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $brands[] = $row;
    }
    
    echo json_encode($brands);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
