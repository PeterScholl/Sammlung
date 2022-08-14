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
    // Attention!! No Logging to console because is used before header!
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
  
  // getNextID in table or -1 if none
  function getNextID($table, $rowid) {
    global $db;
    if (!ctype_alpha($table)) {
      return -1;
    }
    $rownr = (int)$rowid;
    $sql = "SELECT min(rowid) FROM ".$table." WHERE rowid>".$rownr.";";
    $res = $db->querySingle($sql);
    if ($res) {
      return $res;
    } else {
      return -1;
    }
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
      } else if ($col=='created' or $col=='edited') {
        $strcol=$strcol.$col.",";
        $strval=$strval." strftime(\"%Y-%m-%d %H:%M:%S\",\"now\"),";
      }
    }
    console_log("strval:".htmlspecialchars($strval));
    if (strlen($strcol)>0) {
      $sql = "INSERT INTO ".$table." (".substr($strcol,0,-1).") VALUES (".substr($strval,0,-1).");";
      console_log("Zeile wird eingetragen: ".htmlspecialchars($sql));
      $ret = $db->exec($sql);
      if(!$ret) {
        echo $db->lastErrorMsg();
      } else {
        console_log("Zeile wurde eingetragen");
      }
    }    
    console_log("... verlasse addTableRow");
  }
  //eine Zeile in einer Tabelle löschen / delete a row in a table
  function delTableRow($table,$rowid) {
    global $db;
    console_log("Deleting: ".$rowid." from table ".$table);
    if (ctype_alnum($table) && is_integer($rowid)) {
      $stmt = $db->prepare('DELETE FROM '.$table.' WHERE rowid=:id');
      $stmt->bindValue(':id', $rowid, SQLITE3_INTEGER);
      //$stmt->bindValue(':table', $table, SQLITE3_TEXT);
      $result = $stmt->execute();
      return (true);
    } else {
      console_log("Unaccepted Values in DELETE - rowid: ".$rowid." table ".$table);
      return(false);
    }
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
  
  // Objekte für die komplexe Objektdarstellung zurückliefern - als array
  function getObjekte($offset, $limit, $mitOrt=false, $mitDokument=false,$mitBild=false) {
    // is used in ajax - so no debug output allowed
    if (!is_numeric($offset) or $offset<0) $offset=0;
    if (!is_numeric($limit) or $limit<0 or $limit>50) $limit=20;
    global $db;
    $sql = "select count() from objekt;";
    $anzObjekte=$db->querySingle($sql); // TODO: check wether result is ok
    $stmt = $db->prepare('SELECT rowid,* FROM objekt ORDER BY sort,rowid ASC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    //console_log("Query: ".htmlspecialchars($stmt->getSQL(true)));
    $result = $stmt->execute();
    $retobj = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      //auf verknüpfte Orte prüfen
      $orte = getTableAsArray("objektAnOrt",$where='objektID='.$row['rowid']);
      $ortearray = [];
      if ($mitOrt && !empty($orte)) {
         foreach($orte as $x => $xvalue) {
           $ortearray[$xvalue['ortID']]=array( $xvalue['anzahl'], getStringToOrtId($xvalue['ortID']) );
         }
      }
      $row['orte']=$ortearray;
      //$row['bild'] mit richtigem Path füllen
      //TODO: Falls kein Bild empty.jpg einbauen
      if ($mitBild) {
        $row['bild']=str_replace(UPLOADDIR,THUMBNAILDIR,$db->querySingle("select place from files where rowid=".$row['bild']));
      } else {
        $row['bild']=THUMBNAILDIR."\empty.jpg";
      }
      array_push($retobj, $row);
    }
    //TODO: check wether there is no result and offset <> 0 then set offset to 0
    if (empty($retobj) and $offset>0) return getObjekte(0,$limit,$mitOrt,$mitDokument,$mitBild);
    $retobj['page']=ceil(($offset+1)/$limit); //current page number
    $retobj['numpages']=ceil($anzObjekte/$limit); // number of pages
    return $retobj;
  }
  
  //rekursiv Themenliste ausgeben
  function printThemenListeAsUL($id, $tiefe = 0) {
    //check if $id is Integer
    if (! is_int($id)) { return; }
    global $db;
    $sql = "SELECT rowid, bezeichnung FROM themen WHERE superthema=".$id." ORDER BY sort;";
    logdb("sqlite_inc - printThemenListeAsUL - SQL: ".$sql);
    $result = $db->query($sql);
    if ($result) { // Success
      if ($result->numColumns() && $result->columnType(0) != SQLITE3_NULL) {
        // have rows
        if ($tiefe==0) { echo "<UL>"; } else { echo "<UL class=\"nested\">"; }
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
          // check if there are subitems of this item
          $sql = "SELECT * FROM themen WHERE superthema=".$row["rowid"].";";
          if ($db->querySingle($sql)) {
            //yes there are subitems
            echo "<li><span class=\"caret\">".$row["bezeichnung"]."</span>";
            printThemenListeAsUL($row["rowid"], $tiefe=$tiefe+1);
            echo "</li>";
          } else {
            //there are no subitems
            echo "<li>".$row["bezeichnung"]."</li>";
          }
        }
        echo "</UL>";
      } else {
        // zero rows - Nothing to do!
        logdb("sqlite_inc - printThemenListeAsUL - no rows - should not happen!");
      } 
    } else {
      logdb("Error on fetching result");
    }
  }

  //rekursiv Orteliste ausgeben
  function printOrteListeAsUL($id, $tiefe = 0) {
    //check if $id is Integer
    if (! is_int($id)) { return; }
    global $db;
    $sql = "SELECT rowid, bezeichnung FROM ort WHERE superort=".$id." ORDER BY sort;";
    logdb("sqlite_inc - printOrteListeAsUL - SQL: ".$sql);
    $result = $db->query($sql);
    if ($result) { // Success
      if ($result->numColumns() && $result->columnType(0) != SQLITE3_NULL) {
        // have rows
        if ($tiefe==0) { echo "<UL>"; } else { echo "<UL class=\"nested\">"; }
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
          // check if there are subitems of this item
          $sql = "SELECT * FROM ort WHERE superort=".$row["rowid"].";";
          if ($db->querySingle($sql)) {
            //yes there are subitems
            echo "<li><span class=\"caret\">".$row["bezeichnung"]."</span>";
            printOrteListeAsUL($row["rowid"], $tiefe=$tiefe+1);
            echo "</li>";
          } else {
            //there are no subitems
            echo "<li>".$row["bezeichnung"]."</li>";
          }
        }
        echo "</UL>";
      } else {
        // zero rows - Nothing to do!
        logdb("sqlite_inc - printOrteListeAsUL - no rows - should not happen!");
      } 
    } else {
      logdb("Error on fetching result");
    }
  }
  
  function insertUpdateTheme($bezeichnung, $superthema, $rowid=-1) {
    global $db;
    if ($rowid>0) { //edit theme
      //TODO
    } else { // new theme
      if (strlen($bezeichnung)>0) {
        logdb("New Theme anlegen");
        //TODO check if superthema is integer and exists
        
        $stmt = $db->prepare('INSERT INTO themen (bezeichnung,superthema,created,edited) VALUES (:bez, :st, strftime("%Y-%m-%d %H:%M:%S","now"),strftime("%Y-%m-%d %H:%M:%S","now"));');
        $stmt->bindValue(':bez', $bezeichnung, SQLITE3_TEXT);
        $stmt->bindValue(':st', $superthema, SQLITE3_INTEGER);
        console_log("  Statement to execute: ".htmlspecialchars($stmt->getSQL(true)));
        $stmt->execute();
      } else { // String has length 0
        logdb("New Theme has length 0");
        return -1;
      }
    }
    return 0;
  }

  function getStringToOrtId($id) {
    if ($id==-1) return "";
    global $db;
    if (is_int($id)) {
      $sql = "SELECT bezeichnung,superort FROM ort where rowid=".$id.";";
      logdb("SQL: ".$sql);
      if($res=$db->querySingle($sql,$entireRow=true)) {
        logdb("  Success - result: ".$res);
        if ($res['superort']==-1) return $res['bezeichnung'];
        return getStringToOrtId($res['superort'])."/".$res['bezeichnung'];
      } else {
        logdb("  Error - no result");
        return "";
      }
    }
    return $path;   
  }

  function checkOrtOrder($sup=-1,$nextval=1) {
    global $db;
    if ($sup==-1) logdb("generating new Sorting of Table ort");
    $stmt = $db->prepare('SELECT rowid,* FROM ort WHERE superort=:sup ORDER BY sort ASC;');
    $stmt->bindValue(':sup', $sup, SQLITE3_INTEGER);
    logdb("  Statement to execute: ".htmlspecialchars($stmt->getSQL(true)));
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      //$sql = "UPDATE TABLE ort SET sort=".$nextval." WHERE rowid=".$row['rowid'].";";
      updateTableRow("ort",$row['rowid'], "sort", $nextval,$nocheck=true);
      $nextval=($nextval+5)-($nextval%5);
      $nextval = checkOrtOrder($sup=$row['rowid'],$nextval=$nextval);
      
    }
    return $nextval;
  }
  
  function printOrteAsSelection() {
    echo "<option value=\"-1\">Keiner</option>";
    global $db;
    $depthlist = array();
    array_push($depthlist,-1);
    $pre = "";
    $lastsup = -1;
    $stmt = $db->prepare('SELECT rowid,bezeichnung,superort FROM ort ORDER BY sort ASC;');
    logdb("  Statement to execute: ".htmlspecialchars($stmt->getSQL(true)));
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      if ($row['superort']!=$lastsup) { // change of depth
        while (in_array($row['superort'], $depthlist)) { // Rücksprung auf alte ebene
          array_pop($depthlist);
        }
        array_push($depthlist, $row['superort']);
        $pre = $pre."-";
        $pre = substr($pre,0,count($depthlist)-1);
        $lastsup=$row['superort'];
        console_log_json($depthlist);
      }
      echo "<option value=\"".$row['rowid']."\"".($row['rowid']==$_SESSION['aktort']?" selected":"").">".$pre.$row['bezeichnung']."</option>";
    }
  }
  
  function insertUpdateObjekt($bezeichnung, $bild, $rowid=-1) {
    global $db;
    if ($rowid>0) { //edit objekt
      //TODO
    } else { // new objekt
      if (!is_null($bezeichnung) and strlen($bezeichnung)>0) {
        $stmt = $db->prepare('INSERT INTO objekt (bezeichnung,bild,created,edited) VALUES (:bez, :bild, strftime("%Y-%m-%d %H:%M:%S","now"),strftime("%Y-%m-%d %H:%M:%S","now"));');
        $stmt->bindValue(':bez', $bezeichnung, SQLITE3_TEXT);
        $stmt->bindValue(':bild', $bild, SQLITE3_INTEGER);
        logdb("  Statement to execute: ".htmlspecialchars($stmt->getSQL(true)));
        $stmt->execute();
      } else { // String has length 0
        logdb("New Objekt has length 0");
        return -1;
      }
    }
    return getLastInsertedRowID();
  }

  // get number of uploaded files during last hour
  function getNrOfUploadsInLastHour() {
    global $db;
    $sql = "SELECT count(*) FROM files where strftime('%s','now') - strftime('%s',created) < 3600;";
    console_log("SQL: ".$sql);
    if($res=$db->querySingle($sql)) {
      $anz_upl=$res;
    } else {
      console_log("  (ERROR- but not really) no result means no uploads");
      $anz_upl = 0;      
    }
    console_log("  there were ".$anz_upl." uploads during the last hour");
    return $anz_upl;
  }
  
  // get FilePath from file - id
  function getFilePathFromFileID($id) {
    global $db;
    $path = null;
    if (is_int($id)) {
      $sql = "SELECT place FROM files where rowid=".$id.";";
      logdb("SQL: ".$sql);
      if($res=$db->querySingle($sql)) {
        $path=$res;
        logdb("  Success - result: ".$res);
      } else {
        logdb("  Error - no result");
      }
    }
    return $path;
  }
  
  function getLastInsertedRowID() {
    global $db;
    $sql = "SELECT LAST_INSERT_ROWID();";
    if($res=$db->querySingle($sql)) {
      return $res;
    } 
    return -1;
  }
  
  // get FileName from file - id
  function getFileNameFromFileID($id) {
    global $db;
    $path = null;
    if (is_int($id)) {
      $sql = "SELECT name FROM files where rowid=".$id.";";
      logdb("SQL: ".$sql);
      if($res=$db->querySingle($sql)) {
        $path=$res;
        logdb("  Success - result: ".$res);
      } else {
        logdb("  Error - no result");
      }
    }
    return $path;
  }

  //Benutzeranmeldedaten prüfen - gibt User-ID zurück (-2 wenn unbekannt, -3 wenn falsches Passwort, -1 bei sonstigen Fehlern)
  function getUserIdOf($user,$pass) {
    global $db;
    console_log("Benutzer anmelden");
    logdb("Benuter anmelden: ".$user);
    //User in Datenbank suchen
    $stmt = $db->prepare("SELECT rowid,password FROM users WHERE name=:name");
    if ($stmt->bindValue(':name', $user, SQLITE3_TEXT)) { //  && $stmt->bindValue(':pass', $pass, SQLITE3_TEXT)) {
      set_error_handler(function() { /* ignore errors */ });
      console_log("  BindValues done");
      if ($result = $stmt->execute()) { //erfolgreich
        console_log("  Statement executed: ".$stmt->getSQL(true));
        if ($row = $result->fetchArray(SQLITE3_ASSOC) ) {
          restore_error_handler();
          console_log("  Passwort prüfen ...");
          if (password_verify($pass,$row["password"])) {
            console_log("  success - UserID: ".$row["rowid"]);
            $_SESSION["username"]=$user;
            return $row["rowid"];
          }
          console_log("  Wrong password");
          return -3;
        } else {
          console_log("  Unknown username: ".$user);
          return -2;
        }
      }
      restore_error_handler();
      return -1;
    }
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
  
  function logdb($message) {
    // Attention!! No Logging to console because is used before header!
    global $db;
    //console_log("Log eintrag: ".$message);
    $stmt = $db->prepare('INSERT INTO log (created,ip,userid,message) VALUES (strftime("%Y-%m-%d %H:%M:%S","now"),:ip,:userid,:message)');
    //console_log("Anzahl Parameter in Statement: ".$stmt->paramCount());
    $stmt->bindValue(':ip', getUserIpAddr(), SQLITE3_TEXT);
    $stmt->bindValue(':userid', $_SESSION["userid"], SQLITE3_INTEGER);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $result = $stmt->execute();
    //console_log_json($result);
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
  
  //Set new password or create new user 
  function setNewPassword($user, $pass) {
    global $db;
    // TODO: check if user exists
    console_log("Passwort setzen");
    $stmt = $db->prepare('UPDATE users SET password=:value, lastedited=strftime("%Y-%m-%d %H:%M:%S","now") WHERE name=:username');
    console_log("Anzahl Parameter in Statement: ".$stmt->paramCount());
    $stmt->bindValue(':value', PASSWORD_HASH($pass, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':username', $user, SQLITE3_TEXT);
    $result = $stmt->execute();
    console_log_json($result);
    // TODO irgendwie auf Success prüfen?
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
    // log
    checkTableExists("log","CREATE TABLE log (created TEXT, ip TEXT, userid INT, message TEXT);");
    
    // Themenbereiche
    if (!checkTableExists("themen","CREATE TABLE themen (bezeichnung TEXT, superthema INTEGER DEFAULT -1, sort INTEGER, created TEXT, edited TEXT);")) {
      //Tabelle wurde neu angelegt - Basisdaten einrichten
           $sql =<<<EOF
        INSERT INTO themen (bezeichnung) VALUES ('Elektrizitätslehre');
        INSERT INTO themen (bezeichnung) VALUES ('Wärmelehre');
        INSERT INTO themen (bezeichnung,superthema) VALUES ('Stromkreis', (SELECT rowid from themen WHERE bezeichnung='Elektrizitätslehre'));
EOF;
       $ret = $db->exec($sql);
       if(!$ret) {
          echo $db->lastErrorMsg();
       } else {
          console_log("Tabelle themen mit Basisdaten bef&uuml;llt");
       }
    }
    // Objekt
    checkTableExists("objekt","CREATE TABLE objekt (bezeichnung TEXT, bild TEXT DEFAULT NULL, sort INTEGER, created TEXT, edited TEXT);");
    // Ort
    checkTableExists("ort","CREATE TABLE ort (bezeichnung TEXT, superort INTEGER DEFAULT -1, sort INTEGER, created TEXT, edited TEXT);");
    // ObjektIstAmOrt
    checkTableExists("objektAnOrt","CREATE TABLE objektAnOrt (objektID INTEGER NOT NULL, ortID INTEGER NOT NULL, anzahl NOT NULL, created TEXT, edited TEXT);");
    // Versuch
    checkTableExists("versuch","CREATE TABLE versuch (bezeichnung TEXT);");
    // VersuchContainsObjekt
    checkTableExists("versuchCobjekt","CREATE TABLE versuchCobjekt (vid INTEGER, oid INTEGER, anzahl INTEGER);");
    // Files
    checkTableExists("files","CREATE TABLE files (name TEXT, place TEXT, size INTEGER, downloads INTEGER, mimetype TEXT, created TEXT, edited TEXT);");
    if (file_exists(UPLOADDIR)) {
      console_log("DoChecks: upload dir exists (".UPLOADDIR.")");
      if (is_dir(UPLOADDIR)) {
        console_log(" and is a directory");
      } else {
        console_log("  "-UPLOADDIR." is not a directory");
        //TODO: What to do now?
      }
    } else {
      console_log("DoChecks: creating uploaddir ".UPLOADDIR);
      if (mkdir(UPLOADDIR, 0775)) {
        console_log("  success");
      } else {
        console_log("  failed");
      }
      
    }
    
    
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
       
    //TODO Limits prüfen - wenn es welche geben sollte (z.B. Objekte anlegen in einer gewissen Zeit)
    if (CHECKLIMITS) {
      // Alle Bordkarten löschen die älter als MAXTIME sind
      $sql = "DELETE FROM log where strftime('%s','now') - strftime('%s',created) > ".MAXLOGTIME.";";
      console_log("SQL: ".$sql);
      if($db->exec($sql)) {
        console_log("logs cleaned");
      } else {
        console_log("error in log cleaning");
        console_log("errormsg: ".$db->lastErrorMsg);
      }      
    }
  }

// ***** Upload-Aktionen ****
  //eine Datei in der Datenbank speichern te Tabellenzeile zurückgegeben ohne rowid
  function storeUploadedFileInDB($filename, $place, $size) {
    global $db;
    console_log("File in DB eintragen");
    $stmt = $db->prepare('INSERT INTO files (name, place, size, downloads, mimetype, created, edited) VALUES (:fname, :place, :size, 0,:mimetype, strftime("%Y-%m-%d %H:%M:%S","now"),strftime("%Y-%m-%d %H:%M:%S","now"))');
    $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
    $stmt->bindValue(':fname', $filename, SQLITE3_TEXT);
    $stmt->bindValue(':place', $place, SQLITE3_TEXT);
    $stmt->bindValue(':mimetype', mime_content_type($place), SQLITE3_TEXT);
    console_log("  Statement: ".htmlspecialchars($stmt->getSQL(true)));
    $result = $stmt->execute();
    $insertedid = getLastInsertedRowID();
    console_log("   Statement executed - rowid: ".$insertedid);
    return $insertedid;
  }
?>
