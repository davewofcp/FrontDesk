<?php if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; } ?>
<h3><?php echo $RESPONSE; ?></h3>
