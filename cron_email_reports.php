<?php

// TO BE RUN EVERY HOUR AT XX:00

require_once("mysql_connect.php");
require_once("core/common.php");
require_once("core/class.phpmailer.php");
require_once("core/class.smtp.php");

//date_default_timezone_set("America/New_York"); // got thru 'config.php' via 'mysql_connect.php'

$DEBUG_MAIL = true;

if ($DEBUG_MAIL) {
	$logfile = "cron_email_reports.log";
	$log = fopen($logfile, 'a') or die("FATAL: Can't open log file.");
}

echo ts()." START\n";
if ($DEBUG_MAIL) fwrite($log,ts()." START\n");

function ts() {
	return "[". date("Y-m-d H:i:s") ."]";
}

$br = "";
if (isset($_SERVER["SERVER_NAME"])) {
	require_once("core/sessions.php");
	if (!isset($USER)) {
		header("Location: /login.php"); exit;
	}
	if (!TFD_HAS_PERMS('admin','use')) {
		echo "You do not have the needed permissions to run this script.";
		exit;
	}
	$br = "<br>";
}

$store_list = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");

if ( mysql_num_rows($store_list) ) {
  // pre-cache once
  $handle = fopen(dirname(__FILE__) ."/default.css", "r");
  $STYLE = fread($handle, filesize(dirname(__FILE__) ."/default.css"));
  fclose($handle);

  $EMAILING = 1;

  $REPORT_HEAD = "<html><head><title>Front Desk Report</title>\n";
  $REPORT_HEAD .= "<style type=\"text/css\">\n" .$STYLE ."\n</style>\n";

  $REPORT_FOOT = "</body>\n</html>\n";
}

