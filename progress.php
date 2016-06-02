<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2016-06-02
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    exit;
}
slash_vars();
if (isset($_GET['vm']))
    $vm=$_GET['vm'];
$sql_reply=get_SQL_line("SELECT filecopy FROM vms WHERE id = '$vm'");
echo $sql_reply[0];