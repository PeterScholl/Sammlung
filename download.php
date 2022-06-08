<?php
//Script taken from https://www.w3docs.com/snippets/php/automatic-download-file.html
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
        logdb("DL: User not logged in?".$_SESSION["user"]);
        http_response_code(404);
        die();  
}
?>
