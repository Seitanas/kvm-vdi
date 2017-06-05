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
//==================================================================
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
//==================================================================
function show_hide_table_section(parentid,status){
    $.ajax({
    type : 'POST',
        url : 'inc/infrastructure/TableState.php',
        data: {
        parentid: parentid,
        status: status,
        },
    });

}
//==================================================================
function send_token(websockets_address, websockets_port,token,value,spice_password){
    $.ajax({
        type : 'POST',
        url : 'inc/infrastructure/Websocket.php',
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
//==================================================================
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
//==================================================================
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
//==================================================================
function load_vm_list(){
    $.getJSON("inc/infrastructure/ListVms.php?type=ALL", {},  function(json){
            $('#multiselect').empty();
            $.each(json, function(i, obj){
                     $('#multiselect').append($('<option>').text(obj.name).attr('value', obj.id));
            });
    });
}
//==================================================================
function show_non_vdi_vms(status){
    var $poolid=$('#poollist').val();
    if (status=='checked'){
        $("#show-non-vdi-vms-checkbox").removeClass('fa-check-square-o');
        $("#show-non-vdi-vms-checkbox").addClass('fa-square-o');
        ("#show-non-vdi-vms-checkbox").data("status","");
        load_vm_pool_list($poolid, false);
    }
    else {
        $("#show-non-vdi-vms-checkbox").removeClass('fa-square-o');
        $("#show-non-vdi-vms-checkbox").addClass('fa-check-square-o');
        $("#show-non-vdi-vms-checkbox").data("status","checked");
        load_vm_pool_list($poolid, true);
    }
}
//==================================================================
function fill_source_machines(hypervisor){
    $.ajax({
        type : 'POST',
        url : 'list_machines.php',
        data: {
            hypervisor: hypervisor,
            type:'sourcemachine',
        },
        success:function (data) {
            var src = "#source-machine";
            $(src).children().remove();
            if (data){
                var obj = jQuery.parseJSON(data);
                $.each(obj, function(key,value) {
                    $(src).append('<option value="' + value + '">' + value + "</option>'");
                });
            }
        }
    })
}
//==================================================================
function showAlert(title, text, icon, type){
    new PNotify({
        title: title,
        text: text,
        type: type,
        styling: 'bootstrap3',
        icon: icon,
        buttons: {
            closer_hover: false,
            sticker: false
        }
    });
}
//==================================================================
function generatePassword(){
    var password = "";
    var char_map = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for( var i=0; i < 10; i++ )
        password += char_map.charAt(Math.floor(Math.random() * char_map.length));
    return password;
}
//==================================================================
$(document).ready( function() { 
    $('#RefreshButton').click(function() {
        refresh_screen();
    });

    $('#AddADGroupButton').click(function() {
        if(!$('#ADGroup')[0].checkValidity()){
            $('#ADGroup').find('input[type="submit"]').click();
        }
        else{
            $.post({
                url : 'inc/infrastructure/UpdateADGroups.php',
                data: {
                    type : 'new',
                    group_name : $('#GroupName').val(),
                },
                success:function (data) {
                    var reply=jQuery.parseJSON(data);
                    if ("error" in reply)
                        showAlert("Error", reply.error, "fa fa-exclamation-triangle fa-fw", "error");
                    if ("success" in reply)
                        showAlert("Success", reply.success, "fa fa-check-circle-o fa-fw", "success");
                    refresh_screen();
                    $("#GroupName").val("");
                }
            });
        }
    });

    $('#PWGen').click(function() {
        var password = generatePassword();
        $("#password").val(password);
    });

    $('#UpdateCredentialsButton').click(function() {
        if(!$('#CredentialsForm')[0].checkValidity()){
            $('#CredentialsForm').find('input[type="submit"]').click();
        }
        else{
            $.post({
                url : 'inc/infrastructure/ManageCredentials.php',
                data: {
                    type : 'new',
                    credential_type : $('#credential_type').val(),
                    username : $('#username').val(),
                    password : $('#password').val(),
                },
                success:function (data) {
                    var reply=jQuery.parseJSON(data);
                    if ("error" in reply)
                        showAlert("Error", reply.error, "fa fa-exclamation-triangle fa-fw", "error");
                    if ("success" in reply)
                        showAlert("Success", reply.success, "fa fa-check-circle-o fa-fw", "success");
                    $("#username").val("");
                    $("#password").val("");
                }
            });
        }
    });

    $('.ResetPasswordButton').click(function() {
        var id = $(this).data('id');
        var password = generatePassword();
        $("#ShowPasswordBox").removeClass('hide');
        $("#ShowPasswordBox").html("Password: " + password);
        $.post({
            url : 'inc/infrastructure/ManageCredentials.php',
            data: {
                id : id,
                type : 'update-pw',
                password : password,
                credential_type : $('#credential_type').val(),
            },
            success:function (data) {
                var reply=jQuery.parseJSON(data);
                showAlert("Success", reply.success, "fa fa-check-circle-o fa-fw", "success");
            }
        })
    })

    $('.DeleteCredentialButton').click(function() {
        $("#SubmitCredentialsButton").removeClass('hide');
        var id = $(this).data('id');
        $('#user-'+id).prop('checked', true);
        $(".name-"+id).addClass('users-deleted');
    })

    $('#SubmitCredentialsButton').click(function() {
        var to_delete = [];
        if (confirm('Are you sure?')){
            $(":checked").each(function() {
            if ($(this).val()!='on')
                to_delete.push($(this).val());
            $("#row-name-"+$(this).val()).remove();
            });
            $.post({
                url : 'inc/infrastructure/ManageCredentials.php',
                data: {
                    type : 'delete',
                    credid : to_delete,
                    credential_type : $('#credential_type').val(),
                },
                success:function (data) {
                    var reply=jQuery.parseJSON(data);
                    showAlert("Success", reply.success, "fa fa-check-circle-o fa-fw", "success");
                    $("#SubmitCredentialsButton").addClass('hide');
                }
            });
        }
    })
});
