<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-06-28
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
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("Add new pool");?></h4>
        </div>
        <div class="modal-body">
	    <div class="row">
		 <div class="col-md-2">
		 </div>
		 <div class="col-md-8">
		    <input type="text" class="form-control col-md-1" placeholder="<?php echo _("Name");?>" name="poolname" id="poolname">
		</div>
		 <div class="col-md-2">
		 </div>
	    </div>
	    <div class="row">
		 <div class="col-md-2">
		 </div>
		 <div class="col-md-8">
		     <div class="alert alert-info hide" id="progress"></div>
		</div>
		 <div class="col-md-2">
		 </div>
	    </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="button" class="btn btn-primary" id="submit"><?php echo _("Save changes");?></button>
        </div>
    </div>
</body>
<script>

$(document).ready(function(){
    function paint_danger(){
	$("#progress").removeClass('alert-info');
	$("#progress").addClass('alert-danger');
    }
    $('#submit').click(function() {
	$("#progress").removeClass('alert-danger');
	$("#progress").addClass('alert-info');
	$("#progress").removeClass('hide');
	
        $.ajax({
            type : 'POST',
            url : 'update_pools.php',
            data: {
		type : 'new',
                poolname : $('#poolname').val(),
		
            },
            success:function (data) {
		if (data=='EXISTS'){
            	    $("#progress").html("<i class=\"fa fa-minus-circle fa-fw\"></i><?php echo _("Pool already exists.");?>");
		    paint_danger();
		}
		if (data=='EMPTY_POOL'){
            	    $("#progress").html("<i class=\"fa fa-minus-circle fa-fw\"></i><?php echo _("Poolname field is empty.");?>");
		    paint_danger();
		}
		if (data=='SUCCESS'){
    	    	    setTimeout(function() {
    			    $("#progress").addClass('hide');;
		    }, 2000);
    		    $("#progress").removeClass('alert-danger');
		    $("#progress").removeClass('alert-info');
		    $("#progress").addClass('alert-success');
		    $("#username").val("");
		    $("#password").val("");
            	    $("#progress").html("<i class=\"fa fa-thumbs-o-up fa-fw\"></i><?php echo _("Success");?>");
		}
            }
        });
    });
});
</script>
</html>