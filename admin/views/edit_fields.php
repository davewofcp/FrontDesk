<?php

if (isset($_POST["category"])) {
	$CATEGORY = mysql_real_escape_string($_POST["category"]);
}
if (isset($_POST["category_new"]) && $_POST["category_new"] != "") {
	$CATEGORY = mysql_real_escape_string($_POST["category_new"]);
}

if (isset($_POST["do"]) && $_POST["do"] == "Add New") {

	mysql_query("INSERT INTO option_values (category,value) VALUES ('".$CATEGORY."','".mysql_real_escape_string($_POST["new_value"])."')");

  $_POST["option_id"] = mysql_insert_id();
}


if (isset($_POST["do"]) && $_POST["do"] == "Change") {

	mysql_query("UPDATE option_values SET value = '".mysql_real_escape_string($_POST["edit_value"])."' WHERE id = ".intval($_POST["option_id"]));

}

if (isset($_POST["do"]) && $_POST["do"] == "Delete") {

	mysql_query("DELETE FROM option_values WHERE id = ".intval($_POST["option_id"]));

}


if(isset($CATEGORY)){
  switch($CATEGORY){
    case "device_cat":
      $CATEGORY = "device_type";
    break;
  }
}
if(isset($_POST["category"])){
  switch($_POST["category"]){
    case "device_cat":
      $_POST["category"] = "device_type";
    break;
  }
}

?>
<style type="text/css">
#con1 {
  position: relative;
  display: inline;
  float: left;
  width: 100%;
}
#con2 {
  position: relative;
  float: right;
  display: inline;
}
#form1 {
  /*margin-left:75px;*/
}
#form2 {
  /*margin-right:50px;*/
}
.clear {
  clear: both;
}
.right {
  text-align: right;
}
.left {
  text-align: left;
}
.border {
  border:1px solid #000;
}
.bold {
  font-weight: bold;
}
.width250 {
  max-width: 250px;
}
.arr {
  text-decoration: none;
  color: black;
  font-weight: bolder;
  font-size: 48px;
}
.none {
  text-decoration: none;
  border: none;
}
</style>

<h3>Editable Fields</h3>
<div id="con1">
  <?php if (isset($CATEGORY)) { ?><b>Category:</b> <?php echo $CATEGORY; } else { ?><b>Please Select a Category</b><?php } ?>

  <form action="?module=admin&do=edit_fields" id="form1" method="post">
  <?php if (isset($CATEGORY)) { ?><input type="hidden" name="category" value="<?php echo $CATEGORY; ?>"><?php } ?>

  <select name="<?php echo (isset($CATEGORY) ? "option_id":"category"); ?>" multiple id="stform" style="width:250px;height:200px;">
  <?php

  if (isset($CATEGORY)) {
  	$result = mysql_query("SELECT * FROM option_values WHERE category = '".$CATEGORY."' ORDER BY value");
  	while ($row = mysql_fetch_assoc($result)) {
  		echo "<option id=\"ostep".$row["id"]."\" value=\"".$row["id"]."\">".$row["value"]."</option>\n";
  	}
  } else {
  	$result = mysql_query("SELECT DISTINCT category FROM option_values ORDER BY category");
  	while ($row = mysql_fetch_assoc($result)) {
  		echo "<option value=\"".$row["category"]."\">".$row["category"]."</option>\n";
  	}
  }

  ?>
  </select>
  <br>
  <?php

  if(isset($CATEGORY)){

    if($CATEGORY=="device_type") {

  ?>

  <div class="left width250">
    Name: <input type="edit" name="new_value" size="20"><br />
    <input type="submit" name="do" value="Add New">
  </div>
  <br>
  <div class="left width250">
    Name: <input type="edit" name="edit_value" id="edit_a_value" size="20"><br />
    <input type="submit" name="do" value="Change">
    <input type="submit" name="do" value="Delete">
  </div>

  <?php

    } else {

  ?>

  <div class="width250">
    <input type="edit" name="new_value" size="30">
    <input type="submit" name="do" value="Add New"><br>
    <input type="edit" name="edit_value" id="edit_a_value" size="30"><br>
    <input type="submit" name="do" value="Change">
    <input type="submit" name="do" value="Delete">
  </div>

  <?php

    }

  } else {

  ?>

  <div class="width250">
    <input type="submit" value="View Options">
    <br><br>
    <input type="edit" name="category_new" size="30">
    <input type="submit" name="do" value="Add New Category">
  </div>

  <?php

  }

  ?>

  </form>
</div>

<?php
if(isset($CATEGORY) && ($CATEGORY=="device_type")) {
?>

<div id="con2"></div>

<?php
}
?>

<div class="clear"></div>
<?php
  echo alink("Back to Administration","?module=admin");
?>
