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
slash_vars();
set_lang();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <script src="inc/js/modal/kvm-vdi-kvm-hypervisors.js"></script>
</head>
<body>
    <form id="HypervisorForm">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _("Add new hypervisor");?></h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <input type="text" class="form-control col-md-1" placeholder="<?php echo _("SSH address of new hypervisor.");?>" id="address1" required>
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <input type="text" class="form-control col-md-1" placeholder="<?php echo _("SSH port. Leave empty for default.");?>" id="port">
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <input type="text" class="form-control" placeholder="<?php echo _("Hypervisor address for thin client network (if differs).");?>" id="address2">
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <input type="text" class="form-control" placeholder="<?php echo _("Hypervisor name (optional).");?>" id="name">
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <div class="alert alert-info hide" id="HypervisorProgress"><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><?php echo _("Contacting hypervisor");?></div>
                </div>
                <div class="col-md-1"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="button" class="btn btn-primary" id="AddHypervisorButton"><?php echo _("Save changes");?></button>
            <input type="submit" class="hide">
        </div>
    </div>
    </form>
</body>
</html>