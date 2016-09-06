function draw_table(){
    $( "#main_table" ).load( "draw_table.php" );
}
function update_VM_lock(vmid,lock){
    $.ajax({
	type : 'POST',
        url : 'lock_vm.php',
        data: {
	    vm: vmid,
	    lock: lock,
    	},
    });
}
function lock_VM(vmid){
    if ($("#copy-disk-from-source-button-"+vmid ).hasClass( 'disabled' )){
	$("#lock-vm-button-"+vmid).html("VM locked:<i class=\"fa fa-fw fa-square-o\" aria-hidden=\"true\"></i>");
	$("#copy-disk-from-source-button-"+vmid).removeClass('disabled');
	$(".lockable-vm-buttons-"+vmid).removeClass('disabled');
	$("#populate-machines-button-"+vmid).removeClass('disabled');
	update_VM_lock(vmid,'false');
    }
    else{
	update_VM_lock(vmid,'true');
	$("#lock-vm-button-"+vmid).html("VM locked:<i class=\"fa fa-fw fa-check-square-o\" aria-hidden=\"true\"></i>");
	$("#copy-disk-from-source-button-"+vmid).addClass('disabled');
	$(".lockable-vm-buttons-"+vmid).addClass('disabled');
	$("#populate-machines-button-"+vmid).addClass('disabled');
    }
}
function show_hide_table_section(parentid,status){
    $.ajax({
	type : 'POST',
        url : 'inc/infrastructure/table_pos.php',
        data: {
	    parentid: parentid,
	    status: status,
    	},
    });

}
function send_token(websockets_address, websockets_port,token,value,spice_password){
    $.ajax({
        type : 'POST',
        url : 'websocket.php',
        data: {
            'token': token,
            'value': value,
        },
        success:function (data) {
            if (data=='OK'){
                 window.open("spice_html5/?host="+websockets_address+"&port="+websockets_port+"?password="+spice_password+"&vmInfoToken="+token);
                $('#loadingVM').modal('hide');
            }
        }
    })
}
function load_client_pool_list(poolid,type){
    $.getJSON("clients_in_pool.php?side=from&poolid="+poolid+"&type="+type, {},  function(json){
            $('#multiselect').empty();
            $.each(json, function(i, obj){
                     $('#multiselect').append($('<option>').text(obj.username).attr('value', obj.id));
            });
    });
    $.getJSON("clients_in_pool.php?side=to&poolid="+poolid+"&type="+type, {},  function(json){
            $('#multiselect_to').empty();
            $.each(json, function(i, obj){
                    $('#multiselect_to').append($('<option>').text(obj.username).attr('value', obj.id));
            });
    });
}
function load_vm_pool_list(poolid, non_VDI_vms){
    var list_non_vdi_vms=0;
    if (non_VDI_vms)
	list_non_vdi_vms=1;
    $.getJSON("vms_in_pool.php?side=from&list_non_vdi_vms="+list_non_vdi_vms, {},  function(json){
            $('#multiselect').empty();
            $.each(json, function(i, obj){
                     $('#multiselect').append($('<option>').text(obj.name).attr('value', obj.id));
            });
    });
    $.getJSON("vms_in_pool.php?side=to&poolid="+poolid, {},  function(json){
            $('#multiselect_to').empty();
            $.each(json, function(i, obj){
                    $('#multiselect_to').append($('<option>').text(obj.name).attr('value', obj.id));
            });
    });
}

function show_non_vdi_vms(status){
    var $poolid=$('#poollist').val();
    if (status=='checked'){
	$("#show-non-vdi-vms-checkbox").removeClass('fa-check-square-o');
	$("#show-non-vdi-vms-checkbox").addClass('fa-square-o');
	$("#show-non-vdi-vms-checkbox").data("status","");
	load_vm_pool_list($poolid, false);
    }
    else {
	$("#show-non-vdi-vms-checkbox").removeClass('fa-square-o');
	$("#show-non-vdi-vms-checkbox").addClass('fa-check-square-o');
	$("#show-non-vdi-vms-checkbox").data("status","checked");
	load_vm_pool_list($poolid, true);
    }
}
$(document).ready(function(){
    $('#create-vm-button-click').click(function() {
	$("#new_vm_creation_info_box").addClass('hide');
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
		    numsock: $('#numsock').val(),
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
		    }
            	    else if (data=='SUCCESS' || $('#machine_type').val()=='initialmachine'){
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