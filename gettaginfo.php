<?php

if(!isset($_GET["file"]))
	die("No file parameter given.");
$filename = urldecode($_GET["file"]);
if(!file_exists($filename)) {
	die("File not found.");
}

header('Content-type: application/json; charset:UTF-8');

// include getID3() library (can be in a different directory if full path is specified)
require_once('getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;

// Analyze file and store returned data in $ThisFileInfo
$ThisFileInfo = $getID3->analyze($filename);

// TODO comment out
getid3_lib::CopyTagsToComments($ThisFileInfo);
?>
{
	"artist": "<?php echo isset($ThisFileInfo['comments_html']['artist'][0]) ? html_entity_decode($ThisFileInfo['comments_html']['artist'][0]) : ''; ?>",
	"title": "<?php echo isset($ThisFileInfo['comments_html']['title'][0]) ? html_entity_decode($ThisFileInfo['comments_html']['title'][0]) : ''; ?>",
	"album":"<?php echo isset($ThisFileInfo['comments_html']['album'][0]) ? html_entity_decode($ThisFileInfo['comments_html']['album'][0]) : ''; ?>"
}