<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-06-01
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
if (empty($vm)||empty($hypervisor)){
    exit;
}
$h_reply=get_SQL_line("SELECT * FROM hypervisors WHERE id='$hypervisor'");
$v_reply=get_SQL_line("SELECT * FROM vms WHERE id='$vm'");
ssh_connect($h_reply[2].":".$h_reply[3]);
$address=ssh_command("sudo virsh domdisplay " . $v_reply[1], true);
$address=str_replace("localhost",$remote_spice_substitute[$h_reply[2]],$address);
$address=$address . "?password=" . $v_reply[9];
$rnd=uniqid();
set_lang();
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title><?php echo _("VM screen");?></title>  
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("VM name: ") . $v_reply[1]; ?></h4>
        </div>
        <div class="modal-body">
	    <?php echo '<img src="screenshot.php?vm=' . $vm . '&hypervisor=' . $hypervisor . '&' . $rnd . '">'; ?>
        </div>
        <div class="modal-footer">
	    <?php echo '<a class="btn btn-info" type="button" href="' . $address . '" target="_new">' . _("Open remote console") . '</a>';?>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
        </div>
    </div>
</body>
</html>