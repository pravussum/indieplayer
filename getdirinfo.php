<?php

if(!isset($_GET["dir"]))
	die("No dir parameter given.");
$dir = urldecode($_GET["dir"]);
if(!file_exists($dir)) {
	die("Directory not found: " + $dir);
}

$reldir = realpath($dir);
header('Content-type: application/json; charset:UTF-8');

 // data.headLink
 // data.entries
 // 	entry.type: 	audio | dir | ... (file)
 //		entry.fullpath
 //		entry.file
?>
{
	"headLink": "<?php echo addcslashes(buildHeadLink($reldir), '\"\\'); ?>",
	"entries": [
			<?php
			    $direntries = scandir($reldir);
			    $first = TRUE;
			    foreach ($direntries as $file) {
				    if($file == ".")
						continue;	
			    	if(!$first)
			    		echo ",";
			    	else
						$first = FALSE;
			    	echo '{';
			        echo '"file":"'.$file.'",';
					$fullpath = realpath($reldir.DIRECTORY_SEPARATOR.$file);
			        echo '"fullpath":"'.addcslashes($fullpath,'\\').'",';	
			        
			        if(isAudioFile($file)) {
						echo '"type":"audio"';
			        } else {
						if(is_dir($fullpath))  {
			        		echo '"type":"dir"';
			        	} else {
			        		echo '"type":"file"';	
			        	}					
			        }
			    	echo "}";
			    }
			?>	
	]	
}
<?php
	function isAudioFile($file) {
		if(!isset($file) )
			return false;
		$parts = explode(".", $file);
		if(in_array($parts[count($parts) - 1], array("mp3", "ogg", "flac")))
			return true;
		return false;
	}


	function buildHeadLink($dir) {
		if (!strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Unix-OS: prepend dir separator
			$result = DIRECTORY_SEPARATOR;
			$currentPath = DIRECTORY_SEPARATOR;
		} else {
			$result = "";	
			$currentPath = "";
		}
		$first = TRUE;
		$parts = explode(DIRECTORY_SEPARATOR,$dir);
		foreach($parts as $part) {
			if($first) {
				$first = FALSE;
			} else {
				$currentPath .= DIRECTORY_SEPARATOR;
				$result .= DIRECTORY_SEPARATOR; 
			}
			$currentPath .= $part;
			$result .= "<span class='dirlink' dir='$currentPath'>$part</span>";			
		}
		return $result;
	}

?>