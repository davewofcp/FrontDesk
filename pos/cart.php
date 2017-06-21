<?php


function add_to_cart($from_table, $from_key_name, $from_key, $writeback,
					 $amt, $qty, $descr, $taxable, $group, $heading, $customer_id = 0) {
	global $USER, $ITEMS_IN_CART, $SESSION;

	$sql = "INSERT INTO pos_cart_items (from_table,from_key_name,from_key,writeback,".
	       "amt,qty,descr,is_taxable,users__id__sale,grp,is_heading) VALUES (";
	$sql .= "'". mysql_real_escape_string($from_table) ."',";
	$sql .= "'". mysql_real_escape_string($from_key_name) ."',";
	$sql .= "'". mysql_real_escape_string($from_key) ."',";
	$sql .= "'". mysql_real_escape_string($writeback) ."',";
	$sql .= "'". mysql_real_escape_string($amt) ."',";
	$sql .= strval(intval($qty)) .",";
	$sql .= "'". mysql_real_escape_string($descr) ."',";
	$sql .= "'". mysql_real_escape_string($taxable) ."',";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($group) ."',";
	$sql .= "'". mysql_real_escape_string($heading) ."')";
	mysql_query($sql) or die(mysql_error() .";;". $sql);

	$id = mysql_insert_id();

	$ITEMS_IN_CART++;

	if (intval($customer_id) > 0)
		set_customer(intval($customer_id));

	return $id;
}

?>
