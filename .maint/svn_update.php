<?php

set_time_limit(0);

exec("svn cleanup \"".str_replace("\\","/",realpath(dirname(__FILE__)))."\"");

$output = array();
$cmd = exec("svn up \"".str_replace("\\","/",realpath(dirname(__FILE__)))."\"",$output);

echo "SVN Update Output:<PRE>";
echo str_replace("<","&lt;",join("\n",$output));
echo "</PRE>";

?>