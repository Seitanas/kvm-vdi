$(document).ready(function(){
    $('#AddHypervisorButton').click(function() {
        if(!$('#HypervisorForm')[0].checkValidity()){
            $('#HypervisorForm').find('input[type="submit"]').click();
        }
        else{
            $("#HypervisorProgress").removeClass('hide');
            $.post({
                url : 'inc/infrastructure/KVM/UpdateHypervisors.php',
                data: {
                    type : 'new',
                    address1 : $('#address1').val(),
                    address2 : $('#address2').val(),
                    port : $('#port').val(),
                    name : $('#name').val(),
                },
                success:function (data) {
                    var msg = jQuery.parseJSON(data);
                    if ("success" in msg){
                        $("#address1").val("");
                        $("#address2").val("");
                        $("#port").val("");
                        $("#name").val("");
                        refresh_screen();
                    }
                    $("#HypervisorProgress").addClass('hide');
                    refresh_screen();
                    formatAlertMessage(data);
                }
            });
        }
    });

    $('.DeleteHypervisorButton').click(function() {
        $("#SubmitHypervisorsButton").removeClass('hide');
        var id = $(this).data('id');
        $('#hypervisor-'+id).prop('checked', true);
        $(".name-"+id).addClass('hypervisor-deleted');
    })

    $('#SubmitHypervisorsButton').click(function() {
        var to_delete = [];
        $.confirm({
            title: 'Alert!',
            content: 'Are you sure?',
            animation: 'opacity',
            buttons: {
                yes: {
                    btnClass: 'btn-danger',
                    action: function(){
                        $(":checked").each(function() {
                            if ($(this).val()!='on')
                            to_delete.push($(this).val());
                            $("#row-name-"+$(this).val()).remove();
                        });
                        $.post({
                            url : 'inc/infrastructure/KVM/UpdateHypervisors.php',
                            data: {
                                type : 'delete',
                                hypervisor : to_delete,
                            },
                            success:function (data) {
                                $("#SubmitHypervisorsButton").addClass('hide');
                                refresh_screen();
                                formatAlertMessage(data);
                            }
                        });
                    }
                },
                no: {
                    btnClass: 'btn-primary',
                }
            }
        });
    });
});