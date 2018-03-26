<?php
/*
KVM-VDI
Tadas Ustinavičius


Vilnius,Lithuania.
2018-03-26
*/
include ('functions/config.php');
require_once('functions/functions.php');
slash_vars();
set_lang();
if ($_SERVER['HTTP_USER_AGENT']=='KVM-VDI client')
    $html5_client=0;
else
    $html5_client=1;
if (isset ($_POST['username'])){
    $mysql_conn=SQL_connect();
    $username=mysqli_real_escape_string($mysql_conn,$_POST['username']);
    $password=mysqli_real_escape_string($mysql_conn,$_POST['password']);
    $sql_reply=mysqli_fetch_row(mysqli_query($mysql_conn, "SELECT id,password FROM clients WHERE username LIKE '$username' AND isdomain=0"));
    mysqli_close($mysql_conn);
    if (session_status() == PHP_SESSION_NONE) 
        session_start();
    $_SESSION['client_logged']='';
    if(!empty($sql_reply[1])){
        if (password_verify($password, $sql_reply[1])){
            if (session_status() == PHP_SESSION_NONE) 
                session_start();
            $_SESSION['client_logged']='yes';
            $_SESSION['userid']=$sql_reply[0];
            $_SESSION['username']=$username;
            $ip = $_SERVER['REMOTE_ADDR'];
            add_SQL_line("UPDATE clients SET lastlogin=now(), ip='$ip' WHERE id='$sql_reply[0]'");
            header("Location: $serviceurl/client_pools.php");
            exit;
        }
    }
    else if ($LDAP_backend){
        $group_array='';
        if ($LDAP_backend=='activedir'){
            $query_user = $username."@".$domain_name;
            $group_array=list_ad_groups($username,$password,$query_user,$html5_client);
            $group_array=join("', '",$group_array);
        }
        else if ($LDAP_backend=='ldap'){
            $query_user= $username;
            $group_array=list_ldap_groups($username,$password,$query_user,$html5_client);
            $group_array = join("', '",$group_array); 
        }
        write_log("Groups for $query_user: " . $group_array);
        if(!empty($group_array)){
            $group_array="'" . $group_array . "'";
            $ip = $_SERVER['REMOTE_ADDR'];
            add_SQL_line("INSERT INTO clients (username,ip,isdomain,lastlogin) VALUES ('$query_user','$ip','1',NOW()) ON DUPLICATE KEY UPDATE ip='$ip', lastlogin=NOW()");
            $sql_reply=get_SQL_line("SELECT id FROM clients WHERE username LIKE '$query_user'");
            if (session_status() == PHP_SESSION_NONE) 
                session_start();
            if ($LDAP_backend=='activedir')
                $_SESSION['ad_user']='yes';
            else if ($LDAP_backend=='ldap')
                $_SESSION['ad_user']='LDAP';
            $_SESSION['client_logged']='yes';	    
            $_SESSION['userid']=$sql_reply[0];
            $_SESSION['username']=$query_user;
            $_SESSION['group_array']=$group_array;
        }
        else if(!$html5_client) {
            echo 'LOGIN_FAILURE';
            exit;
        }
    }
    else if(!$html5_client) {
        echo 'LOGIN_FAILURE';
        exit;
    }
}
if (!check_client_session()){
    if(!$html5_client) 
        echo 'LOGIN_FAILURE';
    else 
        header ("Location: $serviceurl/client_index.php?error=1");
    exit;
}
set_lang();
header("KVM-VDI-engine: " . $engine);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Tadas Ustinavičius">
    <title>KVM-VDI</title>
    <link href="inc/css/bootstrap.min.css" rel="stylesheet">
    <link href="inc/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="inc/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="inc/css/kvm-vdi.css" rel="stylesheet">
    <link href="inc/css/sb-admin-2.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

<!-- Modal -->
<div class="modal fade" id="loadingVM" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <span class="glyphicon glyphicon-time">
                    </span><?php echo _("Please wait");?>
                 </h4>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-info progress-bar-striped active" style="width: 100%">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal end -->

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
	        <?php
		$userid=$_SESSION['userid'];
		$username=$_SESSION['username'];
	    	echo '<a class="navbar-brand">' . $username . '</a>';
		?>
  </div>
    <ul class="nav navbar-nav navbar-right">
      <li><?php
	    if($html5_client)
		echo '<a href="logout.php?type=client"><span class="glyphicon glyphicon-log-in"></span> ' . _("Logout") . '</a></li>';
	?>
    </ul>
