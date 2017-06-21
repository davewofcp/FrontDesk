<script type="text/javascript">
function showHideMsg(obj) {
	if (obj.checked) {
		document.getElementById('msg').style.display = '';
	} else {
		document.getElementById('msg').style.display = 'none';
	}
}
</script>
<h3>View Newsletter</h3>

<?php echo alink_onclick("Delete This Newsletter","?module=admin&do=delete_newsletter&id=".$NEWSLETTER["id"],"javascript:return confirm('Are you sure you want to delete this newsletter?');"); ?><br><br>

<?php if (isset($RESPONSE)) { echo "<font size=\"+1\"><b>".$RESPONSE."</b></font><br><br>\n\n"; } ?>

Subject: <b><?php echo $NEWSLETTER["subj"]; ?></b><br>
Last emailed: <b><?php echo ($NEWSLETTER["last_emailed"] == null ? "Never" : $NEWSLETTER["last_emailed"]); ?></b><br>
Sent to: <b><?php echo $NEWSLETTER["emailed_to"]; ?></b> addresses<br>

<?php if ($NEWSLETTER["is_attachment"]) { ?>
<h3>Message Body</h3>
<div class="maininv">
<?php echo $NEWSLETTER["msg"]; ?>
</div><br>
<?php } ?>

<h3>Newsletter</h3>
<div class="maininv">
<?php echo $NEWSLETTER["html"]; ?>
</div><br><br><br>

<?php echo alink("Send Test Email To Me","?module=admin&do=send_newsletter&id=". $NEWSLETTER["id"] ."&test=1"); ?><br><br>
<?php echo alink_onclick("Send Newsletter Now","?module=admin&do=send_newsletter&id=". $NEWSLETTER["id"],"javascript:return confirm('Are you sure you want to send this newsletter now?');"); ?><br>

<hr>

<h3>Edit Newsletter</h3>

<form action="?module=admin&do=edit_newsletter&id=<?php echo $NEWSLETTER["id"]; ?>" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Subject</td>
  <td><input type="edit" name="subj" size="50" maxlength="255" value="<?php echo str_replace('"',"'",$NEWSLETTER["subj"]); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Attachment?</td>
  <td><input type="checkbox" name="is_attachment" value="1" onChange="javascript:showHideMsg(this);"<?php if ($NEWSLETTER["is_attachment"]) { echo " CHECKED"; } ?>></td>
 </tr>
 <tr id="msg"<?php if (!$NEWSLETTER["is_attachment"]) { echo " style=\"display:none;\""; } ?>>
  <td class="heading" align="right">Message Body</td>
  <td><textarea name="msg" rows="10" cols="50"><?php echo str_replace("</textarea>","&lt;/textarea>",$NEWSLETTER["msg"]); ?></textarea></td>
 </tr>
 <tr>
  <td class="heading" align="right">HTML</td>
  <td><textarea name="html" rows="30" cols="50"><?php echo str_replace("</textarea>","&lt;/textarea>",$NEWSLETTER["html"]); ?></textarea></td>
 </tr>
 <tr>
  <td colspan="2" align="center"><input type="submit" value="Save Changes"></td>
 </tr>
</table>
</form>
