<?php
include dirname(__FILE__) . '/../../functions/config.php';
require_once(dirname(__FILE__) . '/../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
set_lang();
if(isset($_POST['type']))
    $type = $_POST['type'];
$poolid = addslashes($_POST['poolid']);
$clientlist = $_POST['clientlist'];
$clientlist = explode(",",$clientlist);
$client_count = sizeof($clientlist);
$x=0;
$clientid = addslashes($clientlist[0]);
if ($client_count && $poolid){
    if ($type == 'ad')
        add_SQL_line("DELETE FROM poolmap_ad WHERE `poolid` = '$poolid'");
    else
        add_SQL_line("DELETE FROM poolmap WHERE `poolid` = '$poolid'");
    while ($client_count >= $x){
        $clientid = addslashes($clientlist[$x]);
        if (!empty($clientid)){
            if ($type == 'ad')
                add_SQL_line("INSERT INTO poolmap_ad (poolid, groupid) VALUES('$poolid', '$clientid')");
            else
                add_SQL_line("INSERT INTO poolmap (poolid, clientid) VALUES('$poolid', '$clientid')");
        }
        ++$x;
    }
}
else {
    echo json_encode(array('error' => _('Missing values')));
    exit;
}
echo json_encode(array('success' => _('Updated successfully')));
?>