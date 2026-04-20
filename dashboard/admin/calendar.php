<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../header.php");

// Fetch appointments for FullCalendar
$appointments = [];
$res = mysqli_query($con, "SELECT a.*, c.name as client_name, s.name as staff_name 
                           FROM appointments a 
                           JOIN clients c ON a.client_id = c.client_id 
                           LEFT JOIN staff s ON a.staff_id = s.staff_id");
while($row = mysqli_fetch_assoc($res)) {
    $formattedTime = date('h:i A', strtotime($row['time']));
    $statusColor = strtolower($row['status']) == 'completed' ? '#198754' : (strtolower($row['status']) == 'cancelled' ? '#dc3545' : '#ffc107');
    $statusClass = strtolower($row['status']) == 'completed' ? 'event-completed' : (strtolower($row['status']) == 'cancelled' ? 'event-cancelled' : 'event-pending');
    $staffDisp = $row['staff_name'] ? htmlspecialchars($row['staff_name']) : 'Removed Stylist';
    
    $appointments[] = [
        'title' => $formattedTime . " - " . $row['service'],
        'start' => $row['date'] . 'T' . $row['time'],
        'description' => "<div class='text-start lh-lg'>
                            <div class='mb-2 border-bottom border-secondary pb-2'><i class='fas fa-user text-gold me-2'></i> <b>Client:</b> " . htmlspecialchars($row['client_name']) . "</div>
                            <div class='mb-2 border-bottom border-secondary pb-2'><i class='fas fa-cut text-gold me-2'></i> <b>Stylist:</b> " . $staffDisp . "</div>
                            <div><i class='fas fa-info-circle text-gold me-2'></i> <b>Status:</b> <span style='color: {$statusColor}'>" . ucfirst($row['status']) . "</span></div>
                          </div>",
        'className' => $statusClass,
        'textColor' => '#ffffff'
    ];
}
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3><i class="fas fa-calendar-alt text-gold me-2"></i> Visual Scheduling</h3>
                <p class="text-muted mb-0">Manage and view all salon appointments interactively on the master calendar.</p>
            </div>
            <div>
                <a href="appointments.php" class="btn-gold"><i class="fas fa-list me-2"></i>Switch to List View</a>
            </div>
        </div>

        <div class="glass-card mb-4 mt-2 px-4 py-3 d-flex justify-content-center gap-4 flex-wrap mx-auto shadow-lg" style="max-width: fit-content; border-radius: 50px; border: 1px solid rgba(218, 165, 32, 0.3);">
            <span class="badge rounded-pill px-4 py-2 fs-6 fw-bold shadow-sm" style="background: linear-gradient(135deg, #b8860b, #d4a017);"><i class="fas fa-clock me-2"></i>Pending</span>
            <span class="badge rounded-pill px-4 py-2 fs-6 fw-bold shadow-sm" style="background: linear-gradient(135deg, #146c43, #198754);"><i class="fas fa-check-circle me-2"></i>Completed</span>
            <span class="badge rounded-pill px-4 py-2 fs-6 fw-bold shadow-sm" style="background: linear-gradient(135deg, #8b0000, #b22222);"><i class="fas fa-times-circle me-2"></i>Cancelled</span>
        </div>

        <div class="glass-card calendar-wrapper shadow-lg p-4 mx-auto" style="max-width: 1300px; border-radius: 25px;">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 750,
        expandRows: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        themeSystem: 'bootstrap5',
        events: <?php echo json_encode($appointments); ?>,
        eventClick: function(info) {
            Swal.fire({
                title: "<h4 class='text-gold mb-0'>" + info.event.title + "</h4>",
                html: info.event.extendedProps.description,
                background: '#1a1a1a',
                color: '#fff',
                confirmButtonColor: '#daa520',
                customClass: {
                    popup: 'border border-secondary'
                }
            });
        }
    });
    calendar.render();
});
</script>

