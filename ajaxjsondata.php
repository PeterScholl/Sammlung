<?php
  //ACHTUNG - alle Ausgaben dieses Skriptes müssen
  //JSON-Format haben - auch FEHLERMELDUNGEN ?!
  $debug=false;
  global $retObj;
  $retObj = new stdClass();

  function debugTextOutput($test) {
    global $debug;
    if ($debug) {
      echo "".$test."<br>\n";
    }
  }

  //debug-Optionen
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  ini_set('error_log', './ERROR.LOG');
  error_reporting(E_ALL & ~E_NOTICE);
  
  require_once("config.php"); // konfiguration lesen
  // Initialize the session
  session_start();
  
  // TODO: BERECHTIGUNGEN überprüfen - SESSION?!

  //Open-and-prepare database
  require_once("sqlite_inc.php");

  if(!$db) {
    debugTextOutput("Datenbank kann nicht geöffnet werden!");
    $retObj->error = "could not open database!";
  } 
  
  // Processing get-data when form is submitted
  if($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET["debug"])) {
      $debug=true;
      debugTextOutput("Session-Info");
      debugTextOutput(json_encode($_SESSION));
    }
    if (isset($_GET["genThumbnail"]) and isset($_GET["fileid"])) { //hier Thumbnails generiert werden
      debugTextOutput("Generating thumbnail to fileid: ".$_GET["fileid"]);
      $fileid = (int)$_GET["fileid"];
      if (is_integer($fileid) and $fileid>0) {
        debugTextOutput("fileid in Ordnung");
        $row = getSingleTableRow("files",$fileid);
        debugTextOutput("Row as json: ".json_encode($row));
        if ($row) { //result erhalten
          //TODO
          $n_width = 200; //TODO default width of thumbnail
          $add = $row['place'];
          debugTextOutput("Ort: ".$row['place']);
          $tsrc = "thumbnails/".basename($add);
          if ($row['mimetype']=="image/gif"){
              $im=imagecreatefromgif($add);
              $width=ImageSx($im);              
              $height=ImageSy($im);                  
              $n_height=($n_width/$width) * $height; 
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
              debugTextOutput("Width: ".$width." Height: ".$height);    
              $n_height=($n_width/$width) * $height;
              $newimage=imagecreatetruecolor($n_width,$n_height);                 
              imageCopyResized($newimage,$im,0,0,0,0,$n_width,$n_height,$width,$height);
              ImageJpeg($newimage,$tsrc);
              chmod("$tsrc",0666);
              $retObj->resultText = "thumbnail generated: ".$tsrc;
            } catch (Exception $e) {
              $retObj->resultText = "thumbnail generation failed";
            }
          } else if($row['mimetype']=="image/png"){
              $im=imagecreatefrompng($add); 
              $width=ImageSx($im);              
              $height=ImageSy($im);             
              $n_height=($n_width/$width) * $height;
              $newimage=imagecreatetruecolor($n_width,$n_height);                 
              imageCopyResized($newimage,$im,0,0,0,0,$n_width,$n_height,$width,$height);
              ImageJpeg($newimage,$tsrc);
              chmod("$tsrc",0666);
          } else {
            $retObj->resultText = "File is no image";
          }
          $retObj->nextID = getNextID("files",$fileid);
          
        } else {
          $retObj->error = "File-ID does not exist";
          $retObj->nextID = getNextID("files",$fileid);
        }
      } else {
        debugTextOutput("File id is no integer!!!");
        $retObj->error = "File Id is no integer";
      }
    } else if (isset($_GET["checkFiles"]) and isset($_GET["fileid"])) { 
      //check if Files in DB exist
      debugTextOutput("Checking Files and Files-DB");
      $fileid = (int)$_GET["fileid"];
      if (is_integer($fileid) and $fileid>0) {
        debugTextOutput("fileid in Ordnung");
        $row = getSingleTableRow("files",$fileid);
        debugTextOutput("Row as json: ".json_encode($row));
        if ($row) { //result erhalten
          if (!file_exists($row['place'])) {
            $retObj->resultText = $row['name']." : ".$row['place']." does not exist - deleting table row";
            if (delTableRow("files",$row['rowid'])) {
              $retObj->resultText .= "-done";
            } else {
              $retObj->resultText .= "-failed";
            }
          } else {
            $retObj->resultText = $row['name']." : ".$row['place']." exists";
            if (isset($_GET['withMimeType']) and $_GET['withMimeType']=="true") {
              $retObj->resultText .= "- with MimeType ";
              updateTableRow("files",$row['rowid'],"mimetype",mime_content_type($row['place']));
            }
          }
        } else {
          $retObj->resultText = "File does not exist in DB";
        }
        $retObj->nextID = getNextID("files",$fileid);
      } else {
        $retObj->resultText = "Invalid File-ID";
        $retObj->error = "Invalid File-ID";
        $retObj->nextID = -1;
      }
    } else if (isset($_GET["insObjekt"])) {
      //TODO check if user is logged in and allowed to do or admin logged in 
      //TODO Validate input fields
      $id = (int)$_GET["objid"];  // numeric 
      $bez = $_GET["bez"];   // String - text
      $anz = (int)$_GET["anz"];   // numeric
      $ort = (int)$_GET["ortid"]; // numeric
      $bild = (int)$_GET["bildid"]; //number
      debugTextOutput("Insert or Edit Objekt");
      if (is_integer($anz)) {
        if (insertUpdateObjekt($bez,$anz,$bild)>=0) {
          $retObj->resultText = "done";
        } else {
          $retObj->resultText = "failed";
        }
      }
    } else if (isset($_GET["test"])) { // hier haben wir eine Testabfrage zum ausprobieren
      debugTextOutput("Testabfrage ausführen!!");
      //$retObj = getTableToSQL("SELECT rowid,*,MAX(strftime('%s',created)) AS created_seconds FROM files;");
      //$retObj = getNextID("files",120);
      //$retObj = getStringToOrtId(5);
      include("Bild_class.php");
      debugTextOutput(json_encode(new Bild()));
    }
  }


$myJSON = json_encode($retObj);

echo $myJSON;
?>
