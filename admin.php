<?php
  //debug-Optionen
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  ini_set('error_log', './ERROR.LOG');
  error_reporting(E_ALL & ~E_NOTICE);

  require_once("config.php"); // konfiguration lesen

    //Funktionen für Log-auf die Konsole
    function console_log_json( $data ){
        echo '<script>';
        echo 'console.log('. json_encode( $data ) .')';
        echo '</script>';
    }
    function console_log( $data ){
      echo '<script>';
      echo 'console.log("'. $data .'")';
      echo '</script>';
    }

    // Initialize the session
    session_start();
    // Check if the user is logged in, if not then redirect him to login page
    if(!isset($_SESSION["adminloggedin"]) || $_SESSION["adminloggedin"] !== true){
        header("location: adminlogin.php");
        exit;
    }
    // Process logout before anything else happens
    if($_SERVER["REQUEST_METHOD"] == "GET") {
      if(isset($_GET["logout"])) { // should log out
        $_SESSION = array(); //delete session-variables
        // Destroy the session.
        session_destroy();
        // Redirect to homepage
        header("location: ".HOMEPAGE);
        exit;
      }
    }
    
    //Open-and-prepare database
    require_once("sqlite_inc.php");

   if(!$db) {
      echo $db->lastErrorMsg(); //does this work $db seems to be null
   } else {
      //console_log( "Opened database successfully");
   }
   
    
    // Define variables and initialize with empty values
    $username = $password = "";
    $username_err = $password_err = $login_err = "";
    $result = $result_err = "";
    $showtables = false;
    
    // Processing get-data when form is submitted
    if($_SERVER["REQUEST_METHOD"] == "GET") {
      //TODO: Tabellen für Sammlung eintragen
      if (isset($_GET["deleteSQLtables"])) {
        $sql =<<<EOF
      DROP TABLE inseln;
      DROP TABLE clients;
      DROP TABLE piraten;
      DROP TABLE enable_options;
EOF;
        $ret = $db->exec($sql);
         if(!$ret) {
            $result_err = $db->lastErrorMsg();
         } else {
            $result = "Tabellen gelöscht";
         }
      } else if (isset($_GET["showtables"])) {
        $show='tables';
      } else if (isset($_GET["setuserpass"])) {
        $show='setuserpass';
      } else if (isset($_GET["options"])) {
        $show='options';
        if (isset($_GET["changerow"])) {
          //Change the specified option
          changeOptionWithID(filter_input(INPUT_GET, 'changerow', FILTER_VALIDATE_INT));
        }
      } else if (isset($_GET["checkFiles"])) { //hier soll die Konsistenz zwischen Dateien und Datenbank geprüft werden
        $show='checkFiles';
      } else if (isset($_GET["wartung"])) { //Generate Thumbnails to all images
        $show='wartung'; 
      } 
      if (isset($_GET["delrow"])) { //hier soll eine Tabellenzeile gelöscht werden
        $rowid = filter_input(INPUT_GET, 'delrow', FILTER_VALIDATE_INT);
        $tablename = trim(filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING));
        $sql = "DELETE FROM ".$tablename." WHERE rowid=".$rowid.";";
        console_log("SQL: ".$sql);
        $db->exec($sql);
      } 
      if (isset($_GET["deltable"])) { //hier soll eine Tabelle gelöscht werden
        $tablename = trim(filter_input(INPUT_GET, 'deltable', FILTER_SANITIZE_STRING));
        $sql = "DROP TABLE ".$tablename.";";
        console_log("SQL: ".$sql);
        $db->exec($sql);
      }
      if (isset($_GET["updaterow"]) && isset($_GET["rowid"]) && isset($_GET["table"])) { 
        //hier ist eine Tabellenzeile zu aktualisieren
        foreach (array_keys($_GET) as $key) {
          if (!in_array($key, ['table','rowid','updaterow'])) {
            //console_log($key." - wird aktualisiert");
            updateTableRow($_GET['table'], $_GET['rowid'], $key, $_GET[$key]);
          }
        }
        $show='tables';
      }
      if (isset($_GET["addrow"]) && isset($_GET["table"])) { 
        //hier ist eine Tabellenzeile neu anzulegen
        console_log("Neue Tabellenzeile anlegen....");
        $data = array();
        foreach ($_GET as $k => $v) {
          console_log("Key:".$k." Value:".$v);
          if (!is_null($v) && is_object($v)) {
            $data[$k] = clone $v;
          } else {
            $data[$k] = $v;
          }
        }
        console_log_json($data);
        console_log("das war noch in admin.php");
        addTableRow($_GET["table"],$data);
        $show='tables';
      }
      if (isset($_GET["changerow"]) && isset($_GET["table"])) {
        //Änderung einer Tabellenzeile vorbereiten
        console_log("GET-Befehl - Tabellenzeile aendern");
        $show='changeTableRow';
        $rowToChange = getSingleTableRow((filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING)),filter_input(INPUT_GET, 'changerow', FILTER_VALIDATE_INT));
        $tableToChange = (filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING));
        console_log("... in Tabelle: ".$tableToChange." bzw. ".$_GET["table"]);
      }
      if (isset($_GET["showaddrow"]) && isset($_GET["table"])) {
        //Anlegen einer Tabellenzeile vorbereiten
        console_log("GET-Befehl - Tabellenzeile anlegen");
        $show='addTableRow';
        $tableToAddRow = (filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING));
        console_log("... in Tabelle: ".$tableToAddRow." bzw. ".$_GET["table"]);
      }
    }
     
    // Processing form data when form is submitted
    if($_SERVER["REQUEST_METHOD"] == "POST"){
      if (isset($_POST["submitnewpass"]) && isset($_POST["username"]) && isset($_POST["passwort"])) { //New password is submitted
        console_log("Neues Passwort setzen");
        //hier muss das Passwort neu gesetzt werden
        setNewPassword($_POST["username"], $_POST["passwort"]);
        $show='tables';
      }        
    }

    
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <title>Sammlungsverwaltungstool-Admin</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="./fcc.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-sm bg-dark navbar-dark fixed-top">
  <a class="navbar-brand" href="#">Sammlungstool - Administration</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="collapsibleNavbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo HOMEPAGE;?>">Home</a>
      </li>
    </ul>
  </div>  
