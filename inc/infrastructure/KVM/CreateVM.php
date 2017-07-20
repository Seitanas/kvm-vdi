<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-07-20
Vilnius, Lithuania.
*/
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$machine_type = remove_specialchars($_POST['machine_type']);
$hypervisor = remove_specialchars($_POST['hypervisor']);
$source_hypervisor = remove_specialchars($_POST['source_hypervisor']);
$source_volume = remove_specialchars($_POST['source_volume']);
$source_drivepath = remove_specialchars($_POST['source_drivepath']);
$source_drive_size = remove_specialchars($_POST['source_drive_size']);
$iso_image = '';
if (isset($_POST['iso_image']))
    $iso_image = remove_specialchars($_POST['iso_image']);
if (isset($_POST['iso_path']))
    $iso_path = $_POST['iso_path'];
$numsock = remove_specialchars($_POST['numsock']);
$numcore = remove_specialchars($_POST['numcore']);
$numram = 1024*remove_specialchars($_POST['numram']);
$network = remove_specialchars($_POST['network']);
$machinename = remove_specialchars($_POST['machinename']);
$machinecount = remove_specialchars($_POST['machinecount']);
$os_type = remove_specialchars($_POST['os_type']);
$os_version = remove_specialchars($_POST['os_version']);
$numcpu = $numsock*$numcore;
if (check_empty($machine_type,$hypervisor,$numsock,$numcore,$numram,$network,$machinename,$machinecount)){
    echo json_encode(array('error' => 'Missing values.'));
    exit;
}
$cdrom_cmd = "";
$boot_iso = "";
$boot_cmd = "";
if ($iso_image == 'on'&&!empty($iso_path)){
        $boot_iso = escapeshellarg($default_iso_path . '/' . $iso_path);
}
else 
    $boot_cmd = "--pxe --noautoconsole";
$h_reply = get_SQL_line("SELECT ip, port FROM hypervisors WHERE id = '$hypervisor'");
if ($machine_type != 'import')
    ssh_connect($h_reply[0].":".$h_reply[1]);
