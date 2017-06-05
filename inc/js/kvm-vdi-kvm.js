function reloadKVMVmTable(){
    $( "#main_table" ).load( "inc/infrastructure/KVM/DrawTable.php" );
}
//==================================================================
$(document).ready(function(){
    $('#main_table').on("click", "a.DeleteVMButton", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        if (confirm('Are you sure?')){
            $('#PleaseWaitDialog').modal('show');
            var vm = $(this).attr('data-vm');
            var hypervisor = $(this).attr('data-hypervisor');
            var action = $(this).attr('data-action');
            var parent = $(this).attr('data-parent');
            $.post({
                url: 'inc/infrastructure/KVM/DeleteVM.php',
                data: {
                    vm: vm,
                    hypervisor: hypervisor,
                    action: action,
                    parent: parent
                },
                success: function(data) {
                    formatAlertMessage(data)
                    refresh_screen();
                    $('#PleaseWaitDialog').modal('hide');
                },
            });
        }
    });
//==================================================================
    $('#create-vm-button-click').click(function() {
        $("#new_vm_creation_info_box").addClass('hide');
        if(!$('#new_vm')[0].checkValidity()){
            $('#new_vm').find('input[type="submit"]').click();
        }
        else{
            $("#new_vm_creation_info_box").removeClass('hide');
            $("#new_vm_creation_info_box").removeClass('alert-danger');
            $("#new_vm_creation_info_box").addClass('alert-info');
            $("#new_vm_creation_info_box").html("<i class=\"fa fa-spinner fa-spin fa-fw\"></i>Creating VM please wait.");
            $(".create_vm_buttons").addClass('disabled');
            var machinename = $('#machinename').val();
            if ($('#machine_type').val()=='import')
                machinename = $('#source-machine').val();
                $.ajax({
                    type : 'POST',
                    url : 'inc/infrastructure/KVM/CreateVM.php',
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
                        machinename: machinename,
                        machinecount: $('#machinecount').val(),
                        os_type: $('#os_type').val(),
                        os_version: $('#os_version').val(),
                        vmname: $('#machinename').val(),
                        source_hypervisor: $('#source-hypervisor').val(),
                    },
                    success:function (data) {
                        if (data=='VMNAME_EXISTS'){
                            $("#new_vm_creation_info_box").html("<i class=\"fa fa-fw\"></i>VM already exists.");
                            $("#new_vm_creation_info_box").removeClass('hide');
                            $("#new_vm_creation_info_box").removeClass('alert-success');
                            $("#new_vm_creation_info_box").addClass('alert-danger');
                            $(".create_vm_buttons").removeClass('disabled');
                        }
                        else if (data=='SUCCESS' || $('#machine_type').val()=='initialmachine'){
                            $("#new_vm_creation_info_box").removeClass('alert-danger');
                            $("#new_vm_creation_info_box").removeClass('hide');
                            $("#new_vm_creation_info_box").addClass('alert-success');
                            $("#new_vm_creation_info_box").html("<i class=\"fa fa-thumbs-o-up fa-fw\"></i>Success");
                            $(".create_vm_buttons").removeClass('disabled');
                            refresh_screen();
                    }
                }
            });
        }
    });

    $('#main_table').on("click", "a.HypervisorMaintenanceButton", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        var hypervisor = $(this).data('hypervisor');
        var maintenance = $(this).data('maintenance');
        var $this = $(this); //need to move reference for ajax callback as $(this) will not work in ajax 'success:'
        $.post({
            url: 'inc/infrastructure/KVM/HypervisorMaintenance.php',
                data: {
                    maintenance: maintenance,
                    hypervisor: hypervisor,
                },
                success: function(data) {
                    if (maintenance == 1){
                        $this.data('maintenance', '0');
                        $this.removeClass('glyphicon-ok-circle');
                        $this.removeClass('btn-success');
                        $this.addClass('glyphicon-ban-circle');
                        $this.addClass('btn-default');
                        $('#hypervisor-table-' + hypervisor).addClass('hypervisor-screen-disabled');
                    }
                    else{
                        $this.data('maintenance', '1');
                        $this.removeClass('glyphicon-ban-circle btn-default');
                        $this.addClass('glyphicon-ok-circle btn-success');
                        $('#hypervisor-table-' + hypervisor).removeClass('hypervisor-screen-disabled');
                      }
                    formatAlertMessage(data);
                    refresh_screen();
                    $('#PleaseWaitDialog').modal('hide');
                },
        });
    });
});
//==================================================================