</nav>

<div class="container-fluid" style="margin-top:80px">
  <?php 
    if(!empty($result)){
      echo '<div class="alert alert-info">' . $result . '</div>';
    }        
    if(!empty($result_err)){
      echo '<div class="alert alert-danger">' . $result_err . '</div>';
    }        
  ?>


  <div class="row">
    <div class="col-sm-3">
      <h3>Admin Menu</h3>
      <ul class="nav nav-pills flex-column">
        <li class="nav-item">
          <a class="nav-link active" href="?logout">Logout</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="?deleteSQLtables">Tabellen l&ouml;schen</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="?showtables">Tabellen anzeigen</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="?setuserpass">Benutzerpasswort setzen</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="?options">Optionen</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="uploadForm.php">Upload File</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="?checkFiles">Check Files and FileDB</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="?wartung">Wartungsmen&uuml;</a>
        </li>
        <li class="nav-item">
          <a class="nav-link disabled" href="#">.. to be continued ..</a>
        </li>
      </ul>
      <hr class="d-sm-none">
    </div>
    <div class="col-sm-9">
    <?php
      if ($show=='tables') {
        echo "\n";
        //alle Tabellen ermitteln
        $tablesquery = $db->query("SELECT name FROM sqlite_master WHERE type='table';");
        $tables = array();

        while ($table = $tablesquery->fetchArray(SQLITE3_ASSOC)) {
          array_push($tables,$table['name']);
        }
        
        if (!empty($tables)) {
          foreach($tables as $name) {
            $sql = "SELECT rowid,* FROM ".$name . ";";
            if ($res = $db->query($sql)) {
              echo "<h4><a href=\"?deltable=".$name."&showtables\" class=\"text-danger\" role=\"button\">&times;</a>Tabelle ".$name;
              echo "<a href=\"?showaddrow&table=".$name."\" role=\"button\">&plus;</a></h4>\n";
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
                    } else {
                      echo $row[$i]."</td>\n";
                    }
                  }		
                  echo "</tr>\n";
                }
              echo "</tbody></table></div>\n";
            }
          }
        }
        echo "\n";
      } else if ($show=='options') {
        //Optionen aus Tabelle auslesen
        $sql = "SELECT rowid,* FROM enable_options;";
        if ($res = $db->query($sql)) {
          echo "<h4>Optionen einstellen</h4>\n";
          echo "<div class=\"table-responsive\"><table class=\"table\"><thead><tr>\n";
          echo "<th>Name</th><th>Wert</th><th>Beschreibung</th>\n";
          echo "</tr></thead><tbody>\n";
            while($row = $res->fetchArray(SQLITE3_BOTH)) {
              echo "<tr><td>".$row['name']."</td>\n";
              echo "<td><a href=\"?options&changerow=".$row['rowid']."\" class=\"text-important\">".($row['value']==1?"true":"false")."</a></td>\n";
              echo "<td>".$row['description_optional']."</td>\n";
              echo "</tr>\n";
            }
          echo "</tbody></table></div>\n";
        }
      } else if ($show=='changeTableRow') {
        //Formular erstellen
        echo "<h4>Tabelle: ".$tableToChange." - Zeile: ".$rowToChange['rowid']."</h4>\n";
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
            <?php foreach(array_keys($rowToChange) as $key) { 
              if ($key=='rowid') {
                echo "<input type=\"hidden\" name=\"rowid\" value=\"".$rowToChange['rowid']."\">";
              } else { ?>
            <div class="form-row">
              <div class="col">
                <label><?php echo $key; ?></label>
              </div>
              <div class="col">
                <input type="text" name="<?php echo $key; ?>" value="<?php echo $rowToChange[$key]; ?>">
              </div>
            </div>
            <?php }
            } ?>
            <div class="form-group">
              <input type="hidden" name="table" value="<?php echo $tableToChange; ?>">
              <input type="hidden" name="updaterow" value="<?php echo $rowToChange['rowid']; ?>">
              <input type="submit" class="btn btn-primary" value="Senden">
            </div>
        </form>
        <?php
      } else if ($show=='addTableRow') {
        //Formular erstellen
        echo "<h4>Tabelle: ".$tableToAddRow." - neuer Eintrag</h4>\n";
        // get an array of all keys of the table
        $columnnames=getColumnNames($tableToAddRow);
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
            <?php foreach($columnnames as $key) { 
              if ($key=='rowid') {
                echo "<input type=\"hidden\" name=\"rowid\" value=\"".$rowToChange['rowid']."\">";
              } else { ?>
            <div class="form-row">
              <div class="col">
                <label><?php echo $key; ?></label>
              </div>
              <div class="col">
                <input type="text" name="<?php echo $key; ?>" value="">
              </div>
            </div>
            <?php }
            } ?>
            <div class="form-group">
              <input type="hidden" name="table" value="<?php echo $tableToAddRow; ?>">
              <input type="hidden" name="addrow" value="">
              <input type="submit" class="btn btn-primary" value="Senden">
            </div>
        </form>
        <?php
      } else if ($show=='setuserpass') {
        ?>
        <p>Hier kann das Passwort eines Benutzers gesetzt werden</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
          <div class="form-row">
              <div class="col">
                <label>Benutzername</label>
              </div>
              <div class="col">
                <input type="text" name="username" value="hans">
              </div>
          </div>
          <div class="form-row">
              <div class="col">
                <label>neues Passwort</label>
              </div>
              <div class="col">
                <input type="text" name="passwort" value="pass">
              </div>
          </div>
          <div class="form-row">
            <button class="btn btn-primary" type="submit" name="submitnewpass" id="submit">Senden</button>
          </div>
        </form>
        <?php        
      } else if ($show=='checkFiles') {
        echo "<h1>Checking Files and Files-DB</h1><ul>";
        echo "<a href=\"?checkFiles=true&withMimeType=true\">checkWithMime</a>";
        $sql = "SELECT rowid,* FROM files;";
        if ($res = $db->query($sql)) {
          while($row = $res->fetchArray(SQLITE3_ASSOC)) {
            if (!file_exists($row['place'])) {
              echo "<li>".$row['name']." : ".$row['place']." does not exist - deleting table row";
              if (delTableRow("files",$row['rowid'])) {
                echo "-done </li>";
              } else {
                echo "-failed </li>";
              }
            } else {
              echo "<li>".$row['name']." : ".$row['place']." exists";
              if (isset($_GET['withMimeType'])) {
                echo " - with MimeType ";
                updateTableRow("files",$row['rowid'],"mimetype",mime_content_type($row['place']));
              }
              echo "</li>";
            }
          }
        } else {
          $message_err="Could not query filedb";
        }
        echo "</ul>";  
      } else if ($show=='wartung') {
        //Buttons und Ausgabefeld
        ?>
        <h4>Wartungsoptionen</h4>
        <button type="button" class="btn btn-primary" onclick="genThumbnailsWithLog(1)">Jetzt Thumbnails generieren</button>
        <button type="button" class="btn btn-primary" onclick="checkFilesWithLog(1,withMime=false)">Files pr&uuml;fen</button>
        <button type="button" class="btn btn-primary" onclick="checkFilesWithLog(1,withMime=true)">Files pr&uuml;fen - MimeType in DB generieren</button>
        <p id="wartungsoutput"></p>
        <?php
      }
    ?>
    </div>
  </div>
</div>

<?php
  include("footer.html");
?>

</body>
</html>

<?php
    $db->close();
?>
