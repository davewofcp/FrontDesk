<?php

date_default_timezone_set("America/New_York");
set_time_limit(0);

$servers = array(
	"192.168.1.200",
	"192.168.2.200",
	"192.168.3.200",
	"192.168.4.200",
);

class AltPath {
	public $path = "";
	public $server = "";
	public function __construct() {
		$args = func_get_args();
		$this->path = $args[0];
		$this->server = $args[1];
	}
}

$alt_paths = array(
	new AltPath("bspa","192.168.3.200"),
);

foreach ($servers as $server) {
	echo "<b>$server</b> : <hr>\n";
	$contents = file_get_contents("http://$server/new/svn_update.php");
	if ($contents) echo $contents;
	else echo "FGC FAIL<br>";
}


foreach ($alt_paths as $ap) {
	echo "<b>{$ap->server} / {$ap->path}</b> : <hr>\n";
	$contents = file_get_contents("http://{$ap->server}/{$ap->path}/svn_update.php");
	if ($contents) echo $contents;
	else echo "FGC FAIL<br>";	
}

?>