<?php
// search_items.php — ใช้ item_number เป็น "เลขครุภัณฑ์"
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

require_once __DIR__ . '/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q === '' || mb_strlen($q) < 2) {
        echo json_encode([]); exit;
    }
    $like = '%' . $q . '%';

    // ตั้งค่าตารางและคอลัมน์
    $itemsTable = 'items';
    $assetWant  = ['item_number']; // << เลขครุภัณฑ์ของคุณ
    $idWant     = ['item_id','id'];
    $serialWant = ['serial_number','serial_no','serial','sn'];
    $brandWant  = ['brand','manufacturer'];
    $modelWant  = ['model_name','model','model_no','model_code','modelcode'];

    // ฟังก์ชันช่วยเลือกคอลัมน์ที่มีจริง
    $dbName = $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
    $pickCol = function(array $cands) use ($conn, $dbName, $itemsTable) {
        $in = implode("','", array_map([$conn,'real_escape_string'], $cands));
        $sql = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA='{$conn->real_escape_string($dbName)}'
                  AND TABLE_NAME='{$conn->real_escape_string($itemsTable)}'
                  AND COLUMN_NAME IN ('$in')";
        $res = $conn->query($sql);
        $present = [];
        while ($r = $res->fetch_assoc()) $present[$r['COLUMN_NAME']] = true;
        foreach ($cands as $c) if (isset($present[$c])) return $c;
        return null;
    };

    // ระบุคอลัมน์จริง
    $idCol     = $pickCol($idWant)     ?? 'item_id'; // เดาเป็น item_id ถ้าไม่พบ
    $assetCol  = $pickCol($assetWant);              // ต้องมี!
    $serialCol = $pickCol($serialWant);             // ถ้ามีก็ใช้
    $brandCol  = $pickCol($brandWant);
    $modelCol  = $pickCol($modelWant);

    if (!$assetCol) { echo json_encode([]); exit; } // ไม่มี item_number ในตาราง → คืนผลว่าง

    // สร้าง SELECT/WHERE
    $select = [
        "`$idCol` AS item_id",
        "`$assetCol` AS asset_number",
        $serialCol ? "`$serialCol` AS serial_number" : "NULL AS serial_number",
        $brandCol  ? "`$brandCol`  AS brand"         : "NULL AS brand",
        $modelCol  ? "`$modelCol`  AS model_name"    : "NULL AS model_name",
    ];
    $where  = ["`$assetCol` LIKE ?"];
    $types  = 's';
    $params = [$like];
    if ($serialCol) { $where[] = "`$serialCol` LIKE ?"; $types .= 's'; $params[] = $like; }

    $orderCol = $modelCol ?: $assetCol;

    $sql = "SELECT ".implode(', ', $select)."
            FROM `$itemsTable`
            WHERE ".implode(' OR ', $where)."
            ORDER BY `$orderCol` ASC
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'item_id'       => (string)($row['item_id'] ?? ''),
            'asset_number'  => (string)($row['asset_number'] ?? ''),
            'serial_number' => (string)($row['serial_number'] ?? ''),
            'brand'         => (string)($row['brand'] ?? ''),
            'model_name'    => (string)($row['model_name'] ?? '')
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // ไม่ปล่อย error HTML ไปให้ frontend
    http_response_code(200);
    echo json_encode(['error' => true, 'message' => 'search_failed'], JSON_UNESCAPED_UNICODE);
}