if ($machine_type == 'simplemachine' || $machine_type == 'sourcemachine'){
    $x=0;
    while ($x < $machinecount){
        $name = $machinename;
        if ($machinecount > 1)
            $name = $machinename.sprintf("%0" . strlen($machinecount) . "s", $x+1);
        $existing_vm = getSQLarray("SELECT name FROM vms WHERE BINARY name = '$name'");
        if (!empty($existing_vm)){
            echo json_encode(array('error' => _('Machine ' . $existing_vm[0]['name'] . ' already exists')));
            exit;
        }
        ++$x;
    }
    $x=0;
    while ($x < $machinecount){
        if ($machinecount > 1)
            $name = $machinename.sprintf("%0" . strlen($machinecount) . "s", $x+1);
        else
            $name = $machinename;
        $spice_pw = $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 13);
        $disk = $source_drivepath . '/' . $name . "-" . uniqid() . ".qcow2";
        $vm_cmd = "sudo virt-install --name=" . escapeshellarg($name) . " --disk path=" . escapeshellarg($disk) . ",format=qcow2,bus=virtio,cache=none --disk path=" . $boot_iso . ",device=cdrom,target=hdc,perms=ro --soundhw=ac97 --vcpus=" . escapeshellarg($numcpu) . ",cores=" . escapeshellarg($numcore). ",sockets=" . escapeshellarg($numsock) . " --ram=" . escapeshellarg($numram) . " --network bridge=" . escapeshellarg($network) . ",model=virtio --os-type=" . escapeshellarg($os_type) . " --os-variant=" . escapeshellarg($os_version) . " --graphics spice,listen=0.0.0.0,password=" . $spice_pw . " --redirdev usb,type=spicevmc --video qxl --noreboot --wait=0 " . $boot_cmd;
        $drive_cmd = "sudo qemu-img create -f qcow2 -o size=" . escapeshellarg($source_drive_size) . "G " . escapeshellarg($disk);
        $chown_command = "sudo chown $libvirt_user:$libvirt_group $disk";
        $xmledit_cmd = "sudo " . $hypervisor_cmdline_path . "/vdi-xmledit -name " . escapeshellarg($name);
        if ($message = process_stdout(ssh_command($drive_cmd,true))){
            echo json_encode(array('error' => _('Got error while creating disk image: ' . $message)));
            exit;
        }
        if ($message = process_stdout(ssh_command($vm_cmd,true))){
            echo json_encode(array('error' => _('Got error while creating VM: ' . $message)));
            exit;
        }
        add_SQL_line("INSERT INTO  vms (name,hypervisor,machine_type,spice_password,os_type) VALUES ('$name','$hypervisor','$machine_type','$spice_pw','$os_type')");
        if ($message = process_stdout(ssh_command($xmledit_cmd,true))){
            echo json_encode(array('error' => _('Got error while editing doamin XML : ' . $message)));
            exit;
        }
        ++$x;
    }
}
if ($machine_type == 'initialmachine'){
    $name = $machinename;
    $existing_vm = getSQLarray("SELECT name FROM vms WHERE BINARY name = '$name'");
    if (!empty($existing_vm)){
        echo json_encode(array('error' => _('Machine ' . $existing_vm[0]['name'] . ' already exists')));
        exit;
    }
    $disk = $source_drivepath . '/' . $name . "-" . uniqid() . ".qcow2";
    $vm_cmd = "sudo virt-install --name=" . escapeshellarg($name) . " --disk path=" . escapeshellarg($disk) . ",format=qcow2,bus=virtio,cache=none --disk path=,device=cdrom,target=hdc --soundhw=ac97 --vcpus=" . escapeshellarg($numcpu) . ",cores=" . escapeshellarg($numcore). ",sockets=" . escapeshellarg($numsock) . " --ram=" . escapeshellarg($numram) . " --network bridge=" . escapeshellarg($network) . ",model=virtio --os-type=" . escapeshellarg($os_type) . " --os-variant=" . escapeshellarg($os_version) . " --graphics spice,listen=0.0.0.0 --redirdev usb,type=spicevmc --video qxl --import --noreboot --import";
    $drive_cmd = "sudo qemu-img create -f qcow2 -o size=1G " . escapeshellarg($disk);
    $chown_command = "sudo chown $libvirt_user:$libvirt_group $disk";
    $xmledit_cmd = "sudo " . $hypervisor_cmdline_path . "/vdi-xmledit -name " . escapeshellarg($name);
    if ($message = process_stdout(ssh_command($drive_cmd,true))){
        echo json_encode(array('error' => _('Got error while creating disk image: ' . $message)));
        exit;
    }
    write_log(ssh_command($chown_command,true));
    if ($message = process_stdout(ssh_command($vm_cmd,true))){
        echo json_encode(array('error' => _('Got error while creating VM: ' . $message)));
        exit;
    }
    if ($message = process_stdout(ssh_command($xmledit_cmd,true))){
        echo json_encode(array('error' => _('Got error while editing domain XML : ' . $message)));
        exit;
    }
    add_SQL_line("INSERT INTO  vms (name,hypervisor,machine_type,source_volume, os_type) VALUES ('$name','$hypervisor','$machine_type','$source_volume','$os_type')");
    $v_reply=get_SQL_line("SELECT id FROM vms WHERE name='$name'");
    header("Location: $serviceurl/inc/infrastructure/KVM/CopyDisk.php?vm=" . $v_reply[0] . "&hypervisor=" . $hypervisor);
    exit;
}

