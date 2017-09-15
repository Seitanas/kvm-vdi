function showHideTableSection(parentid,status){
    $.post({
        url : 'inc/infrastructure/KVM/TableState.php',
        data: {
            parentid: parentid,
            status: status,
        },
    });
}
//==================================================================
function updateVMLock(vmid,lock){
    $.post({
        url : 'inc/infrastructure/KVM/LockVM.php',
        data: {
            vm: vmid,
            lock: lock,
        },
        success: function(data){
            formatAlertMessage(data);
        },
    });
}
//==================================================================
function lockVM(vmid){
    if ($("#copy-disk-from-source-button-"+vmid ).hasClass( 'disabled' )){
        updateVMLock(vmid,'false');
        $("#lock-vm-button-"+vmid).html("VM locked:<i class=\"fa fa-fw fa-square-o\" aria-hidden=\"true\"></i>");
        $("#copy-disk-from-source-button-"+vmid).removeClass('disabled');
        $(".lockable-vm-buttons-"+vmid).removeClass('disabled');
        $("#PopulateMachinesButton-"+vmid).removeClass('disabled');
    }
    else{
        updateVMLock(vmid,'true');
        $("#lock-vm-button-"+vmid).html("VM locked:<i class=\"fa fa-fw fa-check-square-o\" aria-hidden=\"true\"></i>");
        $("#copy-disk-from-source-button-"+vmid).addClass('disabled');
        $(".lockable-vm-buttons-"+vmid).addClass('disabled');
        $("#PopulateMachinesButton-"+vmid).addClass('disabled');
    }
}
//==================================================================
function reloadKVMVmTable(){
    $( "#main_table" ).load( "inc/infrastructure/KVM/DrawTable.php" );
}
//==================================================================
function checkVMStatus(vm, state, is_parent){
    function runQuery(){
        $.post({
            url: 'inc/infrastructure/KVM/GetVMState.php',
                data: {
                    vm: vm,
                    is_parent: is_parent,
                },
                success: function(data) {
                    var required_state = '';
                    if(state == 'mass_on' || state == 'up')
                        required_state = 'running';
                    else
                        required_state = 'shut';
                    reply = jQuery.parseJSON(data);
                    if (is_parent){ // if there are multiple machines (using mass_x button from initial machine)
                        item_count = reply.length;
                        $.each(reply, function(i, obj){
                            if (obj['state'] == required_state){// if machine is in required state, update information in table
                                $("#VMStatusText-" + obj.id).html(obj.state_html);
                                $("#PowerProgressBar-" + obj.id).addClass('hide');
                                /*
                                    after specific machine is in required state, redraw its action
                                    buttons to match its current state:
                                */
                                if(required_state == 'shut'){
                                    $(".VMIsOffButtons-" + obj.id).removeClass('hide');
                                    $(".VMIsOnButtons-" + obj.id).addClass('hide');
                                }
                                if(required_state == 'running'){
                                    $(".VMIsOffButtons-" + obj.id).addClass('hide');
                                    $(".VMIsOnButtons-" + obj.id).removeClass('hide');
                                }
                                --item_count;
                            }
                            else{
                                $("#PowerProgressBar-" + obj.id).removeClass('hide');
                            }
                        });
                        if (item_count > 0){ // if there are still machines in unwanted state, re-run query
                            setTimeout(function() {runQuery()}, 4000);
                        }
                    }
                    else{// if it is a single VM power cycle
                        if (reply.state == required_state){
                            $("#VMStatusText-" + reply.id).html(reply.state_html);
                            $("#PowerProgressBar-" + reply.id).addClass('hide');
                            /*
                                after specific machine is in required state, redraw its action
                                buttons to match its current state:
                            */
                            if(required_state == 'shut'){
                                $(".VMIsOffButtons-" + reply.id).removeClass('hide');
                                $(".VMIsOnButtons-" + reply.id).addClass('hide');
                            }
                            if(required_state == 'running'){
                                $(".VMIsOffButtons-" + reply.id).addClass('hide');
                                $(".VMIsOnButtons-" + reply.id).removeClass('hide');
                            }
                        }
                        else{
                            $("#PowerProgressBar-" + reply.id).removeClass('hide');
                            setTimeout(function() {runQuery()}, 4000);
                        }
                    }
                },
            });
        }
    runQuery();
}
//==================================================================
$(document).ready(function(){
    $('#main_table').on("click", "a.DeleteVMButton", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        var vm = $(this).attr('data-vm');
        var hypervisor = $(this).attr('data-hypervisor');
        var action = $(this).attr('data-action');
        var parent = $(this).attr('data-parent');
        $.confirm({
            title: 'Alert!',
            content: 'Are you sure?',
            animation: 'opacity',
            buttons: {
                yes: {
                    btnClass: 'btn-danger',
                    action: function(){
                        $('#PleaseWaitDialog').modal('show');
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
                },
                no: {
                    btnClass: 'btn-primary',
                }
            }
        });
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

    $('#main_table').on("click", "a.LockVMButton", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        lockVM($(this).data('id'));
    });

    $('#main_table').on("click", ".MaintenanceCheckbox", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        var vm = $(this).data('id');
        $.post({
            url: 'inc/infrastructure/KVM/VMMaintenance.php',
                data: {
                    source: vm,
                    action: 'single',
                },
                success: function(data) {
                    formatAlertMessage(data);
                },
        });
    });

    $('#main_table').on("click", ".SnapshotCheckbox", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        var vm = $(this).data('id');
        $.post({
            url: 'inc/infrastructure/KVM/VMSnapshot.php',
                data: {
                    vm: vm,
                    action: 'single',
                },
                success: function(data) {
                    formatAlertMessage(data);
                },
        });
    });

    $('#main_table').on("click", ".CopyDiskButton", function(e) { //since table items are dynamically generated, we will not get ordinary .click() event
        e.preventDefault(); // prevent href to go # (jump to the top of the page)
        var vm = $(this).attr('data-vm');
        var hypervisor = $(this).attr('data-hypervisor');
        $.confirm({
            title: 'Alert!',
            content: 'Are you sure?',
            animation: 'opacity',
            buttons: {
                yes: {
                    btnClass: 'btn-danger',
                    action: function(){
                        $.get({
                            url: 'inc/infrastructure/KVM/CopyDisk.php',
                            data: {
                                vm: vm,
                                hypervisor: hypervisor,
                            },
                            success: function(data) {
                                formatAlertMessage(data)
                                refresh_screen();
                            },
                        });
                    }
                },
                no: {
                    btnClass: 'btn-primary',
                }
            }
        });

    });

    $('#main_table').on("click", ".MassMaintenanceButton", function(e) { //since table items are dynamically generated, we will not get ordinary .click() event
        e.preventDefault(); // prevent href to go # (jump to the top of the page)
        $('#PleaseWaitDialog').modal('show');
        var sourcevm = $(this).data('source');
        var action = $(this).data('action');
        $.post({
            url: 'inc/infrastructure/KVM/VMMaintenance.php',
                data: {
                    source: sourcevm,
                    action: action,
                },
                success: function(data) {
                    if (action == 'mass_on')
                        $('.MaintenanceCheckboxChild-' + sourcevm).prop('checked', 'true');
                    else
                        $('.MaintenanceCheckboxChild-' + sourcevm).prop('checked', '');
                    $('#PleaseWaitDialog').modal('hide');
                    formatAlertMessage(data);
                },
        });
    });

    $('#main_table').on("click", ".MassSnapshotButton", function(e) { //since table items are dynamically generated, we will not get ordinary .click() event
        e.preventDefault(); // prevent href to go # (jump to the top of the page)
        $('#PleaseWaitDialog').modal('show');
        var sourcevm = $(this).data('source');
        var action = $(this).data('action');
        $.post({
            url: 'inc/infrastructure/KVM/VMSnapshot.php',
                data: {
                    vm: sourcevm,
                    action: action,
                },
                success: function(data) {
                    if (action == 'mass_on')
                        $('.SnapshotCheckboxChild-' + sourcevm).prop('checked', 'true');
                    else
                        $('.SnapshotCheckboxChild-' + sourcevm).prop('checked', '');
                    $('#PleaseWaitDialog').modal('hide');
                    formatAlertMessage(data);
                },
        });
    });

    $('#main_table').on("click", ".ParentRow", function(e) { //since table items are dynamically generated, we will not get ordinary .click() event
        if ($('#childof-'+this.id).hasClass('fa-minus')){
            $('#childof-'+this.id).removeClass('fa-minus');
            $('#childof-'+this.id).addClass('fa-plus');
            showHideTableSection(this.id,'hide');
        }
        else {
            $('#childof-'+this.id).removeClass('fa-plus');
            $('#childof-'+this.id).addClass('fa-minus');
            showHideTableSection(this.id,'show');
        }
    });

    $('#main_table').on("click", ".PopulateMachinesButton", function(e) { // since table items are dynamically generated, we will not get ordinary .click() event
        e.preventDefault(); // prevent href to go # (jump to the top of the page)
        var vm = $(this).attr('data-vm');
        var hypervisor = $(this).attr('data-hypervisor');
        $.confirm({
            title: 'Alert!',
            content: 'All virtual machines will be powered off and their initial snapshots recreated.\nProceed?',
            animation: 'opacity',
            buttons: {
                yes: {
                    btnClass: 'btn-danger',
                    action: function(){
                        $('#PleaseWaitDialog').modal('show');
                        $.post({
                            url: 'inc/infrastructure/KVM/PopulateVMS.php',
                            data: {
                                vm: vm,
                                hypervisor: hypervisor,
                            },
                            success: function(data) {
                                $('#PleaseWaitDialog').modal('hide');
                                formatAlertMessage(data);
                            },
                        });
                   }
                },
                no: {
                    btnClass: 'btn-primary',
                }
            }
        });

    });

    $('#main_table').on("click", ".PowerButton", function(e) { // since table items are dynamically generated, we will not get ordinary .click() event
        e.preventDefault(); // prevent href to go # (jump to the top of the page)
        if (confirm('Are you sure?')){
            var vm = $(this).data('vm');
            var hypervisor = $(this).data('hypervisor');
            var state = $(this).data('state');
            var action = $(this).data('action');
            /*  for single VM power cycles
                'state' values should be:
                up, down, destroy
                must follow 'action' variable with value:
                single

                for multiple VM power cycle
                'action' values should be:
                mass_on, mass_off, mass_destroy
            */
            if (action == 'single')
                checkVMStatus(vm, state, 0);
            else
                checkVMStatus(vm, action, 1);
            $.post({
                url: 'inc/infrastructure/KVM/Power.php',
                    data: {
                        vm: vm,
                        hypervisor: hypervisor,
                        state: state,
                        action: action,
                    },
                    success: function(data) {
                        formatAlertMessage(data);
                    },
            });
        }
    });


});
//==================================================================
