<?php
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
  <title>Family-Food-Control</title>
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
    // für Testzwecke unset session variable
    //unset($_SESSION["clientid"]);
    //console_log("Client-ID gelöscht: ".$_SESSION["clientid"]);
    //session_destroy();
    console_log("Session-ID: ".session_id());
    
    //Variablen anlegen und leer setzen
    $bildname = "";
    $message_info = $message_err = "";
    define("Z_SCHIFFSWAHL",2);
    define("Z_BKEINGABE",1);
    $zustand = Z_BKEINGABE;
    
   //Open-and-prepare database
    require_once("sqlite_inc.php");
    doChecks();

   if(!$db) {
      echo $db->lastErrorMsg();
   } else {
      console_log( "Opened database successfully");
   }

   //Client registrieren
    if (!changeToClientID($_SESSION["clientid"])) { //war nicht schon registriert
      if (!setClientIDUndInselTyp()) { //Registrierung fehlgeschlagen
        $message_err = "Client konnte nicht angemeldet werden, evtl. maximale Anzahl (".MAXCLIENTS.") überschritten...";
      }
    }
    

   
 
    
    // Processing get-data when form is submitted
    if($_SERVER["REQUEST_METHOD"] == "GET") {
      if (isset($_GET["neueBK"])) { //hier soll eine neue Bordkarte erzeugt werden
        if (isEnabled("allowBordCardCreation") && ($bknr = gibNeueBordkartenNummer())) {
          $message_info = "Neue Bordkarte mit der Nummer ".$bknr." erstellt - du befindest dich auf Pirates' Island";
        } else { //Neue Bordkarte konnte nicht erstellt werden
          $message_err = "Erstellen einer neuen Bordkarte nicht möglich - evtl. Maximum (".MAXBK.") überschritten";
        }
      } 
     }
    
    
?>

<body>
<nav class="navbar navbar-expand-sm bg-dark navbar-dark fixed-top">
  <div class="collapse navbar-collapse" id="collapsibleNavbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="ffc.php">Home</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin.php">Admin</a>
      </li>    
      <li class="nav-item">
        <a class="nav-link" data-toggle="modal" href="#infoModal">Info</a>
      </li>          
    </ul>
  </div>  
  <a class="navbar-brand ml-auto" href="#">Einkaufsliste</a><span class="badge badge-light"><?php echo 'Client-ID:'.$_SESSION["clientid"]; ?></span>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
</nav>

<div class="container" style="margin-top:80px">
  <?php
  if ($message_info!="") {
    echo "<div class=\"alert alert-success alert-dismissible\">";
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
    <div class="col-sm-4 mx-auto">
      <h5 id="slname">Einkaufsliste - Name</h5>
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
        <button type="button" class="btn btn-primary" onclick="console.log(fccdaten.artikel);">Test</button>
        <button type="button" id="btnAdd" class="btn btn-primary" onclick="showAddForm()">Add</button>
        <button type="button" id="btnHideAdd" class="btn btn-primary" onclick="hideAddForm()" style="display:none">Hide Add</button>
      </form> 
      <hr class="d-sm-none">
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
        <h4 class="modal-title">Information zu endliche Automaten mit Treasure Island</h4>
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

</body>
</html>

<?php
    $db->close();
?>
