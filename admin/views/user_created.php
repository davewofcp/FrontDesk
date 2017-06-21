<?php if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; } ?>
<h3>User '<?php echo $CREATED_USER["username"]; ?>' Created</h3>
