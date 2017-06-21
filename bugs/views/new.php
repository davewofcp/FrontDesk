<h3>New Bug</h3>

<div style="relative">
<form action="?module=bugs&do=new" method="post">

<div class="floatL relative margin5">
  <div class="heading">Description</div>
  <div class="floatL"><textarea name="descr" id="descr" style="height:120px;width:550px;"></textarea></div>
</div>

<div class="floatL">

<div class="floatL clearL relative margin5">
  <div class="heading">Importance</div>
  <div class="floatL">
    <select name="importance">
      <option value="1">Normal</option>
      <option value="2">Urgent</option>
    </select>
  </div>
</div>
</div>
<div class="floatL">
<div class="floatL relative margin5">
  <div class="heading">Category</div>
  <div class="floatL">
    <select name="category">
<?php

foreach ($BUG_CATEGORIES as $id => $cat) {
	if ($id == 0) continue;
	echo "    <option value=\"$id\">$cat</option>\n";
}

?>
</select>
</div>
</div>

<div class="clear"><br /></div>
<div class="center"><input type="submit" value="New Bug"></div>

<input type="hidden" name="action" value="New">
</form>
</div>
