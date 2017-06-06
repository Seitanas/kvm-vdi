<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius,Lithuania.
2017-06-06
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
    <script src="inc/js/kvm-vdi.js"></script>
</head>
<body>
<form id="PasswordValidator">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("Change password");?></h4>
        </div>
        <div class="modal-body form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <input type="password" class="form-control" placeholder="<?php echo _("Enter password");?>" id="PasswordField" required>
                </div>
                <div class="col-md-3"></div>
            </div>
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <input type="password" class="form-control text-danger" placeholder="<?php echo _("Repeat password");?>" id="PasswordConfirmField" required>
                    <span class="help-block hide" id="PasswordsDoNotMatch"><?php echo _("Passwords do not match");?></span>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
        <button type="button" class="btn btn-primary" id="ChangePasswordButton"><?php echo _("Save changes");?></button>
        <input type="submit" class="hide">
    </div>
</div>
</form>
</body>
</html>