<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

// Handle Deletion
if(isset($_GET['delete'])) {
    $cid = intval($_GET['delete']);
    
    // 1. Get client email to find user account
    $cq = mysqli_query($con, "SELECT email FROM clients WHERE client_id = $cid");
    if($cData = mysqli_fetch_assoc($cq)) {
        $email = $cData['email'];
        // 2. Delete from users (logs them out)
        mysqli_query($con, "DELETE FROM users WHERE email = '$email'");
    }
    
    // 3. Delete from clients
    mysqli_query($con, "DELETE FROM clients WHERE client_id = $cid");
    header("Location: clients.php?delete_success=1");
    exit;
}

// Handle Update Client
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_client'])) {
    $cid = intval($_POST['client_id']);
    $name = $_POST['c_name'];
    $email = $_POST['c_email'];
    $phone = $_POST['c_phone'];
    $pref = $_POST['c_pref'];
    
    $st = mysqli_prepare($con, "UPDATE clients SET name = ?, email = ?, phone = ?, preferences = ? WHERE client_id = ?");
    mysqli_stmt_bind_param($st, "ssssi", $name, $email, $phone, $pref, $cid);
    $usuccess = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
}

// Handle Add Client
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_client'])) {
    $name = $_POST['c_name'];
    $email = $_POST['c_email'];
    $phone = $_POST['c_phone'];
    $pref = $_POST['c_pref'];
    $st = mysqli_prepare($con, "INSERT INTO clients (name, email, phone, preferences) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, "ssss", $name, $email, $phone, $pref);
    $csuccess = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
}

// Fetch Stats
$s1 = mysqli_query($con, "SELECT COUNT(*) as t FROM clients");
$tot_clients = mysqli_fetch_assoc($s1)['t'] ?? 0;

$s2 = mysqli_query($con, "SELECT COUNT(*) as c FROM appointments");
$tot_bookings = mysqli_fetch_assoc($s2)['c'] ?? 0;

$s3 = mysqli_query($con, "SELECT SUM(amount) as r FROM billing");
$tot_revenue = current(mysqli_fetch_assoc($s3)) ?? 0;