<style>
/* FullCalendar Premium Dark Mode Integration & Enhancements */
.fc { color: #f8f9fa; font-family: 'Inter', sans-serif; }
.fc-theme-bootstrap5 a { color: #f8f9fa; text-decoration: none; transition: color 0.3s; }
.fc-theme-bootstrap5 a:hover { color: #daa520; }
.fc-col-header-cell-cushion { color: #daa520 !important; font-weight: 700; padding: 15px 5px !important; text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.85em; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
.fc-list-day-text, .fc-list-day-side-text { color: #daa520; }
.fc-daygrid-day-number { color: #e9ecef; font-weight: 600; padding: 10px !important; margin: 5px; font-size: 1.1em; text-shadow: 0 1px 3px rgba(0,0,0,0.4); z-index: 2; position: relative; transition: color 0.2s; }
.fc-daygrid-day-number:hover { color: #daa520; }
.fc-toolbar-title { color: transparent !important; background-clip: text; -webkit-background-clip: text; background-image: linear-gradient(90deg, #daa520, #ffdf00); font-weight: 800; letter-spacing: 2px; font-size: 1.8rem !important; text-transform: uppercase; text-shadow: 0 4px 6px rgba(0,0,0,0.2) !important; }
.fc-button-primary { background: linear-gradient(135deg, #daa520, #b8860b) !important; border: none !important; color: #111 !important; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; border-radius: 12px !important; box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3) !important; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important; padding: 10px 20px !important; overflow: hidden; }
.fc-button-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(218, 165, 32, 0.5) !important; filter: brightness(1.1); color: #000 !important; }
.fc-button-active { background: linear-gradient(135deg, #b8860b, #8b6508) !important; box-shadow: inset 0 4px 8px rgba(0,0,0,0.4) !important; color: #fff !important; }
.fc-daygrid-event, .fc-timegrid-event, .fc-v-event { border: none !important; border-radius: 8px; padding: 5px 10px; margin: 3px 6px !important; font-size: 0.85em; font-weight: 600; cursor: pointer; transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 3px 6px rgba(0,0,0,0.3); text-shadow: 0 1px 2px rgba(0,0,0,0.4); }
.fc-daygrid-event:hover, .fc-timegrid-event:hover, .fc-v-event:hover { transform: scale(1.05) translateY(-2px); z-index: 10 !important; box-shadow: 0 6px 12px rgba(0,0,0,0.5) !important; filter: brightness(1.1); }

/* Event Gradients */
.fc-event.event-completed { background: linear-gradient(135deg, #146c43, #198754) !important; color: #fff !important; }
.fc-event.event-pending { background: linear-gradient(135deg, #b8860b, #d4a017) !important; color: #fff !important; }
.fc-event.event-cancelled { background: linear-gradient(135deg, #8b0000, #b22222) !important; color: #fff !important; }

.fc-theme-bootstrap5 td, .fc-theme-bootstrap5 th { border-color: rgba(255, 255, 255, 0.06) !important; }
.fc-scrollgrid { border: 1px solid rgba(255, 255, 255, 0.1) !important; border-radius: 15px; overflow: hidden; box-shadow: inset 0 0 30px rgba(0,0,0,0.6); }
.fc-day-today { background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(218, 165, 32, 0.02)) !important; position: relative; border: 1px solid rgba(218, 165, 32, 0.3) !important; }
.fc-day-today::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; box-shadow: inset 0 0 20px rgba(218, 165, 32, 0.15); pointer-events: none; }
.fc-daygrid-day { transition: background 0.3s ease; }
.fc-daygrid-day:hover { background: rgba(255, 255, 255, 0.03) !important; }
.calendar-wrapper { border-top: 4px solid #daa520; background: linear-gradient(180deg, rgba(25,25,25,0.85) 0%, rgba(10,10,10,0.95) 100%); backdrop-filter: blur(15px); border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.7); }
.fc .fc-popover { background: rgba(30, 30, 30, 0.95) !important; backdrop-filter: blur(10px); border: 1px solid rgba(218, 165, 32, 0.3) !important; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
.fc .fc-popover-header { background: rgba(0, 0, 0, 0.5) !important; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 12px 15px; border-radius: 12px 12px 0 0; }
.fc .fc-popover-title { color: #daa520; font-weight: 800; letter-spacing: 1px; }
.fc .fc-popover-close { color: #fff; opacity: 0.7; transition: opacity 0.2s; }
.fc .fc-popover-close:hover { opacity: 1; color: #dc3545; }

/* TimeGrid (Week/Day View) Specific Enhancements */
.fc-timegrid-slot-label-cushion { color: #b0b3b8 !important; font-weight: 500; font-size: 0.9em; }
.fc-timegrid-axis-cushion { color: #daa520 !important; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.fc-timegrid-slot-minor { border-top-style: dashed !important; border-top-color: rgba(255,255,255,0.03) !important; }
.fc-theme-bootstrap5 .fc-timegrid-slot { border-bottom-color: rgba(255,255,255,0.06) !important; }
.fc-timegrid-now-indicator-line { border-color: #ff3366 !important; border-width: 2px !important; box-shadow: 0 0 10px rgba(255, 51, 102, 0.8); z-index: 4; }
.fc-timegrid-now-indicator-arrow { border-width: 6px !important; border-color: #ff3366 !important; border-bottom-color: transparent !important; border-top-color: transparent !important; }
.fc-timegrid-col.fc-day-today { background-color: rgba(218, 165, 32, 0.03) !important; box-shadow: inset 0 0 15px rgba(218, 165, 32, 0.05); }

/* Remove previous hardcoded backgrounds since we put them in the global .fc-event rule */
.fc-timegrid-col-events { margin: 0 4px !important; }
.fc-timegrid-divider { padding: 2px !important; background: linear-gradient(90deg, transparent, rgba(218, 165, 32, 0.2), transparent) !important; border: none !important; }
</style>

<?php include_once("../../footer.php"); ?>
