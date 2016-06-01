<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
$xml=$_POST['xml'];
if (!empty($xml))
    file_put_contents('functions/clients.xml', $xml);
header("Location: $serviceurl/reload_vm_info.php");
exit;
?>
