<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-07-21
*/
include ('functions/config.php');
require_once('functions/functions.php');
slash_vars();
if (isset ($_POST['username'])){
    $username=$_POST['username'];
    $password=$_POST['password'];
    $sql_reply=get_SQL_line("SELECT id,password FROM clients WHERE username LIKE '$username' AND isdomain=0");
    if(!empty($sql_reply[1])){
	if (password_verify($password, $sql_reply[1])){
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
    else if ($ad_enabled){
	$query_user =$username."@".$ad_name;
	$ldap_login_err=0;
	$ldap = ldap_connect($ad_host) or $ldap_login_err=1;
	ldap_bind($ldap,$query_user,$password) or  $ldap_login_err=1;
	if ($ldap_login_err){
	    echo 'LOGIN_FAILURE';
	    exit;
	}
	else {
	    $results = ldap_search($ldap,$ldap_dn,"(samaccountname=$username)",array("memberof","primarygroupid","displayname"));
	    $entries = ldap_get_entries($ldap, $results);
	}
	if($entries['count'] == 0) {
        }
	$output = $entries[0]['memberof'];
	$token = $entries[0]['primarygroupid'][0];
	$fullname= $entries[0]['displayname'][0];
	array_shift($output);
	$results2 = ldap_search($ldap,$ldap_dn,"(objectcategory=group)",array("distinguishedname","primarygrouptoken"));
        $entries2 = ldap_get_entries($ldap, $results2);
        array_shift($entries2);
	foreach($entries2 as $e) {
    	    if($e['primarygrouptoken'][0] == $token) {
		$output[] = $e['distinguishedname'][0];
		break;
	    }
	}
	$group_count=0;
	foreach ($output as &$value) {
	    $tmp_CN=explode(",",$value);
	    $tmp_CN[0]=str_replace("CN=","",$tmp_CN[0]);
	    if (!empty($tmp_CN[0]))
		++$group_count;
	    $group_array="" . $group_array . "','" . $tmp_CN[0];
	#if (strpos($value, $rpm_admin_group)) {$_SESSION['admin']=1;$allowed=1;}
	#if (strpos($value, $rpm_user_group)) {$_SESSION['admin']=0;$allowed=1;}
	}
	if($group_count){
	    $group_array = substr($group_array, 2); 
	    $group_array=$group_array."'";
	    $ad_groups_validate=get_SQL_array("SELECT * FROM ad_groups WHERE name IN ($group_array)");
	    $ip = $_SERVER['REMOTE_ADDR'];
	    add_SQL_line("INSERT INTO clients (username,ip,isdomain,lastlogin) VALUES ('$query_user','$ip','1',NOW()) ON DUPLICATE KEY UPDATE ip='$ip', lastlogin=NOW()");
	    $sql_reply=get_SQL_line("SELECT id FROM clients WHERE username LIKE '$query_user'");
	    session_start();
	    $_SESSION['ad_user']='yes';
	    $_SESSION['client_logged']='yes';	    
	    $_SESSION['userid']=$sql_reply[0];
	    $_SESSION['username']=$query_user;
	    $_SESSION['group_array']=$group_array;
	}
	else {
	    echo 'LOGIN_FAILURE';
	    exit;
	}
	if(empty($ad_groups_validate[0]['id'])){//there are no groups mapped
	    echo 'LOGIN_FAILURE';
	    exit;
	}
    }
    else {
	echo 'LOGIN_FAILURE';
	exit;
    }
}
if (!check_client_session()){
    header ("Location: $serviceurl/client_index.php?error=1");
    exit;
}
set_lang();
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
    <link href="inc/css/custom.css" rel="stylesheet">
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
  </div>
</nav>
    <div class="container">
	<div class="row">
<?php 
    $last_reload=get_SQL_array ("SELECT id FROM config WHERE name='lastreload' AND valuedate > DATE_SUB(NOW(), INTERVAL 30 SECOND) LIMIT 1"); //if there was no reload of VM list in 30 seconds, initiate reload.
    if (!$last_reload[0]['id']){
	add_SQL_line("INSERT INTO config (name,valuedate) VALUES ('lastreload',NOW()) ON DUPLICATE KEY UPDATE valuedate=NOW()");
	reload_vm_info();
    }
    if ($_SESSION['ad_user']=='yes'){
	$group_array=$_SESSION['group_array'];
	$pool_reply=get_SQL_array("SELECT DISTINCT(pool.id), pool.name FROM poolmap_ad  LEFT JOIN pool ON poolmap_ad.poolid=pool.id LEFT JOIN ad_groups ON poolmap_ad.groupid=ad_groups.id WHERE ad_groups.name IN ($group_array)");
    }
    else
	$pool_reply=get_SQL_array("SELECT pool.id, pool.name FROM poolmap  LEFT JOIN pool ON poolmap.poolid=pool.id WHERE clientid='$userid'");
//    print_r($pool_reply);
    $x=0;
    while ($x<sizeof($pool_reply)){
	    $vm_count=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}' AND vms.maintenance!='true' AND hypervisors.maintenance!=1");
	    $vm_count_available=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}' AND vms.maintenance!='true'  AND hypervisors.maintenance!=1 AND vms.lastused < DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) ");
	    $already_have=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}'AND vms.maintenance!='true' AND hypervisors.maintenance!=1 AND vms.clientid='$userid' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)");	
	    $vm_image="text-warning";
	    $provided_vm=array();
	    $provided_vm[0]['name']="none";
	    if ($already_have[0][0]==1){
		$vm_image="text-success";
		$provided_vm=get_SQL_array("SELECT vms.name,vms.state,vms.id FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}'AND vms.maintenance!='true' AND hypervisors.maintenance!=1 AND vms.clientid='$userid' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)");
	    }
	    $pm_icons="";
	    if ($vm_count_available[0][0]==0)
		$vm_image="text-muted";
	    if ($provided_vm[0]['state']=='running'||$provided_vm[0]['state']=='pmsuspended'||$provided_vm[0]['state']=='paused'){
		$pm_icons='<a href="#" class="shutdown"  id="' . $provided_vm[0]['id'] . '"><i class="pull-left fa fa-stop-circle-o text-danger" title="Shutdown machine"></i></a>';
		$pm_icons=$pm_icons.'<a href="#" class="terminate"  id="' . $provided_vm[0]['id'] . '"><i class="pull-left fa fa-times-circle-o text-danger" title="Terminate machine"></i></a>';
	    }
	    echo'<div class="col-md-2">';
		echo '<div class="row text-info">
		    <div class="panel panel-default">
			<div class="panel-heading">
			    <div class="row">
				<div class="col-xs-4">
				    <small>' . $pm_icons  . '</small>
				</div>
				<div class="col-xs-8">
				    <small>' . $provided_vm[0]['name'] . '</small>
				</div>
			    </div>
			    <div class="row">	
				<div class="text-center">
		    		    <a href="#" id="' . $pool_reply[$x]['id'] . '" class="pools">
					<span class="fa-stack fa-4x">
					    <i class="fa fa-square-o fa-stack-2x"></i>
					    <i class="fa fa-power-off fa-stack-1x ' . $vm_image . '"></i>
					</span>
				    </a>
				</div>
			    </div>
			    <div class="row text-center">
				<div>
				    <span>' . $pool_reply[$x]['name'] . '</span>
				</div>
			    </div>
			</div>
			<div class="panel-footer">
		    	    <span class="pull-left"><small>Pool size: ' . $vm_count[0][0] . '</small></span>
			    <span class="pull-right"><small>Available: ' . $vm_count_available[0][0] . '</small></span>
			    <div class="clearfix"></div>
			</div>

			    
		</div>
	</div>
</div>'."\n";
	    ++$x;
	    if ((($x % 4) / 4)==0)//number of columns
		echo '</div>' . "\n". '<div class="row">' . "\n";

    }?>
	</div>
    </div>
    <script src="inc/js/jquery.min.js"></script>
    <script src="inc/js/bootstrap.min.js"></script>
<script>
$(document).ready(function(){
    $('.pools').click(function() {
	$('#loadingVM').modal('show');
	document.title = ""
	document.title = "kvm-vdi-msg:" + $(this).attr('id')
    })
    $('.shutdown').click(function() {
	document.title = ""
	document.title = "kvm-vdi-msg:PM:shutdown:" + $(this).attr('id')
    })
    $('.terminate').click(function() {
	document.title = ""
	document.title = "kvm-vdi-msg:PM:destroy:" + $(this).attr('id')
    })
    function PM(vmid,action){
    $.ajax({
            type : 'POST',
            url : 'client_power.php',
            data: {
                vm : vmid,
                action : action,
            },
	})
    }
})
</script>
  </body>
</html>