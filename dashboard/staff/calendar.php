<?php
include_once("../../includes/auth_check.php");
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'stylist' && $_SESSION["role"] !== 'beautician' && $_SESSION["role"] !== 'nail technician')) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../header.php");

// Get Staff Details linked to this user
$u_id = $_SESSION['id'];
$q_staff = mysqli_query($con, "SELECT staff_id FROM staff WHERE user_id = $u_id");
$sData = mysqli_fetch_assoc($q_staff);
$sid = $sData['staff_id'] ?? 0;

// Fetch appointments for this specific staff member
$appointments = [];
$res = mysqli_query($con, "SELECT a.*, c.name as client_name, b.bill_id 
                           FROM appointments a 
                           JOIN clients c ON a.client_id = c.client_id 
                           LEFT JOIN billing b ON a.appointment_id = b.appointment_id
                           WHERE a.staff_id = $sid");

while($row = mysqli_fetch_assoc($res)) {
    $color = '#daa520'; // Default (Pending)
    $status_label = "Pending";
    
    if($row['status'] == 'completed') {
        if($row['bill_id']) {
            $color = '#28a745'; // Paid
            $status_label = "Payment Received";
        } else {
            $color = '#17a2b8'; // Unpaid
            $status_label = "Finished (Unpaid)";
        }
    } elseif($row['status'] == 'cancelled') {
        $color = '#dc3545';
        $status_label = "Cancelled";
    }

    $appointments[] = [
        'title' => $row['service'] . " - " . $row['client_name'],
        'start' => $row['date'] . 'T' . $row['time'],
        'description' => "Status: " . $status_label,
        'color' => $color,
        'url' => 'appointments.php' // Link back to the registry for actions
    ];
}
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>My Visual Schedule</h3>
                <p class="text-muted mb-0">At-a-glance view of your bookings and payment status.</p>
            </div>
            <div>
                <a href="appointments.php" class="btn-gold">Back to List</a>
            </div>
        </div>

        <div class="glass-card">
            <div id='calendar'></div>
        </div>

        <div class="mt-4 d-flex gap-4">
            <div class="small text-muted"><span class="badge" style="background: #daa520; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span> Upcoming / Pending</div>
            <div class="small text-muted"><span class="badge" style="background: #17a2b8; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span> Finished (Unpaid)</div>
            <div class="small text-muted"><span class="badge" style="background: #28a745; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span> Payment Received</div>
            <div class="small text-muted"><span class="badge" style="background: #dc3545; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span> Cancelled</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        themeSystem: 'bootstrap5',
        events: <?php echo json_encode($appointments); ?>,
        eventClick: function(info) {
            if (info.item.url) {
                // Allow default navigation to appointments page
            } else {
                Swal.fire({
                    title: info.event.title,
                    text: info.event.extendedProps.description,
                    icon: 'info'
                });
            }
        }
    });
    calendar.render();
});
</script>

<style>
/* FullCalendar Dark Mode Integration */
.fc { color: #fff; background: transparent; }
.fc-theme-bootstrap5 a { color: #daa520; text-decoration: none; }
.fc-col-header-cell-cushion { color: #daa520; }
.fc-list-day-text, .fc-list-day-side-text { color: #daa520; }
.fc-daygrid-day-number { color: #fff; }
.fc-toolbar-title { color: #daa520; }
.fc-button-primary { background-color: #daa520 !important; border-color: #daa520 !important; color: #000 !important; }
.fc-button-active { background-color: #b8860b !important; }
.fc-daygrid-event { border: none !important; margin: 2px 5px !important; padding: 2px 5px !important; border-radius: 4px !important; }
.fc-event-title { font-weight: 500; font-size: 0.85rem; }
</style>

<?php include_once("../../footer.php"); ?>
