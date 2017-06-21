<?php

if (!isset($STORES)) $STORES = array();
if (isset($_POST["stores"])) {
	$STORE_MAX = intval($_POST["stores"]);
	for ($i = 1; $i <= $STORE_MAX; $i++) {
		if (isset($_POST["store".$i])) $STORES[] = $i;
	}
}
if (isset($_POST["all"])) $ALL_STORES = 1;

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

$ENTITIES = array();
//$result = mysql_query("SELECT * FROM locations ORDER BY store_number");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
ORDER BY
  location_code
");
while ($row = mysql_fetch_assoc($result)) {
	if (in_array($row["id"],$STORES) || isset($ALL_STORES)) {
		$ENTITIES[$row['id']] = $row;
	}
}

if (!isset($REPORT)) $REPORT = "";
$REPORT .= <<<EOF
<h3>Store Report</h3>
$START until $END<br><br>
EOF;

$TOTAL_MONEY = array();

$chartx=0;
foreach ($ENTITIES as $eid => $entity) {
	$REPORT .= "<div style=\"border-radius: 20px;border:5px ridge #000;padding:15px 7px 15px 7px;margin:15px 0px 15px 0px;\">";
// 	$REPORT .= "<b>Connecting to store #". $entity["store_number"] ." ". $entity["name"] ." at ". $entity["db_host"] ."...</b><br>";
// 	$link = @mysql_connect($entity["db_host"],$entity["db_user"],$entity["db_pass"],true);
// 	if (!$link) {
// 		$REPORT .= "<font color=\"#FF0000\"><b>Failed to connect to database.</b></font><hr>";
// 		continue;
// 	}
// 	$success = @mysql_select_db($entity["db_db"],$link);
// 	if (!$success) {
// 		$REPORT .= "<font color=\"#FF0000\"><b>Failed to select database.</b></font><hr>";
// 		continue;
// 	}

	$REPORT .= "<div style=\"background-color:#CCFFCC;width:100%;\"><b>#". $entity["location_code"] ." ". $entity["title"] ." - ". $entity["city"] .", ". $entity["state"] ."</b></div>";

	$totals = mysql_fetch_assoc(mysql_query("SELECT SUM(paid_cash) AS cash,SUM(paid_credit) AS credit,SUM(paid_check) AS checks,COUNT(*) AS count FROM pos_transactions WHERE org_entities__id = {$eid} AND CAST(tos AS date) >= '".$START."' AND CAST(tos AS date) < '".$END."' AND line_number = 0"));//,$link));
	$total = $totals["cash"] + $totals["credit"] + $totals["checks"];

	$TOTAL_MONEY[$eid] = $total;

	$TRANS = array();
	$result = mysql_query("
SELECT
  t.id,
  t.customers__id,
  c.firstname,
  c.lastname,
  t.paid_cash,
  t.paid_credit,
  t.paid_check,
  t.tos,
  t.descr
FROM pos_transactions t
LEFT JOIN customers c
ON t.customers__id = c.id
WHERE t.org_entities__id = {$eid}
AND t.line_number = 0
AND CAST(t.tos AS date) >= '$START'
AND CAST(t.tos AS date) < '$END'
ORDER BY t.tos DESC
");
	while ($row = mysql_fetch_assoc($result)) {
		$TRANS[] = $row;
	}

	$REPORT .= "
  <div class=\"clear\"><br></div>

  <div class=\"itemrow center\" style=\"width:450px;float:none;margin:10px;\">
    <div class=\"itemcontent\">

      <div style=\"float:left;font-size:20px;padding:3px;\">Transactions</div>
      <div class=\"bold\" style=\"padding:3px;font-size:20px;float:left;\">".intval($totals["count"])."</div>

      <div class=\"clearL\" style=\"float:left;font-size:20px;padding:3px;\">Total</div>
      <div class=\"bold\" style=\"padding:3px;color:red;font-size:20px;float:left;\">$".number_format(floatval($total),2)."</div>
      <div class=\"clear\"><br></div>
      <div class=\"clearL\" style=\"float:left;font-size:20px;padding:3px;\">Cash</div>
      <div class=\"bold\" style=\"padding:3px;color:red;font-size:20px;float:left;\">$".number_format(floatval($totals["cash"]),2)."</div>

      <div class=\"clearL\" style=\"float:left;font-size:20px;padding:3px;\">Credit</div>
      <div class=\"bold\" style=\"padding:3px;color:red;font-size:20px;float:left;\">$".number_format(floatval($totals["credit"]),2)."</div>

      <div class=\"clearL\" style=\"float:left;font-size:20px;padding:3px;\">Checks</div>
      <div class=\"bold\" style=\"padding:3px;color:red;font-size:20px;float:left;\">$".number_format(floatval($totals["checks"]),2)."</div>

    </div>
  ";

  $width="280";
  $height="200";

	$REPORT .= "
    <div class=\"itemcontent\">
      <div class=\"relative\" id=\"trans_chart".$chartx."\" style=\"width:".$width."px;height:".$height."px;\"></div>
    </div>

  </div>

  <script type=\"text/javascript\">
    var transChart = new dhtmlXChart({
    	view: \"pie3D\",
  	  cant:0.7,
  	  gradient:true,
      width: ".$width.",
      ".($total>0 ? "" : "pieInnerText:\"None!\",")."
      height: 20,
    	container: \"trans_chart".$chartx."\",
      color:\"#color#\",
    	value: \"#amount#\",
      label: \"#type#\",
	    radius:".($width/3.18)."
    });
  ";
	if($total>0){
		$REPORT .= "
    var data = [
         {
          id: \"1\",
          amount:".number_format(floatval($totals["cash"]),2).",
          type:\"Cash\",
          color:\"#008800\"
         },
         {
          id: \"2\",
          amount:".number_format(floatval($totals["credit"]),2).",
          type:\"Credit\",
          color:\"#880000\"
         },
         {
          id: \"3\",
          amount:".number_format(floatval($totals["checks"]),2).",
          type:\"Checks\",
          color:\"#000088\"
         }
    ];
    ";
	} else {
		$REPORT .= "
    var data = [
         {
          id: \"1\",
          amount:10000,
          type:\"\",
          color:\"#660000\"
         }
    ];
    ";
	}

	$REPORT .= "
    transChart.parse(data,\"json\");
    //var tt = getElementsByClass(\"dhx_chart\");
    ".($total>0 ? "" : "var tt = getElementsByClass(\"dhx_chart\");")."
    ".($total>0 ? "" : "tt[".$chartx."].style.color=\"white\";")."
    //alert(tt[".$chartx."].innerHTML);
  </script>

  <div class=\"clear\"><br></div>
  ";
  $chartx++;

	// Get user list
	$ST_CHANGES = array();
	$USERS = array();
	$result = mysql_query("SELECT id,username FROM users WHERE org_entities__id = {$eid} AND is_disabled = 0");//,$link);
	while ($row = mysql_fetch_assoc($result)) {
		$USERS[$row["id"]] = $row["username"];
		$ST_CHANGES[$row["id"]] = array();
	}

	// Count all issue changes for each user
	for ($k = 1; $k < count($STATUS); $k++) {
		$and = " AND (issue_changes.description LIKE 'Status%' OR issue_changes.description = 'Issue opened') ";
		$result = mysql_query("SELECT users__id,COUNT(*) AS count FROM issue_changes WHERE org_entities__id = {$eid} AND varref_status = ". $k ."".$and."AND CAST(tou AS date) >= '".$START."' AND CAST(tou AS date) < '".$END."' GROUP BY users__id");//,$link);
		while ($row = mysql_fetch_assoc($result)) {
			if (!isset($ST_CHANGES[$row["users__id"]])) continue;
			$ST_CHANGES[$row["users__id"]][$k] = intval($row["count"]);
		}
	}

	$REPORT .= "<font size=\"+2\">Issue Status Changes</font>\n";
	$REPORT .= "<table border=\"0\">\n";
	$REPORT .= " <tr class=\"heading\" style=\"font-size:10pt;\" align=\"center\">\n";
	$REPORT .= "  <td>Username</td>\n";
	for ($k = 1; $k < count($STATUS); $k++) {
		$REPORT .= "  <td>". $STATUS[$k] ."</td>\n";
	}
	$REPORT .= " </tr>\n";

	foreach ($USERS as $user_id => $username) {
    $var=0;
		for ($k = 1; $k < count($STATUS); $k++) {
			if (!isset($ST_CHANGES[$user_id][$k])) $ST_CHANGES[$user_id][$k] = 0;
			if(intval($ST_CHANGES[$user_id][$k]) > 0){
        $var++;
      }
		}
    if($var > 0){
		$REPORT .= "<!-- ". $user_id ." -->";
		$REPORT .= " <tr align=\"center\">\n";
		$REPORT .= "  <td>". $username ."</td>\n";
		for ($k = 1; $k < count($STATUS); $k++) {
			if (!isset($ST_CHANGES[$user_id][$k])) $ST_CHANGES[$user_id][$k] = 0;
			$REPORT .= "  <td>". intval($ST_CHANGES[$user_id][$k]) ."</td>\n";
		}
		$REPORT .= " </tr>\n";
	  }
	}

	$REPORT .= "</table>\n";

	$SERVICES = array();
	$result = mysql_query("SELECT * FROM services WHERE 1");
	while ($row = mysql_fetch_assoc($result)) {
		$SERVICES[$row["id"]] = $row["name"];
	}

	for ($k = 1; $k < count($STATUS); $k++) {
		$REPORT .= "<font size=\"+2\">".$STATUS[$k]." Issues</font><br>\n";
		$and = " AND issue_changes.description LIKE 'Status%' ";
		$sql = "
SELECT
  issues.id,
  issues.subtotal,
  issues.do_price,
  issues.quote_price,
  firstname,
  lastname,
  intake_ts,
  category_name,
  manufacturer,
  model,
  services,
  issues.varref_status
FROM issue_changes
JOIN issues
ON issue_changes.issues__id = issues.id
LEFT JOIN customers
ON issues.customers__id = customers.id
LEFT JOIN inventory_type_devices
ON issues.device_id = inventory_type_devices.id
LEFT JOIN categories
ON inventory_type_devices.categories__id = categories.id
WHERE issue_changes.org_entities__id = {$eid}
AND issue_changes.varref_status = ".$k."
".$and."
AND CAST(tou AS date) >= '".$START."'
AND CAST(tou AS date) < '".$END."'"
;
		//$result = mysql_query($sql,$link) or die(mysql_error() ."::". $sql);
    $result = mysql_query($sql) or die(mysql_error() ."::". $sql);
		if(mysql_num_rows($result) > 0){
			$REPORT .= "<table class=\"tabledata\" style=\"border:none;\">\n";
			$REPORT .= " <tr align=\"center\" class=\"heading2\">\n";
			$REPORT .= "  <td style=\"width:40px;\">First Name</td>\n";
			$REPORT .= "  <td style=\"width:50px;\">Last Name</td>\n";
			$REPORT .= "  <td style=\"width:50px;\">Intake Time</td>\n";
			$REPORT .= "  <td style=\"width:55px;\">MFC / Model</td>\n";
			$REPORT .= "  <td style=\"width:115px;\">Service Type</td>\n";
			$REPORT .= "  <td style=\"width:45px;\">Status</td>\n";
			$REPORT .= "  <td style=\"width:55px;\">Subtotal</td>\n";
			$REPORT .= " </tr>\n";
			$REPORT .= "</table>\n";
		} else {
			$REPORT .= "<p style=\"font-weight:bold;\">None!</p>";
		}

		while ($row = mysql_fetch_assoc($result)) {
			$REPORT .= "<table border=\"0\" class=\"tabledata\">\n";
			$REPORT .= " <tr align=\"center\">\n";
			$REPORT .= "  <td style=\"width:40px;font-size:13px;\">".$row["firstname"]."</td>\n";
			$REPORT .= "  <td style=\"width:50px;font-size:13px;\">".$row["lastname"]."</td>\n";
			$REPORT .= "  <td style=\"width:50px;font-size:13px;\">".$row["intake_ts"]."</td>\n";
			$REPORT .= "  <td style=\"width:55px;font-size:13px;\">".$row["manufacturer"]."<br>".$row["model"]."</td>\n";

			$str = "";
			if ($row["services"] && $row["services"] != "") {
				$s = explode(":",$row["services"]);
				$sn = array();
				foreach ($s as $svc) {
					if ($svc == "" || !isset($SERVICES[$svc])) continue;
					$sn[] = $SERVICES[$svc];
				}
				if (count($sn) > 0) {
					$str = "- ". join("<br>- ",$sn);
				} else {
					$str = "<i>None</i>";
				}
			} else {
				$str = "<i>None</i>";
			}
			$REPORT .= "  <td style=\"width:115px;font-size:13px;\">".$str."</td>\n";
			$REPORT .= "  <td bgcolor=\"".$ST_COLORS[$row["varref_status"]]."\" style=\"width:45px;\">".$STATUS[$row["varref_status"]]."</td>\n";

			// Price
			$price=0;
			$price = $row["do_price"];

			if(!$row["do_price"]){
					$price = $row["quote_price"];
			}

			$REPORT .= "  <td style=\"width:55px;\">$".number_format($price,2)."</td>\n";
			$REPORT .= " </tr>\n";
			$REPORT .= "</table>\n";
			if (!isset($EMAILING)) {
				$REPORT .= alink_pop("View Issue ".$row["id"],"?module=iss&do=view&id=".$row["issues__id"]) ."<br>\n";
			} else {
				//$REPORT .= alink("View Issue ".$row["issues__id"],"http://". $entity["db_host"] ."/new/?module=iss&do=view&id=".$row["issues__id"]) ."<br>\n";
        $REPORT .= alink("View Issue ".$row["issues__id"],"http://". $_SERVER['HTTP_HOST'] ."/?module=iss&do=view&id=".$row["issues__id"]) ."<br>\n";
			}
		}
		$REPORT .= "<br><br>";

	}

	// RESOLVED ISSUES
	$REPORT .= "<font size=\"+2\">Resolved / Checked Out Issues</font><br>\n";
	$sql = "
SELECT
  issues.id,
  issues.subtotal,
  issues.do_price,
  issues.quote_price,
  firstname,
  lastname,
  intake_ts,
  category_name,
  manufacturer,
  model,
  services,
  issues.varref_status
FROM issue_changes
JOIN issues
ON issue_changes.issues__id = issues.id
LEFT JOIN customers
ON issues.customers__id = customers.id
LEFT JOIN inventory_type_devices
ON issues.device_id = inventory_type_devices.id
LEFT JOIN categories
ON inventory_type_devices.categories__id = categories.id
WHERE issue_changes.org_entities__id = {$eid}
AND issue_changes.description = 'Resolved'
AND CAST(tou AS date) >= '".$START."'
AND CAST(tou AS date) < '".$END."'
";

	//$result = mysql_query($sql,$link) or die(mysql_error($link) ."::". $sql);
  $result = mysql_query($sql) or die(mysql_error() ."::". $sql);
	if(mysql_num_rows($result) > 0){
		$REPORT .= "<table class=\"tabledata\" style=\"border:none;\">\n";
		$REPORT .= " <tr align=\"center\" class=\"heading2\">\n";
		$REPORT .= "  <td style=\"width:40px;\">First Name</td>\n";
		$REPORT .= "  <td style=\"width:50px;\">Last Name</td>\n";
		$REPORT .= "  <td style=\"width:50px;\">Intake Time</td>\n";
		$REPORT .= "  <td style=\"width:55px;\">MFC / Model</td>\n";
		$REPORT .= "  <td style=\"width:115px;\">Service Type</td>\n";
		$REPORT .= "  <td style=\"width:45px;\">Status</td>\n";
		$REPORT .= "  <td style=\"width:55px;\">Subtotal</td>\n";
		$REPORT .= " </tr>\n";
		$REPORT .= "</table>\n";
	} else {
		$REPORT .= "<p style=\"font-weight:bold;\">None!</p>";
	}

	while ($row = mysql_fetch_assoc($result)) {
		$REPORT .= "<table border=\"0\" class=\"tabledata\">\n";
		$REPORT .= " <tr align=\"center\">\n";
		$REPORT .= "  <td style=\"width:40px;font-size:13px;\">".$row["firstname"]."</td>\n";
		$REPORT .= "  <td style=\"width:50px;font-size:13px;\">".$row["lastname"]."</td>\n";
		$REPORT .= "  <td style=\"width:50px;font-size:13px;\">".$row["intake_ts"]."</td>\n";
		$REPORT .= "  <td style=\"width:55px;font-size:13px;\">".$row["manufacturer"]."<br>".$row["model"]."</td>\n";

		$str = "";
		if ($row["services"] && $row["services"] != "") {
			$s = explode(":",$row["services"]);
			$sn = array();
			foreach ($s as $svc) {
				if ($svc == "" || !isset($SERVICES[$svc])) continue;
				$sn[] = $SERVICES[$svc];
			}
			if (count($sn) > 0) {
				$str = "- ". join("<br>- ",$sn);
			} else {
				$str = "<i>None</i>";
			}
		} else {
			$str = "<i>None</i>";
		}
		$REPORT .= "  <td style=\"width:115px;font-size:13px;\">".$str."</td>\n";
		$REPORT .= "  <td bgcolor=\"".$ST_COLORS[$row["varref_status"]]."\" style=\"width:45px;\">".$STATUS[$row["varref_status"]]."</td>\n";

		// Price
		$price=0;
		$price = $row["do_price"];

		if(!$row["do_price"]){
			$price = $row["quote_price"];
		}

		$REPORT .= "  <td style=\"width:55px;\">$".number_format($price,2)."</td>\n";
		$REPORT .= " </tr>\n";
		$REPORT .= "</table>\n";
		if (!isset($EMAILING)) {
			$REPORT .= alink_pop("View Issue ".$row["id"],"?module=iss&do=view&id=".$row["issues__id"]) ."<br>\n";
		} else {
			//$REPORT .= alink("View Issue ".$row["issue_id"],"http://". $entity["db_host"] ."/new/?module=iss&do=view&id=".$row["issue_id"]) ."<br>\n";
      $REPORT .= alink("View Issue ".$row["issues__id"],"http://". $_SERVER['HTTP_HOST'] ."/?module=iss&do=view&id=".$row["issues__id"]) ."<br>\n";
		}
		$REPORT .= "<br><br>";
	}

	$REPORT .= "<br><br><font size=\"+2\">Transactions</font><br>\n";
	if (count($TRANS)) {
		$REPORT .= "";
		$REPORT .= "<table border=\"0\">\n";
		$REPORT .= "<tr align=\"center\"><td><b>ID</b></td><td><b>Time</b></td><td><b>Customer</b></td><td><b>Cash</b></td><td><b>Credit</b></td><td><b>Check</b></td><td>Description</td><td><b>Total</b></td></tr>\n";
		foreach ($TRANS as $tran) {
			$REPORT .= "<tr align=\"center\"><td>{$tran["id"]}</td>";
			$REPORT .= "<td>". date("Y-m-d H:i:s",strtotime($tran["tos"])) ."</td>";
			$REPORT .= "<td>{$tran["firstname"]} {$tran["lastname"]}</td>";
			$REPORT .= "<td>$". number_format($tran["paid_cash"],2) ."</td>";
			$REPORT .= "<td>$". number_format($tran["paid_credit"],2) ."</td>";
			$REPORT .= "<td>$". number_format($tran["paid_check"],2) ."</td>";
			$REPORT .= "<td>".($tran["descr"] ? $tran["descr"] : "(No Description)")."</td>\n";
			$REPORT .= "<td>$". number_format(floatval($tran["paid_cash"]) + floatval($tran["paid_credit"]) + floatval($tran["paid_check"]),2) ."</td>";
			$REPORT .= "</tr>\n";
		}
		$REPORT .= "</table><br>\n";

	} else {
		$REPORT .= "<i>No Transactions for this period</i><br>\n";
	}

	$LOG = mysql_query("SELECT * FROM pos_cash_log WHERE ts < '". mysql_real_escape_string($START) ."'");
	$TOTAL = 0;
	while ($entry = mysql_fetch_assoc($LOG)) {
		if ($entry["is_reset"]) {
			$TOTAL = $entry["amt"];
		} else if ($entry["is_drop"]) {
			$TOTAL -= $entry["amt"];
		} else {
			$TOTAL += $entry["amt"];
		}
	}

	$TOTAL_DISPLAY = number_format($TOTAL,2);

	$REPORT .= "<font size=\"+2\">Cash Log</font><br>\n";
	$REPORT .= "Drawer total before this time period was $$TOTAL_DISPLAY<br><br>\n";
	$REPORT .= <<<EOF
<table border="0" width="700">
 <tr class="heading" align="center">
  <td>Date / Time</td>
  <td>User</td>
  <td>Amount</td>
  <td>Type</td>
  <td>Cash / Check</td>
  <td width="200">Reason</td>
  <td>Transaction</td>
  <td>Total</td>
 </tr>
EOF;

	$LOG = mysql_query("SELECT * FROM pos_cash_log cl JOIN users u ON cl.users__id = u.id WHERE cl.org_entities__id = {$eid} AND CAST(cl.ts AS date) >= '". mysql_real_escape_string($START) ."' AND CAST(cl.ts AS date) < '". mysql_real_escape_string($END) ."' ORDER BY cl.ts");
	while ($entry = mysql_fetch_assoc($LOG)) {
		if ($entry["is_reset"]) {
			$TOTAL = $entry["amt"];
		} else if ($entry["is_drop"]) {
			$TOTAL -= $entry["amt"];
		} else {
			$TOTAL += $entry["amt"];
		}

		$style="";
		if ($entry["is_reset"]) {
			$type = "Total";
			$bgcolor = "#FFFFFF";
		} elseif ($entry["amt"] < 0) {
			$type = "Removed";
			$bgcolor = "#FFCCCC";
		} elseif ($entry["is_drop"]){
			$type = "Drop";
			$bgcolor = "#CCCCFF";
			$style = "border:1px solid red;";
		} else {
			$type = "Added";
			$bgcolor = "#CCFFCC";
		}

		$REPORT .= " <tr align=\"center\" bgcolor=\"".$bgcolor."\">\n";
		$REPORT .= "  <td>". $entry["ts"] ."</td>\n";
		$REPORT .= "  <td>". $entry["username"] ."</td>\n";
		$REPORT .= "  <td>$". number_format($entry["amt"],2) ."</td>\n";
		$REPORT .= "  <td>". $type ."</td>\n";
		$REPORT .= "  <td>". ($entry["is_checks"] ? "Checks":"Cash") ."</td>\n";
		$REPORT .= "  <td>". $entry["reason"] ."</td>\n";
		$REPORT .= "  <td>". ($entry["pos_transactions__id"] == null ? "<i>N/A</i>" : alink("# ". $entry["pos_transactions__id"],"?module=pos&do=view_trans&tid=". $entry["pos_transactions__id"])) ."</td>\n";
		$REPORT .= "  <td>$". number_format($TOTAL,2) ."</td>\n";
		$REPORT .= " </tr>\n";
	}

	$REPORT .= "</table><br>\n";

	$STORE_RPT = 1;
	include "user_score.php";

	$REPORT .= "<div width=\"100%\" style=\"background-color:#FFCCCC\"><b>End of Store Report</b></div>";
	$REPORT .= "<div class=\"space\"></div>";
	$REPORT .= "
	</div>"; //CLOSING DIV

	//mysql_close($link);
}

$REPORT .= "<h3>Store Totals</h3><br>\n";
$REPORT .= <<<END
<table border="0">
<tr align="center" class="heading">
<td>Location</td>
<td>Total</td>
</tr>
END;

$TOTAL_TOTAL = 0;
foreach($ENTITIES as $eid => $entity) {
	if (!isset($TOTAL_MONEY[$eid])) $TOTAL_MONEY[$eid] = 0;
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>".$entity["title"]."</td>\n";
	$REPORT .= "  <td>$".number_format($TOTAL_MONEY[$eid],2)."</td>\n";
	$REPORT .= " </tr>\n";
	$TOTAL_TOTAL += $TOTAL_MONEY[$eid];
}
$REPORT .= " <tr align=\"center\">\n";
$REPORT .= "  <td><b>All Stores</b></td>\n";
$REPORT .= "  <td><b>$".number_format($TOTAL_TOTAL,2)."</td>\n";
$REPORT .= " </tr>\n";
$REPORT .= "</table>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
// else {
// 	// Reset the database connection
// 	mysql_close($DB);
// 	$DB = mysql_connect($db_host, $db_user, $db_pass) or die("Couldn't connect to database.");
// 	mysql_select_db($db_database) or die("Couldn't select database.");
// }
?>
