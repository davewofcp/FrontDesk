<h3>Choose Newsletter</h3>

<form action="?" method="get">
<input type="hidden" name="module" value="admin">
<input type="hidden" name="do" value="view_newsletter">
<select name="id">
<?php

$result = mysql_query("SELECT id,CAST(created AS date) AS create_date,subj FROM newsletters WHERE 1 ORDER BY created");
while ($row = mysql_fetch_assoc($result)) {
	echo "<option value=\"".$row["id"]."\">[".$row["create_date"]."] ".$row["subj"]."</option>\n";
}

?>
</select>
<input type="submit" value="View Newsletter">
</form>
