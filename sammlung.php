<?php
  /* Dies ist das Hauptprogramm mit dem Auswahlmenü und der Darstellung aller optionen */
  //debug-Optionen
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  ini_set('error_log', './ERROR.LOG');
  error_reporting(E_ALL & ~E_NOTICE);
  
  require_once("config.php"); // konfiguration lesen
  require_once("Bild_class.php");

  // Initialize the session
  session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Sammlungsverwaltung</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="./fcc.js"></script>
  <script>window.onload=init();</script>
  <style>
ul, #myUL {
  list-style-type: none;
}

#myUL {
  margin: 0;
  padding: 0;
}

.caret {
  cursor: pointer;
  -webkit-user-select: none; /* Safari 3.1+ */
  -moz-user-select: none; /* Firefox 2+ */
  -ms-user-select: none; /* IE 10+ */
  user-select: none;
}

.caret::before {
  content: "\25B6";
  color: black;
  display: inline-block;
  margin-right: 6px;
}

.caret-down::before {
  -ms-transform: rotate(90deg); /* IE 9 */
  -webkit-transform: rotate(90deg); /* Safari */'
  transform: rotate(90deg);  
}

.nested {
  display: none;
}

.active {
  display: block;
}
</style>
</head>
<?php
   
    
    //session_destroy();
    console_log("Session-ID: ".session_id());
    console_log("PHP-Version: ".phpversion());
    console_log_json($_SESSION);
    
    //Variablen anlegen und leer setzen
    $message_info = $message_err = "";
    // Zustände
    define("Z_SHOWTHEMEN",2);  //Themenübersicht anzeigen
    define("Z_SHOWOBJEKTELIST",1);
    define("Z_SHOWSIMPLEOBJEKTELIST",8); //Einfache Objekteliste
    define("Z_SHOWORTE",7);  //Orte anzeigen
    define("Z_UPLOADDIALOGUE",3); //show upload dialogue
    define("Z_SHOWFILELIST",4);
    define("Z_EDITTHEME",5); //edit or add themes
    define("Z_INSERTOBJ",6); //insert Obj themes
    if (!isset($_SESSION["zustand"])) {
      $_SESSION["zustand"] = Z_SHOWTHEMEN;
    }
    
   //Open-and-prepare database
    require_once("sqlite_inc.php");
    doChecks();

   if(!$db) {
      echo $db->lastErrorMsg();
   } else {
      console_log( "Opened database successfully");
   }

   //TODO: decide if user-authentication at this point is really needed
    
    // Processing get-data when form is submitted
    if($_SERVER["REQUEST_METHOD"] == "GET") {
      if (isset($_GET["show"])) { //hier soll die Ansicht ausgewählt werden
        if($_GET["show"]==="themen") {
          console_log("Themen werden angezeigt");
          $_SESSION["zustand"] = Z_SHOWTHEMEN;
        } else if($_GET["show"]==="orte") {
          console_log("Orte werden angezeigt");
          $_SESSION["zustand"] = Z_SHOWORTE;
        } else if($_GET["show"]==="objekte") {
          console_log("Objekte werden angezeigt");
          $_SESSION["zustand"] = Z_SHOWOBJEKTELIST;
        } else if($_GET["show"]==="objekteSimple") {
          console_log("Objekte werden als einfache Tabelle angezeigt");
          $_SESSION["zustand"] = Z_SHOWSIMPLEOBJEKTELIST;
        } else if($_GET["show"]==="upload") {
          console_log("Uploaddialogue is shown");
          $_SESSION["zustand"] = Z_UPLOADDIALOGUE;
        } else if($_GET["show"]==="files") {
          console_log("Filelist is shown");
          $_SESSION["zustand"] = Z_SHOWFILELIST;
        } else if($_GET["show"]==="edittheme") {
          console_log("Theme should be edited or added");
          $_SESSION["zustand"] = Z_EDITTHEME;  
        } else if($_GET["show"]==="insertObj") {
          console_log("Object should be added");
          $_SESSION["zustand"] = Z_INSERTOBJ;  
        }    
      }
      if (isset($_GET["neueBK"])) { //hier soll eine neue Bordkarte erzeugt werden
        if (isEnabled("allowBordCardCreation") && ($bknr = gibNeueBordkartenNummer())) {
          $message_info = "Neue Bordkarte mit der Nummer ".$bknr." erstellt - du befindest dich auf Pirates' Island";
        } else { //Neue Bordkarte konnte nicht erstellt werden
          $message_err = "Erstellen einer neuen Bordkarte nicht möglich - evtl. Maximum (".MAXBK.") überschritten";
        }
      }
      if (isset($_GET["delrow"])) { //hier soll eine Tabellenzeile gelöscht werden
        $rowid = filter_input(INPUT_GET, 'delrow', FILTER_VALIDATE_INT);
        $tablename = trim(filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING));
        //TODO: prüfen ob angemeldet
        //TODO: prüfen ob Benutzer berechtigt ist
        if ($tablename==="files") {
          console_log("  ein File soll gelöscht werden - noch TODO");
          
          if (unlink(getFilePathFromFileID($rowid))) {
            logdb("Fileid ".$rowid." - path: ".getFilePathFromFileID($rowid)." deleted");
            delTableRow($tablename,$rowid);
          } else {
            $message_err="Could not delete file....";
            logdb("ERROR Fileid ".$rowid." - path: ".getFilePathFromFileID($rowid)." could not be deleted");
          }  
        } else {
          $message_err="Deletion not implemented or not possible";
          logdb(" Deletion of row ".$rowid." from table ".$tablename." not allowed or not possible");
        }
      } 
 
     }
    // Processing post-data when form is submitted
    // hier passiert auch ggf. eine Neuanmeldung
    if($_SERVER["REQUEST_METHOD"] == "POST") {
      console_log("Server-requestmethod ist POST");
      console_log_json($_POST);
      if (isset($_POST['save'])) { // if save button on the File-Upload-form is clicked
        // name of the uploaded file
        // checks from https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
        $filename = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $_FILES['myfile']['name']);
        $filename = preg_replace("([\.]{2,})", '', $filename);
        logdb("File uploaded: ".$filename);
        console_log("File to upload: ".$filename);
        console_log_json($_FILES);
        // get the file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        // the physical file on a temporary uploads directory on the server
        $file = $_FILES['myfile']['tmp_name'];
        $size = $_FILES['myfile']['size'];
        // destination of the file on the server
        $destination = UPLOADDIR.'/' . basename($file); //we keep the temporary name - original filename is stored in db
        console_log("  tmp_name: ".$file);
        console_log("  size: ".$size." - extension: ".$extension);
        if ($_FILES['myfile']['error']>0) {         // check on error
          console_log("   ERROR - Error on upload - nr: ".$_FILES['myfile']['error']. "- see https://www.php.net/manual/de/features.file-upload.errors.php for details");
          $message_err = "Error - file too big or something else went wrong";
        } elseif (!in_array($extension, ['zip', 'pdf', 'docx', 'jpg', 'gif', 'png'])) {
          console_log("   ERROR - Wrong file extension");
          $message_err = "Your file extension must be .zip, .pdf, .docx, .jpg, .gif or .png";
        } elseif ($_FILES['myfile']['size'] > MAXUPLOADFILESIZE) { // file shouldn't be larger than defined in config.php
          console_log("   ERROR - File too large > ".MAXUPLOADFILESIZE);
          $message_err = "Your file is too large. Max: ".MAXUPLOADFILESIZE;
        } else if (getNrOfUploadsInLastHour() > MAXUPLOADCOUNT) { // to many uploads
          console_log("   ERROR - too many uploads".getNrOfUploadsInLastHour());
          $message_err = "too many uploads in last hour";
        } else {
          console_log("  try to move uploaded file from ".htmlspecialchars($file)." to ".htmlspecialchars($destination));
          // move the uploaded (temporary) file to the specified destination
          // TODO: Check if file exists...
          while(file_exists($destination) && strlen(basename($destination)) < 20) {
            $destination=$destination."a";
          }
          if (move_uploaded_file($file, $destination)) {
            console_log("   success");
            storeUploadedFileInDB($filename, $destination, $size);
            $message_info="File ".htmlspecialchars($filename)." uploaded";
          } else {
            console_log("   failed");
            $message_err="Failed to upload file.";
          }
        }
      } else if (isset($_POST['insertobj'])) { // objekt einfügen
        console_log_json($_POST);
        $b = Bild::generateBildFromUpload($_FILES['bildfile']);
        console_log($b->$status." - ".$b->$errmsg);
        if ($b->$status == 0) { // everything went fine on generating the picture (Bild)
          $temp_res = Bild::generateThumbnailToFileId($b->getDbID());
          if ($temp_res->value<>0) {
            console_log("ERROR on thumbnail generation: ".$temp_res->message);
          }
          $bez = filter_input(INPUT_POST,'bezeichnung',FILTER_SANITIZE_STRING);
          // Bezeichnung mit Bild-ID in DB eintragen
          $objid = insertUpdateObjekt($bez,$b->getDbID());
          if ($objid < 0) {
            console_log("ERROR on inserting objekt in DB");
            $message_err = "Object ".$bez." konnte nicht in DB eingetragen werden";
          } else {
            $message_info = "Object ".$bez." in DB eingetragen";
            // Wenn Anzahl UND Ort gesetzt ist
            if (isset($_POST['ort'])) {
              $_SESSION['aktort']=intval(filter_input(INPUT_POST,'ort',FILTER_SANITIZE_NUMBER_INT));
            }
            // - Verknüpfung von Anzahl und Ort eintragen
            if (isset($_POST['anzahl']) and filter_input(INPUT_POST,'anzahl',FILTER_SANITIZE_NUMBER_INT) > 0 and filter_input(INPUT_POST,'ort',FILTER_SANITIZE_NUMBER_INT)>=0) {
              addTableRow("objektAnOrt", array("objektID" =>$objid, "ortID"=>filter_input(INPUT_POST,'ort',FILTER_SANITIZE_NUMBER_INT), "anzahl"=>filter_input(INPUT_POST,'anzahl',FILTER_SANITIZE_NUMBER_INT)));
              $message_info = "Object ".$bez." mit Anzahl und Ort in DB eingetragen";
            }
          }           
        } else { // no bild could be generated
          //TODO: Decide wether the object should be inserted without picture or nothing should be done
          
        }
      } else if (isset($_POST["edittheme"])) { //Thema soll editiert oder angelegt werden
        console_log("Thema anlegen - siehe post-Variablen");
        logdb("Thema anlegen...");
        if (isset($_POST["editid"]) && intval($_POST["editid"])>0) {
          console_log("Theme should be edited: ".filter_input(INPUT_POST,'editid',FILTER_VALIDATE_INT));
        } else {
          console_log("New theme");
          // check if bezeichnung and superthema are valid
          if (insertUpdateTheme(filter_input(INPUT_POST,'bezeichnung',FILTER_SANITIZE_STRING), filter_input(INPUT_POST,'supertheme',FILTER_VALIDATE_INT))<0) {
            $message_err="Thema konnte nicht erstellt werden";
          }
        }        
      } else if (isset($_POST["logout"])) { //Benutzer soll abgemeldet werden
        logdb("User ".$_SESSION["username"]." with ".$_SESSION["user"]." logged off");
        $userid = -1;
        $_SESSION["user"] = -1;
        $_SESSION["username"] = "";
        session_destroy();
      } else if (isset($_POST["login"])) { //Benutzer soll angemeldet werden
        console_log("Anmeldeversuch");
        if (isset($_POST["user"]) && isset($_POST["pass"])) { // Benutzername und Passwort wurden mitgeschickt
          console_log("Jetzt (sollte) angemeldet werden");
          $userid = getUserIdOf(filter_input(INPUT_POST,'user',FILTER_SANITIZE_STRING),filter_input(INPUT_POST,'pass',FILTER_SANITIZE_STRING));
          if ($userid < 0) { // Fehler
            $message_err = "Fehler bei der Anmeldung";
            if ($userid == -3) $message_err = "Anmeldung nicht möglich - falsches Passwort";
            if ($userid == -2) $message_err = "Anmeldung nicht möglich - unbekannter Nutzer";
          }
          $_SESSION["user"]=$userid;
        } else { // Daten für die Anmeldung fehlerhaft
          console_log("Anmeldeversuch fehlgeschlagen");
          $message_err = "Anmeldung nicht m&ouml;glich - unzureichende Anmeldedaten";
        }        
      }
    }
    
    
