<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

// --- SUMMARY INTELLIGENCE ---

// 1. Total Revenue (All Time)
$resTotal = mysqli_query($con, "SELECT SUM(amount) as total FROM billing");
$totalRevenue = mysqli_fetch_assoc($resTotal)['total'];

// 2. Average Ticket Size
$resAvg = mysqli_query($con, "SELECT AVG(amount) as avg FROM billing");
$avgTicket = mysqli_fetch_assoc($resAvg)['avg'];

// 3. Service Success Rate (Completed vs Total)
$resComp = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE status='completed'");
$compCount = mysqli_fetch_assoc($resComp)['count'];
$resAll = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments");
$allCount = mysqli_fetch_assoc($resAll)['count'];
$successRate = ($allCount > 0) ? round(($compCount / $allCount) * 100) : 100;

// 4. Low Stock Criticality
$resCrit = mysqli_query($con, "SELECT COUNT(*) as count FROM inventory WHERE quantity <= (min_stock_level / 2)");
$critStock = mysqli_fetch_assoc($resCrit)['count'];

include_once("../../header.php");

/** 
 * CHART DATA PREP 
 */
// Weekly Trend
$weeklyLabels = []; $weeklyCounts = [];
$resWeekly = mysqli_query($con, "SELECT YEARWEEK(date, 1) as wk, COUNT(*) as count FROM appointments WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK) GROUP BY wk ORDER BY wk ASC");
while($row = mysqli_fetch_assoc($resWeekly)) {
    $weeklyLabels[] = "Week " . substr($row['wk'], 4);
    $weeklyCounts[] = $row['count'];
}

// Revenue Trend
$salesData = []; $salesLabels = [];
$resSales = mysqli_query($con, "SELECT date, SUM(amount) as total FROM billing WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY date ORDER BY date ASC");
while($row = mysqli_fetch_assoc($resSales)) {
    $salesLabels[] = date('D, M d', strtotime($row['date']));
    $salesData[] = $row['total'];
}

// Service Distribution
$serviceLabels = []; $serviceData = [];
$resServ = mysqli_query($con, "SELECT service, COUNT(*) as count FROM appointments GROUP BY service ORDER BY count DESC LIMIT 5");
while($row = mysqli_fetch_assoc($resServ)) {
    $serviceLabels[] = $row['service'];
    $serviceData[] = $row['count'];
}

// --- NEW OPERATIONAL HEATMAP DATA ---

// 1. Weekly Distribution (Radar)
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$dayCounts = array_fill(0, 7, 0);
$resDays = mysqli_query($con, "SELECT DAYNAME(date) as dname, COUNT(*) as count FROM appointments GROUP BY dname");
while($row = mysqli_fetch_assoc($resDays)) {
    $idx = array_search($row['dname'], $days);
    if($idx !== false) $dayCounts[$idx] = $row['count'];
}

// 2. Hourly Intensity (Line/Area)
$hours = []; $hourCounts = [];
for($i=8; $i<=20; $i++) { $hours[] = $i . ":00"; }
$hourCounts = array_fill(0, count($hours), 0);
$resHours = mysqli_query($con, "SELECT HOUR(time) as hr, COUNT(*) as count FROM appointments GROUP BY hr HAVING hr BETWEEN 8 AND 20");
while($row = mysqli_fetch_assoc($resHours)) {
    $idx = $row['hr'] - 8;
    if(isset($hourCounts[$idx])) $hourCounts[$idx] = $row['count'];
}
// --- PERSONNEL & LOYALTY DATA ---

// 1. Client Retention (New vs Returning)
$resReten = mysqli_query($con, "SELECT SUM(CASE WHEN app_count = 1 THEN 1 ELSE 0 END) as new_g, SUM(CASE WHEN app_count > 1 THEN 1 ELSE 0 END) as ret_g FROM (SELECT COUNT(*) as app_count FROM appointments GROUP BY client_id) as sub");
$retData = mysqli_fetch_assoc($resReten);
$newGuests = $retData['new_g'] ?? 0;
$returningGuests = $retData['ret_g'] ?? 0;

