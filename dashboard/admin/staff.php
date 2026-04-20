<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

if(isset($_GET['delete'])) {
    $sid = intval($_GET['delete']);
    
    // 1. Get the user_id linked to this staff member before we delete the staff record
    $q_uid = mysqli_query($con, "SELECT user_id FROM staff WHERE staff_id = $sid");
    if($r_uid = mysqli_fetch_assoc($q_uid)) {
        $u_id = $r_uid['user_id'];
        
        // 2. Delete the staff profile
        // The database foreign key (ON DELETE SET NULL) automatically updates linked appointments
        mysqli_query($con, "DELETE FROM staff WHERE staff_id = $sid");
        
        // 3. Delete the associated user login account
        if($u_id) {
            mysqli_query($con, "DELETE FROM users WHERE id = $u_id");
        }
    }
    
    header("Location: staff.php?d_success=1");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $name = mysqli_real_escape_string($con, $_POST['s_name']);
    $email = mysqli_real_escape_string($con, $_POST['s_email']);
    $password = $_POST['s_pass'];
    $role = $_POST['s_role'];
    $phone = $_POST['s_phone'];
    $comm = $_POST['s_comm'];
    $sched = $_POST['s_days'];
    $slot = $_POST['s_slot'];
    
    // Safety check: Email must be unique
    $checkQ = mysqli_query($con, "SELECT id FROM users WHERE email = '$email'");
    if(mysqli_num_rows($checkQ) > 0) {
        $error = "A user with this email already exists!";
    } else {
        // 1. Create User Account
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $st_u = mysqli_prepare($con, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($st_u, "ssss", $name, $email, $hashedPass, $role);
        
        if (mysqli_stmt_execute($st_u)) {
            $new_user_id = mysqli_insert_id($con);
            
            // 2. Create Staff Profile
            $st = mysqli_prepare($con, "INSERT INTO staff (name, role, phone, commission_rate, schedule, time_slot, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($st, "ssdsssi", $name, $role, $phone, $comm, $sched, $slot, $new_user_id);
            $ssuccess = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
        mysqli_stmt_close($st_u);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_staff'])) {
    $sid = intval($_POST['staff_id']);
    $name = mysqli_real_escape_string($con, $_POST['s_name']);
    $role = $_POST['s_role'];
    $phone = $_POST['s_phone'];
    $comm = $_POST['s_comm'];
    $sched = $_POST['s_days'];
    $slot = $_POST['s_slot'];
    
    // Fetch old record to track changes
    $oldQ = mysqli_query($con, "SELECT * FROM staff WHERE staff_id = $sid");
    $old = mysqli_fetch_assoc($oldQ);
    
    $changes = [];
    if($old['name'] != $name) $changes[] = 'name';
    if($old['role'] != $role) $changes[] = 'role';
    if($old['phone'] != $phone) $changes[] = 'phone';
    if($old['commission_rate'] != $comm) $changes[] = 'commission_rate';
    if($old['schedule'] != $sched) $changes[] = 'schedule';
    if($old['time_slot'] != $slot) $changes[] = 'time_slot';
    
    $isUpdatedFlag = !empty($changes) ? 1 : 0;
    $updatedFields = !empty($changes) ? implode(',', $changes) : NULL;
    
    $st = mysqli_prepare($con, "UPDATE staff SET name = ?, role = ?, phone = ?, commission_rate = ?, schedule = ?, time_slot = ?, is_profile_updated = ?, updated_fields = ? WHERE staff_id = ?");
    mysqli_stmt_bind_param($st, "ssdsssisi", $name, $role, $phone, $comm, $sched, $slot, $isUpdatedFlag, $updatedFields, $sid);
    $usuccess = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);

    if ($usuccess) {
        // Send notification to staff member
        $q_uid = mysqli_query($con, "SELECT s.user_id, u.email FROM staff s JOIN users u ON s.user_id = u.id WHERE s.staff_id = $sid");
        if($r_uid = mysqli_fetch_assoc($q_uid)) {
            $target_user_id = $r_uid['user_id'] ?? 0;
            $target_email = $r_uid['email'] ?? '';
            if ($target_user_id) {
                include_once("../../includes/notifications.php");
                sendNotification($con, $target_email, "Your staff profile have been updated by an administrator. Please review your profile for details.", 'email', $target_user_id);
            }
        }
    }
}

// Fetch Staff Stats
$s1 = mysqli_query($con, "SELECT COUNT(*) as t FROM staff");
$tot_staff = mysqli_fetch_assoc($s1)['t'] ?? 0;

$s2 = mysqli_query($con, "SELECT SUM(b.amount * s.commission_rate / 100) as e FROM staff s JOIN appointments a ON s.staff_id = a.staff_id JOIN billing b ON a.appointment_id = b.appointment_id");
$tot_earnings = mysqli_fetch_assoc($s2)['e'] ?? 0;

$s3 = mysqli_query($con, "SELECT COUNT(*) as s FROM staff WHERE role = 'stylist'");
$tot_stylists = mysqli_fetch_assoc($s3)['s'] ?? 0;

include_once("../../header.php");
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3><i class="fas fa-user-tie text-gold me-2"></i>Staff Directory</h3>
                <p class="text-muted mb-0">Manage stylists, schedules, roles, and review earning commissions.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.15), rgba(0, 123, 255, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-users fs-3 mb-2 d-block text-info" style="opacity: 0.8;"></i>Total Staff</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_staff; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(139, 0, 0, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-cut fs-3 mb-2 d-block text-danger" style="opacity: 0.8;"></i>Active Stylists</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_stylists; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(32, 201, 151, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-coins fs-3 mb-2 d-block text-success" style="opacity: 0.8;"></i>Commissions Generated</h6>
                    <h2 class="mb-0 text-white fw-bold">$<?php echo number_format($tot_earnings, 2); ?></h2>
                </div>
            </div>
        </div>

        <div class="glass-card mb-4 p-4 shadow-lg">
            <h5 class="text-gold mb-3"><i class="fas fa-user-plus me-2"></i>Register New Staff</h5>
            <?php if(isset($ssuccess) && $ssuccess) echo '<div class="alert alert-success py-2 mb-3"><i class="fas fa-check-circle me-2"></i>Staff Account & Profile Created Successfully!</div>'; ?>
            <?php if(isset($usuccess) && $usuccess) echo '<div class="alert alert-info py-2 mb-3"><i class="fas fa-check-circle me-2"></i>Staff Record Updated Successfully!</div>'; ?>
            <?php if(isset($_GET['d_success'])) echo '<div class="alert alert-danger py-2 mb-3"><i class="fas fa-trash-alt me-2"></i>Staff Record Removed Successfully!</div>'; ?>
            <?php if(isset($error)) echo '<div class="alert alert-warning py-2 mb-3"><i class="fas fa-exclamation-triangle me-2"></i>'.$error.'</div>'; ?>
            
            <form action="" method="post" class="row g-3 align-items-end mt-0">
                <!-- Row 1 -->
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-1">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="s_name" class="form-control py-2" placeholder="e.g. Jackson Smith" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-1">Login Email <span class="text-danger">*</span></label>
                    <input type="email" name="s_email" class="form-control py-2" placeholder="staff@elegance.com" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-1">Login Password <span class="text-danger">*</span></label>
                    <input type="password" name="s_pass" class="form-control py-2" placeholder="••••••••" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-1">Phone Number <span class="text-danger">*</span></label>
                    <input type="text" name="s_phone" class="form-control py-2" placeholder="+1 (555)..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>

                <!-- Row 2 -->
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-1">Primary Role <span class="text-danger">*</span></label>
                    <select name="s_role" class="form-control py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                        <option value="stylist" class="text-dark">Stylist</option>
                        <option value="receptionist" class="text-dark">Receptionist</option>
                        <option value="beautician" class="text-dark">Beautician</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-gold fw-bold mb-1">Comm. % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="s_comm" class="form-control py-2" placeholder="15.00" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-light fw-bold mb-1">Work Days <span class="text-danger">*</span></label>
                    <select name="s_days" class="form-control py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                        <option value="Mon-Fri" class="text-dark">Mon - Fri</option>
                        <option value="Sat-Sun" class="text-dark">Sat - Sun</option>
                        <option value="Mon-Sat" class="text-dark">Mon - Sat</option>
                        <option value="Every Day" class="text-dark">Every Day</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-1">Time Slot <span class="text-danger">*</span></label>
                    <select name="s_slot" class="form-control py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                        <option value="8:00 am - 1:00 pm" class="text-dark">8:00 am - 1:00 pm</option>
                        <option value="1:00 pm - 6:00 pm" class="text-dark">1:00 pm - 6:00 pm</option>
                        <option value="6:00 pm - 11:00 pm" class="text-dark">6:00 pm - 11:00 pm</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" name="add_staff" class="btn-gold w-100 py-2 fw-bold shadow-sm" style="font-size: 1.05rem;"><i class="fas fa-plus-circle me-1"></i> Add</button>
                </div>
            </form>
        </div>

        <div class="glass-card p-4 shadow-lg mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h5 class="text-gold mb-0"><i class="fas fa-sitemap me-2"></i>Active Payroll & Roster</h5>
                <div class="search-box" style="min-width: 250px;">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="staffSearch" class="form-control bg-dark text-light border-secondary shadow-none" placeholder="Search staff by name or role..." onkeyup="filterStaff()">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-dark table-hover border-light mb-0 align-middle" style="background: transparent;" id="staffTable">
                    <thead>
                        <tr>
                            <th class="text-gold border-bottom border-light pb-3">Name & Contact</th>
                            <th class="text-gold border-bottom border-light pb-3">Job Role</th>
                            <th class="text-gold border-bottom border-light pb-3">Assigned Schedule</th>
                            <th class="text-gold border-bottom border-light pb-3">Commission Config</th>
                            <th class="text-gold border-bottom border-light pb-3 text-end">Total Period Earnings</th>
                            <th class="text-gold border-bottom border-light pb-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($con, "
                            SELECT s.*, COALESCE(SUM(b.amount * s.commission_rate / 100), 0) as total_earnings 
                            FROM staff s 
                            LEFT JOIN appointments a ON s.staff_id = a.staff_id 
                            LEFT JOIN billing b ON a.appointment_id = b.appointment_id 
                            GROUP BY s.staff_id 
                            ORDER BY s.name ASC
                        ");
                        while($row = mysqli_fetch_assoc($res)) {
                            // Determine role badge style
                            $r = strtolower($row['role']);
                            $rbadge = '<span class="badge rounded-pill bg-secondary shadow-sm">'.ucfirst($row['role']).'</span>';
                            if ($r == 'stylist') $rbadge = '<span class="badge rounded-pill shadow-sm" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">Stylist</span>';
                            else if ($r == 'receptionist') $rbadge = '<span class="badge rounded-pill shadow-sm" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">Receptionist</span>';
                            else if ($r == 'beautician') $rbadge = '<span class="badge rounded-pill shadow-sm" style="background: linear-gradient(135deg, #fd7e14, #ffc107); color: #000;">Beautician</span>';

                            echo "<tr>
                                <td>
                                    <div class='fw-bold text-light'>{$row['name']}</div>
                                    <div class='small text-muted'><i class='fas fa-phone-alt me-1 text-gold'></i> {$row['phone']}</div>
                                </td>
                                <td>{$rbadge}</td>
                                <td>
                                    <div class='d-flex flex-column'>
                                        <span class='text-light'><i class='far fa-calendar-alt me-1 text-gold'></i> {$row['schedule']}</span>
                                        <small class='text-muted'><i class='far fa-clock me-1 text-gold'></i> {$row['time_slot']}</small>
                                    </div>
                                </td>
                                <td><span class='badge bg-dark border border-secondary text-light px-3 py-2'>{$row['commission_rate']}% Rate</span></td>
                                <td class='text-end'><span class='text-gold fw-bold fs-5'>$" . number_format($row['total_earnings'], 2) . "</span></td>
                                <td>
                                    <div class='d-flex justify-content-center align-items-center gap-2'>
                                        <button onclick='editStaff({$row['staff_id']}, \"" . addslashes($row['name']) . "\", \"{$row['role']}\", \"{$row['phone']}\", \"{$row['commission_rate']}\", \"{$row['schedule']}\", \"{$row['time_slot']}\")' class='btn btn-sm btn-outline-light rounded-pill px-3 scale-hover' title='Edit'><i class='fas fa-pen me-1'></i> Edit</button>
                                        <a href='?delete={$row['staff_id']}' class='btn btn-sm btn-outline-danger rounded-pill px-3 scale-hover' title='Delete' onclick='return confirm(\"Permanently remove {$row['name']}\\'s staff profile?\")'><i class='fas fa-trash-alt me-1'></i> Delete</a>
                                    </div>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterStaff() {
    var input, filter, table, tr, tdName, tdRole, i, txtValueName, txtValueRole;
    input = document.getElementById("staffSearch");
    filter = input.value.toUpperCase();
    table = document.getElementById("staffTable");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        tdName = tr[i].getElementsByTagName("td")[0];
        tdRole = tr[i].getElementsByTagName("td")[1];
        if (tdName || tdRole) {
            txtValueName = tdName.textContent || tdName.innerText;
            txtValueRole = tdRole.textContent || tdRole.innerText;
            if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueRole.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}

function editStaff(id, name, role, phone, comm, sched, slot) {
    Swal.fire({
        title: '<span class="text-gold">Update Staff Details</span>',
        html: `
            <form id="editStaffForm" method="post" class="text-start p-2">
                <input type="hidden" name="update_staff" value="1">
                <input type="hidden" name="staff_id" value="${id}">
                <div class="mb-3">
                    <label class="text-gold small">Full Name</label>
                    <input type="text" name="s_name" class="form-control" value="${name}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="text-gold small">Role</label>
                        <select name="s_role" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <option value="stylist" ${role=='stylist'?'selected':''}>Stylist</option>
                            <option value="receptionist" ${role=='receptionist'?'selected':''}>Receptionist</option>
                            <option value="beautician" ${role=='beautician'?'selected':''}>Beautician</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-gold small">Commission %</label>
                        <input type="number" step="0.01" name="s_comm" class="form-control" value="${comm}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="text-gold small">Work Days</label>
                        <select name="s_days" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <option value="Mon-Fri" ${sched=='Mon-Fri'?'selected':''}>Mon - Fri</option>
                            <option value="Sat-Sun" ${sched=='Sat-Sun'?'selected':''}>Sat - Sun</option>
                            <option value="Mon-Sat" ${sched=='Mon-Sat'?'selected':''}>Mon - Sat</option>
                            <option value="Every Day" ${sched=='Every Day'?'selected':''}>Every Day</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-gold small">Time Slot</label>
                        <select name="s_slot" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <option value="8:00 am - 1:00 pm" ${slot=='8:00 am - 1:00 pm'?'selected':''}>8:00 am - 1:00 pm</option>
                            <option value="1:00 pm - 6:00 pm" ${slot=='1:00 pm - 6:00 pm'?'selected':''}>1:00 pm - 6:00 pm</option>
                            <option value="6:00 pm - 11:00 pm" ${slot=='6:00 pm - 11:00 pm'?'selected':''}>6:00 pm - 11:00 pm</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-gold small">Phone Number</label>
                    <input type="text" name="s_phone" class="form-control" value="${phone}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#daa520',
        cancelButtonColor: '#333',
        background: '#111',
        preConfirm: () => {
            document.getElementById('editStaffForm').submit();
        }
    });
}
</script>

<?php include_once("../../footer.php"); ?>