?>

<body onload="fillObjektTable()">
<nav class="navbar navbar-expand-sm bg-dark navbar-dark fixed-top">
  <div class="collapse navbar-collapse" id="collapsibleNavbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo HOMEPAGE;?>">Home</a>
      </li>
      <!-- Dropdown Ansichten -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbardrop" data-toggle="dropdown">
          Ansichten
        </a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=themen">Themen</a>
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=orte">Orte</a>
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=objekte">Objekte</a>
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=files">Files</a>
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=objekteSimple">Objekte (einfach)</a>
        </div>
      </li>
      <!-- Dropdown Aktionen -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbardrop" data-toggle="dropdown">
          Aktionen
        </a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=upload">Upload</a>
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=edittheme">Thema anlegen</a>
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=insertObj">Objekt anlegen</a>
        </div>
      </li>
      <!-- Admin und Info -->
      <li class="nav-item">
        <a class="nav-link" href="admin.php">Admin</a>
      </li>    
      <li class="nav-item">
        <a class="nav-link" data-toggle="modal" href="#infoModal">Info</a>
      </li>          
    </ul>
  </div>  
  <a class="navbar-brand ml-auto" href="#">Sammlungsverwaltung</a>
  <?php
    if ($_SESSION["user"]<1) {
      echo '<a class="badge badge-light" data-toggle="modal" href="#loginModal">anmelden</a>';
    } else {
      echo '<a class="badge badge-light" data-toggle="modal" href="#logoffModal">'.$_SESSION["username"].'</a>';      
      //TODO logoffModal fertig programmieren - Abmeldefunktion
    }
  ?>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
