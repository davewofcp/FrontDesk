<?php

require_once('common.php');

if (isset($_COOKIE["session_id"])) $SESSION_ID = mysql_real_escape_string($_COOKIE["session_id"]);

if (isset($SESSION_ID) && $SESSION_ID > 0) {
	@mysql_query("DELETE FROM sessions WHERE TIMESTAMPDIFF(SECOND,last,CURRENT_TIMESTAMP) > 1800");
	$result = mysql_query("SELECT *,TIMESTAMPDIFF(SECOND,customer_ts,CURRENT_TIMESTAMP) AS customer_age FROM sessions WHERE id = '". $SESSION_ID ."' LIMIT 1");
	if (mysql_num_rows($result) > 0) {
		$SESSION = mysql_fetch_assoc($result);
		if ($SESSION["remote_addr"] != $_SERVER["REMOTE_ADDR"]) {
			setcookie("session_id",null,"-1");
			echo "Invalid source address for this session.";
			exit;
		}

		# user

		$USER = mysql_fetch_assoc(mysql_query("SELECT * FROM users WHERE id = ". $SESSION["users__id"]));

		# populate $PERMS (req. {DOCROOT}/core/common.php and $USER)

    TFD_POPULATE_PERMS();

    # now $PERMS is an assoc array, where keys are modules and values are permissions bitfields, like:
    #
    #  $PERMS['org'] = 1
    #
    # you can run a just-in-time permissions check on it like so:
    #
    #   if ( TFD_HAS_PERMS( 'org' , 'use' ) ) echo "i can 'use' the 'org' module";
    #
    # this effectively causes a lookup for the permissions bitmask constant to apply:
    #
    #   'org' --> 'ORG' and 'use' --> 'USE' : checks $PERMS['org'] against TFD_PERM_ORG_USE
    #
    # TFD_HAS_PERMS() and the permissions bitmask constants are defined in {DOCROOT}/core/common.php
    #
    # also, can check multiple perms on one module at a time, like so:
    #
    #   if ( TFD_HAS_PERMS( 'org' , 'use' , 'edit' , 'delete', ... ) ) echo "got all those perms!";
    #

		#...

		@mysql_query("DELETE FROM sessions WHERE TIMESTAMPDIFF(SECOND,last,CURRENT_TIMESTAMP) > ".intval($USER["timeout"]));
    	if(isset($_GET["cmd"])){
      		if($_GET["cmd"]=="cookie"){

      		} else {
				@mysql_query("UPDATE sessions SET last=CURRENT_TIMESTAMP WHERE id='". $SESSION_ID ."'");
			}
		} else {
		  @mysql_query("UPDATE sessions SET last=CURRENT_TIMESTAMP WHERE id='". $SESSION_ID ."'");
    	}
	}
	if (isset($USER) && !$USER) unset($USER);
} else {

  @mysql_query("DELETE FROM sessions WHERE TIMESTAMPDIFF(SECOND,last,CURRENT_TIMESTAMP) > 1800");

}

function create_session($user) {
	$address = $_SERVER["REMOTE_ADDR"];
	$expire = time() + 60 * 60 * 24 * 30;
	$SESSION_ID = uniqid("",true);
	mysql_query("INSERT INTO sessions (id,users__id,remote_addr,customers__id,inventory_items__id,last) VALUES ('$SESSION_ID',". $user["id"] .",'". $address ."',NULL,NULL,NOW())") or die(mysql_error());
	setcookie("session_id",$SESSION_ID);
	return $SESSION_ID;
}

function delete_session($session_id) {
	mysql_query("DELETE FROM sessions WHERE id = '". $session_id ."'");
	setcookie("session_id",null,"-1");
}

?>