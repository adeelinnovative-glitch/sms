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
    $id = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM feedbacks WHERE id = $id");
    header("Location: feedbacks.php");
    exit;
}

// Handle Featuring Toggle
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_feat'])) {
    $fid = intval($_POST['feedback_id']);
    $nval = $_POST['new_feat_val'];
    mysqli_query($con, "UPDATE feedbacks SET is_featured = $nval WHERE id = $fid");
}

// Fetch Stats
$s1 = mysqli_query($con, "SELECT COUNT(*) as t FROM feedbacks");
$tot_fb = mysqli_fetch_assoc($s1)['t'] ?? 0;

$s2 = mysqli_query($con, "SELECT COUNT(*) as f FROM feedbacks WHERE is_featured = 1");
$tot_feat = mysqli_fetch_assoc($s2)['f'] ?? 0;

$s3 = mysqli_query($con, "SELECT COUNT(*) as r FROM feedbacks WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$tot_recent = mysqli_fetch_assoc($s3)['r'] ?? 0;
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3><i class="fas fa-comments text-gold me-2"></i>User Feedback</h3>
                <p class="text-muted mb-0">Review what our clients are saying and highlight top testimonials.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.15), rgba(0, 123, 255, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-inbox fs-3 mb-2 d-block text-info" style="opacity: 0.8;"></i>Total Submissions</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_fb; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(218, 165, 32, 0.15), rgba(184, 134, 11, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-star fs-3 mb-2 d-block text-warning" style="opacity: 0.8;"></i>Featured on Frontpage</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_feat; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(32, 201, 151, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-history fs-3 mb-2 d-block text-success" style="opacity: 0.8;"></i>Recent (Last 30 Days)</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $tot_recent; ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $res = mysqli_query($con, "SELECT * FROM feedbacks ORDER BY is_featured DESC, date DESC");
            if(mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    $featVal = $row['is_featured'] ? 0 : 1;
                    $featBtnClass = $row['is_featured'] ? 'text-gold border-gold' : 'text-muted border-secondary';
                    $cardStyle = $row['is_featured'] ? 'border: 1px solid rgba(218, 165, 32, 0.4); background: rgba(218, 165, 32, 0.03);' : 'border: 1px solid rgba(255,255,255,0.08);';
                    $btnIcon = $row['is_featured'] ? '<i class="fas fa-star me-1"></i> Featured' : '<i class="far fa-star me-1"></i> Feature';
                    
                    echo "
                    <div class='col-md-6'>
                        <div class='glass-card h-100 p-4 shadow-lg d-flex flex-column' style='{$cardStyle}'>
                            <div class='d-flex justify-content-between align-items-start mb-3'>
                                <h5 class='text-gold mb-0 fw-bold'><i class='fas fa-user-circle me-2 text-muted'></i>{$row['name']}</h5>
                                <span class='badge bg-dark border border-secondary text-muted'><i class='far fa-clock me-1'></i>".date('M d, Y', strtotime($row['date']))."</span>
                            </div>
                            <div class='mb-4 text-light fst-italic' style='border-left: 3px solid #daa520; padding-left: 15px; font-size: 1.05rem; line-height: 1.6;'>
                                \"{$row['message']}\"
                            </div>
                            <div class='d-flex justify-content-between align-items-center mt-auto border-top border-secondary pt-3'>
                                <form method='post' class='d-inline m-0'>
                                    <input type='hidden' name='feedback_id' value='{$row['id']}'>
                                    <input type='hidden' name='new_feat_val' value='$featVal'>
                                    <button type='submit' name='toggle_feat' class='btn btn-sm rounded-pill px-3 scale-hover $featBtnClass' style='background: rgba(0,0,0,0.3);'>
                                        {$btnIcon}
                                    </button>
                                </form>
                                <a href='?delete={$row['id']}' class='btn btn-sm btn-outline-danger rounded-pill px-3 scale-hover' onclick='return confirm(\"Permanently delete this feedback?\")'><i class='fas fa-trash-alt me-1'></i> Delete</a>
                            </div>
                        </div>
                    </div>";
                }
            } else {
                echo "<div class='col-12'><div class='glass-card p-5 text-center text-muted'><h4><i class='fas fa-folder-open mb-3 d-block fs-1'></i>No feedback received yet.</h4></div></div>";
            }
            ?>
        </div>
    </div>
</div>

<?php include_once("../../footer.php"); ?>
