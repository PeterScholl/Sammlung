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
  
  //Use Bild-Class
  require_once("Bild_class.php");

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
      logdb(json_encode($_GET));
      //$fileid = (int)$_GET["fileid"];
      $force = false;
      if (isset($_GET["force"])) {
        $force = filter_input(INPUT_GET,'force',FILTER_VALIDATE_BOOLEAN);
      }
      $fileid = intval(filter_input(INPUT_GET,'fileid',FILTER_SANITIZE_NUMBER_INT));
      $result = Bild::generateThumbnailToFileId($fileid,$force=$force);
      if ($result->value == 0) { // worked
        $retObj->resultText = $result->message;
      } else {
        $retObj->error = $result->message;
        $retObj->resultText = $result->message;
      }
      $retObj->nextID = getNextID("files",$fileid);
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
      if (insertUpdateObjekt($bez,$bild)>=0) {
        $retObj->resultText = "done";
      } else {
        $retObj->resultText = "failed";
      }
    } else if (isset($_GET["getObjektData"])) { // Daten holen für die Objektdarstellung
      // Optionen mit Orten, Limits, Filter müssen irgendwie eingebaut werden
      $limit = filter_input(INPUT_GET,'limit',FILTER_SANITIZE_NUMBER_INT);
      if ($limit < 5 or $limit > 50) {$limit = 20;}
      $offset = filter_input(INPUT_GET,'offset',FILTER_SANITIZE_NUMBER_INT);
      $mitOrt = filter_input(INPUT_GET,'mitOrt',FILTER_VALIDATE_BOOLEAN);
      $mitDokument = filter_input(INPUT_GET,'mitDoc',FILTER_VALIDATE_BOOLEAN);
      debugTextOutput("Limit: ".$limit." mitOrt: ".$mitOrt." mitDokument: ".$mitDokument. " offset: ".$offset);
      $retObj=getObjekte($offset, $limit, $mitOrt, $mitDokument);
      
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
