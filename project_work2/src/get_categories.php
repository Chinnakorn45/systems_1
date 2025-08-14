<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT * FROM categories ORDER BY category_name";
    $result = mysqli_query($link, $sql);
    
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    
    echo json_encode($categories);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
