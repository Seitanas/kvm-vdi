<?php
/*
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
set_lang();
if (isset($_GET['type']))
    $type=$_GET['type'];
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
    	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("Add clients to pool"); ?></h4>
        </div>
        <div class="modal-body">
	<div class="form-group">
    	    <div class="row">
		<div class="col-xs-5">
		     <label for="formGroupExampleInput"><?php echo _("Available clients");?></label>
    		    <select name="multiselect" id="multiselect" class="form-control" size="20" multiple="multiple"></select>
	        </div>
		<div class="col-xs-2">
		    <div  style="margin-top:80px;">
		    <button type="button" id="multiselect_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
        	    <button type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
        	    <button type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
        	    <button type="button" id="multiselect_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
		    </div>
    		</div>
    		<div class="col-xs-5">
		     <label for="formGroupExampleInput"><?php echo _("Clients in pool");?></label>
        	    <select id="multiselect_to" name="multiselect_to" class="form-control" size="20" multiple="multiple"></select>
    		</div>
    </div>
    <div class="row">
	<div class="col-md-4">
	    <label for="client-poollist" class="text-muted"><?php echo _("Pool");?></label>
	    <select class="input-small form-control" id="client-poollist" name="client-poollist" data-type="<?php echo $type;?>">
    	    <?php 
	    $group_array=get_SQL_array("SELECT * FROM pool ORDER BY name");
	    $x=0;
	    while ($group_array[$x]['id']){
	        echo '<option value="' . $group_array[$x]['id'] . '">' . $group_array[$x]['name'] . '</option>';
	        ++$x;
		}?>
	    </select>
	</div>
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
$('#client-poollist').on('change', function(){
    poolid=$('#client-poollist').val();
    var type=$('#client-poollist').data("type");
    load_client_pool_list(poolid,type);
});
</script>
<script>
$(document).ready(function(){
    $('#multiselect').multiselect();
    poolid=$('#client-poollist').val();
    var type=$('#client-poollist').data("type");
    load_client_pool_list(poolid,type);
    $("#submit").click(function(){
	var multivalues="";
	$("#multiselect_to option").each(function(){
    	    multivalues += $(this).val() + ",";
       });
        $.post("manage_clientmaps_do.php",
        {
          poolid: $('#client-poollist').val(),
	  type: type,
          clientlist: multivalues
        });
        $(function () {
	    $('#mediumScreen').modal('toggle');
	});
    });
});
</script>
</html>
