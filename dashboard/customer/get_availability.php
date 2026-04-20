<?php
header('Content-Type: application/json');
include_once("../../db.php");

if (!isset($_GET['staff_id'])) {
    echo json_encode(['error' => 'Missing staff_id']);
    exit;
}

$sid = intval($_GET['staff_id']);

// 1. Get Staff Info (Days and Specific Slot)
$q_staff = mysqli_query($con, "SELECT schedule, time_slot FROM staff WHERE staff_id = $sid");
if(!$sData = mysqli_fetch_assoc($q_staff)) {
    echo json_encode(['error' => 'Staff not found']);
    exit;
}

// 2. Parse Shift Time
$time_slot = $sData['time_slot'];
$minTime = "00:00";
$maxTime = "23:59";

if (!empty($time_slot) && strpos($time_slot, '-') !== false) {
    $parts = explode('-', $time_slot);
    $minTime = date('H:i', strtotime(trim($parts[0])));
    $maxTime = date('H:i', strtotime(trim($parts[1])));
}

// 3. Get Existing Appointments (Busy Slots)
$busy = [];
$q_app = mysqli_query($con, "SELECT date, time FROM appointments WHERE staff_id = $sid AND status != 'cancelled'");
while($row = mysqli_fetch_assoc($q_app)) {
    // Combine into a JS-friendly format or keep separate
    $busy[] = [
        'date' => $row['date'],
        'time' => date('H:i', strtotime($row['time']))
    ];
}

echo json_encode([
    'schedule' => $sData['schedule'],
    'time_slot' => $time_slot,
    'minTime' => $minTime,
    'maxTime' => $maxTime,
    'busy' => $busy
]);
?>
