<?php declare(strict_types=1); // strict requirement - Variablentypen werden geprüft!
  //alle Datenbankoperationen sollten hier als Funktion deklariert sein, so dass man 
  //diese für eine MySQL-Version austauschen könnte


  //Open the database
  //TODO-Fehler abfangen
  class MyDB extends SQLite3 {
    function __construct() {
       $this->open('sammlung.db');
    }
  }
  $db = new MyDB();
  // increased Timeout because of waiting for interfering sqlite-actions
  $db->busyTimeout(5000);
  $db->exec("PRAGMA busy_timeout=5000");
  
  
  // ---- ab hier folgen nur noch Funktionsdefinitionen ----
  // ---- from here on only function-definitions -----------
  
  // -- get the IP-Adress of the client
  function getUserIpAddr(){
    //function is used for logging in Database or recognizing different users
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }
  
  //ein SQL-Statement ausführen und die Tabelle zurückliefern
  //liefert manchmal limitierte Tabellen
  function getTableToSQL($sql) {
    // how to prevent code injection?!
    global $db;
    $array = [];
    $result = $db->query($sql);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      array_push($array,$row);
    }
    return $array;    
  }
  
  //eine vollstaendige Tabelle zurueckliefern
  function getTableAsArray($table, $where="1==1") {
    global $db;
    $array = [];
    $sql = "SELECT rowid,* FROM ".$table." WHERE ".$where.";";
    $result = $db->query($sql);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      array_push($array,$row);
    }
    return $array;    
  }
  
  //eine bestimmte Tabellenzeile zurückgeben 
  //Wenn $rowid = -1 dann wird nur die erste Tabellenzeile zurückgegeben ohne rowid
  function getSingleTableRow($table, $rowid) {
    global $db;
    //console_log("getSingleTableRow(".$table.",".$rowid.")");
    if ($rowid==-1) {
      $stmt = $db->prepare('SELECT * FROM '.$table.' LIMIT 1');
    } else {
      $stmt = $db->prepare('SELECT rowid,* FROM '.$table.' WHERE rowid=:id');
      //console_log("Anzahl Parameter in Statement: ".$stmt->paramCount());
      $stmt->bindValue(':id', $rowid, SQLITE3_INTEGER);
    }
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
  }
  
  //returns columnnames of an SQL-table as an Array
  function getColumnNames($tablename) {
    global $db;
    $colnames=[];
    $sql = "PRAGMA table_info('".$tablename."');";
    console_log("SQL: ".$sql);
    $res=$db->query($sql);
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) { // gefunden
      //$colnames.push($row['name']);
      array_push($colnames,$row['name']);
    }
    return $colnames;
  }
  
  //eine neue Zeile in eine Tabelle eintragen
  function addTableRow($table,$data) {
    global $db;
    console_log("in addTableRow...");
    console_log_json($data);
    $colNames = getColumnNames($table);
    if (empty($colNames)) { //tabelle existiert nicht
      console_log("Tabelle ".$table." existiert nicht!!!");
      return;
    }
    $strcol = "";
    $strval = "";
    foreach($colNames as $col) {
      console_log("Prüfe Spalte: ".$col);
      if (array_key_exists($col,$data)) { //zu diesem key gibt es Daten
        $strcol=$strcol.$col.",";
        $strval=$strval."'".$data[$col]."',";
      }
    }
    console_log("strval:".$strval);
    if (strlen($strcol)>0) {
      $sql = "INSERT INTO ".$table." (".substr($strcol,0,-1).") VALUES (".substr($strval,0,-1).");";
      console_log("Zeile wird eingetragen: ".$sql);
      $ret = $db->exec($sql);
      if(!$ret) {
        echo $db->lastErrorMsg();
      } else {
        console_log("enable_options eingetragen");
      }
    }    
    console_log("... verlasse addTableRow");
  }
  
  //einen bestimmten Wert in einer Tabelle ändern
  function updateTableRow($table, $rowid, $key, $value, $nocheck=false) {
    global $db;
    if ($nocheck || (ctype_alnum($key) && ctype_print($value))) {
      $stmt = $db->prepare('UPDATE '.$table.' SET '.$key.'=:value WHERE rowid=:id');
      //console_log("Anzahl Parameter in Statement: ".$stmt->paramCount());
      $stmt->bindValue(':id', $rowid, SQLITE3_INTEGER);
      //$stmt->bindValue(':key', $key, SQLITE3_TEXT);
      $stmt->bindValue(':value', $value, SQLITE3_TEXT);
      $result = $stmt->execute();
    } else {
      console_log("Unaccepted Values - key: ".$key." value ".$value);
    }
  }

  //wechsel zu wunsch-Client-ID wenn möglich
  function changeToClientID($pref_id) {
    global $db;
    if (!is_numeric($pref_id)) {
      $pref_id=-1;
    }
    console_log("Wechsel zu Wunsch-ID prüfen ".$pref_id);
    //Client in Datenbank suchen
    $sql = "select rowid,user from clients where session_id='".session_id()."' AND rowid=".$pref_id.";";
    console_log("SQL: ".$sql);
    $res=$db->query($sql);
    if ($row = $res->fetchArray(SQLITE3_ASSOC)) { // gefunden
      $sql = "UPDATE clients set lastedited=strftime('%Y-%m-%d %H:%M:%S','now') where rowid='".$row['rowid']."';";
      console_log("SQL: ".$sql);
      $res=$db->exec($sql);
      $_SESSION["clientid"]=$row['rowid'];
      $_SESSION["user"]=$row['user'];
      console_log("clientid: ".$_SESSION["clientid"]." user: ".$_SESSION["user"]);
      return true;
    }
    return false;
  }
  
  function setClientIDUndUser() {
        global $db;
        console_log("Session-ID setzen und in Datenbank registrieren - Inseltyp bestimmen");
        //Client in Datenbank suchen
        $sql = "select rowid,user from clients where session_id='".session_id()."';";
        console_log("SQL: ".$sql);
        $res=$db->query($sql);
        if ($row = $res->fetchArray(SQLITE3_ASSOC)) { // gefunden
          $sql = "UPDATE clients set lastedited=strftime('%Y-%m-%d %H:%M:%S','now') where rowid='".$row['rowid']."';";
          console_log("SQL: ".$sql);
          $res=$db->exec($sql);
          $_SESSION["clientid"]=$row['rowid'];
          $_SESSION["user"]=$row['user'];
          console_log("clientid: ".$_SESSION["clientid"]." user: ".$_SESSION["user"]);
        } else { // nicht gefunden
          return generateExtraClientID();
        }
        return true;  
  }
  
  //Benutzeranmeldedaten prüfen - gibt User-ID zurück (-1 wenn unbekannt)
  function userIdZuAnmeldedaten($user,$pass) {
    global $db;
    console_log("Benutzer anmelden");
    //User in Datenbank suchen
    $stmt = $db->prepare("SELECT rowid FROM users WHERE name=:name AND password=:pass");
    if ($stmt->bindValue(':name', $name, SQLITE3_TEXT) && $stmt->bindValue(':pass', $pass, SQLITE3_TEXT)) {
      set_error_handler(function() { /* ignore errors */ });
      console_log("  BindValues done");
      if ($result = $stmt->execute()) { //erfolgreich
        //console_log("  Statement executed: ".$stmt->getSQL(true));
        if ($row = $result->fetchArray(SQLITE3_ASSOC) ) {
          restore_error_handler();
          console_log("  UserID: ".$row["rowid"]);
          return $row["rowid"];
        }
      }
      restore_error_handler();
      return -1;
    }
  }
  
  //gibt alle zulässigen Client-IDs als Array zu dieser Session-ID
  function getPossibleClientIDs() {
    $return = array();
    global $db;
    console_log("Session-IDs für diese Session-ID auslesen");
    //Client in Datenbank suchen
    $sql = "select rowid,user from clients where session_id='".session_id()."';";
    console_log("SQL: ".$sql);
    $res=$db->query($sql);
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) { // gefunden
      array_push($return, $row['rowid']);      
    }
    return $return;
  }
  
  
  //erzeugt zu dieser Session-ID eine neue Client-ID
  function generateExtraClientID() {
    global $db;
    //Prüfen ob zu viele Clients in der letzten Zeit (Minute, Stunde, 5 Minuten... ?) erstellt wurden
    if (CHECKLIMITS) {
      $sql = "select count(*) as anz from clients;";
      console_log("SQL: ".$sql);
      if($res=$db->querySingle($sql)) {
        console_log("Result: ".$res);
        if ($res>=MAXCLIENTS) {
          return false;
        }
      }
      $sql = "select count(*) as anz from clients where session_id='".session_id()."';";
      console_log("SQL: ".$sql);
      if($res=$db->querySingle($sql)) {
        console_log("Result: ".$res);
        if ($res>=MAXCLIENTIDS) {
          return false;
        }
      }
    }    
    $_SESSION["user"]=-1; // Noch kein aktiver user
    $sql = "INSERT INTO clients (session_id, user, ipaddr, lastedited, created) VALUES ".
    "('".session_id()."',".$_SESSION["user"].",'".getUserIpAddr()."',strftime('%Y-%m-%d %H:%M:%S','now'),strftime('%Y-%m-%d %H:%M:%S','now'));";
    console_log("SQL: ".$sql);
    if($db->exec($sql)) {
      console_log("Client registriert");
    } else {
      console_log("Eintrag nicht erfolgt");
      console_log("Fehler: ".$db->lastErrorMsg);
    }
    $sql = "select max(rowid) from clients where session_id='".session_id()."';";
    console_log("SQL: ".$sql);
    if($res=$db->querySingle($sql)) {
      $_SESSION["clientid"]=$res;
    }
    return true;    
  }
  
  //Optionen in der Tabelle ändern (Von True auf False)
  function changeOptionWithID($rowid) {
    global $db;
    $sql = "UPDATE enable_options SET value=1-value WHERE rowid=".$rowid.";";
    console_log("SQL: ".$sql);
    if($db->exec($sql)) {
      console_log("Wert aktualisiert");
    } else {
      console_log("Enable_Options nicht aktualisiert");
      console_log("Fehler: ".$db->lastErrorMsg);
    }    
  }
  
  //prüft ob in den Optionen gewisse Dinge erlaubt sind
  function isEnabled($string) {
    global $db;
    if (!ctype_alnum($string)) {
      die ("Illegal use! - String for SQL-Statement with illegal characters!");
      return; // String contains non Alphanumeric Symbols
    }
    $sql = "select value from enable_options WHERE name='".$string."';";
    console_log("SQL: ".$sql);
    if($res=$db->querySingle($sql)) {
      return ($res==1);
    }
    return false;
  }
  
  function gibNeueBordkartenNummer() {
      global $db;
      //Anzahl vorhandener Bordkarten ermitteln
      $anz_bk=-1;
      $sql = "select count(*) from piraten;";
      console_log("SQL: ".$sql);
      if($res=$db->querySingle($sql)) {
        $anz_bk=$res;
      }
      console_log("Es gibt ".$anz_bk." Bordkarten");
      if ($anz_bk >= MAXBK) { // Zu viele Bordkarten
        return false;
      }
      $bknr = -1;
      do {
        $bknr = rand(1234,8912+$anz_bk+MAXBK);
        $sql = "select rowid from piraten where bordcardnr=".$bknr.";";
        console_log("SQL: ".$sql);
      } while($res=$db->querySingle($sql));
      //in Datenbank eintragen
      if(bordkartenNummerInDBEintragen($bknr)>=0) {
        console_log("Bordkartennummer registriert");
      } else {
        console_log("Eintrag nicht erfolgt");
        console_log("Fehler: ".$db->lastErrorMsg);
      }
      return $bknr;      
  }
  
  //Trage die übergebene Bordkartennummer in die Datenbank ein
  //gibt diese bei Erfolg zurück sonst -1
  function bordkartenNummerInDBEintragen($bknr) {
    global $db;
    $stmt = $db->prepare("INSERT INTO piraten (bordcardnr, aktinsel, letzteInsel, tour, letzteFahrtZeit, erzeugt) VALUES ".
      "(:bknr,'1','-1','',strftime('%Y-%m-%d %H:%M:%S','now'),strftime('%Y-%m-%d %H:%M:%S','now'))");
    if ($stmt->bindValue(':bknr', $bknr, SQLITE3_INTEGER)) {
      set_error_handler(function() { /* ignore errors */ });
      if ($stmt->execute()) { //erfolgreich
        return $bknr;
      }
      restore_error_handler();
    }
    return -1; 
  }
  
  //Lässt den Piraten mit der Bordkartennr von der Inselnr
  //Mit dem gewählten Schiff fahren und gibt Info zurück
  //Array mit valid = true/false und message
  function gibRoutenInfo($bknr, $schiff, $inselnr) {
    global $db;
    //Return Array
    $arr = array();
    $arr['valid'] = false;
    //zielInsel ermitteln
    $zielinsel = 0;
    $sql = "select ziel".$schiff." from inseln where inselnr=".$inselnr.";";
    console_log("SQL: ".$sql);
    if($res=$db->querySingle($sql)) {
      $zielinsel=$res;
      $arr['ziel']=$zielinsel;
    } else {
      $arr['message']="Fehler! Dieses Schiff (".$schiff.") gibt es nicht !";
      return $arr;
    }
    //Tabelle piraten aktualisieren
    $sql = "UPDATE piraten set letzteFahrtZeit=strftime('%Y-%m-%d %H:%M:%S','now'),aktinsel='".$zielinsel."',letzteInsel='".$inselnr."', tour=tour||'".$schiff."' where bordcardnr='".$bknr."';";
    console_log("SQL: ".$sql);
    if ($res=$db->exec($sql)) {
      $arr['valid'] = true;
      $arr['message'] = "Der Pirat mit der Bordkarte ".$bknr." fährt von ".gibInselName($inselnr)." nach ".gibInselName($zielinsel)."!";
    } else {
      $arr['message'] = "Reise konnte leider nicht gebucht werden - Fehler";
    }
    return $arr;
  }
  
  function inselNrVonClientSetzen($clientid, $neueInselNr) {
    global $db;
    $sql = "UPDATE clients set user='".$neueInselNr."', lastedited=strftime('%Y-%m-%d %H:%M:%S','now') where rowid='".$clientid."';";
    console_log("SQL: ".$sql);
    if($res=$db->exec($sql)) {
      console_log("Success");
      return true;
    }
    return false; 
   }
  
  // gibt eine Array zum Piraten zurück, dass die Infos enthält
  // valid->true/false, aktInsel, letzteInsel, tour
  function gibPiratenInfo($bknr) {
    global $db;
    $sql = "select * from piraten where bordcardnr='".$bknr."';";
    console_log("SQL: ".$sql);
    $res=$db->query($sql);
    $arr = array();
    if ($row = $res->fetchArray(SQLITE3_ASSOC)) {
      $arr['valid']=true;
      $arr['aktInsel']=$row['aktInsel'];
      $arr['letzteInsel']=$row['letzteInsel'];
      $arr['tour']=$row['tour'];
    } else {
      $arr['valid']=false;
    }
    return $arr;    
  }
  
  // Gibt den Namen der Insel mit der Nummer zurück
  function gibInselName($nr) {
    global $db;
    $sql = "select name from inseln where inselnr='".$nr."';";
    console_log("SQL: ".$sql);
    if($res=$db->querySingle($sql)) {
      return $res;
    }
    return "unbekannte Insel";  
  }
    
  function checkTableExists($name,$createstmt) {
    //checks if table $name exists and returns true
    // if not - returns false and table will be created using createstmt
    //TODO: check if name and createstmt are alphanumeric and so on
    global $db;
    if (is_null($db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='".$name."';"))) {
      console_log("Tabelle ".$name." existiert nicht!");
      $ret = $db->exec($createstmt);
      if(!$ret){
          echo $db->lastErrorMsg();
      } else {
        console_log("Table ".$name." created successfully");
      }
      return false;
    }
    return true;
  }

  function doChecks() {
    global $db;
    // prüfen ob tabellen existieren
    // user
    checkTableExists("users","CREATE TABLE users (name TEXT, password TEXT, lastedited TEXT, created TEXT);");
    // clients
    checkTableExists("clients","CREATE TABLE clients (session_id TEXT, user INTEGER, ipaddr TEXT, lastedited TEXT, created TEXT);");
    
    // Themenbereiche
    if (!checkTableExists("themenfelder","CREATE TABLE themenfelder (bezeichnung TEXT, superthema INTEGER DEFAULT -1);")) {
      //Tabelle wurde neu angelegt - Basisdaten einrichten
           $sql =<<<EOF
        INSERT INTO themenfelder (bezeichnung) VALUES ('Elektrizitätslehre');
        INSERT INTO themenfelder (bezeichnung) VALUES ('Wärmelehre');
        INSERT INTO themenfelder (bezeichnung,superthema) VALUES ('Stromkreis', (SELECT rowid from themenfelder WHERE bezeichnung='Elektrizitätslehre'));
EOF;
       $ret = $db->exec($sql);
       if(!$ret) {
          echo $db->lastErrorMsg();
       } else {
          console_log("Tabelle themenfelder mit Basisdaten bef&uuml;llt");
       }
    }
    // Objekt
    checkTableExists("objekt","CREATE TABLE objekt (bezeichnung TEXT, anzahl INTEGER DEFAULT 1, ort INTEGER DEFAULT -1, bild TEXT DEFAULT NULL);");
    // Schrank
    checkTableExists("schrank","CREATE TABLE schrank (bezeichnung TEXT, raum TEXT);");
    // Ort
    checkTableExists("ort","CREATE TABLE ort (schrank INTEGER, fach INTEGER, bezeichnung TEXT DEFAULT '');");
    // Datei
    checkTableExists("datei","CREATE TABLE datei (bezeichnung TEXT, pfad TEXT);");
    // Versuch
    checkTableExists("versuch","CREATE TABLE versuch (bezeichnung TEXT);");
    // VersuchContainsObjekt
    checkTableExists("versuchCobjekt","CREATE TABLE versuchCobjekt (vid INTEGER, oid INTEGER, anzahl INTEGER);");
    
    
    // enableoptions -  0 is false - 1 is true
    if (is_null($db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='enable_options';"))) {
      console_log("Tabelle enable_options existiert nicht!");
      $sql = "CREATE TABLE enable_options (name TEXT NOT NULL, value INTEGER DEFAULT 0, description_optional TEXT);";
      $ret = $db->exec($sql);
      if(!$ret){
          echo $db->lastErrorMsg();
      } else {
        console_log("Table enable_options created successfully");
        
           $sql =<<<EOF
        INSERT INTO enable_options (name,value,description_optional)
        VALUES ('allowMultipClientsPerIP', 0,'allows to generate multiple Client-IDs within one Session - needed basically for test purposes (Default: false)');
        INSERT INTO enable_options (name,value,description_optional)
        VALUES ('allowToChangeIsland', 1,'allows a client to change the assigned island - needed if pupils cant change physical computers (Default: true)');
        INSERT INTO enable_options (name,value,description_optional)
        VALUES ('allowBordCardCreation', 1,'allows a pirate to create a bordcard himself (Default: true)');
EOF;
       $ret = $db->exec($sql);
       if(!$ret) {
          echo $db->lastErrorMsg();
       } else {
          console_log("enable_options eingetragen");
       }
      }
    }
       
    //Limits prüfen
    //diese Tabellen gibt es nicht mehr
    //if (CHECKLIMITS) {
    if (false) {
      // Alle Clients und Bordkarten löschen die älter als MAXTIME sind
      $sql = "DELETE FROM piraten where strftime('%s','now') - strftime('%s',erzeugt) > ".MAXTIME.";";
      console_log("SQL: ".$sql);
      if($db->exec($sql)) {
        console_log("piraten bereinigt");
      } else {
        console_log("Piratenbereinigung fehltgeschlagen");
        console_log("Fehler: ".$db->lastErrorMsg);
      }
      $sql = "DELETE FROM clients where strftime('%s','now') - strftime('%s',created) > ".MAXTIME.";";
      console_log("SQL: ".$sql);
      if($db->exec($sql)) {
        console_log("clients bereinigt");
      } else {
        console_log("Clientbereinigung fehltgeschlagen");
        console_log("Fehler: ".$db->lastErrorMsg);
      }
      
    }
  }
?>
