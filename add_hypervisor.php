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
             <h4 class="modal-title"><?php echo _("Add new hypervisor");?></h4>
        </div>
        <div class="modal-body">
	    <div class="row">
		 <div class="col-md-1">
		 </div>
		 <div class="col-md-10">
		    <input type="text" class="form-control col-md-1" placeholder="<?php echo _("SSH address of new hypervisor.");?>" name="address1" id="address1">
		</div>
		 <div class="col-md-1">
		 </div>
	    </div>
	    <div class="row">
		 <div class="col-md-1">
		 </div>
		 <div class="col-md-10">
		    <input type="text" class="form-control col-md-1" placeholder="<?php echo _("SSH port. Leave empty for default.");?>" name="port" id="port">
		</div>
		 <div class="col-md-1">
		 </div>
	    </div>
	    <div class="row">
		 <div class="col-md-1">
		 </div>
		 <div class="col-md-10">
		    <input type="text" class="form-control" placeholder="<?php echo _("Hypervisor address for thin client network (if differs).");?>" name="address2" id="address2">
		</div>
	        <div class="col-md-1">
	        </div>
	    </div>
	    <div class="row">
		 <div class="col-md-1">
		 </div>
		 <div class="col-md-10">
		    <input type="text" class="form-control" placeholder="<?php echo _("Hypervisor name (optional).");?>" name="name" id="name">
		</div>
	        <div class="col-md-1">
	        </div>
	    </div>
	    <div class="row">
		 <div class="col-md-1">
		 </div>
		 <div class="col-md-10">
		     <div class="alert alert-info hide" id="progress"><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><?php echo _("Contacting hypervisor");?></div>
		</div>
		 <div class="col-md-1">
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
	$("#progress").html("<i class=\"fa fa-spinner fa-spin fa-fw\"></i><?php echo _("Contacting hypervisor");?>");
	$("#progress").removeClass('alert-danger');
	$("#progress").addClass('alert-info');
	$("#progress").removeClass('hide');
        $.ajax({
            type : 'POST',
            url : 'update_hypervisors.php',
            data: {
		type : 'new',
                address1 : $('#address1').val(),
                address2 : $('#address2').val(),
                port : $('#port').val(),
                name : $('#name').val(),
		
            },
            success:function (data) {
		if (data=='BAD_SSH_ADDRESS'||data=='EMPTY_ADDRESS'){
		    paint_danger();
            	    $("#progress").html("<i class=\"fa fa-minus-circle fa-fw\"></i><?php echo _("Invalid hypervisor address.");?>");
		}
		if (data=='BAD_SSH_CREDENTIALS'){
            	    $("#progress").html("<i class=\"fa fa-minus-circle fa-fw\"></i><?php echo _("Invalid hypervisor credentials.");?>");
		    paint_danger();
		}
		if (data=='EXISTS'){
            	    $("#progress").html("<i class=\"fa fa-minus-circle fa-fw\"></i><?php echo _("Hypervisor already exists.");?>");
		    paint_danger();
		}
		if (data=='SUCCESS'){
    		    $("#progress").removeClass('alert-danger');
		    $("#progress").removeClass('alert-info');
		    $("#progress").addClass('alert-success');
            	    $("#progress").html("<i class=\"fa fa-thumbs-o-up fa-fw\"></i><?php echo _("Success");?>");
		    draw_table();
		}
            }
        });
    });
});
</script>
</html>