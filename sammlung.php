<?php
  /* Dies ist das Hauptprogramm mit dem Auswahlmenü und der Darstellung aller optionen */
  //debug-Optionen
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  ini_set('error_log', './ERROR.LOG');
  error_reporting(E_ALL & ~E_NOTICE);
  
  require_once("config.php"); // konfiguration lesen

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
</head>
<?php
   
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
    
    //session_destroy();
    console_log("Session-ID: ".session_id());
    console_log("PHP-Version: ".phpversion());
    console_log_json($_SESSION);
    
    //Variablen anlegen und leer setzen
    $message_info = $message_err = "";
    // Zustände
    define("Z_SHOWTHEMEN",2);  //Themenübersicht anzeigen
    define("Z_SHOWOBJEKTELIST",1);
    define("Z_UPLOADDIALOGUE",3); //show upload dialogue
    define("Z_SHOWFILELIST",4);
    $zustand = Z_SHOWTHEMEN;
    
   //Open-and-prepare database
    require_once("sqlite_inc.php");
    doChecks();
    console_log("File Path 1: ".getFilePathFromFileID(1));

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
          $zustand = Z_SHOWTHEMEN;
        } else if($_GET["show"]==="objekte") {
          console_log("Objekte werden angezeigt");
          $zustand = Z_SHOWOBJEKTELIST;
        } else if($_GET["show"]==="upload") {
          console_log("Uploaddialogue is shown");
          $zustand = Z_UPLOADDIALOGUE;
        } else if($_GET["show"]==="files") {
          console_log("Filelist is shown");
          $zustand = Z_SHOWFILELIST;
        }        
      }
      if (isset($_GET["neueBK"])) { //hier soll eine neue Bordkarte erzeugt werden
        if (isEnabled("allowBordCardCreation") && ($bknr = gibNeueBordkartenNummer())) {
          $message_info = "Neue Bordkarte mit der Nummer ".$bknr." erstellt - du befindest dich auf Pirates' Island";
        } else { //Neue Bordkarte konnte nicht erstellt werden
          $message_err = "Erstellen einer neuen Bordkarte nicht möglich - evtl. Maximum (".MAXBK.") überschritten";
        }
      } 
     }
    // Processing post-data when form is submitted
    // hier passiert auch ggf. eine Neuanmeldung
    if($_SERVER["REQUEST_METHOD"] == "POST") {
      console_log("Server-requestmethod ist POST");
      console_log_json($_POST);
      if (isset($_POST['save'])) { // if save button on the form is clicked
        // name of the uploaded file
        $filename = $_FILES['myfile']['name'];
        logdb("File uploaded: ".$filename);
        console_log("File to upload: ".$filename);
        // get the file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        // the physical file on a temporary uploads directory on the server
        $file = $_FILES['myfile']['tmp_name'];
        $size = $_FILES['myfile']['size'];
        // destination of the file on the server
        $destination = UPLOADDIR.'/' . basename($file); //we keep the temporary name - original filename is stored in db
        console_log("  tmp_name: ".$file);
        console_log("  size: ".$size." - extension: ".$extension);
        if (!in_array($extension, ['zip', 'pdf', 'docx'])) {
          console_log("   ERROR - Wrong file extension");
          $message_err = "Your file extension must be .zip, .pdf or .docx";
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
          $userid = getUserIdOf($_POST["user"],$_POST["pass"]);
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

<body>
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
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=objekte">Objekte</a>
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=files">Files</a>
        </div>
      </li>
      <!-- Dropdown Aktionen -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbardrop" data-toggle="dropdown">
          Aktionen
        </a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="<?php echo HOMEPAGE;?>?show=upload">Upload</a>
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
      // Zustand Themen anzeigen
      // Evtl. treeview ala https://www.w3schools.com/howto/howto_js_treeview.asp
      if ($zustand == Z_SHOWTHEMEN || $zustand == Z_SHOWOBJEKTELIST || $zustand == Z_SHOWFILELIST) {
        switch($zustand) {
          case Z_SHOWTHEMEN:
            echo '<h5 id="tblname">Themen&uuml;bersicht</h5>';
            $name = "themenfelder";
            break;
          case Z_SHOWFILELIST:
            echo '<h5 id="tblname">Files</h5>';
            $name = "files";
            break;
          case Z_SHOWOBJEKTELIST:
          default:
            echo '<h5 id="tblname">Objekt&uuml;bersicht</h5>';
            $name = "objekte";
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
                  echo "<a href=\"?delrow=".$row[0]."&table=".$name."&showtables\" class=\"text-danger\" role=\"button\">&times;</a>";
                  echo "<a href=\"?changerow=".$row[0]."&table=".$name."\">".$row[0]."</a></td>\n";
                } elseif ($zustand == Z_SHOWFILELIST && $res->columnName($i)=="name") {
                  echo "<a href=\"download.php?file=".$row[0]."\" target=\"_blank\">".$row[$i]."</a></td>\n";
                } else {
                  echo $row[$i]."</td>\n";
                }
              }		
              echo "</tr>\n";
            }
          echo "</tbody></table></div>\n";
        }
      } else if ($zustand ==Z_UPLOADDIALOGUE) {
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
      } else{
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
