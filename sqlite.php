<?php
   ini_set('display_errors', 1);
   ini_set('log_errors', 1);
   ini_set('error_log', './ERROR.LOG');
   error_reporting(E_ALL & ~E_NOTICE);
   
   	function printSQLiteResult(SQLite3Result $res) {
		for($i = 0; $i<$res->numColumns(); $i++) {
			echo $res->columnName($i)." ";			
		}		
		echo "\n";
		while($row = $res->fetchArray(SQLITE3_NUM)) {
			for($i = 0; $i<$res->numColumns(); $i++) {
				echo $row[$i]." ";			
			}		
			echo "\n";
		}
	}

   
   class MyDB extends SQLite3 {
      function __construct() {
         $this->open('pirates.db');
      }
   }
   echo "Creating Database...\n";
   $db = new MyDB();

   if(!$db) {
      echo $db->lastErrorMsg();
   } else {
      echo "Opened database successfully\n";
   }

   /*
   $sql = "CREATE TABLE clients (SESSION_ID TEXT, INSELTYP INT, LASTEVENT TEXT, CREATED TEXT);";
   $ret = $db->exec($sql);
   if(!$ret){
      echo $db->lastErrorMsg();
   } else {
      echo "Table created successfully\n";
   }
   
   //another try to create a table
      $sql =<<<EOF
      CREATE TABLE COMPANY
      (ID INT PRIMARY KEY     NOT NULL,
      NAME           TEXT    NOT NULL,
      AGE            INT     NOT NULL,
      ADDRESS        CHAR(50),
      SALARY         REAL);
EOF;

   $ret = $db->exec($sql);
   if(!$ret){
      echo $db->lastErrorMsg();
   } else {
      echo "2 Table created successfully\n";
   }
*/
   $sql="SELECT * FROM CLIENTS;";
   //$sql="SELECT name FROM sqlite_master WHERE type='table';";
   echo "Datenbankabfrage: ".$sql."\n";
   if ($ret = $db->query($sql)) {
     while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
      echo array_keys($row);  
      echo "NAME = ". $row['NAME'] . "\n";
        echo "ID = ". $row['SESSION_ID'] . "\n";
        echo "INSELTYP = ". $row['INSELTYP'] ."\n";
        echo "LASTACTION = ". $row['LASTACTION'] ."\n";
        echo "CREATED = ".$row['CREATED'] ."\n\n";
     }
   }
   
    $tablesquery = $db->query("SELECT name FROM sqlite_master WHERE type='table';");

    while ($table = $tablesquery->fetchArray(SQLITE3_ASSOC)) {
        echo $table['name'] . '<br />';
    }
    
    $tablesquery->reset();
    echo "Num Columns of tablesquery: " . $tablesquery->numColumns();
    printSQLiteResult($tablesquery);
    if ($ret != false) {
      $ret->reset();
      echo "Num Columns of ret: ".$ret->numColumns()."\n";
      printSQLiteResult($ret);
      printSQLiteResult($db->query("SELECT * FROM inseln;"));
    }
    
    echo "\n\nTest Statement\n";
    
    $stmt = $db->prepare('SELECT count(*) AS anz FROM clients WHERE inseltyp=:inseltyp');
    $anzahlen = array();
    for ($i=1;$i<7;$i++) {
      $stmt->bindValue(':inseltyp', "".$i, SQLITE3_TEXT);
      $result = $stmt->execute();
      $row = $result->fetchArray();
      //var_dump($row);
      echo "".$i.": ".$row['anz']."\n";
      $anzahlen[$i]=$row[0];
    }
    var_dump($anzahlen);
    echo "Minimum: ".min($anzahlen)."\n";

	$db->close();
  echo "Tests without Database\n";
  $strings = ["abc","<d>test$?!?=</d>"];
  foreach ($strings as $s) {
    echo "String: ".$s." is FILTER_SANITIZED: ".filter_var($s, FILTER_SANITIZE_STRING)."\n";  
  }
?>
