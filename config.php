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
  
?>
