<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["user"]) || $_SESSION["user"] < 1){
    header("location: sammlung.php");
    exit;
}

// Include config file
require_once "config.php";

// Include Database-Connection and functions
require_once "sqlite_inc.php";

   
//Funktionen für Log-auf die Konsole
function console_log_json( $data ){
  if (DEBUG) {
    echo '<script>';
    echo 'console.log('. json_encode( $data ) .')';
    echo '</script>';
  }
}
function console_log( $data ){
  if (DEBUG) {
    echo '<script>';
    echo 'console.log("'. $data .'")';
    echo '</script>';
  }
}

// Uploads files
if (isset($_POST['save'])) { // if save button on the form is clicked
    // name of the uploaded file
    $filename = $_FILES['myfile']['name'];
    console_log("File to upload: ".$filename);

    // destination of the file on the server
    $destination = 'uploads/' . $filename;

    // get the file extension
    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    // the physical file on a temporary uploads directory on the server
    $file = $_FILES['myfile']['tmp_name'];
    $size = $_FILES['myfile']['size'];
    console_log("  tmp_name: ".$file);
    console_log("  size: ".$size);

    if (!in_array($extension, ['zip', 'pdf', 'docx'])) {
      console_log("   ERROR - Wrong file extension");
      echo "You file extension must be .zip, .pdf or .docx";
    } elseif ($_FILES['myfile']['size'] > MAXUPLOADFILESIZE) { // file shouldn't be larger than defined in config.php
      console_log("   ERROR - file too large");
      echo "File too large!";
    } else {
      console_log("  try to move uploaded file....");
      // move the uploaded (temporary) file to the specified destination
      // TODO: Check if file exists...
      if (move_uploaded_file($file, $destination)) {
        console_log("   success");
        storeUploadedFileInDB($filename, $destination, $size);
      } else {
        console_log("   failed");
          echo "Failed to upload file.";
      }
    }
}
