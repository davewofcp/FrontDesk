<?php

ini_set("display_errors","1");
error_reporting(E_ALL);

require("mysql_connect.php");
require("core/sessions.php");

if (isset($_GET["logout"]) && isset($SESSION_ID)) {
	delete_session($SESSION_ID);
	unset($USER);
	header("Location: /login.php");
	exit;
} elseif(isset($_GET["logout"])){

	header("Location: /login.php");
	exit;

} else {}

if (isset($USER)) {
	header("Location: /index.php");
	exit;
}


if (isset($_POST["user"])) {
	$result = mysql_query("SELECT * FROM users WHERE username = '".mysql_real_escape_string($_POST["user"])."'");
	if (mysql_num_rows($result) < 1) {
		$ERROR = "User does not exist.";
	} else {
		$USER = mysql_fetch_assoc($result);
		if (md5($_POST["pass"] . $USER["salt"]) != $USER["password"]) {
			$ERROR = "Password incorrect.";
			unset($USER);
		} elseif($USER["is_disabled"]) {
      		$ERROR = "User disabled.";
			unset($USER);
    	}
	}

	if (!isset($ERROR)) {
		$SESSION_ID = create_session($USER);
		header("Location: /index.php");
		exit;
	}
}

include "header.php";
display_header();

?><h2>Login</h2><br>

<?php if (isset($ERROR)) { ?>
<font color="#FF0000"><b>ERROR: <?php echo $ERROR; ?></b></font><br><br>
<?php } ?>
<script type="text/javascript">
var Browser = {
  Version: function() {
    var version = 999; // we assume a sane browser
    if (navigator.appVersion.indexOf("MSIE") != -1)
      version = parseFloat(navigator.appVersion.split("MSIE")[1]);
    return version;
  }
}

if (Browser.Version() != 999) {
  alert("You can't use IE on this system.");
}
<?php
$str = "";
$str .= "No longer use this system to add issues. Go back to the old one for now.";
$str .= "Only use this to look at current issues in the system for needed information.";
$str .= "I will get something up and running asap that actually works for us.";
$str .= "Resolve new issues in this system to the best of your ability";

?>
</script>
<form action="login.php" method="post">
<table border="0">
 <tr>
  <td><b>Username:</b></td>
  <td><input type="edit" name="user" size="30"></td>
 </tr>
 <tr>
  <td><b>Password:</b></td>
  <td><input type="password" name="pass" size="30"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" value="Login">
  </td>
 </tr>
</table>
<input type="hidden" name="url" value="<?php ?>">
</form>
<?php

include "footer.php";
display_footer();

?>