include_once("../../header.php");
?>
<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3><i class="fas fa-address-book text-gold me-2"></i>Client Directory</h3>
                <p class="text-muted mb-0">Manage customer details, contact preferences, and directory records.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.15), rgba(0, 123, 255, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-users fs-3 mb-2 d-block text-info" style="opacity: 0.8;"></i>Total Clients</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_clients; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(139, 0, 0, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-calendar-check fs-3 mb-2 d-block text-danger" style="opacity: 0.8;"></i>Lifetime Bookings</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_bookings; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(32, 201, 151, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-hand-holding-usd fs-3 mb-2 d-block text-success" style="opacity: 0.8;"></i>Global Client Revenue</h6>
                    <h2 class="mb-0 text-white fw-bold">$<?php echo number_format($tot_revenue, 2); ?></h2>
                </div>
            </div>
        </div>

        <div class="glass-card mb-5 p-4 shadow-lg">
            <h5 class="text-gold mb-3"><i class="fas fa-user-plus me-2"></i>Register New Client</h5>
            <?php if(isset($csuccess) && $csuccess) echo '<div class="alert alert-success py-2 mb-3"><i class="fas fa-check-circle me-2"></i>Client Added Successfully!</div>'; ?>
            <?php if(isset($usuccess) && $usuccess) echo '<div class="alert alert-info py-2 mb-3"><i class="fas fa-check-circle me-2"></i>Client Record Updated Successfully!</div>'; ?>
            <?php if(isset($_GET['delete_success'])) echo '<div class="alert alert-danger py-2 mb-3"><i class="fas fa-trash-alt me-2"></i>Client Record Removed Successfully!</div>'; ?>
            
            <form action="" method="post" class="row g-4 align-items-end mt-0">
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-2">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="c_name" class="form-control py-2" placeholder="e.g. Jane Doe" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-2">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="c_email" class="form-control py-2" placeholder="jane@example.com" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-light fw-bold mb-2">Phone</label>
                    <input type="text" name="c_phone" class="form-control py-2" placeholder="+1..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-light fw-bold mb-2">Preferences</label>
                    <input type="text" name="c_pref" class="form-control py-2" placeholder="Notes..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" name="add_client" class="btn-gold w-100 py-2 fw-bold shadow-sm" style="font-size: 1.05rem;"><i class="fas fa-plus-circle me-1"></i> Add</button>
                </div>
            </form>
        </div>

        <div class="glass-card p-4 shadow-lg mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h5 class="text-gold mb-0"><i class="fas fa-address-card me-2"></i>Customer Database</h5>
                <div class="search-box" style="min-width: 250px;">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="clientSearch" class="form-control bg-dark text-light border-secondary shadow-none" placeholder="Search clients by name or email..." onkeyup="filterClients()">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover border-light mb-0 align-middle" style="background: transparent;" id="clientTable">
                    <thead>
                        <tr>
                            <th class="text-gold border-bottom border-light pb-3">Client Profile</th>
                            <th class="text-gold border-bottom border-light pb-3">Contact Email</th>
                            <th class="text-gold border-bottom border-light pb-3">Phone Line</th>
                            <th class="text-gold border-bottom border-light pb-3">Special Preferences</th>
                            <th class="text-gold border-bottom border-light pb-3 text-center">Action Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Removed the slow PHP GET search modifier since we now have Live UI text filtering.
                        $query = "SELECT * FROM clients ORDER BY name ASC";
                        $res = mysqli_query($con, $query);
                        while($row = mysqli_fetch_assoc($res)) {
                            echo "<tr>
                                <td><div class='fw-bold text-light'>{$row['name']}</div></td>
                                <td><span class='text-light'><i class='far fa-envelope me-2 text-muted'></i>{$row['email']}</span></td>
                                <td><span class='text-light'><i class='fas fa-phone-alt me-2 text-muted'></i>{$row['phone']}</span></td>
                                <td><span class='badge bg-dark border border-secondary text-light px-3 py-2'>".($row['preferences'] ?: 'None')."</span></td>
                                <td>
                                    <div class='d-flex justify-content-center align-items-center gap-2'>
                                        <button onclick='editClient({$row['client_id']}, \"" . addslashes($row['name']) . "\", \"{$row['email']}\", \"{$row['phone']}\", \"" . addslashes($row['preferences']) . "\")' class='btn btn-sm btn-outline-light rounded-pill px-3 scale-hover' title='Edit'><i class='fas fa-pen me-1'></i> Edit</button>
                                        <a href='?delete={$row['client_id']}' class='btn btn-sm btn-outline-danger rounded-pill px-3 scale-hover' title='Delete' onclick='return confirm(\"Permanently remove {$row['name']}? This deletes their user record.\")'><i class='fas fa-trash-alt me-1'></i> Delete</a>
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
function filterClients() {
    var input, filter, table, tr, tdName, tdEmail, i, txtValueName, txtValueEmail;
    input = document.getElementById("clientSearch");
    filter = input.value.toUpperCase();
    table = document.getElementById("clientTable");
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

function editClient(id, name, email, phone, pref) {
    Swal.fire({
        title: '<span class="text-gold">Update Client Record</span>',
        html: `
            <form id="editClientForm" method="post" class="text-start p-2">
                <input type="hidden" name="update_client" value="1">
                <input type="hidden" name="client_id" value="${id}">
                <div class="mb-3">
                    <label class="text-gold small">Full Name</label>
                    <input type="text" name="c_name" class="form-control" value="${name}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
                <div class="mb-3">
                    <label class="text-gold small">Email Address</label>
                    <input type="email" name="c_email" class="form-control" value="${email}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
                <div class="mb-3">
                    <label class="text-gold small">Phone Number</label>
                    <input type="text" name="c_phone" class="form-control" value="${phone}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
                <div class="mb-3">
                    <label class="text-gold small">Preferences</label>
                    <input type="text" name="c_pref" class="form-control" value="${pref}" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#daa520',
        cancelButtonColor: '#333',
        background: '#111',
        preConfirm: () => {
            document.getElementById('editClientForm').submit();
        }
    });
}
</script>
<?php include_once("../../footer.php"); ?>
