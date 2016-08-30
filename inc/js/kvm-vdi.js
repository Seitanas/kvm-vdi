function draw_table(){
    $( "#main_table" ).load( "draw_table.php" );
}

$(document).ready(function(){
    $('#create-vm-button-click').click(function() {
	$("#new_vm_creation_info_box").addClass('hide');;
	if(!$('#new_vm')[0].checkValidity()){
	    $('#new_vm').find('input[type="submit"]').click();    
	}
	else{
    	    $.ajax({
        	type : 'POST',
        	url : 'create_vm.php',
        	data: {
		    machine_type: $('#machine_type').val(),
		    hypervisor: $('#hypervisor').val(),
		    source_volume: $('#source_volume').val(),
		    source_drivepath: $('#source_drivepath').val(),
		    source_drive_size: $('#source_drive_size').val(),
		    iso_image: $('#iso_image').val(),
		    iso_path: $('#iso_path').val(),
		    numcpu: $('#numcpu').val(),
		    numcore: $('#numcore').val(),
		    numram: $('#numram').val(),
		    network: $('#network').val(),
		    machinename: $('#machinename').val(),
		    machinecount: $('#machinecount').val(),
		    os_type: $('#os_type').val(),
		    os_version: $('#os_version').val(),
		    vmname: $('#machinename').val(),
        	},
        	success:function (data) {
		    if (data=='VMNAME_EXISTS'){
			$("#new_vm_creation_info_box").removeClass('hide');
			$("#new_vm_creation_info_box").removeClass('alert-success');
			$("#new_vm_creation_info_box").addClass('alert-danger');
			$("#new_vm_creation_info_box").html("<i class=\"fa fa-minus-circle fa-fw\"></i>VM name already exists");
		    }
            	    if (data=='SUCCESS'){
			$("#new_vm_creation_info_box").removeClass('alert-danger');
			$("#new_vm_creation_info_box").removeClass('hide');
			$("#new_vm_creation_info_box").addClass('alert-success');
			$("#new_vm_creation_info_box").html("<i class=\"fa fa-thumbs-o-up fa-fw\"></i>Success");
			draw_table();
            	    }
        	}
    	    });
	}
    });
});