<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius,Lithuania.
2018-03-29
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
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
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="Tadas Ustinavičius">
    <title>VDI dashboard</title>
    <link href="inc/css/kvm-vdi.css" rel="stylesheet">
    <!-- Bootstrap core CSS -->
    <link href="inc/css/bootstrap.min.css" rel="stylesheet">
    <!-- metisMenu CSS -->
    <link href="inc/metisMenu/metisMenu.min.css" rel="stylesheet">
    <!-- Font Awesome-->
    <link href="inc/font-awesome/css/font-awesome.min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="inc/css/ie10-viewport-bug-workaround.css" rel="stylesheet">
    <link href="inc/css/dashboard.css" rel="stylesheet">
    <link href="inc/PNotify/pnotify.custom.min.css" rel="stylesheet">
    <link href="inc/jquery-confirm/css/jquery-confirm.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Bootstrap core JavaScript ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="inc/js/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="inc/js/vendor/jquery.min.js"><\/script>')</script>
    <script src="inc/js/bootstrap.min.js"></script>
    <script src="inc/js/multiselect.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="inc/js/ie10-viewport-bug-workaround.js"></script>
    <script src="inc/metisMenu/metisMenu.min.js"></script>
    <script src="inc/PNotify/pnotify.custom.min.js"></script>
    <script src="inc/jquery-confirm/js/jquery-confirm.min.js"></script>
    <script src="inc/js/kvm-vdi.js"></script>
    <?php
        if ($engine == 'OpenStack')
            echo '<script src="inc/js/kvm-vdi-openstack.js"></script>';
        else
            echo '<script src="inc/js/kvm-vdi-kvm.js"></script>';
    ?>
    <script>
    function countdown(filepath,id) {
        var $container = $("#progress-"+id);
        (function step() {
        $.get(filepath, function(count){
            if (count==0){
                $container.removeClass('progress-bar-success');
                $container.css('width','100%').attr('aria-valuenow', count);  
                $container.html('');
            }
            else{
                $container.addClass('progress-bar-success');
                $container.css('width', count+'%').attr('aria-valuenow', count);  
                $container.html(count+'%');
            }
            if (count < 100 && count!='') {
                $container.parent('div').show();
                setTimeout(step, 5000);
            }
            if (count == 100 || count=='') {
                $container.parent('div').hide();
            }
        });
     })();
    }
    function confirmation1() {
        if (confirm("<?php echo _("All virtual machines will be powered off and their initial snapshots recreated.\\nProceed?");?>")) {
            $('#copyalert').show();
            return true;
         }
        return false;
    }
    function confirmation2() {
        if (confirm("<?php echo _("All VDI machines will be deleted.\\nProceed?");?>")) {
            $('#copyalert').show();
            return true;
         }
        return false;
    }
    </script>
  </head>
<?php 
    require_once('functions/functions.php');
    $sql_reply=get_SQL_array("SELECT * FROM hypervisors");
?>
<body>
<div class="modal fade" id="modalWm" tabindex="-1" role="dialog" aria-labelledby="modalWm" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Modal title</h4>

            </div>
            <div class="modal-body"><div class="te"></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<div class="modal fade" id="PleaseWaitDialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
          <h1><?php echo _("Please wait");?></h1>
      </div>
      <div class="modal-body">
        <div class="progress">
          <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalWmLg" tabindex="-1" role="dialog" aria-labelledby="modalWm" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Modal title</h4>

            </div>
            <div class="modal-body"><div class="te"></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>


<!-- /.modal -->

