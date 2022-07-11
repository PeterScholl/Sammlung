<?php

  define("DEBUG", true); //Konsolenausgaben aktivieren oder deaktivieren
  define("CHECKLIMITS", true); //sollen die Grenzwerte geprüft werden
  define("MAXTIME",90*60); //Maximale Zeit, die ein Client leben darf
  define("MAXLOGTIME", 7*24*60*60); //maximum time a log-entry is kept
  define("MAXUPLOADFILESIZE", 9000000); // maximum is 1 MB
  define("MAXUPLOADCOUNT",100); //maximum amount of uploads per hour
  define("ADMINPASS", "pass"); //Passwort für Adminseite
  define("HOMEPAGE", "sammlung.php"); // Initial home-Page
  define("UPLOADDIR", "uploads"); //directory for uploads
  define("THUMBNAILDIR", "thumbnails"); //directory for thumbnails
  
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
  
  //used by functions to return a Result
  class Result {
    public $value = 0; // 0 means no error
    public $message = ""; // used to add information
    
    function __construct() {
        $arguments = func_get_args();
        $numberOfArguments = func_num_args();
        if ($numberOfArguments == 2) {
          $this->value = $arguments[0];
          $this->message = $arguments[1];          
        }
    }
  }

?>
