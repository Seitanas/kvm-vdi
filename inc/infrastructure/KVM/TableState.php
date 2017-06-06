<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-02-20
Vilnius, Lithuania.
*/
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
if (!isset($_POST['parentid']))
    exit;
if (!isset($_POST['status']))
    exit;
$userConfig=get_userconf();
$userConfig['table_section-' . $_POST['parentid']]=$_POST['status'];
write_userconf($userConfig);
