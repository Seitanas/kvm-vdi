<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$maintenance = $_POST['maintenance'];
$hypervisor = $_POST['hypervisor'];
if (empty($hypervisor)){
    echo json_encode(array('error' => _('Missing hypervisor id.')));
    exit;
}
add_SQL_line("UPDATE hypervisors SET maintenance='$maintenance' WHERE id='$hypervisor'");
echo json_encode(array('success' => _('Maintenance mode changed successfully')));
exit;
