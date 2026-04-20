<?php
session_start();
if (!isset($_SESSION["name"])) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../includes/notifications.php");
include_once("../../includes/utils.php");

// Flatpickr Assets
$flatpickr_css = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">';
$flatpickr_js = '<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>';

// Get client_id
$email = $_SESSION["email"];
$client_id = 0;
$stmt = mysqli_prepare($con, "SELECT client_id FROM clients WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if($row = mysqli_fetch_assoc($res)) {
    $client_id = $row['client_id'];
}
mysqli_stmt_close($stmt);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = $_POST['service'];
    $staff_id = $_POST['staff_id'];
    $datetime = $_POST['datetime'];
    $date = date('Y-m-d', strtotime($datetime));
    $time = date('H:i:s', strtotime($datetime));

    // Get Staff Schedule
    $sq = mysqli_query($con, "SELECT name, schedule, time_slot FROM staff WHERE staff_id = $staff_id");
    $sRow = mysqli_fetch_assoc($sq);
    $staffName = $sRow['name'];
    $staffSchedule = $sRow['schedule'];
    $staffSlot = $sRow['time_slot'];

    // Check Schedule availability
    $checkResult = checkStaffAvailability($datetime, $staffSchedule, $staffSlot);

    // Check for past dates
    if(strtotime($datetime) < time()) {
        $error = "You cannot book an appointment in the past. Please select a future date and time.";
    } else if ($checkResult === 'day_off') {
        $error = "<strong>$staffName</strong> does not work on " . date('l', strtotime($datetime)) . "s. Their work days are: $staffSchedule";
    } else if ($checkResult === 'time_off') {
        $error = "<strong>$staffName</strong> is off-duty at the requested time. Their shift today is: $staffSlot";
    } else {
        // Check for conflicts
        $check = mysqli_prepare($con, "SELECT appointment_id FROM appointments WHERE staff_id = ? AND date = ? AND time = ? AND status != 'cancelled'");
        mysqli_stmt_bind_param($check, "iss", $staff_id, $date, $time);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        $conflict = mysqli_stmt_num_rows($check);
        mysqli_stmt_close($check);

        if($conflict > 0) {
            $error = "This stylist is already booked at the selected time. Please choose another slot.";
        } else {
            $ins = mysqli_prepare($con, "INSERT INTO appointments (client_id, staff_id, date, time, service, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($ins, "iisss", $client_id, $staff_id, $date, $time, $service);
            $success = mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            if($success) {
                // Notify Customer
                sendNotification($con, $email, "Your appointment for $service on $date at $time has been booked!", 'email', $client_id);
                
                // Notify Staff (Get staff user_id and email)
                $sq = mysqli_query($con, "SELECT u.email, u.id as user_id FROM staff s JOIN users u ON s.user_id = u.id WHERE s.staff_id = $staff_id");
                if($sr = mysqli_fetch_assoc($sq)) {
                    sendNotification($con, $sr['email'], "New Appointment Assigned: $service with {$_SESSION['name']} on $date at $time.", 'email', $sr['user_id']);
                }
            }
        }
    }
}
include_once("../../header.php");
echo $flatpickr_css;
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>Book Appointment</h3>
                <p class="text-muted mb-0">Select your service and preferred time.</p>
            </div>
        </div>

        <div class="glass-card">
            <?php if(isset($success) && $success) echo '<div class="alert alert-success">Appointment Booked Successfully!</div>'; ?>
            <?php if(isset($error)) echo '<div class="alert alert-danger">'.$error.'</div>'; ?>
            <?php if($client_id == 0) echo '<div class="alert alert-warning">Please setup your client profile fully first.</div>'; ?>
            
            <form action="" method="post">
                <div class="mb-3">
                    <label class="form-label">Select Service</label>
                    <select name="service" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                        <option value="Hair Styling">Hair Styling - $50</option>
                        <option value="Manicure">Manicure - $30</option>
                        <option value="Pedicure">Pedicure - $40</option>
                        <option value="Facial">Facial - $60</option>
                        <option value="Haircut">Haircut - $35</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Preferred Staff</label>
                    <select name="staff_id" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                        <?php
                        $st = mysqli_query($con, "SELECT staff_id, name, role, schedule, time_slot FROM staff");
                        if(mysqli_num_rows($st) > 0) {
                            while($s = mysqli_fetch_assoc($st)) {
                                $dispDetails = " (" . ($s['schedule'] ?? 'Active') . " | " . ($s['time_slot'] ?? 'All Day') . ")";
                                echo "<option value='{$s['staff_id']}'>{$s['name']} - " . ucfirst($s['role']) . $dispDetails . "</option>";
                            }
                        } else {
                            echo "<option value=''>No staff available</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Preferred Date & Time</label>
                    <input type="text" id="datePicker" name="datetime" class="form-control" placeholder="Select Date & Time..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required readonly>
                    <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i> Grayed out dates/times are unavailable or already booked.</small>
                </div>
                <button type="submit" class="btn-gold mt-3">Confirm Booking</button>
            </form>
        </div>
    </div>
</div>
<?php 
echo $flatpickr_js;
include_once("../../footer.php"); 
?>

<script>
let fp;
const daysMap = { 'Sun': 0, 'Mon': 1, 'Tue': 2, 'Wed': 3, 'Thu': 4, 'Fri': 5, 'Sat': 6 };

function initPicker(staffId) {
    if(!staffId) return;
    
    fetch(`get_availability.php?staff_id=${staffId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) return;
            
            if(fp) fp.destroy();
            
            // Map busy slots to Date objects for Flatpickr
            const disabledDates = data.busy.map(slot => {
                return new Date(slot.date + "T" + slot.time);
            });
            
            fp = flatpickr("#datePicker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                minTime: data.minTime,
                maxTime: data.maxTime,
                time_24hr: false,
                minuteIncrement: 30,
                theme: "dark",
                disable: [
                    // Function to disable specific days based on "schedule"
                    function(date) {
                        const day = date.getDay();
                        const sched = data.schedule;
                        
                        if(sched.toLowerCase().includes('every day')) return false;
                        
                        if(sched.includes('-')) {
                             const range = sched.split('-');
                             const start = daysMap[range[0].trim()];
                             const end = daysMap[range[1].trim()];
                             
                             // Handle standard range
                             if(start <= end) {
                                 return !(day >= start && day <= end);
                             } else {
                                 // Wrap around (e.g. Sat-Tue)
                                 return !(day >= start || day <= end);
                             }
                        }
                        
                        // Single day
                        return day !== daysMap[sched.trim()];
                    },
                    // Disable busy slots
                    ...disabledDates
                ],
                onChange: function(selectedDates, dateStr, instance) {
                    // Optional: could do a final conflict check or client-side feedback
                }
            });
        });
}

// Watch for stylist selection
document.querySelector('select[name="staff_id"]').addEventListener('change', (e) => {
    initPicker(e.target.value);
    document.getElementById('datePicker').value = ""; // Clear previous selection
});

// Initial boot
const startStaff = document.querySelector('select[name="staff_id"]').value;
if(startStaff) initPicker(startStaff);
</script>
