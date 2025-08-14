<?php
require_once '../src/db/connection.php';
require_once '../src/send_discord_notification.php';

// Function to retrieve repair requests from the database
function getRepairRequests($conn) {
    $sql = "SELECT * FROM repair_requests WHERE status = 'new'";
    $result = $conn->query($sql);
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Establish database connection
$conn = dbConnect();

// Retrieve new repair requests
$repairRequests = getRepairRequests($conn);

// Check if there are new repair requests
if (!empty($repairRequests)) {
    foreach ($repairRequests as $request) {
        // Prepare data for notification
        $reportDate = date("d/m/Y", strtotime($request['report_date']));
        $reporter = $request['reporter_name'];
        $department = $request['department'];
        $itemName = $request['item_name'];
        $itemCode = $request['item_code'];
        $location = $request['location'];
        $issue = $request['issue_description'];
        $link = "https://your-system.example.com/repairs/" . $request['id'];

        // Send notification to Discord
        sendDiscordNotification($reportDate, $reporter, $department, $itemName, $itemCode, $location, $issue, $link);
    }
}

// Close the database connection
$conn->close();
?>