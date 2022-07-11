<?php

require_once("sqlite_inc.php");

class Bild {
  //Properties
  private $filename = null; //Filename as stored in DB
  private $path = null; // File Path on Server
  public $status = 0; //SUCCESS
  public $errmsg="";
  private $isThumbnail=false; //has to be set explicitly
  private $dbId=-1; // if is not set
  
  function __construct() {
    $status = $this->$filename;
  }
  //returns the URL of the picture to be used in images
  function getURL() {
  }
  
  //gets the thumbnail of the picture as Bild-Class
  function getThumbnail() {
    if ($this->$isThumbnail) {
      return $this;
    } else {
      // TODO generate new Bild-Objekt
    }
  }
  
  //generates new Bild from upload if possible and 
  //stores it in DB
  //should be called with $_FILES['myfile'] as parameter
  public static function generateBildFromUpload($postfile) {
    console_log("In Class Bild - generateBildFromUpload");
    $newBild = new Bild();
    //TODO upload image and return information
    // name of the uploaded file
    // checks from https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    $filename = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $postfile['name']);
    $filename = preg_replace("([\.]{2,})", '', $filename);
    console_log("CLASS Bild: File to upload: ".$filename);
    console_log_json($postfile);
    // get the file extension
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    // the physical file on a temporary uploads directory on the server
    $file = $postfile['tmp_name'];
    $size = $postfile['size'];
    // destination of the file on the server
    $destination = UPLOADDIR.'/' . basename($file); //we keep the temporary name - original filename is stored in db
    console_log("  tmp_name: ".$file);
    console_log("  size: ".$size." - extension: ".$extension);
    if ($postfile['error']>0) {         // check on error
      console_log("   ERROR - Error on upload - nr: ".$postfile['error']. "- see https://www.php.net/manual/de/features.file-upload.errors.php for details");
      $newBild->$errmsg = "Error - file too big or something else went wrong";
    } elseif (!in_array($extension, ['jpg', 'gif', 'png'])) {
      console_log("   ERROR - Wrong file extension");
      $newBild->$errmsg = "Your file extension must be .jpg, .gif or .png";
    } elseif ($size > MAXUPLOADFILESIZE) { // file shouldn't be larger than defined in config.php
      console_log("   ERROR - File too large > ".MAXUPLOADFILESIZE);
      $newBild->$errmsg = "Your file is too large. Max: ".MAXUPLOADFILESIZE;
    } else if (getNrOfUploadsInLastHour() > MAXUPLOADCOUNT) { // to many uploads
      console_log("   ERROR - too many uploads".getNrOfUploadsInLastHour());
      $newBild->$errmsg = "too many uploads in last hour";
    } else {
      console_log("  try to move uploaded file from ".htmlspecialchars($file)." to ".htmlspecialchars($destination));
      // move the uploaded (temporary) file to the specified destination
      // TODO: Check if file exists...
      while(file_exists($destination) && strlen(basename($destination)) < 20) {
        $destination=$destination."a";
      }
      if (move_uploaded_file($file, $destination)) {
        console_log("   success");
        $newBild->$dbId = storeUploadedFileInDB($filename, $destination, $size);
        $newBild->$filename = $filename;
        $newBild->$path = $destination;
        $newBild->$errmsg ="File ".htmlspecialchars($filename)." uploaded";
      } else {
        console_log("   failed");
        $newBild->$status = -1;
        $newBild->$errmsg ="Failed to upload file.";
      }
    }
    console_log("CLASS Bild - leaving generateBildFromUpload");
    return $newBild;
  }
  
  public static function fetchBildFromDB($id) {
    //Fetch information from DB as given by $id
    
  }
  
  
  
}
?>
