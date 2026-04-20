<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../header.php");

// Handle deletion
if(isset($_GET['delete'])) {
    $uid = intval($_GET['delete']);
    // Prevent admin from deleting themselves
    if($uid != $_SESSION['id']) {
        mysqli_query($con, "DELETE FROM users WHERE id = $uid");
    }
    header("Location: users.php");
    exit;
}
// Fetch Stats
$q_all = mysqli_query($con, "SELECT COUNT(*) as c FROM users");
$tot_all = mysqli_fetch_assoc($q_all)['c'] ?? 0;

$q_admin = mysqli_query($con, "SELECT COUNT(*) as c FROM users WHERE role = 'admin'");
$tot_admin = mysqli_fetch_assoc($q_admin)['c'] ?? 0;

$q_cust = mysqli_query($con, "SELECT COUNT(*) as c FROM users WHERE role = 'customer'");
$tot_cust = mysqli_fetch_assoc($q_cust)['c'] ?? 0;
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3><i class="fas fa-users-cog text-gold me-2"></i>System Accounts</h3>
                <p class="text-muted mb-0">Oversee global login credentials, directory permissions, and user removals.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.15), rgba(0, 123, 255, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-globe fs-3 mb-2 d-block text-info" style="opacity: 0.8;"></i>Total Registered Entities</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_all; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(139, 0, 0, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-user-shield fs-3 mb-2 d-block text-danger" style="opacity: 0.8;"></i>Admin Privileges</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_admin; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(32, 201, 151, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-street-view fs-3 mb-2 d-block text-success" style="opacity: 0.8;"></i>Customer Logins</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_cust; ?></h2>
                </div>
            </div>
        </div>

        <div class="glass-card p-4 shadow-lg mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h5 class="text-gold mb-0"><i class="fas fa-id-card-alt me-2"></i>Global Access Registry</h5>
                <div class="search-box" style="min-width: 250px;">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="userSearch" class="form-control bg-dark text-light border-secondary shadow-none" placeholder="Search by name or email..." onkeyup="filterUsers()">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover border-light mb-0 align-middle" style="background: transparent;" id="userTable">
                    <thead>
                        <tr>
                            <th class="text-gold border-bottom border-light pb-3">User Identifier</th>
                            <th class="text-gold border-bottom border-light pb-3">Authenticated Email</th>
                            <th class="text-gold border-bottom border-light pb-3">System Role</th>
                            <th class="text-gold border-bottom border-light pb-3 text-end">Security Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($con, "SELECT * FROM users ORDER BY role ASC, name ASC");
                        while($row = mysqli_fetch_assoc($res)) {
                            $isSelf = $row['id'] == $_SESSION['id'];
                            
                            // Style badges based on role
                            $r = strtolower($row['role']);
                            $rbadge = '<span class="badge rounded-pill bg-secondary shadow-sm">'.ucfirst($row['role']).'</span>';
                            if ($r == 'admin') {
                                $rbadge = '<span class="badge rounded-pill shadow-sm" style="background: linear-gradient(135deg, #dc3545, #8b0000);"><i class="fas fa-shield-alt me-1"></i> Administrator</span>';
                            } else if ($r == 'customer') {
                                $rbadge = '<span class="badge rounded-pill shadow-sm" style="background: linear-gradient(135deg, #198754, #20c997);"><i class="fas fa-user mb-0"></i> Customer</span>';
                            } else {
                                $rbadge = '<span class="badge rounded-pill shadow-sm" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);"><i class="fas fa-briefcase me-1"></i> Staff / '.ucfirst($row['role']).'</span>';
                            }

                            echo "<tr>
                                <td>
                                    <div class='fw-bold text-light'>{$row['name']}</div>
                                    " . ($isSelf ? "<div class='small text-muted'><i class='fas fa-star text-gold me-1'></i>Current Session</div>" : "") . "
                                </td>
                                <td><span class='text-light'><i class='far fa-envelope me-2 text-muted'></i>{$row['email']}</span></td>
                                <td>{$rbadge}</td>
                                <td class='text-end'>";
                                
                            if(!$isSelf) {
                                echo "<a href='?delete={$row['id']}' class='btn btn-sm btn-outline-danger rounded-pill px-3 scale-hover' onclick='return confirm(\"Permanently revoke access for {$row['name']}? This action deletes their login credentials entirely.\")'><i class='fas fa-ban me-1'></i> Revoke Access</a>";
                            } else {
                                echo "<span class='badge bg-dark border border-secondary text-muted px-4 py-2 opacity-75'><i class='fas fa-lock me-1'></i> Protected</span>";
                            }
                            
                            echo "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterUsers() {
    var input, filter, table, tr, tdName, tdEmail, i, txtValueName, txtValueEmail;
    input = document.getElementById("userSearch");
    filter = input.value.toUpperCase();
    table = document.getElementById("userTable");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        tdName = tr[i].getElementsByTagName("td")[0];
        tdEmail = tr[i].getElementsByTagName("td")[1];
        if (tdName || tdEmail) {
            txtValueName = tdName.textContent || tdName.innerText;
            txtValueEmail = tdEmail.textContent || tdEmail.innerText;
            if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueEmail.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<?php include_once("../../footer.php"); ?>
