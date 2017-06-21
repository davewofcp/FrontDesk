<?php

if (isset($_POST["question_id"])) {
	foreach ($_POST["question_id"] as $x => $id) {
		if ($id >= 1000000) {
			$sql = "INSERT INTO feedback_questions (question,is_active) VALUES (";
			$sql .= "'". mysql_real_escape_string($_POST["question"][$x]) ."',";
			if (isset($_POST["is_active_$id"])) $sql .= "1)";
			else $sql .= "0)";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		} else {
			$sql = "UPDATE feedback_questions SET ";
			$sql .= "question = '". mysql_real_escape_string($_POST["question"][$x]) ."',";
			$sql .= "is_active = ";
			if (isset($_POST["is_active_$id"])) $sql .= "1";
			else $sql .= "0";
			$sql .= " WHERE id = ".intval($id);
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
	}
}

$QUESTIONS = array();
$result = mysql_query("SELECT * FROM feedback_questions");
while ($row = mysql_fetch_assoc($result)) {
	$QUESTIONS[] = $row;
}

?>
<script type="text/javascript">
var counter = 1000000;
function addRow() {
	var table=document.getElementById("questions");
	var row=table.insertRow(-1);
	row.align = 'center';
	var cell1=row.insertCell(0);
	var cell2=row.insertCell(1);
	var cell3=row.insertCell(2);
	var cell4=row.insertCell(3);
	cell1.innerHTML = '--';
	cell2.innerHTML = '<input type="hidden" name="question_id[]" value="'+counter+'"><input type="text" name="question[]" size="40">';
	cell3.innerHTML = '<input type="checkbox" name="is_active_'+counter+'" value="1" CHECKED>';
	counter++;
}
</script>
<h3>Modify Feedback Questions</h3>

<i>These questions are asked via the customer portal. They are all on a scale of 1 to 5. Only Enabled questions will be asked. <b>Questions are shared globally and will be used for all stores.</b></i><br><br>

<a href="javascript:addRow();">Add Question</a><br><br>

<form action="?module=admin&do=feedback_questions" method="post">
<table border="0" id="questions">
 <tr align="center">
  <td><b>ID</b></td>
  <td><b>Question</b></td>
  <td><b>Enabled</b></td>
 </tr>
<?php

foreach ($QUESTIONS as $question) {
	echo " <tr align=\"center\">\n";
	echo "  <td>{$question["id"]}</td>\n";
	echo "  <td><input type=\"hidden\" name=\"question_id[]\" value=\"{$question["id"]}\"><input type=\"text\" name=\"question[]\" size=\"40\" value=\"".str_replace('"','\"',$question["question"])."\"></td>\n";
	echo "  <td><input type=\"checkbox\" name=\"is_active_{$question["id"]}\" value=\"1\"".($question["is_active"] ? " CHECKED":"")."></td>\n";
	echo " </tr>\n";
}

?>
</table>
<input type="submit" value="Save Changes">
</form>
