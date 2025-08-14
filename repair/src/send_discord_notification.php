<?php
require_once __DIR__ . '/db.php';

// ฟังก์ชันแปลงวันที่เป็นไทย
function thai_date($datetime) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $ts = strtotime($datetime);
    $day = date('j', $ts);
    $month = $months[(int)date('n', $ts)];
    $year = date('Y', $ts) + 543;
    return "$day $month $year";
}

// ดึงข้อมูลแจ้งซ่อมล่าสุด 1 รายการ
$sql = "SELECT r.*, u.full_name, d.department_name
        FROM repairs r
        LEFT JOIN users u ON r.reported_by = u.user_id
        LEFT JOIN departments d ON u.department = d.department_id
        ORDER BY r.created_at DESC
        LIMIT 1";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    $reportDate = thai_date($row['created_at']);
    $reporter = $row['full_name'];
    $department = $row['department_name'];
    $itemName = $row['model_name'];
    $itemCode = $row['asset_number'];
    // Prioritize 'location' if it exists, otherwise use 'location_name', default to '-'
    $location = !empty($row['location']) ? $row['location'] : (!empty($row['location_name']) ? $row['location_name'] : '-');
    $issue = $row['issue_description'];
    $hasImage = (!empty($row['image']) && $row['image'] !== '-') ? 'มี' : 'ไม่มี';
} else {
    exit('ไม่พบข้อมูลแจ้งซ่อม');
}

$data = [
    "embeds" => [[
        "title" => "🔧 แจ้งซ่อมครุภัณฑ์ใหม่",
        "color" => 16753920,
        "fields" => [
            [
                "name" => "ข้อมูลแจ้งซ่อม:",
                "value" => "📅 **วันที่แจ้ง**: $reportDate\n\n" . // Added extra \n
                           "🧑‍💼 **ผู้แจ้ง**: $reporter\n\n" . // Added extra \n
                           "🖥 **รายการ**: $itemName\n\n" . // Added extra \n
                           "🏷 **เลขครุภัณฑ์**: $itemCode\n\n" . // Added extra \n
                           "📍 **สถานที่**: $location\n\n" . // Added extra \n
                           "❗ **อาการที่พบ**: $issue\n\n" . // Added extra \n
                           "🖼 **รูปภาพ**: $hasImage",
                "inline" => false
            ]
        ],
        "footer" => [
            "text" => "คลิกเพื่อดูรายละเอียดในระบบ"
        ],
        "timestamp" => date("c")
    ]]
];

// ใส่ webhook URL จริงของคุณ
$webhookUrl = "https://discordapp.com/api/webhooks/1341720703434489856/heBAzahluGlbQIuVQqTyIq4YqzofZ5Jo8D9EZrEfn4hQ-Z6rBPTh3IhOMKUM7JTmfouw";

$options = [
    "http" => [
        "header"  => "Content-type: application/json",
        "method"  => "POST",
        "content" => json_encode($data, JSON_UNESCAPED_UNICODE)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($webhookUrl, false, $context);

// บันทึก log การแจ้งเตือนลงฐานข้อมูล
$repair_id = isset($row['repair_id']) ? $row['repair_id'] : null;
$status = ($result === FALSE) ? 'fail' : 'success';
$response = $result === FALSE ? '' : $result;

$stmt_log = $conn->prepare("INSERT INTO discord_logs (repair_id, status, response) VALUES (?, ?, ?)");
$stmt_log->bind_param('iss', $repair_id, $status, $response);
$stmt_log->execute();
$stmt_log->close();
?>