<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2016-05-30
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$machine_type=$_POST['machine_type'];
$hypervisor=$_POST['hypervisor'];
$source_volume=$_POST['source_volume'];
$source_drivepath=$_POST['source_drivepath'];
$source_drive_size=$_POST['source_drive_size'];
$iso_image='';
if (isset($_POST['iso_image']))
    $iso_image=$_POST['iso_image'];
if (isset($_POST['iso_path']))
    $iso_path=$_POST['iso_path'];
$numcpu=$_POST['numcpu'];
$numcore=$_POST['numcore'];
$numram=1024*$_POST['numram'];
$network=$_POST['network'];
$machinename=$_POST['machinename'];
$machinecount=$_POST['machinecount'];
$os_type=$_POST['os_type'];
$os_version=$_POST['os_version'];
if (check_empty($machine_type,$hypervisor,$numcpu,$numcore,$numram,$network,$machinename,$machinecount)){
    header("Location: $serviceurl/dashboard.php");
    exit;
}
$cdrom_cmd="";
if ($iso_image=='on'){
    $boot_cmd="--noautoconsole --cdrom " . $default_iso_path . '/' . $iso_path;
}
else 
    $boot_cmd="--pxe --noautoconsole";
$h_reply=get_SQL_line("SELECT ip, port FROM hypervisors WHERE id='$hypervisor'");
ssh_connect($h_reply[0].":".$h_reply[1]);
if ($machine_type=='simplemachine'||$machine_type=='sourcemachine'){
    echo $h_reply[0]."ddd";
    $x=0;
    while ($x<$machinecount){
	$name=$machinename.sprintf("%0" . strlen($machinecount) . "s", $x+1);
	$disk=$source_drivepath . '/' . $name . "-" . uniqid() . ".qcow2";
	$vm_cmd="sudo virt-install --name=" . $name . " --disk path=" . $disk . ",format=qcow2,bus=virtio,cache=none --soundhw=ac97 --vcpus=" . $numcpu . ",cores=" . $numcore . " --ram=" . $numram . " --network bridge=" . $network . ",model=virtio --os-type=" . $os_type . " --os-variant=" . $os_version . " --graphics spice,listen=0.0.0.0 --redirdev usb,type=spicevmc --video qxl --noreboot " . $boot_cmd;
	$drive_cmd="sudo qemu-img create -f qcow2 -o size=" . $source_drive_size . "G " . $disk;
	$chown_command="sudo chown $libvirt_user:$libvirt_group $disk";
	add_SQL_line("INSERT INTO  vms (name,hypervisor,machine_type) VALUES ('$name','$hypervisor','$machine_type')");
	ssh_command($drive_cmd,true);
        ssh_command($chown_command,true);
	ssh_command($vm_cmd,true);
	++$x;

    }
}
if ($machine_type=='initialmachine'){
    $name=$machinename;
    $disk=$source_drivepath . '/' . $name . "-" . uniqid() . ".qcow2";
    $vm_cmd="sudo virt-install --name=" . $name . " --disk path=" . $disk . ",format=qcow2,bus=virtio,cache=none --soundhw=ac97 --vcpus=" . $numcpu . ",cores=" . $numcore . " --ram=" . $numram . " --network bridge=" . $network . ",model=virtio --os-type=" . $os_type . " --os-variant=" . $os_version . " --graphics spice,listen=0.0.0.0 --redirdev usb,type=spicevmc --video qxl --import --noreboot";
    $drive_cmd="sudo qemu-img create -f qcow2 -o size=1G " . $disk;
    $chown_command="sudo chown $libvirt_user:$libvirt_group $disk";
    ssh_command($drive_cmd,true);
    ssh_command($chown_command,true);
    ssh_command($vm_cmd,true);
    add_SQL_line("INSERT INTO  vms (name,hypervisor,machine_type,source_volume) VALUES ('$name','$hypervisor','$machine_type','$source_volume')");
    $v_reply=get_SQL_line("SELECT id FROM vms WHERE name='$name'");
    header("Location: $serviceurl/copy_disk.php?vm=" . $v_reply[0] . "&hypervisor=" . $hypervisor);
}

if ($machine_type=='vdimachine'){
    $source_reply=get_SQL_line("SELECT name FROM vms WHERE id='$source_volume'");
    $source_disk=str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep vda| awk '{print $2}' ",true)));
    if (empty ($source_disk)) //if there is no vda drive, perhaps client uses non virtio controller
        $source_disk=str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep hda| awk '{print $2}' ",true)));
    $x=0;
    while ($x<$machinecount){
	$name=$machinename.sprintf("%0" . strlen($machinecount) . "s", $x+1);
	$disk=$source_drivepath . '/' . $name . "-" . uniqid() . ".qcow2";
	$vm_cmd="sudo virt-install --name=" . $name . " --disk path=" . $disk . ",format=qcow2,bus=virtio,cache=none --soundhw=ac97 --vcpus=" . $numcpu . ",cores=" . $numcore . " --ram=" . $numram . " --network bridge=" . $network . ",model=virtio --os-type=" . $os_type . " --os-variant=" . $os_version . " --graphics spice,listen=0.0.0.0 --redirdev usb,type=spicevmc --video qxl --noreboot --import";
	$drive_cmd="sudo qemu-img create -f qcow2 -b " . $source_disk . " " . $disk;
	$xmledit_cmd="sudo " . $hypervisor_cmdline_path . "/vdi-xmledit -name " . $name;
	$chown_command="sudo chown $libvirt_user:$libvirt_group $disk";
	ssh_command($drive_cmd,true);
	ssh_command($chown_command,true);
	ssh_command($vm_cmd,true);
	ssh_command($xmledit_cmd,true);
	add_SQL_line("INSERT INTO  vms (name,hypervisor,machine_type,source_volume) VALUES ('$name','$hypervisor','$machine_type','$source_volume')");
	++$x;

    }
}

header("Location: $serviceurl/reload_vm_info.php");
exit;
?>
