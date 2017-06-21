<?php

display_header();

if (!isset($_GET["do"])) $_GET["do"] = "index";

switch ($_GET["do"]) {
	case "view":
		include "views/view.php";
		break;
	default:
		include "views/view.php";
		break;
}

display_footer();

?>
