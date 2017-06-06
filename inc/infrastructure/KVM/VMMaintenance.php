<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$sourcevm = $_POST['source'];
$action = $_POST['action'];
if (empty($sourcevm)){
    echo json_encode(array('error' => _('Missing source VM.')));
    exit;
}
if ($action == "single"){
    $maintenance=getSQLArray("SELECT maintenance FROM vms WHERE id = '$sourcevm'");
    if ($maintenance[0]['maintenance'] == "true")
        add_SQL_line("UPDATE vms SET maintenance = 'false' WHERE id = '$sourcevm'");
    else
        add_SQL_line("UPDATE vms SET maintenance = 'true' WHERE id = '$sourcevm'");
}
if ($action == "mass_on")
    add_SQL_line("UPDATE vms SET maintenance = 'true' WHERE source_volume = '$sourcevm'");
if ($action == "mass_off")
    add_SQL_line("UPDATE vms SET maintenance = 'false' WHERE source_volume = '$sourcevm'");
echo json_encode(array('success' => _('Maintenance updated.')));
exit;
?>