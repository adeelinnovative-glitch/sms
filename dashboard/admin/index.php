<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

// --- PERSISTENT DISMISSAL HANDLER (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dismiss_activity') {
    if (!isset($_SESSION['dismissed_activities'])) {
        $_SESSION['dismissed_activities'] = [];
    }
    $newIds = json_decode($_POST['ids'], true) ?: [];
    $_SESSION['dismissed_activities'] = array_unique(array_merge($_SESSION['dismissed_activities'], $newIds));
    exit(json_encode(['status' => 'success']));
}

// --- ANALYTICS CALCULATIONS ---
// ... (rest of calculations remain same)
$resToday = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE date = CURDATE()");
$appToday = mysqli_fetch_assoc($resToday)['count'];

$resMonth = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$appMonth = mysqli_fetch_assoc($resMonth)['count'];

$resPrevMonth = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$appPrevMonth = mysqli_fetch_assoc($resPrevMonth)['count'];
$appTrend = ($appPrevMonth > 0) ? round((($appMonth - $appPrevMonth) / $appPrevMonth) * 100) : 100;

$resRev = mysqli_query($con, "SELECT COALESCE(SUM(amount), 0) as total FROM billing WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$revenue = mysqli_fetch_assoc($resRev)['total'];

$resRevPrev = mysqli_query($con, "SELECT COALESCE(SUM(amount), 0) as total FROM billing WHERE MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$revPrev = mysqli_fetch_assoc($resRevPrev)['total'];
$revTrend = ($revPrev > 0) ? round((($revenue - $revPrev) / $revPrev) * 100) : 100;

$resStock = mysqli_query($con, "SELECT COUNT(*) as count FROM inventory WHERE quantity <= min_stock_level");
$lowStock = mysqli_fetch_assoc($resStock)['count'];

$resClients = mysqli_query($con, "SELECT COUNT(*) as count FROM clients");
$clientCount = mysqli_fetch_assoc($resClients)['count'];

include_once("../../header.php");
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Intelligence Overview</h3>
                <p class="text-muted small">Real-time business performance & salon monitoring.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="reports.php" class="btn btn-outline-gold btn-sm px-3">Business Intel</a>
                <a href="inventory.php" class="btn-gold btn-sm px-3">Inventory Portal</a>
            </div>
        </div>

        <!-- Metric Grid - Row 1 -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <a href="appointments.php" class="text-decoration-none">
                    <div class="glass-card hover-lift" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.15), transparent); cursor: pointer;">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted small text-uppercase">Appointments</h6>
                            <span class="badge bg-<?= $appTrend >= 0 ? 'success' : 'danger' ?>-transparent text-<?= $appTrend >= 0 ? 'success' : 'danger' ?> small">
                                <?= $appTrend >= 0 ? '↑' : '↓' ?> <?= abs($appTrend) ?>%
                            </span>
                        </div>
                        <h3 class="mb-1 text-white"><?= $appMonth ?></h3>
                        <p class="text-muted small mb-0"><?= $appToday ?> priority today</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="reports.php" class="text-decoration-none">
                    <div class="glass-card hover-lift" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), transparent); cursor: pointer;">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted small text-uppercase">Revenue (MTD)</h6>
                            <span class="badge bg-<?= $revTrend >= 0 ? 'success' : 'danger' ?>-transparent text-<?= $revTrend >= 0 ? 'success' : 'danger' ?> small">
                                <?= $revTrend >= 0 ? '↑' : '↓' ?> <?= abs($revTrend) ?>%
                            </span>
                        </div>
                        <h3 class="mb-1 text-white">$<?= number_format($revenue, 2) ?></h3>
                        <p class="text-muted small mb-0">Monthly target path</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="inventory.php" class="text-decoration-none">
                    <div class="glass-card hover-lift" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), transparent); cursor: pointer;">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted small text-uppercase">Stock Health</h6>
                            <span class="badge bg-<?= $lowStock > 0 ? 'warning' : 'success' ?>-transparent text-<?= $lowStock > 0 ? 'warning' : 'success' ?> small">
                                <?= $lowStock ?> Alerts
                            </span>
                        </div>
                        <h3 class="mb-1 text-white"><?= $lowStock ?> <span class="fs-6 text-muted">Items</span></h3>
                        <p class="text-muted small mb-0">Procurement required</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Metric Grid - Row 2 -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <a href="clients.php" class="text-decoration-none">
                    <div class="glass-card hover-lift" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.15), transparent); cursor: pointer;">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted small text-uppercase">Customer Base</h6>
                            <span class="text-gold small">✦ Elite VIP</span>
                        </div>
                        <h3 class="mb-1 text-white"><?= $clientCount ?></h3>
                        <p class="text-muted small mb-0">Active salon patrons</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="users.php" class="text-decoration-none">
                    <div class="glass-card hover-lift" style="background: linear-gradient(135deg, rgba(111, 66, 193, 0.2), transparent); cursor: pointer;">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted small text-uppercase">Digital Accounts</h6>
                            <?php
                            $resUsr = mysqli_query($con, "SELECT COUNT(*) as count FROM users");
                            $uCount = mysqli_fetch_assoc($resUsr)['count'];
                            ?>
                            <span class="text-gold small">☁ Online</span>
                        </div>
                        <h3 class="mb-1 text-white"><?= $uCount ?></h3>
                        <p class="text-muted small mb-0">Registered system users</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="staff.php" class="text-decoration-none">
                    <div class="glass-card hover-lift" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), transparent); cursor: pointer;">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted small text-uppercase">Active Staff</h6>
                            <?php
                            $resSt = mysqli_query($con, "SELECT COUNT(*) as count FROM staff");
                            $stCount = mysqli_fetch_assoc($resSt)['count'];
                            ?>
                            <span class="text-gold small">⚒ Artisans</span>
                        </div>
                        <h3 class="mb-1 text-white"><?= $stCount ?></h3>
                        <p class="text-muted small mb-0">Professional team size</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Timeline Section -->
            <div class="col-md-8">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-gold mb-0">Recent Activity</h5>
                        <button class="btn btn-link text-rose btn-sm p-0" id="clearFeedBtn">Refresh Feed</button>
                    </div>
                    
                    <div class="timeline" id="adminTimeline">
                        <?php
                        $dismissed = $_SESSION['dismissed_activities'] ?? [];
                        $blacklistStr = count($dismissed) > 0 ? "'" . implode("','", $dismissed) . "'" : "''";

                        $query = "(SELECT 'booking' as type, CONCAT('app_', a.appointment_id) as source_id, c.name, a.service, a.date as event_date, a.time as event_time, 'bg-gold' as theme 
                                   FROM appointments a JOIN clients c ON a.client_id = c.client_id 
                                   HAVING source_id NOT IN ($blacklistStr))
                                  UNION
                                  (SELECT 'payment' as type, CONCAT('bill_', b.bill_id) as source_id, c.name, CONCAT('Payment of $', b.amount) as service, b.date as event_date, '00:00:00' as event_time, 'bg-success' as theme 
                                   FROM billing b JOIN clients c ON b.client_id = c.client_id 
                                   HAVING source_id NOT IN ($blacklistStr))
                                  ORDER BY event_date DESC, event_time DESC LIMIT 6";
                        
                        $resAct = mysqli_query($con, $query);
                        
                        if(mysqli_num_rows($resAct) > 0) {
                            while($act = mysqli_fetch_assoc($resAct)) {
                                $isBooking = $act['type'] == 'booking';
                                $icon = $isBooking ? '✦' : '⭐';
                                $title = $isBooking ? "Service Appointed" : "Transaction Finalized";
                                $desc = $isBooking ? "{$act['service']} for {$act['name']}" : "Received {$act['service']} from {$act['name']}";
                            ?>
                            <div class="timeline-item d-flex gap-3 mb-4 position-relative" data-activity-id="<?= $act['source_id'] ?>">
                                <div class="timeline-dot <?= $act['theme'] ?> flex-shrink-0" style="width: 12px; height: 12px; border-radius: 50%; margin-top: 6px; z-index: 2;"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0 text-white"><?= $title ?></h6>
                                        <span class="text-muted small"><?= date('M d, H:i', strtotime($act['event_date'] . ' ' . $act['event_time'])) ?></span>
                                    </div>
                                    <p class="text-muted small mb-0"><?= $desc ?></p>
                                </div>
                            </div>
                            <?php } 
                        } else {
                            echo '<div class="text-center py-5"><p class="text-muted italic">No recent activity</p></div>';
                        } ?>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="col-md-4">
                <div class="glass-card mb-4" style="background: linear-gradient(135deg, rgba(212,175,55,0.1), transparent);">
                    <h6 class="text-gold small text-uppercase mb-3">Top Performer</h6>
                    <?php
                    $resTop = mysqli_query($con, "SELECT s.name, SUM(b.amount) as total FROM billing b JOIN appointments a ON b.appointment_id = a.appointment_id JOIN staff s ON a.staff_id = s.staff_id WHERE MONTH(b.date) = MONTH(CURDATE()) GROUP BY s.staff_id ORDER BY total DESC LIMIT 1");
                    $top = mysqli_fetch_assoc($resTop);
                    ?>
                    <h5 class="mb-1"><?= $top['name'] ?? 'Team' ?></h5>
                    <p class="text-muted small">$<?= number_format($top['total'] ?? 0, 2) ?> generated this month</p>
                </div>

                <div class="glass-card">
                    <h6 class="text-gold small text-uppercase mb-3">System Health</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Database Status</span>
                        <span class="text-success small">Connected</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Auto-Sync</span>
                        <span class="text-success small">Active</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">System Version</span>
                        <span class="text-gold small">v2.1 Gold Edition</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Persistent Clear Feed Logic
