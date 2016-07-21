<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
set_lang();
if(isset($_POST['type']))
    $type=$_POST['type'];
$poolid = addslashes($_POST['poolid']);
$clientlist = $_POST['clientlist'];
$clientlist=explode(",",$clientlist);
$client_count=sizeof($clientlist);
$x=0;
$clientid=addslashes($clientlist[0]);
if ($client_count&&$poolid){
    if ($type=='ad')
        add_SQL_line("DELETE FROM poolmap_ad WHERE `poolid`='$poolid'");
    else
	add_SQL_line("DELETE FROM poolmap WHERE `poolid`='$poolid'");
    while ($client_count>=$x){
	$clientid=addslashes($clientlist[$x]);
	if (!empty($clientid)){
	    if ($type=='ad')
		add_SQL_line("INSERT INTO poolmap_ad (poolid, groupid) VALUES('$poolid', '$clientid')");
	    else
		add_SQL_line("INSERT INTO poolmap (poolid, clientid) VALUES('$poolid', '$clientid')");
		//add_SQL_line("INSERT INTO poolmap (poolid, clientid) SELECT * FROM (SELECT '$poolid' AS pool, '$clientid' AS client) AS tmp WHERE NOT EXISTS (SELECT clientid FROM poolmap WHERE poolid = '$poolid' AND clientid='$clientid') LIMIT 1;");
	    }
	++$x;
    }
}
?>