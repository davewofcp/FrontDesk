<?php

if (!TFD_HAS_PERMS('admin','use')) {
	echo "You do not have the needed permissions to access this page.";
} else {

?><h3>Import Quickbooks IIF</h3>

This utility will scrape the IIF file for customer data. It will be saved to a temporary database table
and you will be asked to review entries for duplicates and make corrections before entries are saved to
the primary customer database.<br><br>

<form action="admin/import_dupe_checker.php?file=1" method="post" enctype="multipart/form-data">
<b>IIF File:</b> <input type="file" name="file" id="file"> <input type="submit" value="Upload"><br>
<input type="checkbox" name="import_all" value="1"> Skip Temporary Table and Import All *
</form><br>

* All customers found in the IIF will be directly imported to the customer database if this option is selected.<br><br>

<?php echo alink("Review Existing Imported Data","admin/import_dupe_checker.php"); ?><br><br>

<?php echo alink("Back to Administration","?module=admin"); ?>

<?php } ?>