document.getElementById('clearFeedBtn').addEventListener('click', function() {
    const timeline = document.getElementById('adminTimeline');
    const items = timeline.querySelectorAll('.timeline-item');
    const idsToDismiss = Array.from(items).map(item => item.getAttribute('data-activity-id'));

    if(idsToDismiss.length === 0) return;

    // Send to server to persist dismissal
    const formData = new FormData();
    formData.append('action', 'dismiss_activity');
    formData.append('ids', JSON.stringify(idsToDismiss));

    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            timeline.style.opacity = '0';
            setTimeout(() => {
                timeline.innerHTML = '<div class="text-center py-5"><p class="text-muted italic">No recent activity</p></div>';
                timeline.style.opacity = '1';
            }, 300);
        }
    });
});
</script></div>
</div>

<style>
.timeline-item::after {
    content: '';
    position: absolute;
    left: 5px;
    top: 18px;
    bottom: -26px;
    width: 2px;
    background: rgba(255,255,255,0.05);
    z-index: 1;
}
.timeline-item:last-child::after { display: none; }
.bg-gold-transparent { background: rgba(212, 175, 55, 0.15); }
.bg-success-transparent { background: rgba(30, 255, 100, 0.1); }
.bg-danger-transparent { background: rgba(255, 30, 100, 0.1); }
.bg-warning-transparent { background: rgba(255, 180, 0, 0.1); }
</style>