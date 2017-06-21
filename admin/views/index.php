<?php if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; } ?>
<?php if (isset($ERROR)) { ?>
<font color="#FF0000"><b>Error: <?php echo $ERROR; ?></b></font><br><br>
<?php } ?>
<div align="center">
<table border="0">
 <tr align="center">
  <td width="33%" class="heading">Users</td>
  <td width="34%" class="heading">Modules</td>
  <td width="33%" class="heading">Reports</td>
 </tr>
 <tr align="center">
  <td valign="top">
   <?php echo alink("+ Add New User","?module=admin&do=new_user"); ?><br>
   <?php echo alink("View All Users","?module=admin&do=list_users"); ?><br>
   <?php echo alink("Punch Cards Here","?module=admin&do=punch"); ?><br>
   <?php echo alink("All Punch Cards","?module=admin&do=all_punch"); ?><br>
   <?php echo alink("Punch Card Graph","?module=admin&do=punch_graph"); ?><br>
   <?php echo alink("Tasks","?module=admin&do=tasks"); ?><br>
   <?php echo alink("Daily Tasks","?module=admin&do=rec_tasks"); ?>
  </td>
  <td valign="top">
   <?php echo alink("Get Updates","update.php"); ?><br>
  </td>
  <td valign="top">
   <?php echo alink("Store Report","?module=admin&do=rpt_store_select"); ?><br>
   <?php echo alink("Cash Report","?module=admin&do=rpt_cash_dts"); ?><br>
   <?php echo alink("Inventory Sold Report","?module=admin&do=rpt_invsold"); ?><br>
   <?php echo alink("Sales Tax Report","?module=admin&do=rpt_tax_dts"); ?><br>
   <?php echo alink("Cash Log Report","?module=admin&do=rpt_cashlog_dts"); ?><br>
   <?php echo alink("Customer Report","?module=admin&do=rpt_cust"); ?><br>
   <?php echo alink("Marketing Report","?module=admin&do=rpt_marketing"); ?><br>
   <?php echo alink("User Report","?module=admin&do=rpt_user_dts"); ?><br>
   <?php echo alink("Drop Box Report","?module=admin&do=rpt_drops"); ?><br>
   <?php echo alink("Punch Cards Report","?module=admin&do=rpt_punchcards_dts"); ?><br>
   <?php echo alink("Inventory Requests","?module=admin&do=rpt_inv_requests"); ?><br>
   <?php echo alink("Inventory Added","?module=admin&do=rpt_invadd"); ?><br>
   <?php echo alink("User Scores","?module=admin&do=score_dt"); ?><br>
   <?php echo alink("Finished Issues","?module=admin&do=rpt_fin_iss"); ?><br>
   <?php echo alink("Invoice Changes","?module=admin&do=rpt_invoice_chg_dts"); ?>
  </td>
 </tr>
 <tr align="center">
  <td class="heading">Configuration</td>
  <td class="heading">Finances</td>
  <td class="heading">Data</td>
 </tr>
 <tr align="center">
  <td valign="top">
   <?php echo alink("Store Locations","?module=admin&do=locations"); ?><br>
   <?php echo alink("Editable Fields","?module=admin&do=edit_fields"); ?><br>
   <?php echo alink("Edit Categories","?module=admin&do=cat_edit"); ?><br>
   <?php echo alink("Edit Services","?module=admin&do=svc_edit"); ?><br>
   <?php echo alink("Edit Service Steps","?module=admin&do=step_edit"); ?>
  </td>
  <td valign="top">
   <?php echo alink("Make Deposit","?module=admin&do=deposit"); ?><br>
   <?php echo alink("Deposit History","?module=admin&do=rpt_deposits_dts"); ?>
  </td>
  <td valign="top">
   <?php echo alink("Export Customer Data","?module=admin&do=export_cust"); ?><br>
   <?php if ($USER["username"] == "admin") echo alink("Mass-Edit Customer Data","admin/mass_edit_cust.php") ."<br>"; ?>
   <?php if ($USER["username"] == "admin") echo alink("Import Quickbooks IIF","?module=admin&do=import_iif") ."<br>"; ?>
   <?php if ($USER["username"] == "admin") echo alink("De-Dupe Customers","admin/dedupe_cust.php"); ?>
  </td>
 </tr>
 <tr align="center">
  <td class="heading">Newsletters</td>
  <td class="heading">Feedback</td>
  <td class="heading">User Reports</td>
 </tr>
 <tr align="center">
  <td valign="top">
  <?php echo alink("View","?module=admin&do=view_newsletter"); ?><br>
  <?php echo alink("Create","?module=admin&do=create_newsletter"); ?>
  </td>
  <td valign="top">
  <?php echo alink("Modify Questions","?module=admin&do=feedback_questions"); ?><br>
  </td>
  <td valign="top">
  <?php echo alink("+ New Report","?module=admin&do=user_rpt_new"); ?><br>
  <?php echo alink("List Reports","?module=admin&do=user_rpt_list"); ?>
  </td>
 </tr>
</table>
</div>
