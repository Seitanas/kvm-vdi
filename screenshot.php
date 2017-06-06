<?php
require_once('functions/config.php');
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
$filepath=$temp_folder. '/' . $v_reply[1] . ".ppm";
ssh_connect($h_reply[2].":".$h_reply[3]);
#images with .ppmbugfix extensions are used as temporary dirty-fix for 
#issue, where only every second screenshot is updated from libvirt/qemu.
#https://www.redhat.com/archives/libvirt-users/2016-August/msg00075.html
#http://lists.nongnu.org/archive/html/qemu-discuss/2016-09/msg00000.html
ssh_command("sudo virsh screenshot " . $v_reply[1] . " " . $filepath."bugfix",true);
ssh_command("sudo virsh screenshot " . $v_reply[1] . " " . $filepath, true);
$im=ssh_command("sudo cat ". $filepath,true);
$image = new Imagick();
$image->readImageBlob($im);
$image->setImageFormat("png");
$image->scaleImage(865, 865, true);
header("Content-type: image/png");
echo $image->getImageBlob();
ssh_command("sudo rm " . $filepath."bugfix", true);
ssh_command("sudo rm " . $filepath, true);
