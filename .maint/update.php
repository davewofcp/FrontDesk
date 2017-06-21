<?php

// Read the update site config file
if (!file_exists(".update_config")) die("Update script not configured (missing .update_config file)");
$configFile = fopen(".update_config","r");
$UPDATE_SITE = "";
$ADMIN_MD5 = "";
$ADMIN_SALT = "";
while (!feof($configFile)) {
	$line = explode("=",trim(fgets($configFile)));
	$option = $line[0];
	$value = $line[1];
	switch ($option) {
		case "site":		$UPDATE_SITE = $value;	break;
		case "admin_md5":	$ADMIN_MD5 = $value;	break;
		case "admin_salt":	$ADMIN_SALT = $value;	break;
	}
}
fclose($configFile);
if ($UPDATE_SITE == "") die("Update site not configured ('site' not set in config)");
if ($ADMIN_MD5 == "") die("Admin password not configured ('admin_md5' not set in config)");

// Validate admin password
if (!isset($_SERVER['PHP_AUTH_PW']) || strtolower(md5($_SERVER['PHP_AUTH_PW'] . $ADMIN_SALT)) != strtolower($ADMIN_MD5)) {
    header('WWW-Authenticate: Basic realm="Computer Answers - POS Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

// Connect to database
require_once("config.php");
$DB = mysql_connect($db_host,$db_user,$db_pass) or die("Couldn't connect to database.");
mysql_select_db($db_database) or die("Couldn't select database.");

// Handle AJAX call for package download/install
if ($_GET["ajax"] == "1") {
	$module = $_GET["module"];
	$version = $_GET["version"];
	
	if ($_GET["download"] == 1) {
		// Download package
		$address = $_GET["address"];
		$local_file = fopen("pkg/". $module ."/". $version .".zip","w");
		$pp = curl_init($address);
		curl_setopt($pp, CURLOPT_FILE, $local_file);
		$buffer = curl_exec($pp);
		curl_close($pp);
		fclose($local_file);
		
		echo "DLOK";
		exit;
	}
	
	if ($_GET["install"] == 1) {
		// Unzip package
		mkdir("pkg/". $module ."/". $version, 0777, true);
		$install_dir = "pkg/". $module ."/". $version ."/";
		$zip = zip_open("pkg/". $module ."/". $version .".zip");
		if ($zip) {
			while ($zip_entry = zip_read($zip)) {
				$fp = fopen("pkg/". $module ."/". $version ."/". zip_entry_name($zip_entry), "w");
				if (zip_entry_open($zip, $zip_entry, "r")) {
					$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					fwrite($fp,"$buf");
					zip_entry_close($zip_entry);
					fclose($fp);
				}
			}
			zip_close($zip);
		} else {
			echo "IER-ZP";
			exit;
		}
		
		// Execute manifest commands
		if (!file_exists($install_dir ."manifest")) { echo "IER-MF"; exit; }
		$manifest = fopen($install_dir ."manifest","r");
		while (!feof($manifest)) {
			$line = explode(" ",trim(fgets($manifest)));
			$command = $line[0];
			switch ($command) {
				case "copy":
					copy($install_dir . $line[1], $line[1]);
					break;
				case "delete":
					unlink($line[1]);
					break;
			}
		}
		
		// Run database update
		if (file_exists($install_dir ."db.sql")) {
			$sql_file = fopen($install_dir ."db.sql","r");
			$sql = "";
			while (!feof($sql_file)) {
				$sql .= trim(fgets($sql_file));
			}
			$DB_RESULT = mysql_query($sql);
		}
		
		// Run post-install script
		if (file_exists($install_dir ."run.php")) {
			include($install_dir ."run.php");
		}
		
		echo "IOK";
	}
	
	exit;
}

// Get current module information
$modules = array();
$versions = array();
$titles = array();
$result = mysql_query("SELECT * FROM modules");
while($row = mysql_fetch_array($result)) {
	$modules[] = $row["module"];
	$versions[$row["module"]] = $row["version"];
	$titles[$row["module"]] = $row["title"];
}

// Add module versions to query and fetch site
$UPDATE_SITE .= "?";
foreach($modules as $module) $UPDATE_SITE .= $module ."=". $versions[$module] ."&";
$UPDATE_PACKAGES = file_get_contents($UPDATE_SITE);
if (!$UPDATE_PACKAGES) die("Unable to contact update site");
$UPDATE_PACKAGES = trim($UPDATE_PACKAGES);

// Parse response			module=X.Y;http://package.location/module.zip
//						OR  module=OK
$UPDATE_DATA = explode("\n",$UPDATE_PACKAGES);
$UPDATE_VERSIONS = array();
$UPDATE_ADDRS = array();
$DOWNLOADED = array();
if (count($UPDATE_DATA) < 2) die("Invalid response from update site");
foreach($UPDATE_DATA as $update) {
	$update_line = explode("=",$update);
	$module = $update_line[0];
	$package_info = explode(";",$update_line[1]);
	if ($package_info[0] != "OK") {
		$UPDATE_VERSIONS[$module] = $package_info[0];
		if (file_exists("/pkg/". $module ."/". $package_info[0] .".zip")) {
			$DOWNLOADED[$module] = $package_info[0];
		}
		if (count($package_info) >= 2) $UPDATE_ADDRS[$module] = urlencode($package_info[1]);
	}
}

?><html>
<head>
<title>Computer Answers - POS Admin</title>
<script type="text/javascript">
var xmlhttp;
var active_module;
var active_version;

if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp = new XMLHttpRequest();
} else { // code for IE6, IE5
	xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
}

xmlhttp.onreadystatechange = function() {
	if (xmlhttp.readyState != 4) return;
	var status = document.getElementById(active_module +"_status");
	var button = document.getElementById(active_module +"_button");
	if (xmlhttp.status == 200) {
		var response = xmlhttp.responseText.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
		if (response == "DLOK") {
			button.innerHTML = "<a href=\"#\" onClick=\"click_install('"+ active_module +"','"+ active_version +"')\">Install "+ active_version +"</a>";
			status.innerHTML = "Downloaded "+ active_version +" OK";
		}
		else if (response == "IER-ZP") {
			status.innerHTML = "Error unzipping package file.";
		}
		else if (response == "IER-MF") {
			status.innerHTML = "Error reading package manifest.";
		}
		else if (response == "IOK") {
			button.innerHTML = "Up-to-date ("+ active_version +")";
			status.innerHTML = "Installed "+ active_version +" OK";
		}
		else {
			status.innerHTML = "Unknown response: "+ response;
		}
	} else {
		status.innerHTML = "Error: "+ xmlhttp.status;
	}
}

function click_download(module,version,address) {
	active_module = module;
	active_version = version;
	xmlhttp.open("GET","update.php?ajax=1&download=1&module="+module+"&version="+version+"&address="+address,true);
	xmlhttp.send();
}
function click_install(module,version) {
	active_module = module;
	active_version = version;
	xmlhttp.open("GET","update.php?ajax=1&install=1&module="+module+"&version="+version,true);
	xmlhttp.send();
}
</script>
</head>
<body>
<div align="center"><h2>Installed Modules</h2>

<table border="0" cellpadding="5">
<?php 

foreach($modules as $module) {
	echo "<tr><td>". $titles[$module] ." ". $versions[$module] ."</td>\n";
	echo "<td><div id=\"". $module ."_button\">\n";
	if ($UPDATE_VERSIONS[$module]) {
		if (isset($DOWNLOADED[$module])) {
			echo "<a href=\"#\" onClick=\"click_install('".$module."','".$DOWNLOADED[$module]."')\">Install ". $DOWNLOADED[$module] ."</a>";
		} else {
			echo "<a href=\"#\" onClick=\"click_download('".$module."','".$UPDATE_VERSIONS[$module]."','".$UPDATE_ADDRS[$module]."')\">Download ". $UPDATE_VERSIONS[$module] ."</a>";
		}
	} else {
		echo "Up-to-date";
	}
	echo "</div></td>\n";
	echo "<td><div id=\"". $module ."_status\"></div></td>\n";
	echo "</div></tr>\n";
}

?>
</table>

<a href="/">Back to POS</a>

</div>

</body>
</html>