<!-- Modal vm console-->
<div class="modal fade " id="vmConsole" tabindex="-1" role="dialog" aria-labelledby="vmConsole" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Modal title</h4>

            </div>
            <div class="modal-body"><div class="te"></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<div class="navbar navbar-inverse navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">VDI dashboard</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                <li><a href="#" id="RefreshButton" data-toggle="hover">
                    <button type="button" class="btn btn-info" aria-label="Refresh" title="<?php echo _("Refresh VM info");?>">
                    <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button></a>
               </li>
            </ul>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?php 
        while ($upgradedfrom=check_upgrade()){
            echo '<div class="row">
                <div class="col-md-6 col-md-offset-2 text-center">
                    <div class="alert alert-info" role="alert">' . _("Database upgraded from: $upgradedfrom") . '
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                </div>
            </div>';
}
?>
    <div class="row">
	<div class="col-md-2 sidebar nopadding">
    	    <ul class="metismenu nav" id="left-menu">
		<li>
		    <a href="#" aria-expanded="false"><span class="fa arrow"></span><i class="fa fa-user fa-fw"></i><?php echo _("Clients");?></a>
		    <ul aria-expanded="false">
    	    		<li class="nav"><a href="add_credential.php?credential_type=client" data-toggle="modal" data-target="#modalWm"><i class="fa fa-user-plus fa-fw"></i><?php echo _("Add client");?></a></li>
			<li class="nav"><a href="list_credentials.php?credential_type=client" data-toggle="modal" data-target="#modalWm"><i class="fa fa-recycle fa-fw"></i><?php echo _("Manage clients");?></a></li>
			<?php
			if ($LDAP_backend=='activedir'){
				    echo '<li class="nav-divider"></li>';
				    echo '<li class="nav"><a href="add_ad_group.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-group fa-fw"></i>' .  _("Add AD group") . '</a></li>';
				    echo '<li class="nav"><a href="list_credentials.php?credential_type=adgroup" data-toggle="modal" data-target="#modalWm"><i class="fa fa-recycle fa-fw"></i>' .  _("Manage AD groups") . '</a></li>';
			}
			if ($LDAP_backend=='ldap'){
				    echo '<li class="nav-divider"></li>';
				    echo '<li class="nav"><a href="add_ad_group.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-group fa-fw"></i>' .  _("Add LDAP attribute") . '</a></li>';
				    echo '<li class="nav"><a href="list_credentials.php?credential_type=adgroup" data-toggle="modal" data-target="#modalWm"><i class="fa fa-recycle fa-fw"></i>' .  _("Manage LDAP attributes") . '</a></li>';
			}
			?>
			<li class="nav-divider"></li>
			<li class="nav"><a href="add_pool.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-cloud fa-fw"></i><?php echo _("Add pool");?></a></li>
			<li class="nav"><a href="list_credentials.php?credential_type=pool" data-toggle="modal" data-target="#modalWm"><i class="fa fa-recycle fa-fw"></i><?php echo _("Manage pools");?></a></li>
			<li class="nav"><a href="manage_vm_pool.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-desktop fa-fw"></i><?php echo _("Add VMs to pool");?></a></li>
			<li class="nav"><a href="manage_client_pool.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-male fa-fw"></i></i><?php echo _("Add clients to pool");?></a></li>
			<?php 
				if ($LDAP_backend=='activedir')
				    echo '<li class="nav"><a href="manage_client_pool.php?type=ad" data-toggle="modal" data-target="#modalWm"><i class="glyphicon glyphicon-user fa-fw"></i>' .  _("Add AD group to pool") . '</a></li>';
				else if ($LDAP_backend=='ldap')
				    echo '<li class="nav"><a href="manage_client_pool.php?type=ad" data-toggle="modal" data-target="#modalWm"><i class="glyphicon glyphicon-user fa-fw"></i>' .  _("Add LDAP attribute to pool") . '</a></li>';
			?>
		    </ul>
		</li>
    	        <li><a href="new_vm.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-laptop fa-fw"></i><?php echo _("Create Virtual Machines");?></a></li>
        <?php if  ($engine != 'OpenStack'){
        echo '
        <li>
                <a href="#" aria-expanded="false"><span class="fa arrow"></span><i class="fa fa-wrench fa-fw"></i>' . _("Tools") .'</a>
                <ul aria-expanded="false">
                    <li class="nav"><a href="dhcpconf_gen.php" data-toggle="modal" data-target="#modalWmLg"><i class="fa fa-cubes fa-fw"></i>' . _("Generate DHCP config") . '</a></li>
                </ul>
        </li>';}?>
		<li>
	    	    <a href="#" aria-expanded="false"><span class="fa arrow"></span><i class="fa fa-cogs fa-fw"></i><?php echo _("System");?></a>
            <ul aria-expanded="false">
                <?php if ($engine != 'OpenStack'){
                echo '
                <li class="nav"><a href="add_hypervisor.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-database fa-fw"></i>' . _("Add hypervisor") . '</a></li>
                <li class="nav"><a href="list_hypervisors.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-cloud fa-fw"></i>' . _("Modify hypervisors") . '</a></li>
                <li class="nav-divider"></li>';}?>
                <li class="nav"><a href="add_credential.php?credential_type=user" data-toggle="modal" data-target="#modalWm"><i class="fa fa-user-plus fa-fw"></i><?php echo _("Add administrator");?></a></li>
                <li class="nav"><a href="list_credentials.php?credential_type=user" data-toggle="modal" data-target="#modalWm"><i class="fa fa-recycle fa-fw"></i><?php echo _("Manage administrators");?></a></li>
                <li class="nav-divider"></li>
                <li class="nav"><a data-target="#modalWm" data-toggle="modal" href="about.php"><i class="fa fa-star-o fa-fw"></i><?php echo _("About")?></a></li>
		    </ul>
		</li>
		<li>
	    	    <a href="#" aria-expanded="false"><span class="fa arrow"></span><i class="fa fa-user fa-fw"></i><?php echo _("Profile");?></a>
		    <ul aria-expanded="false">
			<li class="nav"><a href="change_password.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-key fa-fw"></i><?php echo _("Change password");?></a></li>
	    		<li class="nav"><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i><?php echo _("Logout");?></a></li>
		    </ul>
		</li>
	    </ul>
	</div>
	<div class="col-md-offset-2 col-md-10">
	    <div class="main">
		<div class="alert alert-info" id="populatealert" style="display:none;">
		    <?php echo _("<strong>Please wait!</strong> Populating virtual machines.");?>
		</div>
		<div class="alert alert-info" id="copyalert" style="display:none;">
		    <?php echo _("<strong>Please wait!</strong> Copying image.");?>
		</div>
		<div id="main_table"><?php if ($engine=='OpenStack')
                                        draw_dashboard_table();
                                    ?>
                </div>
	    </div>
	</div>
    </div>
</div>

</body>
<script>
$("#left-menu").metisMenu({ toggle: false });
var engine=<?php echo "'" . $engine . "'";?>;
function refresh_screen(){
    if (engine == 'OpenStack')
        reloadOpenStackVmTable();
    else
        reloadKVMVmTable();
}
$(document).ready(function(){
    refresh_screen();
});
</script>

</html>
