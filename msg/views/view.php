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

$MSG = mysql_fetch_assoc(mysql_query($sql="SELECT *, m.id as id FROM messages m LEFT JOIN users u ON m.users__id__2 = u.id WHERE m.id = ".intval($_GET["id"])));

if ($MSG["users__id__1"] != $USER["id"]) {
	echo "Unable to view messages belonging to another user.";
} else {

	mysql_query("UPDATE messages SET is_read = 1 WHERE id = ".intval($_GET["id"]));

	$loc = "";
	$FROM_STORE = "";
	$TO_STORE = "";
	if ($MSG["box"] == 1) {
		if ($MSG["username"]) {
			$FROM = $MSG["username"];
			$FROM_STORE = " (St#".$LOC[ $MSG['org_entities__id'] ]['location_code'].")";
			$loc = "&location=".$MSG['org_entities__id'];
		} else {
			$FROM = "<i>System</i>");
		}
		$TO = $USER["username"];
	} else {
		$FROM = $USER["username"];
		$TO = $MSG["username"];
		$TO_STORE = " (St#".$LOC[ $MSG['org_entities__id'] ]['location_code'].")";
		$loc = "&location=".$MSG['org_entities__id'];
	}

?>

<h3>View Message</h3>

<table border="0" width="650">
 <tr>
  <td class="heading" align="right">From</td>
  <td><?php echo $FROM.$FROM_STORE; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">To</td>
  <td><?php echo $TO.$TO_STORE; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Date</td>
  <td><?php echo $MSG["ts"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Subject</td>
  <td><?php echo $MSG["subject"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Message</td>
  <td style="border: 1px solid #000;"><?php echo $MSG["message"]; ?></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <?php echo alink("Reply","?module=msg&do=send&to=".$FROM.$loc); ?> |
  <?php echo alink("Delete","?module=msg&do=delete&id=".$MSG["id"]); ?>
  </td>
 </tr>
</table>

<?php } ?>
