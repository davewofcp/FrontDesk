<h3>Customer Feedback</h3>

<form action="?module=cust&do=feedback_sub&id=<?php echo $CUSTOMER["id"]; ?>" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Customer</td>
  <td><?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Score</td>
  <td>
   <input type="radio" name="score" value="1"> 1
   <input type="radio" name="score" value="2"> 2
   <input type="radio" name="score" value="3"> 3
   <input type="radio" name="score" value="4"> 4
   <input type="radio" name="score" value="5"> 5
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Issue ID</td>
  <td>
   <input type="edit" name="issue_id" size="8" value="<?php echo isset($_GET["issue_id"]) ? $_GET["issue_id"] : ""; ?>">
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Feedback</td>
  <td><textarea name="feedback" rows="5" cols="50"></textarea></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" value="Submit Feedback">
  </td>
 </tr>
</table>
</form>
