<?php
// Initialize the session
session_start();

// config laden - mit Passwort ;-)
require_once("config.php");
 
// Check if the user is already logged in, if yes then redirect him to admin page
if(isset($_SESSION["adminloggedin"]) && $_SESSION["adminloggedin"] === true){
    header("location: admin.php");
    exit;
}
  
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
     
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($password_err)){
        //console_log($_POST["password"]);
							if(trim($_POST["password"])===ADMINPASS){
								// Password is correct, so start a new session
								session_start();
								
								// Store data in session variables
								$_SESSION["adminloggedin"] = true;
								
								// Redirect user to admin page
								header("location: admin.php");
							} else{
								// Password is not valid, display a generic error message
								$login_err = "Invalid password.";
							}
    }
    
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Login</h2>
        <p>Please give admin Password to manage Sammlung.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Admin-Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
                <a href="pirates.php" class="btn btn-primary">Home</a>
            </div>
        </form>
    </div>
</body>
</html>
