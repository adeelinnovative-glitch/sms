<?php
session_start();
if (!isset($_SESSION["name"])) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../header.php");

// Get client data
$email = $_SESSION["email"];
$msg = "";
$err = "";

$q = mysqli_query($con, "SELECT * FROM clients WHERE email = '$email'");
$client = mysqli_fetch_assoc($q);
$cl_id = $client['client_id'];

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $newName = mysqli_real_escape_string($con, $_POST['name']);
    $newPhone = mysqli_real_escape_string($con, $_POST['phone']);
    $newPref = mysqli_real_escape_string($con, $_POST['preferences']);
    
    // Handle Profile Pic Upload
    $pic_name = $client['profile_pic']; // Default to current
    $picChanged = false;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $targetDir = "../../assets/profile_pics/";
        $fileName = "client_" . $cl_id . "_" . time() . "_" . basename($_FILES["profile_pic"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        $allowTypes = array('jpg','png','jpeg','gif');
        if (in_array(strtolower($fileType), $allowTypes)) {
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFilePath)) {
                $pic_name = $fileName;
                $picChanged = true;
                // Delete old pic if exists
                if (!empty($client['profile_pic']) && file_exists($targetDir . $client['profile_pic'])) {
                    unlink($targetDir . $client['profile_pic']);
                }
            } else {
                $err = "Sorry, there was an error uploading your file.";
            }
        } else {
            $err = "Sorry, only JPG, JPEG, PNG, & GIF files are allowed.";
        }
    }

    if (empty($err)) {
        // Check if anything actually changed
        if ($newName === $client['name'] && $newPhone === $client['phone'] && $newPref === $client['preferences'] && !$picChanged) {
            $msg = "No changes were made to your profile.";
            $msgType = "info";
        } else {
            $upd = mysqli_prepare($con, "UPDATE clients SET name = ?, phone = ?, preferences = ?, profile_pic = ? WHERE client_id = ?");
            mysqli_stmt_bind_param($upd, "ssssi", $newName, $newPhone, $newPref, $pic_name, $cl_id);
            
            if (mysqli_stmt_execute($upd)) {
                $_SESSION['name'] = $newName; // Sync session
                $msg = "Profile updated successfully!";
                $msgType = "success";
                // Refresh local data
                $client['name'] = $newName;
                $client['phone'] = $newPhone;
                $client['preferences'] = $newPref;
                $client['profile_pic'] = $pic_name;
            } else {
                $err = "Error updating profile.";
            }
            mysqli_stmt_close($upd);
        }
    }
}
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>My Profile</h3>
                <p class="text-muted mb-0">Manage your personal details and preferences.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="glass-card h-100">
                    <h5 class="text-gold mb-4">Profile Photo</h5>
                    <div class="mb-4">
                        <?php 
                        $picPath = !empty($client['profile_pic']) ? "../../assets/profile_pics/" . $client['profile_pic'] : "../../assets/img/default-user.png";
                        ?>
                        <div class="profile-pic-preview mx-auto shadow-lg" style="width: 150px; height: 150px; border-radius: 50%; border: 3px solid var(--gold); overflow: hidden; background: #222;">
                            <img src="<?= $picPath ?>?v=<?= time() ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </div>
                    <p class="small text-muted mb-0">Member since: <?= date('M Y') ?></p>
                </div>
            </div>

            <div class="col-md-8">
                <div class="glass-card">
                    <h5 class="text-gold mb-4">Personal Details</h5>
                    <?php 
                    if($msg) {
                        $alertClass = (isset($msgType) && $msgType == 'info') ? 'alert-info' : 'alert-success';
                        echo "<div class='alert $alertClass'>$msg</div>"; 
                    }
                    ?>
                    <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

                    <form action="" method="post" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-gold">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($client['name']) ?>" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-gold">Email Address (Locked)</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($client['email']) ?>" readonly style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05); color: #888; cursor: not-allowed;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-gold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($client['phone']) ?>" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-gold">Change Photo</label>
                            <input type="file" name="profile_pic" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                        </div>
                        <div class="col-12 mt-4">
                            <label class="form-label small text-gold">Treatment Preferences & Style Notes</label>
                            <textarea name="preferences" rows="4" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" placeholder="Tell us about your hair type or favorite skin treatments..."><?= htmlspecialchars($client['preferences']) ?></textarea>
                        </div>
                        
                        <div class="col-12 mt-4 text-end">
                            <button type="submit" name="update_profile" class="btn-gold px-5">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once("../../footer.php"); ?>
