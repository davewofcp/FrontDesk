<?php
$id = intval($_GET["id"]);

$BUG = mysql_fetch_assoc(mysql_query("SELECT * FROM bugs WHERE org_entities__id = {$USER['org_entities__id']} AND id=".$id));

$result = mysql_query("SELECT username FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND id=".intval($BUG["users__id"]));
if (mysql_num_rows($result)) {
	$var = mysql_fetch_assoc($result);
	$username = $var["username"];
} else {
	$username = "<i>Deleted</i>";
}

?><h3>View Bug #<?php echo $_GET["id"] ?></h3>
<div class="relative floatL maininv" style="">
<form action="?module=bugs&do=view&id=<?php echo intval($_GET["id"]); ?>" method="post">

  <div class="floatL">
    <div class="itemhead">Submitter</div>
    <div class="itemrow"><?php echo $username; ?></div>
  </div>

  <div class="floatL">
    <div class="itemhead">Date</div>
    <div class="itemrow"><?php echo $BUG["created_ts"]; ?></div>
  </div>

  <div class="floatL clearL">
    <div class="itemhead">Status</div>
    <div class="itemrow"><?php echo $BUG_STATUS[$BUG["varref_status"]]; ?></div>
  </div>

  <div class="floatL">
    <div class="itemhead">Importance</div>
    <div class="itemrow" style="<?php echo ($BUG["importance"]==2 ? "font-weight:bolder;color:#FF0000;" : "") ?>"><?php echo $BUG_IMPORTANCE[$BUG["importance"]]; ?></div>
  </div>

  <div class="floatL clearL">
    <div class="itemhead">Type</div>
    <div class="itemrow"><?php echo $BUG_CATEGORIES[$BUG["category"]]; ?></div>
  </div>

  <div class="floatL clear">
    <div class="itemrow">
      <div class="itemhead">Description</div>
      <?php echo $BUG["descr"]; ?>
    </div>
  </div>
<?php
$result = mysql_query("SELECT * FROM bugs_notes WHERE org_entities__id = {$USER['org_entities__id']} AND bugs__id=".$BUG["id"]." ORDER BY note_ts DESC");

if(mysql_num_rows($result)){
	echo '
	<div class="floatL clear">
	<div class="itemhead">Notes</div>
	<div class="floatL">';
	while($row = mysql_fetch_assoc($result)){

		$result2 = mysql_query("SELECT username FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND id=".intval($row["users__id"]));
		if (mysql_num_rows($result2)) {
			$var = mysql_fetch_assoc($result2);
			$username = $var["username"];
		} else {
			$username = "<i>Deleted</i>";
		}
		echo "
		<div style=\"margin:15px;\">
		<div class=\"block left\" style=\"width:620px;padding:5px 5px 0px 0px;\">". $row["note"] ."</div>
		<div class=\"bolder bbottom left\">Written by ". $username ." on ". $row["note_ts"] ."</div>
		</div>
		";
	}
	echo "
	</div>
	</div>";
}
?>
  <div class="clear"><br></div>
  <div class="floatL clear">
    <div class="itemhead">New Note</div>
    <div class="itemrow"><textarea name="new_note" style="width:600px;height:100px;"></textarea></div>
  </div>

  <div class="clear center"><input type="submit" value="Add Note"></div>


  <input type="hidden" name="action" value="new_note">
  <input type="hidden" name="bug_id" value="<?php echo intval($_GET["id"]); ?>">
  </form>

  <?php
  if(isset($_GET["admin"])){
  ?>
  <div class="clear"><br></div>

  <form action="?module=bugs&do=update" method="post">
  <div class="relative center" align="center">

    <div class="relative center bold">Admin Update</div>

    <div class="clear"></div>

    <div class="floatL">
      <div class="itemhead">Status:</div>
      <div class="itemrow">
        <select name="status">
          <option value="0"<?php echo ($BUG["varref_status"]==0 ? " SELECTED" : "") ?>>New</option>
          <option value="1"<?php echo ($BUG["varref_status"]==1 ? " SELECTED" : "") ?>>Do it</option>
          <option value="2"<?php echo ($BUG["varref_status"]==2 ? " SELECTED" : "") ?>>Finished</option>
        </select>
      </div>
    </div>
    <div class="floatL">
      <div class="itemhead">Importance:</div>
      <div class="itemrow">
        <select name="importance">
          <option value="1"<?php echo ($BUG["importance"]==1 ? " SELECTED" : "") ?>>Normal</option>
          <option value="2"<?php echo ($BUG["importance"]==2 ? " SELECTED" : "") ?>>Urgent</option>
        </select>
      </div>
    </div>

    <div class="floatL">
      <div class="itemhead">Category:</div>
      <div class="itemrow">
    <select name="category">
<?php

foreach ($BUG_CATEGORIES as $id => $cat) {
	if ($id == 0) continue;
	$s = "";
	if ($id == $BUG["category"]) $s = " SELECTED";
	echo "    <option value=\"$id\"$s>$cat</option>\n";
}

?>
        </select>
      </div>
    </div>

    <div class="floatL" style="">
      <div class="itemrow"><input type="submit" value="Update Bug"></div>
    </div>
  </div>

  <input type="hidden" name="bug_id" value="<?php echo intval($_GET["id"]); ?>">
  </form>

  <?php } ?>

</div>


