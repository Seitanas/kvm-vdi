<?php
/*
KVM-VDI
Tadas Ustinavičius
2016-09-01
Vilnius, Lithuania.
*/
include dirname(__FILE__) . '/../../functions/config.php';
require_once(dirname(__FILE__) . '/../../functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
if (!isset($_POST['parentid']))
    exit;
if (!isset($_POST['status']))
    exit;
$parentid=$_POST['parentid'];
$status=$_POST['status'];
$_SESSION['table_section-'.$parentid]=$status;
