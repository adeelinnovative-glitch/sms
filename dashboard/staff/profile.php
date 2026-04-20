<?php
include_once("../../includes/auth_check.php");
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'stylist' && $_SESSION["role"] !== 'beautician' && $_SESSION["role"] !== 'nail technician')) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

$u_id = $_SESSION['id'];
$msg = "";

// Fetch Current Staff Details
$q = mysqli_query($con, "SELECT s.*, u.email FROM staff s JOIN users u ON s.user_id = u.id WHERE s.user_id = $u_id");
$sData = mysqli_fetch_assoc($q);

// Detect admin updates for highlighting
$updates = !empty($sData['updated_fields']) ? explode(',', $sData['updated_fields']) : [];

// Clear the notification flag once viewed
if ($sData['is_profile_updated'] == 1) {
    mysqli_query($con, "UPDATE staff SET is_profile_updated = 0, updated_fields = NULL WHERE user_id = $u_id");
}

function showUpdateBadge($field, $updates) {
    if (in_array($field, $updates)) {
        echo '<span class="badge ms-2" style="font-size: 0.65rem; vertical-align: middle; color: #D4AF37; border: 1px solid #D4AF37; background: rgba(212, 175, 55, 0.1); padding: 2px 8px; border-radius: 4px;">Updated by Admin</span>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $newName = mysqli_real_escape_string($con, $_POST['name']);
    $newPhone = mysqli_real_escape_string($con, $_POST['phone']);
    $newEmail = mysqli_real_escape_string($con, $_POST['email']);
    
    // Handle Profile Picture
    $picName = $sData['profile_pic'];
    $picChanged = false;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $picName = "staff_" . $u_id . "_" . time() . "." . $ext;
        $target = "../../assets/profile_pics/" . $picName;
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target);
        $picChanged = true;
    }

    // Check if anything actually changed
    if ($newName === $sData['name'] && $newEmail === $sData['email'] && $newPhone === $sData['phone'] && !$picChanged) {
        $msg = '<div class="alert alert-info">No changes were made to your profile.</div>';
    } else {
        // Update Users table
        $upUser = mysqli_prepare($con, "UPDATE users SET name = ?, email = ? WHERE id = ?");
        mysqli_stmt_bind_param($upUser, "ssi", $newName, $newEmail, $u_id);
        mysqli_stmt_execute($upUser);
        
        // Update Staff table
        $upStaff = mysqli_prepare($con, "UPDATE staff SET name = ?, phone = ?, profile_pic = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($upStaff, "sssi", $newName, $newPhone, $picName, $u_id);
        
        if (mysqli_stmt_execute($upStaff)) {
            $_SESSION['name'] = $newName;
            $_SESSION['email'] = $newEmail;
            $msg = '<div class="alert alert-success">Profile updated successfully!</div>';
            // Refresh data
            $q = mysqli_query($con, "SELECT s.*, u.email FROM staff s JOIN users u ON s.user_id = u.id WHERE s.user_id = $u_id");
            $sData = mysqli_fetch_assoc($q);
        } else {
            $msg = '<div class="alert alert-danger">Error updating profile.</div>';
        }
    }
}

include_once("../../header.php");
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>My Staff Profile</h3>
                <p class="text-muted mb-0">Manage your persona and contact information.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-7">
                <div class="glass-card">
                    <h5 class="text-gold mb-4">Edit Basic Information</h5>
                    <?= $msg ?>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light border-opacity-10">
                            <?php 
                            $pic_src = !empty($sData['profile_pic']) ? "../../assets/profile_pics/" . $sData['profile_pic'] : "../../assets/img/default-user.png";
                            ?>
                            <div class="me-3" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid var(--accent-gold); overflow: hidden; background: #222;">
                                <img src="<?= $pic_src ?>?v=<?= time() ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div>
                                <label class="form-label mb-1">Update Profile Picture</label>
                                <input type="file" name="profile_pic" class="form-control form-control-sm" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                                <small class="text-muted">JPG or PNG supported.</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name <?php showUpdateBadge('name', $updates); ?></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($sData['name']) ?>" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($sData['email']) ?>" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number <?php showUpdateBadge('phone', $updates); ?></label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($sData['phone']) ?>" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn-gold mt-3">Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="col-md-5">
                <div class="glass-card mb-4">
                    <h5 class="text-gold mb-3">Employment Details</h5>
                    <div class="mb-3">
                        <small class="text-muted d-block">Assigned Role <?php showUpdateBadge('role', $updates); ?></small>
                        <span class="text-light"><?= strtoupper($sData['role']) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Current Shift <?php showUpdateBadge('schedule', $updates); ?></small>
                        <span class="text-light"><?= htmlspecialchars($sData['schedule']) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Time Slot <?php showUpdateBadge('time_slot', $updates); ?></small>
                        <span class="text-light"><?= htmlspecialchars($sData['time_slot']) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Commission Rate <?php showUpdateBadge('commission_rate', $updates); ?></small>
                        <span class="text-gold h4"><?= $sData['commission_rate'] ?>%</span>
                    </div>
                </div>

                <div class="glass-card">
                    <h6 class="text-gold mb-2">Need a change?</h6>
                    <p class="small text-muted mb-3">Role, schedule, and commission settings are managed by the administrator.</p>
                    <div class="p-3 rounded" style="background: rgba(212, 175, 55, 0.05); border: 1px dashed rgba(212, 175, 55, 0.3);">
                        <small class="text-gold d-block mb-1">Management Contact:</small>
                        <a href="mailto:admin@elegance.com" class="text-light" style="font-size: 0.9rem;">admin@elegance.com</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once("../../footer.php"); ?>