</nav>

<div class="container" style="margin-top:80px">
  <?php
  if ($message_info!="") {
    echo "<div id=\"message_info\" class=\"alert alert-success alert-dismissible\">";
    echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
    echo "<strong>".$message_info."</strong></div>";
  }
  if ($message_err!="") {
    echo "<div class=\"alert alert-danger alert-dismissible\">";
    echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
    echo "<strong>".$message_err."</strong></div>";
  }
  ?>
  <div class="row">
    <div class="col-sm-12 mx-auto">
      <?php
      // alle Zustände, bei denen keine spezielle Tabelle angezeigt werden muss
      // Evtl. treeview ala https://www.w3schools.com/howto/howto_js_treeview.asp
      if ($_SESSION["zustand"] == Z_SHOWSIMPLEOBJEKTELIST || $_SESSION["zustand"] == Z_SHOWFILELIST) {
        switch($_SESSION["zustand"]) {
          case Z_SHOWFILELIST:
            echo '<h5 id="tblname">Files</h5>';
            $name = "files";
            break;
          case Z_SHOWSIMPLEOBJEKTELIST:
          default:
            echo '<h5 id="tblname">Objekt&uuml;bersicht</h5>';
            $name = "objekt";
            break;
        }
        $sql = "SELECT rowid,* FROM ".$name . ";";
        if ($res = $db->query($sql)) {
         echo "<div class=\"table-responsive\"><table class=\"table\"><thead><tr>\n";
            for($i = 0; $i<$res->numColumns(); $i++) {
              echo "<th>".$res->columnName($i)."</th>\n";			
            }
          echo "</tr></thead><tbody>\n";
            while($row = $res->fetchArray(SQLITE3_NUM)) {
              echo "<tr>";
              for($i = 0; $i<$res->numColumns(); $i++) {
                echo "<td>";
                if ($i==0) {
                  //echo "<a href=\"?delrow=".$row[0]."&table=".$name."&showtables\" class=\"text-danger\" role=\"button\">&times;</a>";
                  echo "<a onclick=\"setRowDeleteModal('".$row[0]."','".$name."');document.getElementById('confirmRowDelete').style.display='block'\" class=\"text-danger\" role=\"button\">&times;</a>";
                  echo "<a href=\"?changerow=".$row[0]."&table=".$name."\">".$row[0]."</a></td>\n";
                } elseif ($_SESSION["zustand"] == Z_SHOWFILELIST && $res->columnName($i)=="name") {
                  echo "<a href=\"download.php?file=".$row[0]."\" target=\"_blank\">".$row[$i]."</a></td>\n";
                } elseif ($_SESSION["zustand"] == Z_SHOWOBJEKTELIST && $res->columnName($i)=="bild") {
                  $b = Bild::fetchBildFromDB($row[$i]);
                  console_log_json($b->getThumbnail());
                  console_log("image: ".$row[$i]." - Thumbnail: ".$b->getThumbnail()->getURL());
                  echo "<img src=\"".$b->getThumbnail()->getURL()."\"></td>\n";
                } else {
                  echo $row[$i]."</td>\n";
                }
              }		
              echo "</tr>\n";
            }
          echo "</tbody></table></div>\n";
        }
      } else if ($_SESSION["zustand"] == Z_SHOWOBJEKTELIST) {
        if (!isset($_SESSION["limitPerPage"]) || !is_int($_SESSION["limitPerPage"]) || $_SESSION["limitPerPage"] > 50) {
          $_SESSION["limitPerPage"]=20;
        }
        //Objektübersicht
        ?>
        <div class="mb-2">
          <h5>Objekt&uuml;bersicht</h5>
          <button type="button" class="btn btn-primary btn-sm" onclick="toggleContentDisplayById('ObjekteFilter')">Filter</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="toggleContentDisplayById('ObjekteAnsicht')">Ansicht</button>
        </div>
        <?php
        //Menü für Filter
        ?>
        <div id="ObjekteFilter" style="display:none">
          Hier kommt die Form um den Filter anzupassen
        </div>
        <?php
        //Menü für Ansicht
        ?>
        <div id="ObjekteAnsicht" style="display:none">
          <div class="row gy-2 gx-3 align-items-center">
            <div class="col-auto">
              <div class="form-outline">
                <label class="form-label">Spalten ausw&auml;hlen</label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpalteID" checked />
                <label class="form-check-label" for="ObjekteSpalteID"> ID </label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpalteBezeichnung" checked />
                <label class="form-check-label" for="ObjekteSpalteBezeichnung"> Bezeichnung </label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpalteBild" checked />
                <label class="form-check-label" for="ObjekteSpalteBild"> Bild </label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpalteOrte" checked />
                <label class="form-check-label" for="ObjekteSpalteOrte"> Orte </label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpalteDokumente" checked />
                <label class="form-check-label" for="ObjekteSpalteDokumente"> Dokumente </label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpalteSortID" checked />
                <label class="form-check-label" for="ObjekteSpalteSortID"> SortID </label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpaltecreated" />
                <label class="form-check-label" for="ObjekteSpaltecreated"> created </label>
              </div>
            </div>
            <div class="col-auto">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="ObjekteSpalteedited" />
                <label class="form-check-label" for="ObjekteSpalteedited"> edited </label>
              </div>
            </div>
            <div class="col-auto">
              <label for="limit" class="mr-2">Limit: </label>
              <input type="number" class="form-inline" id="limit" size="3" name="username" value="10" min="5" max="50">
            </div>
          </div>
        </div>
        <?php
        //hier die eigentliche Tabelle aufbauen
        ?>
        <div class="table-responsive">
          <table class="table" id="tableOfObjekte">
            <thead><tr>
              <th>ID</th>
              <th>Bezeichnung</th>
              <th>Bild</th>
              <th>Ort(e)</th>
              <th>Dokumente</th>
              <th>SortID</th>
              <th>created</th>
              <th>edited</th>
            </tr></thead>
            <tbody id="tableOfObjekteBody">
              <tr><td>1</td></tr>
              <tr><td>2</td></tr>
              <tr><td>3</td></tr>
              <tr><td>4</td></tr>
              <tr><td>5</td></tr>
            </tbody>
          </table>
        </div>
        <div id="ObjekteNavigation">
          <a href="">&lt;&lt;</a>
          <a href="">&lt;</a>
          <a href="">&gt;</a>
          <a href="">&gt;&gt;</a>
        </div>
        <?php
        
        //nach Aufbau der Seite durch Javascript und AjaxJsonData auffüllen lassen
        // z.B. mit <table onload="fillObjektTable()">

      } else if ($_SESSION["zustand"] ==Z_SHOWTHEMEN) {
        // Zustand Themen anzeigen
        echo '<h5 id="tblname">Themen&uuml;bersicht</h5>';
        //generate as nested unordered list
        printThemenListeAsUL(-1);
        ?>
        <script>
        var toggler = document.getElementsByClassName("caret");
        var i;

        for (i = 0; i < toggler.length; i++) {
          toggler[i].addEventListener("click", function() {
            this.parentElement.querySelector(".nested").classList.toggle("active");
            this.classList.toggle("caret-down");
          });
        }
        </script>
        <?php
      } else if ($_SESSION["zustand"] ==Z_UPLOADDIALOGUE) {
        //Uploaddialogue
        ?>
        <div class="container">
          <h2>Upload File</h2>
          <p>Bitte die Datei hochladen</p>
          <form action="./<?php echo HOMEPAGE;?>" method="post" enctype="multipart/form-data">
            <div class="custom-file mb-3">
              <input type="file" class="custom-file-input" id="customFile" name="myfile">
              <label class="custom-file-label" for="customFile">Datei waehlen</label>
            </div>
            
            <div class="mt-3">
              <button type="submit" name="save" class="btn btn-primary">Save</button>
            </div>
          </form>
        </div>
        <script>
        // the following code makes the name of the file appear on select
        $(".custom-file-input").on("change", function() {
          var fileName = $(this).val().split("\\").pop();
          $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });
        </script>       
        <?php
      } else if ($_SESSION["zustand"] ==Z_SHOWORTE) {
        // Zustand Orte anzeigen
        echo '<h5 id="tblname">&Uuml;bersicht Orte</h5>';
        //generate as nested unordered list
        printOrteListeAsUL(-1);
        ?>
        <script>
        var toggler = document.getElementsByClassName("caret");
        var i;

        for (i = 0; i < toggler.length; i++) {
          toggler[i].addEventListener("click", function() {
            this.parentElement.querySelector(".nested").classList.toggle("active");
            this.classList.toggle("caret-down");
          });
        }
        </script>
        <?php
      } else if ($_SESSION["zustand"] ==Z_EDITTHEME) {
        // Thema editieren oder anlegen
        $superthema="Keins";
        if (isset($_GET["themaid"])) { 
          //thema soll editiert werden
          $editid = intval($_GET["themaid"]);
          echo '<h5>Thema editieren - ID '.$editid.'</h5>';
          $zeile = getSingleTableRow("themen",$editid);
          $bezvorgabe=htmlspecialchars($zeile["bezeichnung"]);
          if ($zeile["superthema"]!=-1) {
            $zeile = getSingleTableRow("themen",intval($zeile["superthema"]));
            $superthema=htmlspecialchars($zeile["bezeichnung"]);
            $superthemaId=intval($zeile["rowid"]);
          } 
        } else {
          echo '<h5>Thema anlegen</h5>';
          $bezvorgabe="&lt;Neues Thema&gt;";
          $superthemaId=-1;
        }
        ?>
        <div id="EditOrAddTheme" class="form-group">
          <form class="form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <?php echo '<input type="hidden" name="editid" value="'.$editid.'">'; ?>
            <div class="form-row">
              <label for="supertheme" class="mt-2 mb-0">Überthema</label>
              <select class="form-control" placeholder="<?php echo $superthema; ?>" id="supertheme" name="supertheme">
                <option value="-1">Keins</option>
                <?php
                $array = getTableToSQL("SELECT rowid,bezeichnung FROM themen ORDER BY sort ASC");
                for ($i = 0; $i < count($array); $i++) {
                  echo '<option value="'.$array[$i]["rowid"].'"'.($i==$superthemaId?' selected="selected"':'').'>'.htmlspecialchars($array[$i]["bezeichnung"]).'</option>'."\n";
                }
                ?>
              </select>
            </div>
            <div class="form-row">
              <label for="bezeichnung" class="mt-2 mb-0">Bezeichnung</label>
              <input type="text" class="form-control" placeholder="<?php echo $bezvorgabe; ?>" name="bezeichnung">
            </div>
            <div class="form-row mt-2">
              <button type="submit" class="btn btn-primary" id="edittheme" name="edittheme">Submit</button>
            </div>
          </form>
        </div>
        <?php
      } else if ($_SESSION["zustand"] ==Z_INSERTOBJ) {
        // Objekt anlegen (TODO: oder später auch Editieren?!)
        if (isset($_GET["editid"])) { 
          //Objekt soll editiert werden
          $editid = intval($_GET["editid"]);
        } else {
          // Objekt wird neu angelegt
        }
        ?>
        <h5>Objekt editieren oder anlegen</h5>
        <div id="EditOrAddObject" class="form-group">
          <form name="EditOrAddObject" class="form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <?php echo '<input type="hidden" name="editid" value="'.$editid.'">'; ?>
            <div class="form-row">
              <label for="bezeichnung" class="mt-2 mb-0">Bezeichnung</label>
              <input type="text" class="form-control" placeholder="<?php echo $bezvorgabe; ?>" name="bezeichnung">
            </div>
            <div class="form-row">
              <label for="anzahl" class="mt-2 mb-0">Anzahl (wenn gr&ouml;&szlig;er 0 wird auch der Ort eingetragen)</label>
              <input type="text" class="form-control" value="0" name="anzahl">
            </div>
            <div class="form-row">
              <label for="ort" class="mt-2 mb-0">Ort</label>
              <!-- <input type="text" class="form-control" placeholder="Wo" name="ort"> -->
              <select class="form-control" name="ort" id="ort">
                <?php
                printOrteAsSelection();
                ?>
              </select>
            </div>
            <div class="form-row">
              <label for="bildfile" class="mt-2 mb-0">Bild</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="customFile" name="bildfile">
                <label class="custom-file-label" for="customFile">Datei waehlen oder leer lassen</label>
              </div>
              <script>
              // the following code makes the name of the file appear on select
              $(".custom-file-input").on("change", function() {
                var fileName = $(this).val().split("\\").pop();
                $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
              });
              </script>
            </div>       
            <div class="form-row mt-2">
              <button type="submit" class="btn btn-primary mr-2" id="insertobj" name="insertobj">Mit Bild</button>
              <button type="button" class="btn btn-primary" onclick="insertEditObjekt()">Ohne Bild</button>
            </div>
          </form>
          <p id="insertObjResult"></p>
        </div>
        <?php
      } else {
      ?>
        <p>
          <table id="sort" class="table table-striped">
            <thead>
              <tr><th>Nr.</th><th>Bezeichnung</th><th>Menge</th><th>Einheit</th><th>erl.</th></tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </p>
        <hr>
        <div id="inputNewArticle" class="form-group" style="display:none">
          <label for="article" >Artikel</label>
          <input type="text" class="form-control" list="dl_articles" placeholder="Eier" id="article" name="article">
          <datalist id="dl_articles">
          </datalist>
          <label for="menge" style="display:none">Menge</label>
          <input type="text" class="form-control" placeholder="1" id="menge" name="menge" style="display:none">
          <label for="Einheit" style="display:none">Einheit</label>
          <input type="text" class="form-control" placeholder="St&uuml;ck" id="einheit" name="einheit" style="display:none">
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
        <button type="button" class="btn btn-primary" onclick="holeDaten()">Daten</button>
        <button type="button" id="btnAdd" class="btn btn-primary" onclick="showAddForm()">Add</button>
        <button type="button" id="btnHideAdd" class="btn btn-primary" onclick="hideAddForm()" style="display:none">Hide Add</button>
      </form> 
      <hr class="d-sm-none">
      <?php } ?>
    </div>
  </div>
