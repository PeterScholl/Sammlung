<?php
//Script taken from https://www.w3docs.com/snippets/php/automatic-download-file.html
require_once("config.php");
require_once("sqlite_inc.php");
session_start();
logdb("Session-ID: ".session_id());

if(isset($_REQUEST["file"]) && $_SESSION["user"]>0){
    
    // Get parameters
    // $file = urldecode($_REQUEST["file"]); // Decode URL-encoded string
    $fileid = intval($_REQUEST["file"]);
    $filepath=getFilePathFromFileID($fileid);

    /* Check if the file name includes illegal characters
    like "../" using the regular expression */
    //if(preg_match('/^[^.][-a-z0-9_.]+[a-z]$/i', $file)){
    if ($filepath) {
        // Process download
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
//            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Content-Disposition: attachment; filename="'.getFileNameFromFileID($fileid).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            die();
        } else {
            http_response_code(404);
	        die();
        }
    } else {
        logdb("Invalid file id! ".$fileid);
        http_response_code(404);
        die("Invalid file id! ".$fileid);
    }
} else {
  //header("Refresh:5; url=".HOMEPAGE);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <title>Download-Page</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="./fcc.js"></script>
    <script>window.onload=init();</script>
  </head>
  <html>
    <h1>Could not download file</h1>
    <p>Probably you're not logged in</p>
    <Div id="CountDownTime">Closing in 5 seconds</div>
    <script>CountDownStart(5);</script>
  </html>
  <?php
  logdb("DL: User not logged in?".$_SESSION["user"]);
  die();  
}
?>
