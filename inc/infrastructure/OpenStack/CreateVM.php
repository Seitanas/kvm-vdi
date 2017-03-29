<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    exit;
}
slash_vars();
$vm_type = $_POST['vm_type'];
$source = $_POST['source'];
$os_type = $_POST['os_type'];
$flavor = $_POST['flavor'];
$vm_name = $_POST['vm_name'];

