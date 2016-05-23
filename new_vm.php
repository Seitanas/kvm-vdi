<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-05-13
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
$h_reply=get_SQL_array("SELECT * FROM hypervisors WHERE maintenance=0");
set_lang();
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
             <h4 class="modal-title"><?php echo _("Create virtual machine(s)");?></h4>
        </div>
        <div class="modal-body">
	    <div class="row">
		 <div class="col-md-5">
		    <label><?php echo _("Machine type:");?></label>
		    <select class="form-control selectClass" name="machine_type" id="machine_type" required tabindex="1">
			<option selected value=""><?php echo _("Please select machine type");?></option>
	    	        <option value="simplemachine"><?php echo _("Simple machine");?></option>
        		<option value="initialmachine"><?php echo _("Initial machine");?></option>
			<option value="sourcemachine"><?php echo _("Source machine");?></option>
			<option value="vdimachine"><?php echo _("VDI machine");?></option>
	    	    </select>
		</div>
		 <div class="col-md-5">
		    <label><?php echo _("Target hypervisor:");?></label>
		    <select class="form-control selectClass" name="hypervisor" id="hypervisor" required tabindex="2">
			<option selected value=""><?php echo _("Please select hypervisor");?></option>
			<?php
			$x=0;
			while ($x<sizeof($h_reply)){
			    echo '<option value="' . $h_reply[$x]['id'] .  '">' . $h_reply[$x]['ip'] . '</option>';
			    ++$x;
			}?>
	    	    </select>
		 </div>
	    </div>
	    <div class="row">
		<?php 
	    		echo '<div class="col-md-5 hide" id="hypervisor-sourceimage">
			    <label>' . _("Use volume from:") . '</label>';
			$v_reply=get_SQL_array("SELECT id,name,machine_type,hypervisor FROM vms WHERE (machine_type='sourcemachine' OR machine_type='initialmachine') ORDER By name");
			$y=0;
			echo '<select class="form-control" name="source_volume" id="source_volume">' ."\n";
			echo '<option selected value="">' . _("Please select source") . '</option>'."\n";
			while ($y<sizeof($v_reply)){
			    echo '<option class="' . $v_reply[$y]['machine_type'] . ' hypervisor-' . $v_reply[$y]['hypervisor']  . '" value="' . $v_reply[$y]['id'] .  '">' . $v_reply[$y]['name'] . '</option>' ."\n";
			    ++$y;
			}
			echo '</select>' . "\n";
			echo '</div>' . "\n";
		    ?>
		    <div id="hypervisor-manualpath" class="hide">
		        <div class="col-md-5">
		    	    <label><?php echo _("Specify disk path:");?></label>		    
			    <input type="text" name="source_drivepath" class="form-control" id="source_drivepath" value="<?php echo $default_imagepath; ?>">
			</div>
			<div class="col-md-4">
    				<label><?php echo _("Disk size");?></label>		    
			    <div class="input-group">
				<input type="number" min="1" value="10" name="source_drive_size" class="form-control">
				<span class="input-group-addon">GB</span>
			    </div>
			</div>
		    </div>	
	    </div>
	    <div class="hide" id="hypervisor-imagepath">
		<div class="row">
		    <div class="col-md-9">
			<label><?php echo _("Mount CD iso:");?></label>
			<div class="input-group">
			    <span class="input-group-addon" style="min-width:40px;">
				<input type="checkbox" name="iso_image" id="iso_image">
			    </span>
			    <select class="form-control" name="iso_path" id="iso_path" disabled>
				<option value=""><?php echo _("Select ISO image");?></option>
			<?php
			$x=0;
			while ($x<sizeof($h_reply)){
			    ssh_connect($h_reply[$x]['ip'].":".$h_reply[$x]['port']);
			    $files=explode("\n",ssh_command("sudo ls " . $default_iso_path . "|grep -i .iso", true));
			    foreach ($files as &$value) {
				if (!empty($value))
				    echo '<option class="iso_option hypervisor_iso-' . $h_reply[$x]['id'] . '" value="' . $value . '">' . $value . '</option>'."\n";
			    }
	    		    ++$x;
			}
			?>
			    </select>
			</div>
		    </div>
		</div>
	    </div>
	    <div class="row">
		<div class="col-md-4">
		    <label><?php echo _("Hardware info:");?></label>
		    <div class="input-group">
			<input type="number" min="1" value="1" class="form-control" name="numcpu">
			<span class="input-group-addon"><?php echo _("CPUs");?></span>
		    </div>
		    <div class="input-group">
			<input type="number" min="1" value="1" class="form-control" name="numcore">
			<span class="input-group-addon"><?php echo _("Cores");?></span>
		    </div>
		    <div class="input-group">
			<input type="number" min="1" value="1" class="form-control" name="numram">
			<span class="input-group-addon"><?php echo _("GB RAM");?></span>
		    </div>
		    <div class="input-group">
			<input type="text" value="<?php echo $default_bridge; ?>" class="form-control" name="network">
			<span class="input-group-addon"><?php echo _("Network");?></span>
		    </div>
		</div>
		<div class="col-md-8" id="newmachine-os">
		    <label>System info:</label>
		    <div class="input-group">
			<span class="input-group-addon"><?php echo _("OS type");?></span>
			<select class="form-control osselection" name="os_type" id="os_type" tabindex="3" required>
			    <option selected value=""><?php echo _("Please select OS type");?></option>
	    		    <option value="linux"><?php echo _("Linux");?></option>
        		    <option value="windows"><?php echo _("Windows");?></option>
	    		</select>
		    </div>
		    <div class="input-group hide" id="os">
			<span class="input-group-addon"><?php echo _("Version");?></span>
			<select class="form-control osselection" name="os_version" id="os_version" tabindex="4" required>
			    <option selected value=""><?php echo _("Please select version");?></option>
	    		    <option class="linux" value="debiansqueeze">Debian Squeeze (or newer)</option>
	    		    <option class="linux" value="debianlenny">Debian Lenny</option>
	    		    <option class="linux" value="debianetch">Debian Etch</option>
	    		    <option class="linux" value="ubuntuprecise">Ubuntu 12.04 LTS</option>
	    		    <option class="linux" value="ubuntusaucy">Ubuntu 13.10 (or newer)</option>
	    		    <option class="linux" value="fedora18">Fedora 18</option>
	    		    <option class="linux" value="fedora19">Fedora 19</option>
	    		    <option class="linux" value="fedora20">Fedora 20</option>
        		    <option class="windows" value="win7">Microsoft Windows 7 (or newer)</option>
        		    <option class="windows" value="vista">Microsoft Windows Vista</option>
        		    <option class="windows" value="winxp">Microsoft Windows XP</option>
        		    <option class="windows" value="win2k8">Microsoft Windows Server 2008</option>
        		    <option class="windows" value="win2k3">Microsoft Windows Server 2003</option>
	    		</select>
		    </div>
		</div>
	    </div>
	    <div class="row">
		<hr class="divider">	
		<div class="col-md-5">
        	    <label><?php echo _("Mass deployment");?></label>
		</div>
	    </div>
	    <div class="row">
		<div class="col-md-5">
		    <label><?php echo _("Prepend machine name:");?></label>		    
		    <input type="text" name="machinename" placeholder="somename-" class="form-control" required>
		</div>
		<div class="col-md-5">
		    <label><?php echo _("Number of machines to create:");?></label>		    
		    <input type="number" name="machinecount" min="1" value="1" class="form-control" required>
		</div>
	    </div>
        </div>
	
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="submit" class="btn btn-primary"><?php echo _("Create VMs");?></button>
        </div>
    </div>
