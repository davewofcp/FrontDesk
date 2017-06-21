<?php

if (isset($_GET["to"])) {
	$TO = $_GET["to"];
} else {
	$TO = "";
}

if (isset($_GET["location"])) {
	$LOCATION = $_GET["location"];

  $result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$LOCATION}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");

	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
	} else {
    $result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			$LOCATION = $data["id"];
		} else {
			$LOCATION = 0;
		}
	}
} else {
    $result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		$LOCATION = $data["id"];
	} else {
		$LOCATION = 0;
	}
}

$USERS = mysql_query("SELECT id,username FROM users WHERE is_disabled = 0 ORDER BY username");

?>
<script type="text/javascript">
var to = '<?php echo $TO; ?>';
function refreshLocation() {
	var s = document.getElementById('location');
	var store = s.options[s.selectedIndex].value;
	window.location = '?module=msg&do=send&location='+store+'&to='+to;
}
function checkFields() {
	if (document.getElementById('subject').value == '') {
		alert('Subject is required.');
		return false;
	}
	var to = document.getElementById('to');
	if (to.options[to.selectedIndex].value == '0') {
		var c = confirm('Send this message to all users?');
		if (!c) return false;
	}
	return true;
}
</script>
<h3>Send Message</h3>

<form action="?module=msg&do=send_sub" method="post">
<table border="0" width="650">
 <tr>
  <td class="heading" align="right">Store</td>
  <td>
   <select id="location" name="location" onChange="refreshLocation();">
<?php

//$result = mysql_query("SELECT store_number,name FROM locations");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
while ($row = mysql_fetch_assoc($result)) {
	echo "<option value=\"{$row["id"]}\"".($row["id"] == $LOCATION ? " SELECTED":"").">{$row["title"]}</option>\n";
}

?>
   </select>
  </td>
 </tr>
  <td class="heading" align="right">To</td>
  <td>
   <select id="to" name="to"><option value="0">All Users</option>
<?php

while ($_user = mysql_fetch_assoc($USERS)) {
	if ($_user["username"] == $TO) $s = " SELECTED";
	else $s = "";
	echo "   <option value=\"".$_user["id"]."\"".$s.">".$_user["username"]."</option>\n";
}

?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Subject</td>
  <td><input type="edit" id="subject" name="subject" size="50" maxlength="200"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Message</td>
  <td><textarea name="message" rows="6" cols="60"></textarea></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" value="Send Message" onClick="return checkFields();">
  </td>
 </tr>
</table>
</form>
