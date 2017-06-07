<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$vm = $_POST['vm'];
$action = $_POST['action'];
if (empty($vm)){
    echo json_encode(array('error' => _('Missing source VM.')));
    exit;
}
if ($action == "mass_on")
    add_SQL_line("UPDATE vms SET snapshot = 'true' WHERE source_volume = '$vm'");
if ($action == "mass_off")
    add_SQL_line("UPDATE vms SET snapshot = 'false' WHERE source_volume = '$vm'");
if ($action == "single"){
    $snapshot = get_SQL_line("SELECT snapshot FROM vms WHERE id = '$vm'");
    if ($snapshot[0] == "true")
        add_SQL_line("UPDATE vms SET snapshot = 'false' WHERE id = '$vm'");
    else
        add_SQL_line("UPDATE vms SET snapshot = 'true' WHERE id = '$vm'");
}
echo json_encode(array('success' => _('Maintenance updated.')));
exit;