WHILE (false !== ($data = mysql_fetch_assoc($store_list))) {
  $store_id = $data['id'];
	$store_name = $data["title"];

  $ATTACHMENTS = array();
  $TITLES = array();

  $result = mysql_query($sql="SELECT * FROM reports_config r JOIN users u ON r.users__id = u.id WHERE u.org_entities__id = {$data['id']} AND DATEDIFF(NOW(),r.last_emailed) >= r.email_every AND r.hr = HOUR(NOW())");
  while ($REPORT_CONFIG = mysql_fetch_assoc($result)) {
    $emails = explode(",",$REPORT_CONFIG["email"]);
    foreach ($emails as $email) {
      if (!check_email_address(trim($email))) {
        if ($DEBUG_MAIL) fwrite($log,ts()." - Invalid email for user {$REPORT_CONFIG["username"]}, skipping\n");
        continue 2; // break out of foreach, continue in while loop
      }
    }

    $START = $REPORT_CONFIG["last_emailed"];
    $END = date("Y-m-d");

    $REPORTS_TO_SEND = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
    $sending = false;
    for ($i = 0; $i < 15; $i++) {
      if (substr($REPORT_CONFIG["reports"],$i,1) != "_") {
        $REPORTS_TO_SEND[$i] = "1";
        $sending = true;
      }
      else $REPORTS_TO_SEND[$i] = 0;
    }

    if (!$sending) continue;

    $STORES = explode(":",$REPORT_CONFIG["stores"]);
    if (in_array("all",$STORES)) $ALL_STORES = 1;

    $USERS = explode(":",$REPORT_CONFIG["users"]);
    if (in_array("all",$USERS)) $ALL_USERS = 1;

    if ($REPORT_CONFIG["attach"]) $attach = true;
    else $attach = false;

    // Store Report
    if ($REPORT_CONFIG["stores"] != null && $REPORT_CONFIG["stores"] != "::") {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/store.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Store Report ".$START." to ".$END;
    }

    // Cash Report
    if ($REPORTS_TO_SEND[0]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/cash.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Cash Report ".$START." to ".$END;
    }

    // Cash Log Report
    if ($REPORTS_TO_SEND[1]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/cashlog.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Cash Log Report ".$START." to ".$END;
    }

    // Customer Report
    if ($REPORTS_TO_SEND[2]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/customers.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Customer Report ".$START." to ".$END;
    }

    // Marketing Report
    if ($REPORTS_TO_SEND[3]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/marketing.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Marketing Report ".$START." to ".$END;
    }

    // Deposits Report
    if ($REPORTS_TO_SEND[4]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/deposits.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Deposits Report ".$START." to ".$END;
    }

    // User Report
    if ($REPORT_CONFIG["users"] != null && $REPORT_CONFIG["users"] != "::") {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/user.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "User Report ".$START." to ".$END;
    }

    // Drop Box Report
    if ($REPORTS_TO_SEND[5]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/drops.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Drop Box Report";
    }

    // Punch Cards Report
    if ($REPORTS_TO_SEND[6]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/punchcards.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Punch Cards Report";
    }

    // Inventory Requests Report
    if ($REPORTS_TO_SEND[7]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/inv_requests.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Inventory Requests Report";
    }

    // User Score Report
    if ($REPORTS_TO_SEND[8]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/user_score.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "User Score Report";
    }

    // Inventory Added Report
    if ($REPORTS_TO_SEND[9]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/inv_added.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Inventory Added Report";
    }

    // Inventory Sold Report
    if ($REPORTS_TO_SEND[10]) {
      $REPORT = ($attach ? $REPORT_HEAD : "");
      include("admin/rpt/inv_sold.php");
      $REPORT .= ($attach ? $REPORT_FOOT : "");
      $ATTACHMENTS[] = $REPORT;
      $TITLES[] = "Inventory Sold Report";
    }

    //TODO: Load this data from configuration
    $mail = new PHPMailer();
    $mail->IsSMTP();
    //$mail->SMTPDebug = 1;
    $mail->Host     = "smtp.biz.rr.com";

    $mail->SMTPAuth = true;
    $mail->Username = "Pavppz1@albany.twcbc.com";
    $mail->Password = "";
    $mail->From     = "Pavppz1@albany.twcbc.com";
    $mail->FromName = "Computer Answers";

    $emails = explode(",",$REPORT_CONFIG["email"]);
    foreach ($emails as $email) {
      $mail->AddAddress(trim($email));
    }

    $mail->Subject = "$store_name - Front Desk Reports: ".$START." to ".$END;

    if ($attach) {
      $mail->Body = "The reports you've requested are attached. You can change your email reports configuration in the 'System' tab.";

      for ($x=0; $x < count($ATTACHMENTS); $x++) {
        $mail->AddStringAttachment($ATTACHMENTS[$x],"{$TITLES[$x]}.html");
      }
    } else {
      $mail->IsHTML(true);

      $MESSAGE = $REPORT_HEAD;
      for ($x = 0; $x < count($ATTACHMENTS); $x++) {
        $MESSAGE .= $ATTACHMENTS[$x] ."<br><hr>\n";
      }
      $MESSAGE .= $REPORT_FOOT;

      $mail->Body = $MESSAGE;
    }

    if(!$mail->Send()) {
      echo $br."\nERROR sending email to ".$TO.$br.$br."\n\n";
      if ($DEBUG_MAIL) fwrite($log,ts()." - ERROR sending email to {$REPORT_CONFIG["email"]}\n\n{$mail->ErrorInfo}\n\n");
    } else {
      echo "Emailed ".count($ATTACHMENTS)." reports to ".$REPORT_CONFIG["email"].$br."\n";
      mysql_query("UPDATE reports_config SET last_emailed = NOW() WHERE report_config_id = ".$REPORT_CONFIG["report_config_id"]);
      if ($DEBUG_MAIL) fwrite($log,ts()." - Emailed ".count($ATTACHMENTS)." reports to {$REPORT_CONFIG["username"]} : {$REPORT_CONFIG["email"]}\n");
    }

    sleep(1);
  }

  echo ts()." DONE$br\n";
  if ($DEBUG_MAIL) {
    fwrite($log,ts()." DONE\n");
  }
} //end big while loop

if ($DEBUG_MAIL) {
  fclose($log);
}

?>