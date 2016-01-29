<?php

// Your remote name
$remote = "origin";

// Aliases for branches and directories
$branch = 'master';
$dir = 'path/to/dir';

// Do you want a log file with web hook posts?
$log = FALSE;




# ===========================================================================
# ==== No Humans Below. Only Honey Badgers. http://youtu.be/4r7wHMg5Yjg =====
# ===========================================================================

$ip = $_SERVER['REMOTE_ADDR'];
$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
$change_happened = false;

# Receive POST data
$payload = json_decode(file_get_contents('php://input'), true);

if (empty($ip)) {
  header($protocol.' 400 Bad Request');
  die('invalid ip address');
} elseif (empty($payload)) {
  header($protocol.' 400 Bad Request');
  die("missing payload<br /><img src='http://i.qkme.me/3sst5f.jpg' />");
}

# Log posts
if($log) {
  file_put_contents($_SERVER['SCRIPT_FILENAME'].'.log',"Web Hook Post: ".date("F j, Y, g:i a")."\n".$payload."\n\n", FILE_APPEND);
}

# Check if exists some changes on branch
if( isset($payload['push']['changes']) && count($payload['push']['changes']) > 0 ){
	foreach ( $payload['push']['changes'] as $change ) {
		if( isset($change['new']) && isset($change['new']['type']) && ($change['new']['type'] === 'branch') && isset($change['new']['name']) && ($change['new']['name'] === $branch) ){
			$change_happened = true;
		}
		else{
			die("no news in change");
		}
	}
}
else{
	die("Apparently there is nothing to update for this branch\n");
}

if($change_happened){
	# Capture current directory
	$original_dir = getcwd();
	
	#git pull changes
	$command_output = array();
	$command_executed = 0;
	chdir($dir);
	exec("git pull", $command_output, $command_executed);
	if($command_executed){
		exec("chown www-data:web * -R");
		exec("chmod 775 * -R");
		chdir($original_dir);

		die("Changes applied from branch: " . $branch . "\nCommand output:\n" . implode('\n', $command_output));
	}
	else{
		die("Command git pull not executed");
	}
}
else{
	die("Apparently there is nothing to update for this branch\n");
}

# The End

# =====
# util
# ====
function filterNonDir($path) {
  # If path doesn't exist, take it out of the array
  return is_dir($path) ? $path:FALSE;
}