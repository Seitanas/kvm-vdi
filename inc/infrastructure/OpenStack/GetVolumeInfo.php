<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$volume_id = $_POST['volume_id'];
if (!empty ($volume_id))
   echo getVolumeInfo($volume_id);