</nav>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3"></div>
        <div class="col-md-6">
            <div class="alert alert-warning hidden" id="warningbox"></div>
        </div>
        <div class="col-md-3"></div>
    </div>
    <div id="mainscreen"></div>
    <?php
        if (!$html5_client)
            draw_html5_buttons();?>
</div>
<script src="inc/js/jquery.min.js"></script>
<script src="inc/js/bootstrap.min.js"></script>
<script src="inc/js/kvm-vdi.js"></script>
<?php if ($engine == 'OpenStack')
    echo '<script src="inc/js/kvm-vdi-openstack.js"></script>';
?>
<script>
$(document).ready(function(){
var vm_booted=0;
var retries=4;
var checker_object;
var screen_object;
var engine = '<?php echo $engine;?>';
var use_kvmvdi_html5_client = '<?php echo $use_kvmvdi_html5_client;?>';
var client_url = '';
if (engine == 'OpenStack'){
    client_url = 'inc/infrastructure/OpenStack/GetClientConnection.php';
}
else
    var client_url = 'client.php';

var html5_client=<?php echo $html5_client ;?>;
    function reload_screen(){
        $( "#mainscreen" ).load( "draw_html5_buttons.php", function() {});
    }
    reload_screen();
if (html5_client){
    screen_object = setInterval(function(){reload_screen();},5000);
}
function call_vm(poolid){
    $.ajax({
        type : 'POST',
        url : client_url,
        data: {
            'pool': poolid,
            'protocol': "SPICE",
            'username': '',
            'password': '',
        },
        success:function (data) {
            vm=jQuery.parseJSON(data);
            if (vm.status=='OK'){
                vm_booted=1;
                clearInterval(checker_object);
                if (engine != 'OpenStack')
                    send_token(<?php echo "'" . $websockets_address . "', '" . $websockets_port . "', ";?>vm.name,vm.address,vm.spice_password);
                else if (engine == 'OpenStack' && use_kvmvdi_html5_client){
                    send_token(vm.address, vm.port, vm.token, vm.value, vm.spice_password);
                }
                else{
                    window.open(vm.html5_url);
                    heartbeatVM(vm.vm_id);
                }
            }
            if (vm.status=='MAINTENANCE'){
                $("#warningbox").html("<strong><?php echo _("Warning!");?></strong> <?php echo _("No VMs available. System in maintenance mode.");?><a class=\"close\" href=\"#\"  onclick=\"$('#warningbox').addClass('hidden')\">&times;</a>");
                $("#warningbox").removeClass('hidden');
                retries=0;
                clearInterval(checker_object);
            }
            if (vm.status=='BOOTUP'){
                //console.log("VM is booting");
            }
            if (vm.status=='NO_FREE_VMS'){
                $('#loadingVM').modal('hide');
                $("#warningbox").html("<strong><?php echo _("Warning!");?></strong> <?php echo _("No free VMs available.");?><a class=\"close\" href=\"#\"  onclick=\"$('#warningbox').addClass('hidden')\">&times;</a>");
                $("#warningbox").removeClass('hidden');
                retries=0;
                clearInterval(checker_object);
            }
        }
    })
}
function statusChecker(poolid){
    if (retries && !vm_booted){
//  console.log("checking");
    call_vm(poolid)
    }
    retries--;
}
    //$('.pools').click(function() {
    $(document).delegate(".pools","click",function(){
    $('#loadingVM').modal('show');
    if (!html5_client){
        document.title = ""
        document.title = "kvm-vdi-msg:" + $(this).attr('id')
    }
    else{
        heatbet_enabled=0;
        vm_booted=0;
        retries=4;
        var pool= $(this).attr('id');
        call_vm(pool);
        checker_object = setInterval(function(){ statusChecker(pool);}, 4000); //since ajax calls are asyncronous, we need to make some kind of scheduler for them not to be called at once
    }
    })
//    $('.shutdown').click(function() {
    $(document).delegate(".shutdown","click",function(){
    if (!html5_client){
        document.title = ""
        document.title = "kvm-vdi-msg:PM:shutdown:" + $(this).attr('id');
    }
    else {
        $.ajax({
            type : 'POST',
            url : 'client_power.php',
            engine: engine,
            data: {
            'vm': $(this).attr('id'),
            'action': 'shutdown',
            }
        });
    }
    })
//    $('.terminate').click(function() {
    $(document).delegate(".terminate","click",function(){
    if (!html5_client){
        document.title = ""
        document.title = "kvm-vdi-msg:PM:destroy:" + $(this).attr('id')
    }
    else {
        $.ajax({
            type : 'POST',
            url : 'client_power.php',
            data: {
            'vm': $(this).attr('id'),
            'action': 'destroy',
            }
        });
    }
    })
})
</script>
</body>
</html>