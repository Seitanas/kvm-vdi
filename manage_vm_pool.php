<?php
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
    <div class="modal-content">
        <div class="modal-header">
            <div class="row">
                <div class="col-md-8 text-left">
                    <h4 class="modal-title"><?php echo _("Add VMs to pool"); ?></h4>
                </div>
                <div class="col-md-3 text-right">
                    <div class="dropdown">
                        <button class="btn btn-default dropdown-toggle fa-cog" type="button" id="options" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <span class="fa fa-cog" aria-hidden="true"></span> 
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="options">
                            <li><a href="#" id="ShowNonVDIVMSButton"><i data-status="" class="fa fa-square-o fa-fw" id="ShowNonVDIVMSCheckBox"></i><?php echo _("Show non-VDI VMs");?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-1 text-right">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
            </div>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-5">
                        <label for="multiselect" class="text-muted"><?php echo _("Available Vms");?></label>
                        <select name="multiselect" id="multiselect" class="form-control" size="20" multiple="multiple"></select>
                    </div>
                    <div class="col-md-2">
                        <div  style="margin-top:80px;">
                            <button type="button" id="multiselect_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
                            <button type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
                            <button type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
                            <button type="button" id="multiselect_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label for="multiselect_to" class="text-muted"><?php echo _("Vms in pool");?></label>
                        <select id="multiselect_to" name="multiselect_to" class="form-control" size="20" multiple="multiple"></select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4"></div>
                    <div class="col-md-4 text-center">
                        <label for="VMPoolList" class="text-muted"><?php echo _("Pool");?></label>
                        <select class="input-small form-control" id="VMPoolList">
                            <?php $group_array=get_SQL_array("SELECT * FROM pool ORDER BY name");
                            $x=0;
                            while ($x < sizeof($group_array)){
                                echo '<option value="' . $group_array[$x]['id'] . '">' . $group_array[$x]['name'] . '</option>';
                                ++$x;
                            }?>
                        </select>
                    </div>
                    <div class="col-md-4"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="clearfix"></div>
            <button type="button" id="ManageVMPoolButton" class="btn btn-primary" data-dismis="modal"><?php echo _("Submit");?></button>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
        </div>
    </div>
</body>

<script>
$(document).ready(function(){
    $('#multiselect').multiselect();
    $poolid=$('#VMPoolList').val();
    loadVMPoolList($poolid,false);
});
</script>
</html>