</div>

<div class="container m-3">
  &nbsp;
</div>

<?php
  include("footer.html");
?>

<div id="confirmRowDelete" class="modal">
  <span onclick="document.getElementById('confirmRowDelete').style.display='none'" class="close" title="Close Modal">&times;</span>
  <form id="rowDeleteForm" class="modal-content" action="sammlung.php" method="get">
    <div class="container">
      <h1>Delete Row</h1>
      <p id="rowDeleteFormText">Are you sure you want to delete Row # in Table tabel?</p>

      <div class="clearfix">
        <button type="button" class="cancelbtn" onclick="document.getElementById('confirmRowDelete').style.display='none'">Cancel</button>
        <button id="confirmRowDeleteBtn" type="button" onclick="location.href='sammlung.php';" class="deletebtn">Delete</button>
      </div>
    </div>
  </form>
</div>
<div class="modal fade" id="infoModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Information zur Sammlungsverwaltung</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        Diese Webseite soll dazu dienen eine Physiksammlung zu verwalten und Informationen zu möglichen Experimenten zu finden!        
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Schliessen</button>
      </div>

    </div>
  </div>
</div>
 <div id="loginModal" class="modal fade" role="dialog">  
      <div class="modal-dialog">  
   <!-- Modal content-->  
           <div class="modal-content">  
                <div class="modal-header">  
                     <h4 class="modal-title">Login</h4>  
                     <button type="button" class="close" data-dismiss="modal">&times;</button>  
                </div>  
                <div class="modal-body">  
                  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                  <label>Username</label>  
                  <input type="text" name="user" id="username" class="form-control" />  
                  <br />  
                  <label>Password</label>  
                  <input type="password" name="pass" id="password" class="form-control" />  
                  <br />  
                  <input type="submit" name="login" id="login_button" class="btn btn-warning">
                  </form>
                </div>  
           </div>  
      </div>  
 </div>  
 <div id="logoffModal" class="modal fade" role="dialog">  
      <div class="modal-dialog">  
   <!-- Modal content-->  
           <div class="modal-content">  
                <!-- Modal Header -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-header">  
                     <h4 class="modal-title">Logout</h4>  
                     <button type="button" class="close" data-dismiss="modal">&times;</button>  
                </div>  
                <!-- Modal body -->
                <div class="modal-body">  
                  Abmelden
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                  <button type="submit" name="logout" id="logout_button" class="btn btn-primary">Abmelden</button>
                  <button type="button" class="btn btn-primary" data-dismiss="Abbrechen">Abbrechen</button>
                </div>  
                </form>
           </div>  
      </div>  
 </div>  

</body>
</html>

<?php
    $db->close();
?>
