<?php
/**
 * Elegant Salon Utility Helpers
 * Handles staff schedule parsing and validation.
 */

/**
 * Checks if a requested date and time is within a staff member's schedule.
 * @param string $requestedDateTime The full datetime of the appointment.
 * @param string $daysPart The work days string (e.g. "Mon-Fri").
 * @param string $timesPart The time slot string (e.g. "8:00 am - 1:00 pm").
 * @return string 'valid', 'day_off', or 'time_off'
 */
function checkStaffAvailability($requestedDateTime, $daysPart, $timesPart) {
    if (empty($daysPart) || empty($timesPart)) return 'valid';

    // 1. Validate Day of the Week
    $requestedDay = date('D', strtotime($requestedDateTime)); // Mon, Tue, etc.
    $dayValid = false;

    if (stripos($daysPart, 'Every Day') !== false || stripos($daysPart, 'Mon-Sun') !== false) {
        $dayValid = true;
    } else if (strpos($daysPart, '-') !== false) {
        // Range like "Mon-Fri"
        $dayRange = explode('-', $daysPart);
        $startDay = trim($dayRange[0]);
        $endDay = trim($dayRange[1]);
        
        $daysOfWeek = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7];
        $startIdx = $daysOfWeek[$startDay] ?? 1;
        $endIdx = $daysOfWeek[$endDay] ?? 7;
        $currentIdx = $daysOfWeek[$requestedDay] ?? 0;

        if ($currentIdx >= $startIdx && $currentIdx <= $endIdx) {
            $dayValid = true;
        }
    } else {
        // Single day check
        if ($requestedDay === trim($daysPart)) $dayValid = true;
    }

    if (!$dayValid) return 'day_off';

    // 2. Validate Time (Shift Hours)
    // Expected format: "8:00 am - 1:00 pm"
    $timeRange = explode('-', $timesPart);
    if (count($timeRange) < 2) return 'valid';

    $reqTime = date('H:i:s', strtotime($requestedDateTime));
    $startTime = date('H:i:s', strtotime(trim($timeRange[0])));
    $endTime = date('H:i:s', strtotime(trim($timeRange[1])));

    // Shift check
    if ($reqTime >= $startTime && $reqTime <= $endTime) {
        return 'valid';
    }

    return 'time_off';
}
?>
