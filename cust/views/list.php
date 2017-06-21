<h3>All Customers</h3>

<table width="780" border="0">
 <tr class="heading" align="center">
  <td>#</td>
  <td>First Name</td>
  <td>Last Name</td>
  <td>Company</td>
  <td>Home Phone</td>
  <td>Cell Phone</td>
  <td>View</td>
 </tr>
<?php

while ($customer = mysql_fetch_assoc($CUSTOMERS)) {
	echo " <tr align=\"center\">\n";
	echo "  <td>". $customer["id"] ."</td>\n";
	echo "  <td>". $customer["firstname"] ."</td>\n";
	echo "  <td>". $customer["lastname"] ."</td>\n";
	echo "  <td>". $customer["company"] ."</td>\n";
	echo "  <td>". ($customer["phone_home"] ? display_phone($customer["phone_home"]) : "<i>None</i>") ."</td>\n";
	echo "  <td>". ($customer["phone_cell"] ? display_phone($customer["phone_cell"]) : "<i>None</i>") ."</td>\n";
	echo "  <td>". alink("View","?module=cust&do=view&id=". $customer["id"]) ."</td>\n";
	echo " </tr>\n";
}

?>
</table>
