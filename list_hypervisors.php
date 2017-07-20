<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius,Lithuania.
2017-07-20
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
set_lang();
$hypervisors_reply=get_SQL_array("SELECT * FROM hypervisors ORDER BY name,ip DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <link href="inc/x-editable/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet">
    <script src="inc/x-editable/bootstrap3-editable/js/bootstrap-editable.js"></script>
    <script src="inc/js/modal/kvm-vdi-kvm-hypervisors.js"></script>
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _("Modify hypervisors");?></h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-3 hypervisor-line">
                    Name
                </div>
                <div class="col-md-3 hypervisor-line">
                    Address
                </div>
                <div class="col-md-3 hypervisor-line">
                    SPICE address
                </div>
                <div class="col-md-3 hypervisor-line">
                </div>
        </div>
    <?php
    $x=0;
    while ($x < sizeof($hypervisors_reply)){
        echo '<div class="row hypervisor-list" id="row-name-' . $hypervisors_reply[$x]['id']  . '">
                    <div class="col-md-3 hypervisor-line name-' . $hypervisors_reply[$x]['id']  . '">
                        <a href="#" class="hypervisor" data-type="text" data-name="update-name" data-pk="' . $hypervisors_reply[$x]['id']  . '" data-url="inc/infrastructure/KVM/UpdateHypervisors.php">' . $hypervisors_reply[$x]['name'] . '</a>
                    </div>
                    <div class="col-md-3 hypervisor-line name-' . $hypervisors_reply[$x]['id']  . '">
                        <a href="#" class="hypervisor" data-type="text" data-name="update-address" data-pk="' . $hypervisors_reply[$x]['id']  . '" data-url="inc/infrastructure/KVM/UpdateHypervisors.php">' . $hypervisors_reply[$x]['ip'] . '</a>
                    </div>
                    <div class="col-md-3 hypervisor-line  name-' . $hypervisors_reply[$x]['id']  . '">
                        <a href="#" class="hypervisor" data-type="text" data-name="update-spice" data-pk="' . $hypervisors_reply[$x]['id']  . '" data-url="inc/infrastructure/KVM/UpdateHypervisors.php">' . $hypervisors_reply[$x]['address2'] . '</a>
                    </div>
                    <div class="col-md-3 hypervisor-line">
                        <input class="hide" type="checkbox" name="hypervisor[]" value="' . $hypervisors_reply[$x]['id']  . '" id="hypervisor-' . $hypervisors_reply[$x]['id']  . '">
                        <button type="button" class="btn btn-warning DeleteHypervisorButton"  data-id="' . $hypervisors_reply[$x]['id']  . '">' . _("Delete") . '</button>
                    </div>
            </div>';
        ++$x;
    }
    ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="button" class="btn btn-primary hide" id="SubmitHypervisorsButton"><?php echo _("Save changes");?></button>
        </div>
    </div>
</body>
<script>
$(document).ready(function(){
    $('.hypervisor').editable({
        success: function(response, newValue) {
            refresh_screen();
        }
    });
});
</script>
</html>