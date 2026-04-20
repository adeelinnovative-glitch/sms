<?php include_once("db.php")?>
<?php include_once("header.php")?>
<div class="auth-wrapper">
<div class="auth-form">
  <h2 class="text-center mb-4 text-gold">Create Account</h2>
<form action="" method="post">
  <div class="mb-4">
    <label for="name" class="form-label">Username</label>
    <input type="text" class="form-control" id="name" placeholder="jackson" name="username">
    
    <label for="exampleInputEmail1" class="form-label">Email address</label>
    <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="email@example.com" name="useremail">
    <div id="emailHelp" class="form-text mb-3">We'll never share your email with anyone else.</div>
    
    <label for="inputPassword5" class="form-label">Password</label>
    <input type="password" id="inputPassword5" class="form-control" aria-describedby="passwordHelpBlock" name="userpass">
  </div>
 
  <button type="submit" class="btn-gold w-100 mb-3">Create Account</button>
  <p class="text-center">Already have an account? <a href="login.php">Login</a></p>
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



if($_SERVER["REQUEST_METHOD"] == "POST")

                {

                  $NAME = $_POST["username"];
                  $EMAIL = $_POST["useremail"];
                  $PASSWORD = $_POST["userpass"];

if (empty($NAME) || empty($EMAIL) || empty($PASSWORD)) {
    echo '<script>Swal.fire("All Fields Are Required!")</script>';
    exit;
}

$stmt_check = mysqli_prepare($con, "SELECT id FROM users WHERE name=? OR email=?");
mysqli_stmt_bind_param($stmt_check, "ss", $NAME, $EMAIL);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) > 0) {
    echo '<script>Swal.fire("Username or Email Already Exists")</script>';
    mysqli_stmt_close($stmt_check);
    exit;
}
mysqli_stmt_close($stmt_check);

$HASHPASSWORD = password_hash($PASSWORD, PASSWORD_DEFAULT);
             
$stmt = mysqli_prepare($con, "INSERT INTO users (name,email,password,role) VALUES(?, ?, ?, 'customer')");
mysqli_stmt_bind_param($stmt, "sss", $NAME, $EMAIL, $HASHPASSWORD);
$result = mysqli_stmt_execute($stmt);
          
              if ($result) {
                  // Smart Linking: Check if client record already exists (e.g. added by admin)
                  $check_client = mysqli_prepare($con, "SELECT client_id FROM clients WHERE email = ?");
                  mysqli_stmt_bind_param($check_client, "s", $EMAIL);
                  mysqli_stmt_execute($check_client);
                  mysqli_stmt_store_result($check_client);

                  if (mysqli_stmt_num_rows($check_client) > 0) {
                      // Client exists - link to this login by syncing the name
                      $update_client = mysqli_prepare($con, "UPDATE clients SET name = ? WHERE email = ?");
                      mysqli_stmt_bind_param($update_client, "ss", $NAME, $EMAIL);
                      mysqli_stmt_execute($update_client);
                      mysqli_stmt_close($update_client);
                  } else {
                      // No existing client found - create a new profile
                      $client_stmt = mysqli_prepare($con, "INSERT INTO clients (name, email, phone) VALUES(?, ?, 0)");
                      mysqli_stmt_bind_param($client_stmt, "ss", $NAME, $EMAIL);
                      mysqli_stmt_execute($client_stmt);
                      mysqli_stmt_close($client_stmt);
                  }
                  mysqli_stmt_close($check_client);

                  echo '
              <script>
              Swal.fire({
              position: "top-end",
              icon: "success",
              title: "Account Created Successfully",
              showConfirmButton: false,
              timer: 2500
              }).then(()=>{
              window.location.href="login.php"
              })
              </script>
          
              ';
              }





                }
                
                
                
                
                ?>







<?php include_once("footer.php")?>
