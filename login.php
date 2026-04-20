<?php session_start();?>
<?php include_once("db.php")?>
<?php include_once("header.php")?>
<div class="auth-wrapper">
<div class="auth-form">
  <h2 class="text-center mb-4 text-gold">Elegance Login</h2>
<form action="" method="post">
  <div class="mb-4">
    <label for="name" class="form-label">Email or Username</label>
    <input type="text" class="form-control" id="name" name="username" placeholder="e.g. staff@salon.com or Jackson">
    
    <label for="inputPassword5" class="form-label">Password</label>
    <input type="password" id="inputPassword5" class="form-control" name="userpass" placeholder="••••••••">
  </div>
 
  <button type="submit" class="btn-gold w-100 mb-3">Login</button>
  <p class="text-center">Don't have an account? <a href="signup.php">Create Account</a></p>
</form>
</div>
</div>

<script>
// Click outside to close (Redirect to Home)
document.querySelector('.auth-wrapper').addEventListener('click', function(e) {
    if (e.target === this) {
        window.location.href = 'index.php';
    }
});
</script>

                <?php
                if(isset($_GET['logout']) && $_GET['logout'] == 'success') {
                  echo '
                  <script>
                  Swal.fire({
                    icon: "info",
                    title: "Successfully Logged Out",
                    text: "We hope to see you again soon!",
                    timer: 3000,
                    showConfirmButton: false
                  })
                  </script>';
                }
                
                if($_SERVER["REQUEST_METHOD"] == "POST")
                {
                  $NAME = $_POST["username"];
                  $PASSWORD = $_POST["userpass"];

                  if(empty($NAME) || empty($PASSWORD)){
                    echo '<script>Swal.fire("All Fields Are Required!")</script>';
                    exit;
                  }

                  // Logic: Allow login with either Email or Name
                  $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE name = ? OR email = ?");
                  mysqli_stmt_bind_param($stmt, "ss", $NAME, $NAME);
                  mysqli_stmt_execute($stmt);
                  $result = mysqli_stmt_get_result($stmt);

                  if ($data = mysqli_fetch_assoc($result)) {
                    if (password_verify($PASSWORD, $data["password"])) {
                      $_SESSION["name"] = $data["name"];
                      $_SESSION["email"] = $data["email"];
                      $_SESSION["role"] = $data["role"];
                      $_SESSION["id"] = $data["id"];

                      $role = strtolower($data["role"]);
                      $redirect = "dashboard/customer/index.php"; // Default fallback
                      
                      if($role == 'admin') {
                        $redirect = "dashboard/admin/index.php";
                      } elseif(in_array($role, ['receptionist', 'stylist', 'beautician'])) {
                        $redirect = "dashboard/staff/index.php";
                      }

                      echo "
                      <script>
                      Swal.fire({
                        icon: 'success',
                        title: 'Login Successfully',
                        showConfirmButton: false,
                        timer: 2000
                      }).then(()=>{
                        window.location.href='$redirect'
                      })
                      </script>";
                    } else {
                      echo '
                      <script>
                      Swal.fire({
                        icon: "error",
                        title: "Invalid Credentials!",
                        timer: 2500,
                        showConfirmButton: false
                      })
                      </script>';
                    }
                  } else {
                    echo '
                    <script>
                    Swal.fire({
                      icon: "error",
                      title: "Login Failed",
                      timer: 2500,
                      showConfirmButton: false
                    })
                    </script>';
                  }
                }
                ?>
                <?php include_once("footer.php")?>