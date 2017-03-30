<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    exit;
}
slash_vars();
$snapshot_id = $_POST['snapshot_id'];
if (!empty ($snapshot_id))
   echo getSnapshotInfo($snapshot_id);
