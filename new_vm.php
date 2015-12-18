<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2015-12-17
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
$h_reply=get_SQL_array("SELECT * FROM hypervisors");
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title>Remote file for Bootstrap Modal</title>  
</head>
<body>
<style>
.input-group-addon {
    min-width:80px;
}
</style>
<form method="POST" action="create_vm.php">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title">Create virtual machine(s)</h4>
        </div>
        <div class="modal-body">
	    <div class="row">
		 <div class="col-md-5">
		    <label>Machine type:</label>
		    <select class="form-control selectClass" name="machine_type" id="machine_type">
			<option selected value="">Please select machine type</option>
	    	        <option value="simplemachine">Simple machine</option>
        		<option value="initialmachine">Initial machine</option>
			<option value="sourcemachine">Source machine</option>
			<option value="vdimachine">VDI machine</option>
	    	    </select>
		</div>
		 <div class="col-md-5">
		    <label>Target hypervisor:</label>
		    <select class="form-control selectClass" name="hypervisor" id="hypervisor">
			<option selected value="">Please select hypervisor</option>
			<?php
			$x=0;
			while ($h_reply[$x]['id']){
			    echo '<option value="' . $h_reply[$x]['id'] .  '">' . $h_reply[$x]['ip'] . '</option>';
			    ++$x;
			}?>
	    	    </select>
		 </div>
	    </div>
	    <div class="row">
		<?php 
		    $x=0;
		    while ($h_reply[$x]['id']){    
	    		echo '<div class="col-md-5 hide" id="hypervisor-' . $h_reply[$x]['id'] . '">
			    <label>Use volume from:</label>';
			$v_reply=get_SQL_array("SELECT id,name FROM vms WHERE hypervisor='{$h_reply[$x][id]}' ORDER By name");
			$y=0;
			echo '<select class="form-control" name="source_volume" id="source_volume">' ."\n";
			while ($v_reply[$y]['id']){
			    echo '<option value="' . $v_reply[$y]['id'] .  '">' . $v_reply[$y]['name'] . '</option>' ."\n";
			    ++$y;
			}
			echo '</select>' . "\n";
			echo '</div>' . "\n";
		    ++$x;
		    }
		    ?>
		    <div id="hypervisor-manualpath" class="hide">
		        <div class="col-md-5">
		    	    <label>Specify disk path:</label>		    
			    <input type="text" name="source_drivepath" class="form-control" required>
			</div>
			<div class="col-md-4">
    				<label>Disk size</label>		    
			    <div class="input-group">
				<input type="number" min="1" value="10" name="source_drive_size" class="form-control" required>
				<span class="input-group-addon">GB</span>
			    </div>
			</div>
		    </div>	
	    </div>
	    <div class="hide" id="hypervisor-imagepath">
		<div class="row">
		    <div class="col-md-9">
			<label>Mount CD iso:</label>
			<div class="input-group">
			    <span class="input-group-addon" style="min-width:40px;">
				<input type="checkbox" name="iso_image" id="iso_image">
			    </span>
			    <input type="text" value="<?php echo $default_iso_path; ?>" class="form-control" name="iso_path" id="iso_path" disabled>
			</div>
		    </div>
		</div>
	    </div>
	    <div class="row">
		<div class="col-md-4">
		    <label>Hardware info:</label>
		    <div class="input-group">
			<input type="number" min="1" value="1" class="form-control" name="numcpu">
			<span class="input-group-addon">CPUs</span>
		    </div>
		    <div class="input-group">
			<input type="number" min="1" value="1" class="form-control" name="numcore">
			<span class="input-group-addon">Cores</span>
		    </div>
		    <div class="input-group">
			<input type="number" min="1" value="1" class="form-control" name="numram">
			<span class="input-group-addon">GB RAM</span>
		    </div>
		    <div class="input-group">
			<input type="text" value="<?php echo $default_bridge; ?>" class="form-control" name="network">
			<span class="input-group-addon">Network</span>
		    </div>
		</div>
	    </div>
	    <div class="row">
		<hr class="divider">	
		<div class="col-md-5">
        	    <label>Mass deployment</label>
		</div>
	    </div>
	    <div class="row">
		<div class="col-md-5">
		    <label>Prepend machine name:</label>		    
		    <input type="text" name="machinename" placeholder="somename-" class="form-control" required>
		</div>
		<div class="col-md-5">
		    <label>Number of machines to create:</label>		    
		    <input type="number" name="machinecount" min="1" value="1" class="form-control" required>
		</div>
	    </div>
        </div>
	
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Create VMs</button>
        </div>
    </div>
</form>
<script>
$('.selectClass').on('change', function(){
    $('[id^=hypervisor-]').addClass('hide');
    $hypervisor_id=$('#hypervisor').val();
    if (($('#machine_type').val() == 'initialmachine' || $('#machine_type').val() == 'vdimachine') && $hypervisor_id!='') {
        $('#hypervisor-'+$hypervisor_id).removeClass('hide');
    }
    if (($('#machine_type').val() == 'simplemachine' || $('#machine_type').val() == 'sourcemachine') && $hypervisor_id!='') {
	$('#hypervisor-manualpath').removeClass('hide');
	$('#hypervisor-imagepath').removeClass('hide');
    }
	
})

$('#iso_image').on('change', function(){
    if (this.checked) {
	$('#iso_path').prop('disabled', false);
    }
    else {
	$('#iso_path').prop('disabled', true);
    }
})
</script>
</body>
</html>