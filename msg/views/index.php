<?php

$sql = <<<EOMYSQL
--
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store';
--
EOMYSQL;

$result = MYSQL_QUERY( $sql ) );
$LOC = ARRAY();
WHILE ( FALSE !== ($row = MYSQL_FETCH_ASSOC( $result ) ) ) {
  $LOC[ $row['id'] ] = $row;
}

$MESSAGE_BOX = array(
	"",
	"Inbox",
	"Sent Messages"
);

if (isset($_GET["box"])) {
	$BOX = intval($_GET["box"]);
	if ($BOX > 2 || $BOX < 1) $BOX = 1;
} else {
	$BOX = 1;
}

?><h3><?php echo $MESSAGE_BOX[$BOX]; ?></h3>
<?php if (isset($RESPONSE)) { echo "<font size=\"+2\">".$RESPONSE."</font><br><br>\n"; } ?>

<?php echo alink("View ".$MESSAGE_BOX[3 - $BOX],"?module=msg&do=index&box=".(3 - $BOX)); ?> |
<?php echo alink("Send Message","?module=msg&do=send"); ?><br>

<table border="0" width="700">
 <tr class="heading" align="center">
  <td>User</td>
  <td>Date</td>
  <td>Subject</td>
  <td>Delete</td>
 </tr>
<?php

$i = 0;
$result = mysql_query("SELECT *,m.id as id FROM messages m LEFT JOIN users u ON m.users__id__2 = u.id WHERE m.users__id__1 = ".$USER["id"]." AND m.box = ".$BOX." ORDER BY m.ts DESC",$DB);
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\"".($row["is_read"] ? "":" style=\"font-weight:bold;\"").">\n";
	if ($row["username"]) {
		echo "  <td>". $row["username"] ." (St#".$LOC[ $row['org_entities__id'] ]['location_code'].")</td>\n";
	} else {
		echo "  <td><i>System</i></td>\n";
	}
	echo "  <td>". $row["ts"] ."</td>\n";
	echo "  <td><a href=\"?module=msg&do=view&id=". $row["id"] ."\">". $row["subject"] ."</a></td>\n";
	echo "  <td>". alink("Delete","?module=msg&do=delete&box=".$BOX."&id=". $row["id"]) ."</td>\n";
	echo " </tr>\n";
	$i++;
}

echo " <tr><td colspan=\"4\" align=\"center\">".$i." messages</td></tr>\n";

?>
</table>
