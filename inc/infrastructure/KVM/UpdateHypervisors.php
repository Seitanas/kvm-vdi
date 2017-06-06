<?php
include ('../../../functions/config.php');
require_once('../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$name = '';
$type = '';
if (isset($_POST['type']))
    $type=$_POST['type'];
if (isset($_POST['name']))
    $name=$_POST['name'];
if ($type == 'new'){
    if (empty($_POST['address1'])){
        echo json_encode(array('error' => _('Empty address')));
        exit;
    }
    else $address1 = $_POST['address1'];
    if (!empty($_POST['address2']))
        $address2 = $_POST['address2'];
    else
        $address2 = $address1;
    if (!empty($_POST['port']))
        $port = $_POST['port'];
    else
        $port = 22;
    $existing=get_SQL_line("SELECT id FROM hypervisors WHERE ip = '$address1'");
    if (!empty($existing[0])){
        echo json_encode(array('error' => _("Hypervisor $address1 already exists")));
        exit;
    }
    $reply = ssh_connect($address1 . ":" . $port);
    if (!empty($reply)) {
        echo json_encode(array('error' => _("Got error: $reply")));
        exit;
    }
    else {
        $output = ssh_command("sudo virsh list --all",true);
        if (strpos($output, 'sudo:') !== false){ //check if sudo is configured
            echo json_encode(array('error' => _('Sudo failure')));
            exit;
        }
    add_SQL_line("INSERT INTO hypervisors (name,ip, port, maintenance,address2) VALUES ('$name','$address1','$port',0,'$address2')");
    echo json_encode(array('success' => _("Hypervisor added $address1 successfully")));
    }
    exit;
}
if ($name == 'update-name'){//using x-editable jQuery plugin, which uses different param naming
    $pk = $_POST['pk'];
    $value = $_POST['value'];
    add_SQL_line("UPDATE hypervisors SET name = '$value' WHERE id = '$pk'");
    exit;
}
if ($name == 'update-address'){//using x-editable jQuery plugin, which uses different param naming
    $pk = $_POST['pk'];
    $value = $_POST['value'];
    add_SQL_line("UPDATE hypervisors SET ip = '$value' WHERE id = '$pk'");
    exit;
}
if ($name == 'update-spice'){//using x-editable jQuery plugin, which uses different param naming
    $pk=$_POST['pk'];
    $value = $_POST['value'];
    add_SQL_line("UPDATE hypervisors SET address2 = '$value' WHERE id = '$pk'");
    exit;
}
if ($type == 'delete'){
    $hypervisor = $_POST['hypervisor'];
    foreach ($hypervisor as $id){
        add_SQL_line("DELETE FROM hypervisors WHERE id = '$id'");
        add_SQL_line("DELETE FROM vms WHERE hypervisor = '$id'");
    }
    echo json_encode(array('success' => _('Deleted successfully')));
}
?>