</form>
<script>
$('.selectClass').on('change', function(){
    $('#hypervisor-sourceimage').addClass('hide');
    $('#hypervisor-imagepath').addClass('hide');
    $('.sourcemachine').hide();	
    $('.initialmachine').hide();	
    $('.iso_option').hide();	
    $hypervisor_id=$('#hypervisor').val();
    if (($('#machine_type').val() == 'initialmachine' || $('#machine_type').val() == 'vdimachine') && $hypervisor_id!='') {
	$('#hypervisor-sourceimage').removeClass('hide');	    
	$('.hypervisor-'+$hypervisor_id).show();
	if ($('#machine_type').val() == 'initialmachine'){
	    $('.initialmachine').hide();	
	    $('#source_volume').prop('selectedIndex',0);
	}
	if ($('#machine_type').val() == 'vdimachine'){
	    $('.sourcemachine').hide();	
	    $('#source_volume').prop('selectedIndex',0);
	}
        $('#hypervisor-'+$hypervisor_id).removeClass('hide');
	$('#hypervisor-manualpath').removeAttr('required');
	$('#source_drivepath').prop('required',false);
	$('#source_volume').prop('required',true);
	$('.osselection').prop('required',false);
    }
    if (($('#machine_type').val() == 'simplemachine' || $('#machine_type').val() == 'sourcemachine') && $hypervisor_id!='') {
	$('.hypervisor_iso-'+$hypervisor_id).show();	
	$('#hypervisor-manualpath').removeClass('hide');
	$('#hypervisor-imagepath').removeClass('hide');
	$('#source_drivepath').prop('required',true);
	$('#source_volume').prop('required',false);
	$('.osselection').prop('required',true);
    }
	
})
$('#os_type').on('change', function(){
    if ($('#os_type').val()=='linux'){
	$('#os_version').prop('selectedIndex',0);
	$('#os').removeClass('hide');
	$('.windows').hide();
	$('.linux').show();
    }
    if ($('#os_type').val()=='windows'){
	$('#os_version').prop('selectedIndex',0);
	$('#os').removeClass('hide');
	$('.windows').show();
	$('.linux').hide();
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