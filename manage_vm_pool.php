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
			    <li><a href="#" id="show-non-vdi-vms-button-click"><i data-status="" class="fa fa-square-o fa-fw" id="show-non-vdi-vms-checkbox"></i><?php echo _("Show non-VDI VMs");?></a></li>
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
		    <div>

		    </div>
    		</div>
    		<div class="col-md-5">
		     <label for="multiselect_to" class="text-muted"><?php echo _("Vms in pool");?></label>
        	    <select id="multiselect_to" name="multiselect_to" class="form-control" size="20" multiple="multiple"></select>
    		</div>
    </div>
    <div class="row">

	<div class="col-md-4">
	</div>
	<div class="col-md-4 text-center">	    
	    <label for="poollist" class="text-muted"><?php echo _("Pool");?></label>
	    <select class="input-small form-control" id="poollist" name="poollist">
    	    <?php $group_array=get_SQL_array("SELECT * FROM pool ORDER BY name");
	    $x=0;
	    while ($group_array[$x]['id']){
	        echo '<option value="' . $group_array[$x]['id'] . '">' . $group_array[$x]['name'] . '</option>';
	        ++$x;
		}?>
	    </select>
	</div>
	<div class="col-md-4"></div>
    </div>
    <div class="row">
	<div class="col-md-12"></div>
    </div>
        <div class="modal-footer">
	    <div class="clearfix"></div>
	    <button type="button" id="submit" class="btn btn-primary" data-dismis="modal"><?php echo _("Submit");?></button>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
        </div>
    </div>
</body>

<script>
$('#poollist').on('change', function(){
    $poolid=$('#poollist').val();
    load_vm_pool_list($poolid, "");
});
</script>
<script>
$(document).ready(function(){
    $('#multiselect').multiselect();
    $poolid=$('#poollist').val();
    load_vm_pool_list($poolid);
    $("#submit").click(function(){
	var multivalues="";
	$("#multiselect_to option").each(function(){
    	    multivalues += $(this).val() + ",";
       });
        $.post("manage_vmmaps_do.php",
        {
          poolid: $('#poollist').val(),
          vmlist: multivalues
        });
        $(function () {
	    $('#mediumScreen').modal('toggle');
	});
    });
    $('a#show-non-vdi-vms-button-click').click(function() {
	show_non_vdi_vms($("#show-non-vdi-vms-checkbox").data('status'));
    });
});
</script>
</html>
