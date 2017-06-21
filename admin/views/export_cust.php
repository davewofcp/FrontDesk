<h3>Export Customer Data</h3>

Data will be exported into a CSV (comma-separated) file. Select fields to include.<br><br>

<form action="core/export.php?export=cust" method="post">
<table border="0">
 <tr>
  <td class="heading">Customer ID</td>
  <td><input type="checkbox" name="customer_id" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">First Name</td>
  <td><input type="checkbox" name="firstname" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Last Name</td>
  <td><input type="checkbox" name="lastname" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Sex</td>
  <td><input type="checkbox" name="sex" value="1"></td>
 </tr>
 <tr>
  <td class="heading">Date of Birth</td>
  <td><input type="checkbox" name="dob" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Company</td>
  <td><input type="checkbox" name="company" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Street Address</td>
  <td><input type="checkbox" name="address" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">City</td>
  <td><input type="checkbox" name="city" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">State</td>
  <td><input type="checkbox" name="state" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Zip</td>
  <td><input type="checkbox" name="zip" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Email</td>
  <td><input type="checkbox" name="email" value="1"></td>
 </tr>
 <tr>
  <td class="heading">Home Phone</td>
  <td><input type="checkbox" name="phone_home" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Cell Phone</td>
  <td><input type="checkbox" name="phone_cell" value="1" CHECKED></td>
 </tr>
 <tr>
  <td class="heading">Referral</td>
  <td><input type="checkbox" name="referral" value="1"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Export to CSV">
  </td>
 </tr>
</table>
</form>
