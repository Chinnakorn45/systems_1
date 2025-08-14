<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT m.model_id, m.model_name, b.brand_name 
            FROM models m 
            LEFT JOIN brands b ON m.brand_id = b.brand_id 
            ORDER BY m.model_name";
    $result = mysqli_query($link, $sql);
    
    $models = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $models[] = $row;
    }
    
    echo json_encode($models);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
