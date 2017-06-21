<script type="text/javascript">
function showHideMsg(obj) {
	if (obj.checked) {
		document.getElementById('msg').style.display = '';
	} else {
		document.getElementById('msg').style.display = 'none';
	}
}
</script>

<h3>Create Newsletter</h3>

<form action="?module=admin&do=create_newsletter" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Subject</td>
  <td><input type="edit" name="subj" size="50" maxlength="255"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Attachment?</td>
  <td><input type="checkbox" name="is_attachment" value="1" onChange="javascript:showHideMsg(this);"></td>
 </tr>
 <tr id="msg" style="display:none;">
  <td class="heading" align="right">Message Body</td>
  <td><textarea name="msg" rows="10" cols="50"></textarea></td>
 </tr>
 <tr>
  <td class="heading" align="right">HTML</td>
  <td><textarea name="html" rows="30" cols="50"></textarea></td>
 </tr>
 <tr>
  <td colspan="2" align="center"><input type="submit" value="Create Newsletter"></td>
 </tr>
</table>
</form>
