<h3>Bugs</h3>

<div><?php echo alink("Open New Bug","?module=bugs&do=new"); ?></div>

<br />

<div style="display:inline;margin:10px;"><?php echo alink("View New Bugs","?module=bugs"); ?></div>
<div style="display:inline;margin:10px;"><?php echo alink("View Open Bugs","?module=bugs&do=view_open"); ?></div>
<div style="display:inline;margin:10px;"><?php echo alink("View Closed Bugs","?module=bugs&do=view_closed"); ?></div>


<?php
$x=0;
$y="New Bugs";
if(isset($_GET["do"])){
  if($_GET["do"]=="view_open"){$x=1;$y="Open Bugs";}
  if($_GET["do"]=="view_closed"){$x=2;$y="Resolved Bugs";}
}
$result = mysql_query("SELECT * FROM bugs WHERE org_entities__id = {$USER['org_entities__id']} AND varref_status='".$x."' && (is_deleted IS NULL || is_deleted!=1)");

echo '
<div class="clear bold center"><br>'. $y .'</div>';

	while($BUG = mysql_fetch_assoc($result)){
		$result2 = mysql_query("SELECT username FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND id=".intval($BUG["users__id"]));
		if (mysql_num_rows($result2)) {
			$var = mysql_fetch_assoc($result2);
			$username = $var["username"];
		} else {
			$username = "<i>Deleted</i>";
		}

	$BUG["created_ts"] = explode(" ",$BUG["created_ts"]);
?>
<div class="relative floatL maininv" style="margin:2px;background:<?php echo $STATUS_COLOR[$BUG["varref_status"]]; ?>">

  <div class="floatL">
    <div class="itemhead">ID</div>
    <div class="itemrow"><?php echo $BUG["id"]; ?></div>
  </div>

  <div class="floatL">
    <div class="itemhead">Submitter</div>
    <div class="itemrow"><?php echo $username; ?></div>
  </div>

  <div class="floatL">
    <div class="itemhead">Date</div>
    <div class="itemrow"><?php echo $BUG["created_ts"][0]; ?></div>
  </div>

  <div class="floatL">
    <div class="itemhead">Importance</div>
    <div class="itemrow" style="<?php echo ($BUG["importance"]==2 ? "font-weight:bolder;color:#FF0000;" : "") ?>"><?php echo $BUG_IMPORTANCE[$BUG["importance"]]; ?></div>
  </div>

  <div class="floatL">
    <div class="itemhead">Type</div>
    <div class="itemrow"><?php echo $BUG_CATEGORIES[$BUG["category"]]; ?></div>
  </div>

  <div class="floatL clearL">
    <div class="itemrow">
      <div class="itemhead">Description</div>
      <?php
        $max = 210;
        echo substr($BUG["descr"],0,$max);
        if(mb_strlen($BUG["descr"])>$max || strlen($BUG["descr"])>$max)echo "...";
      ?>
    </div>
  </div>

  <div class="floatL relative">
    <div class="itemrow" style="margin-left:10px;"><?php echo alink("View","?module=bugs&do=view&id=".$BUG["id"]); ?>

<?php
if(TFD_HAS_PERMS('admin','use')){
	echo "&nbsp;&nbsp;". alink_onclick("Delete","?module=bugs&do=delete&id=".$BUG["id"],"return confirm('Are you sure you want to delete?')");
}
?>
</div>
</div>
</div>
<?php }

if(!mysql_num_rows($result)){
?>

<div class="bold">
  No <?php echo $y; ?> found!
</div>

<?php } ?>