if ($machine_type == 'vdimachine'){
    $x = 0;
    while ($x < $machinecount){
        $name   =   $machinename.sprintf("%0" . strlen($machinecount) . "s", $x+1);
        $existing_vm = getSQLarray("SELECT name FROM vms WHERE BINARY name = '$name'");
        if (!empty($existing_vm)){
            echo json_encode(array('error' => _('Machine ' . $existing_vm[0]['name'] . ' already exists')));
            exit;
        }
        ++$x;
    }
    $source_reply = get_SQL_line("SELECT name FROM vms WHERE id = '$source_volume'");
    $source_disk = str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep vda| awk '{print $2}' ",true)));
    if (empty ($source_disk)) //if there is no vda drive, perhaps client uses non virtio controller
        $source_disk = str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep hda| awk '{print $2}' ",true)));
    $x = 0;
    while ($x < $machinecount){
        $name = $machinename.sprintf("%0" . strlen($machinecount) . "s", $x+1);
        $disk = $source_drivepath . '/' . $name . "-" . uniqid() . ".qcow2";
        $vm_cmd = "sudo virt-install --name=" . escapeshellarg($name) . " --disk path=" . escapeshellarg($disk) . ",format=qcow2,bus=virtio,cache=none --disk path=,device=cdrom,target=hdc,perms=ro --soundhw=ac97 --vcpus=" . escapeshellarg($numcpu) . ",cores=" . escapeshellarg($numcore). ",sockets=" . escapeshellarg($numsock) . " --ram=" . escapeshellarg($numram) . " --network bridge=" . escapeshellarg($network) . ",model=virtio --os-type=" . escapeshellarg($os_type) . " --os-variant=" . escapeshellarg($os_version) . " --graphics spice,listen=0.0.0.0 --redirdev usb,type=spicevmc --video qxl --noreboot --import";
        $drive_cmd = "sudo qemu-img create -f qcow2 -b " . $source_disk . " " . escapeshellarg($disk);
        $xmledit_cmd = "sudo " . $hypervisor_cmdline_path . "/vdi-xmledit -name " . escapeshellarg($name);
        $chown_command = "sudo chown $libvirt_user:$libvirt_group $disk";
        if ($message = process_stdout(ssh_command($drive_cmd,true))){
            echo json_encode(array('error' => _('Got error while creating disk image: ' . $message)));
            exit;
        }
        write_log(ssh_command($chown_command,true));
        if ($message = process_stdout(ssh_command($vm_cmd,true))){
            echo json_encode(array('error' => _('Got error while creating VM: ' . $message)));
            exit;
        }
        if ($message = process_stdout(ssh_command($xmledit_cmd,true))){
            echo json_encode(array('error' => _('Got error while editing doamin XML : ' . $message)));
            exit;
        }
        add_SQL_line("INSERT INTO  vms (name,hypervisor,machine_type,source_volume,os_type) VALUES ('$name','$hypervisor','$machine_type','$source_volume','$os_type')");
        ++$x;
    }
}

if ($machine_type == 'import'){
    $s_reply = get_SQL_line("SELECT ip, port FROM hypervisors WHERE id = '$source_hypervisor'");
    ssh_connect($s_reply[0] . ":" . $s_reply[1]);
    $machine_xml = ssh_command("sudo virsh dumpxml $machinename",true);
    ssh_disconnect();
    $source_vm = get_SQL_array("SELECT * FROM vms WHERE BINARY name = '$machinename' AND hypervisor = '$source_hypervisor'");
    $existing_vm = getSQLarray("SELECT name FROM vms WHERE BINARY name = '$machinename' AND hypervisor = '$hypervisor'");
    if (!empty($existing_vm)){
        echo json_encode(array('error' => _('Machine ' . $existing_vm[0]['name'] . ' already exists')));
        exit;
    }
    ssh_connect($h_reply[0] . ":" . $h_reply[1]);
    ssh_command("echo " . '"' . $machine_xml . '"' . " > $temp_folder/$machinename.xml",true);
    write_log(ssh_command("sudo virsh define $temp_folder/$machinename.xml",true));
    ssh_command("rm $temp_folder/$machinename.xml",true);
    add_SQL_line("INSERT INTO  vms (name,hypervisor,machine_type,source_volume,os_type) VALUES ('$machinename','$hypervisor','sourcemachine','','{$source_vm[0]['os_type']}')");
}
echo json_encode(array('success' => _('Created successfully')));
exit;