// 2. Staff Leaderboard
$staffLeaders = [];
$resLeaders = mysqli_query($con, "SELECT s.name, COUNT(a.appointment_id) as total_bookings, SUM(IFNULL(b.amount, 0)) as total_rev FROM staff s LEFT JOIN appointments a ON s.staff_id = a.staff_id LEFT JOIN billing b ON a.appointment_id = b.appointment_id GROUP BY s.staff_id ORDER BY total_rev DESC LIMIT 3");
while($row = mysqli_fetch_assoc($resLeaders)) {
    $staffLeaders[] = $row;
}
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center mb-5">
            <div>
                <h3 class="mb-1 text-gold">Executive Intelligence</h3>
                <p class="text-muted small">Comprehensive financial & operational performance suite.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-gold btn-sm px-4">Export PDF</button>
        </div>

        <!-- EXECUTIVE SUMMARY ROW -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="glass-card text-center border-bottom border-gold border-3" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), transparent);">
                    <p class="text-muted small text-uppercase mb-1">Total Gross Revenue</p>
                    <h2 class="text-gold mb-0">$<?= number_format($totalRevenue, 0) ?></h2>
                    <small class="text-success small fw-bold">Projected ↑</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card text-center border-bottom border-gold border-3" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.15), transparent);">
                    <p class="text-muted small text-uppercase mb-1">Average Ticket</p>
                    <h2 class="text-white mb-0">$<?= number_format($avgTicket, 0) ?></h2>
                    <small class="text-muted small">Per Guest</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card text-center border-bottom border-gold border-3" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.15), transparent);">
                    <p class="text-muted small text-uppercase mb-1">Success Rate</p>
                    <h2 class="text-white mb-0"><?= $successRate ?>%</h2>
                    <small class="text-muted small">Appointment Completion</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card text-center border-bottom border-<?= $critStock > 0 ? 'rose' : 'gold' ?> border-3" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), transparent);">
                    <p class="text-muted small text-uppercase mb-1">Critial Alerts</p>
                    <h2 class="<?= $critStock > 0 ? 'text-rose' : 'text-white' ?> mb-0"><?= $critStock ?></h2>
                    <small class="text-muted small">Immediate Action</small>
                </div>
            </div>
        </div>

        <!-- SECTION 1: MARKET DYNAMICS -->
        <div class="mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width: 40px; height: 1px; background: var(--accent-gold); opacity: 0.3;"></div>
                <h5 class="text-gold small text-uppercase mb-0" style="letter-spacing: 2px;">Market Dynamics</h5>
                <div class="flex-grow-1" style="height: 1px; background: var(--accent-gold); opacity: 0.1;"></div>
            </div>
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="glass-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="text-gold mb-0">Financial Growth (7D Trajectory)</h5>
                            <span class="text-muted small">Real-time revenue curve</span>
                        </div>
                        <canvas id="revenueChart" height="300"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card h-100">
                        <h5 class="text-gold mb-4">Service Popularity</h5>
                        <canvas id="serviceChart" height="280"></canvas>
                        <div class="mt-4 text-center">
                            <p class="text-muted small mb-0">Top 5 Services by Volume</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: TEAM INTELLIGENCE -->
        <div class="mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width: 40px; height: 1px; background: var(--accent-gold); opacity: 0.3;"></div>
                <h5 class="text-gold small text-uppercase mb-0" style="letter-spacing: 2px;">Team Intelligence</h5>
                <div class="flex-grow-1" style="height: 1px; background: var(--accent-gold); opacity: 0.1;"></div>
            </div>
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="glass-card h-100">
                        <h5 class="text-gold mb-4">Artisan Hall of Fame</h5>
                        <div class="d-flex flex-column gap-3">
                            <?php 
                            $rankThemes = ['#D4AF37', '#17a2b8', '#B76E79'];
                            foreach($staffLeaders as $index => $leader): 
                                $theme = $rankThemes[$index] ?? '#555';
                                $bookingMax = max(1, $staffLeaders[0]['total_bookings']);
                                $productivity = round(($leader['total_bookings'] / $bookingMax) * 100);
                            ?>
                            <div class="p-3" style="background: rgba(255,255,255,0.03); border-radius: 12px; border-left: 4px solid <?= $theme ?>;">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="d-flex align-items-center justify-content-center h5 mb-0" style="width: 40px; height: 40px; background: <?= $theme ?>; color: #000; border-radius: 50%; font-weight: 800;">
                                            <?= $index + 1 ?>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <h6 class="mb-1 text-white"><?= $leader['name'] ?></h6>
                                        <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05); width: 140px;">
                                            <div class="progress-bar" style="width: <?= $productivity ?>%; background: <?= $theme ?>;"></div>
                                        </div>
                                    </div>
                                    <div class="col text-center">
                                        <span class="text-muted small d-block">Activity</span>
                                        <span class="text-white small fw-bold"><?= $leader['total_bookings'] ?> Bookings</span>
                                    </div>
                                    <div class="col text-end">
                                        <span class="text-muted small d-block">Revenue</span>
                                        <span class="text-gold fw-bold" style="text-shadow: 0 0 10px rgba(212,175,55,0.3);">$<?= number_format($leader['total_rev'], 0) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card h-100 position-relative">
                        <h5 class="text-gold mb-4">Guest Loyalty Index</h5>
                        <div class="position-relative">
                            <canvas id="retentionChart" height="230"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle text-center" style="margin-top: 15px;">
                                <?php 
                                $totalG = max(1, ($newGuests + $returningGuests));
                                $retRate = round(($returningGuests / $totalG) * 100);
                                ?>
                                <h3 class="text-gold mb-0"><?= $retRate ?>%</h3>
                                <p class="text-muted small mb-0">Retention</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top border-white border-opacity-10 d-flex justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: #D4AF37; box-shadow: 0 0 8px #D4AF37;"></div>
                                <span class="text-muted small">Returning</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: #B76E79; box-shadow: 0 0 8px #B76E79;"></div>
                                <span class="text-muted small">First-Time</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: OPERATIONAL TEMPO -->
        <div class="mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width: 40px; height: 1px; background: var(--accent-gold); opacity: 0.3;"></div>
                <h5 class="text-gold small text-uppercase mb-0" style="letter-spacing: 2px;">Operational Tempo</h5>
                <div class="flex-grow-1" style="height: 1px; background: var(--accent-gold); opacity: 0.1;"></div>
            </div>
            <div class="row g-4">
                <div class="col-md-7">
                    <div class="glass-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="text-gold mb-0">Hourly Peak Intensity</h5>
                            <span class="badge bg-gold-transparent text-gold">8AM - 8PM</span>
                        </div>
                        <canvas id="hourlyIntensityChart" height="300"></canvas>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="glass-card h-100">
                        <h5 class="text-gold mb-4">Weekly Occupancy Heat</h5>
                        <div class="px-4">
                            <canvas id="weeklyRadarChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4: TACTICAL LOGISTICS -->
        <div class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width: 40px; height: 1px; background: var(--accent-gold); opacity: 0.3;"></div>
                <h5 class="text-gold small text-uppercase mb-0" style="letter-spacing: 2px;">Tactical Logistics</h5>
                <div class="flex-grow-1" style="height: 1px; background: var(--accent-gold); opacity: 0.1;"></div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="glass-card h-100">
                        <h5 class="text-gold mb-4">Appointment Velocity</h5>
                        <canvas id="bookingChart" height="220"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card h-100">
                        <h5 class="text-gold mb-4">Stock Health (Criticality)</h5>
                        <div class="d-flex flex-column gap-3">
                            <?php
                            $resInvDisp = mysqli_query($con, "SELECT product_name, quantity, min_stock_level FROM inventory ORDER BY (quantity/min_stock_level) ASC LIMIT 5");
                            while($item = mysqli_fetch_assoc($resInvDisp)) {
                                $percent = min(100, round(($item['quantity'] / ($item['min_stock_level'] * 2)) * 100));
                                $color = ($item['quantity'] <= $item['min_stock_level']) ? 'var(--accent-rose)' : 'var(--accent-gold)';
                            ?>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-light small"><?= $item['product_name'] ?></span>
                                    <span class="text-muted small"><?= $item['quantity'] ?> units</span>
                                </div>
                                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.05);">
                                    <div class="progress-bar" style="width: <?= $percent ?>%; background: <?= $color ?>;"></div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    const ctxServ = document.getElementById('serviceChart').getContext('2d');
    const ctxBook = document.getElementById('bookingChart').getContext('2d');
    const ctxRadar = document.getElementById('weeklyRadarChart').getContext('2d');
    const ctxHour = document.getElementById('hourlyIntensityChart').getContext('2d');
    const ctxReten = document.getElementById('retentionChart').getContext('2d');

    // Premium Gradients
    const goldGradient = ctxRev.createLinearGradient(0, 0, 0, 400);
    goldGradient.addColorStop(0, 'rgba(212, 175, 55, 0.4)');
    goldGradient.addColorStop(1, 'rgba(212, 175, 55, 0.05)');

    const roseGradient = ctxHour.createLinearGradient(0, 0, 0, 400);
    roseGradient.addColorStop(0, 'rgba(183, 110, 121, 0.4)');
    roseGradient.addColorStop(1, 'rgba(183, 110, 121, 0.05)');

    const barGradient = ctxBook.createLinearGradient(0, 0, 0, 400);
    barGradient.addColorStop(0, '#B76E79');
    barGradient.addColorStop(1, '#D4AF37');

    Chart.defaults.color = 'rgba(255,255,255,0.6)';
    Chart.defaults.font.family = "'Outfit', sans-serif";

    // 1. Revenue Area Chart
    new Chart(ctxRev, {
        type: 'line',
        data: {
            labels: <?= json_encode($salesLabels) ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?= json_encode($salesData) ?>,
                borderColor: '#D4AF37',
                borderWidth: 3,
                backgroundColor: goldGradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#D4AF37',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, border: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Service Pie Chart (Transforming for visual variety)
    new Chart(ctxServ, {
        type: 'pie',
        data: {
            labels: <?= json_encode($serviceLabels) ?>,
            datasets: [{
                data: <?= json_encode($serviceData) ?>,
                backgroundColor: ['#D4AF37', '#B76E79', '#17a2b8', '#555555', '#777777'],
                borderWidth: 2,
                borderColor: 'rgba(0,0,0,0.3)',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            plugins: { 
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } 
            }
        }
    });

    // 3. Weekly Radar Chart
    new Chart(ctxRadar, {
        type: 'radar',
        data: {
            labels: <?= json_encode($days) ?>,
            datasets: [{
                label: 'Daily Bookings',
                data: <?= json_encode($dayCounts) ?>,
                backgroundColor: 'rgba(212, 175, 55, 0.2)',
                borderColor: '#D4AF37',
                pointBackgroundColor: '#D4AF37',
                borderWidth: 2
            }]
        },
        options: {
            elements: { line: { tension: 0.3 } },
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    angleLines: { color: 'rgba(255,255,255,0.1)' },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    pointLabels: { color: 'rgba(255,255,255,0.8)', font: { size: 10 } },
                    ticks: { display: false }
                }
            }
        }
    });

    // 4. Hourly Intensity Curve
    new Chart(ctxHour, {
        type: 'line',
        data: {
            labels: <?= json_encode($hours) ?>,
            datasets: [{
                label: 'Intensity',
                data: <?= json_encode($hourCounts) ?>,
                borderColor: '#B76E79',
                borderWidth: 3,
                backgroundColor: roseGradient,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { display: false },
                x: { grid: { display: false } }
            }
        }
    });

    // 6. Guest Retention Chart
    new Chart(ctxReten, {
        type: 'doughnut',
        data: {
            labels: ['Returning Clients', 'First-Time Guests'],
            datasets: [{
                data: [<?= $returningGuests ?>, <?= $newGuests ?>],
                backgroundColor: ['#D4AF37', '#B76E79'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: { 
                legend: { display: false }
            }
        }
    });

    // 7. Weekly Booking Flow
    new Chart(ctxBook, {
        type: 'bar',
        data: {
            labels: <?= json_encode($weeklyLabels) ?>,
            datasets: [{
                data: <?= json_encode($weeklyCounts) ?>,
                backgroundColor: barGradient,
                borderRadius: 20,
                barThickness: 15
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, border: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });
};
</script>

<?php include_once("../../footer.php"); ?>
