<?php

require_once("sqlite_inc.php");
require_once("config.php");

class Bild {
  //Properties
  //TODO set most of them to private !!
  public $readablefilename = null; //readable file name
  public $filename = null; //Filename as stored in DB
  public $path = null; // File Path on Server
  public $status = 0; //SUCCESS - everything else means error
  public $errmsg="";
  public $isThumbnail=false; //has to be set explicitly
  public $dbId=-1; // if is not set
  
  function __construct() {
    $status = $this->filename;
  }
  //returns the URL of the picture to be used in images
  function getURL() {
    if (is_null($this->path)) {
      return "./empty.jpg"; // TODO has to be generated - image with Questionmark or something like this
    } else {
      return "./".$this->path;
    }
  }
  
  function getDbID() {
    return $this->dbId;
  }
    
  //gets the thumbnail of the picture as Bild-Object
  function getThumbnail() {
    if ($this->isThumbnail) {
      return $this;
    } else if ($this->dbId==-1) { // No real picture
      $this->status = -1;
      $this->errmsg = 'Thumbnail generation not possible';
      return $this;
    } else {
      $b = clone $this;
      $b->isThumbnail = true;
      // check if thumbnail exists
      if (!is_null($this->filename) and strlen($this->filename)>0 and file_exists(THUMBNAILDIR."/".pathinfo($this->path, PATHINFO_BASENAME))) {
        $b->path = THUMBNAILDIR."/".pathinfo($this->path, PATHINFO_BASENAME);
        return $b;
      } else { // thumbnail does not yet exist
        Bild::generateThumbnailToFileId($b->dbId);
        $b->path = THUMBNAILDIR."/".pathinfo($this->path, PATHINFO_BASENAME);
        return $b;
      }
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
      $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
      );
      console_log("   ERROR - Error on upload - nr: ".$postfile['error']. "- ".$phpFileUploadErrors[$postfile['error']]);
      $newBild->errmsg = "Error - ".$phpFileUploadErrors[$postfile['error']];
      $newBild->status = intval($postfile['error']);
    } elseif (!in_array($extension, ['jpg', 'gif', 'png'])) {
      console_log("   ERROR - Wrong file extension");
      $newBild->errmsg = "Your file extension must be .jpg, .gif or .png";
    } elseif ($size > MAXUPLOADFILESIZE) { // file shouldn't be larger than defined in config.php
      console_log("   ERROR - File too large > ".MAXUPLOADFILESIZE);
      $newBild->errmsg = "Your file is too large. Max: ".MAXUPLOADFILESIZE;
    } else if (getNrOfUploadsInLastHour() > MAXUPLOADCOUNT) { // to many uploads
      console_log("   ERROR - too many uploads".getNrOfUploadsInLastHour());
      $newBild->errmsg = "too many uploads in last hour";
    } else {
      console_log("  try to move uploaded file from ".htmlspecialchars($file)." to ".htmlspecialchars($destination));
      // move the uploaded (temporary) file to the specified destination
      // TODO: Check if file exists...
      while(file_exists($destination) && strlen(basename($destination)) < 20) {
        $destination=$destination."a";
      }
      if (move_uploaded_file($file, $destination)) {
        console_log("   success");
        $newBild->dbId = storeUploadedFileInDB($filename, $destination, $size);
        $newBild->filename = $filename;
        $newBild->path = $destination;
        $newBild->errmsg ="File ".htmlspecialchars($filename)." uploaded";
      } else {
        console_log("   failed");
        $newBild->status = -1;
        $newBild->errmsg ="Failed to upload file.";
      }
    }
    console_log("CLASS Bild - leaving generateBildFromUpload");
    return $newBild;
  }
  
  public static function fetchBildFromDB($id) {
    //Fetch information from DB as given by $id
    $b = new Bild();
    $row = getSingleTableRow("files",intval($id));
    if ($row) { //result erhalten
      $b->path = $row['place'];
      $b->readablefilename = $row['name'];
      $b->dbId = $id;
      $b->filename = pathinfo($b->path, PATHINFO_BASENAME);
      $b->errmsg = "Path ist ".$b->path;
    } else {
      $b->status = -1;
      $b->errmsg = "Picture does not exist in DB";
    }
    return $b;    
  }
  
  public static function generateThumbnailToFileId($fileid, $force = false) {
    $fres = new Result();
    if (is_integer($fileid) and $fileid>0) {
      $row = getSingleTableRow("files",$fileid);
      if ($row) { //result erhalten
        //TODO
        $n_height = 100; //TODO default height of thumbnail
        $add = $row['place'];
        $tsrc = THUMBNAILDIR."/".basename($add);
        if (!$force and file_exists($tsrc)) { //Thumbnail existiert schon
          $fres = new Result(0, "thumbail existiert schon");
        } else if ($row['mimetype']=="image/gif"){
            $im=imagecreatefromgif($add);
            $width=ImageSx($im);              
            $height=ImageSy($im);
            $n_width=($n_height/$height) * $width;
            $newimage=imagecreatetruecolor($n_width,$n_height);
            imageCopyResized($newimage,$im,0,0,0,0,$n_width,$n_height,$width,$height);
            if (function_exists("imagegif")){
                Header("Content-type: image/gif");
                ImageGIF($newimage,$tsrc);
            
            }elseif(function_exists("imagejpeg")){
                Header("Content-type: image/jpeg");
                ImageJPEG($newimage,$tsrc);
            }
            chmod("$tsrc",0666);
        } else if($row['mimetype']=="image/jpeg"){
          try {
            $im=imagecreatefromjpeg($add); 
            $width=ImageSx($im);              
            $height=ImageSy($im);         
            $n_width=($n_height/$height) * $width;
            $newimage=imagecreatetruecolor($n_width,$n_height);                 
            imageCopyResized($newimage,$im,0,0,0,0,$n_width,$n_height,$width,$height);
            ImageJpeg($newimage,$tsrc);
            chmod("$tsrc",0666);
            $fres = new Result(0, "thumbnail generated: ".$tsrc);
          } catch (Exception $e) {
            $fres = new Result(-1, "thumbnail generation failed");
          }
        } else if($row['mimetype']=="image/png"){
            $im=imagecreatefrompng($add); 
            $width=ImageSx($im);              
            $height=ImageSy($im);             
            $n_width=($n_height/$height) * $width;
            $newimage=imagecreatetruecolor($n_width,$n_height);                 
            imageCopyResized($newimage,$im,0,0,0,0,$n_width,$n_height,$width,$height);
            ImageJpeg($newimage,$tsrc);
            chmod("$tsrc",0666);
        } else {
          $fres = new Result(-1, "File is no image");
        }        
      } else {
        $fres = new Result(-1,"File-ID does not exist");
      }
    } else { // invalid $fileid
      $fres = new Result(-1,"Invalid Fileid");
    } 
    return $fres;
  } 
  
  
  
}
?>
