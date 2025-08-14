<?php
// 🔧 ดึงข้อมูลจากฐานข้อมูล
require_once 'db/connection.php';

// ฟังก์ชันเพื่อดึงข้อมูลการแจ้งซ่อมใหม่
function getNewRepairRequest($conn) {
    $sql = "SELECT * FROM repair_requests ORDER BY created_at DESC LIMIT 1"; // ดึงข้อมูลการแจ้งซ่อมล่าสุด
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);

// เช็คการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลการแจ้งซ่อมใหม่
$repairRequest = getNewRepairRequest($conn);
$conn->close();

if ($repairRequest) {
    // 🔧 ตัวอย่างข้อมูลที่ดึงมาจากฐานข้อมูล
    $reportDate = date("d/m/Y", strtotime($repairRequest['created_at'])); // วันที่แจ้ง
    $reporter = $repairRequest['reporter_name'];
    $department = $repairRequest['department'];
    $itemName = $repairRequest['item_name'];
    $itemCode = $repairRequest['item_code'];
    $location = $repairRequest['location'];
    $issue = $repairRequest['issue_description'];
    $link = "https://your-system.example.com/repairs/" . $repairRequest['id'];

    // 🔗 URL Webhook ของ Discord (ให้เปลี่ยนเป็นของคุณ)
    $webhookUrl = "https://discord.com/api/webhooks/WEBHOOK_ID/WEBHOOK_TOKEN";

    // 💬 เตรียมข้อมูล Embed
    $data = [
        "embeds" => [[
            "title" => "🔧 แจ้งซ่อมครุภัณฑ์ใหม่",
            "color" => 16753920, // สีแดง-ส้ม
            "fields" => [
                [
                    "name" => "📅 วันที่แจ้ง",
                    "value" => $reportDate,
                    "inline" => true
                ],
                [
                    "name" => "🧑‍💼 ผู้แจ้ง",
                    "value" => "$reporter ($department)",
                    "inline" => true
                ],
                [
                    "name" => "🖥 รายการ",
                    "value" => $itemName
                ],
                [
                    "name" => "🏷 รหัสครุภัณฑ์",
                    "value" => $itemCode,
                    "inline" => true
                ],
                [
                    "name" => "📍 สถานที่",
                    "value" => $location,
                    "inline" => true
                ],
                [
                    "name" => "❗ อาการที่พบ",
                    "value" => $issue
                ]
            ],
            "footer" => [
                "text" => "คลิกเพื่อดูรายละเอียดในระบบ"
            ],
            "url" => $link,
            "timestamp" => date("c") // ISO8601 format
        ]]
    ];

    // 🌐 ส่งไปยัง Discord Webhook
    $options = [
        "http" => [
            "header"  => "Content-type: application/json",
            "method"  => "POST",
            "content" => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($webhookUrl, false, $context);

    // ✅ เช็กผลลัพธ์
    if ($result === FALSE) {
        echo "❌ ส่งไม่สำเร็จ!";
    } else {
        echo "✅ ส่งแจ้งเตือนไปยัง Discord เรียบร้อยแล้ว!";
    }
} else {
    echo "❌ ไม่พบการแจ้งซ่อมใหม่!";
}
?>