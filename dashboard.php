<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-06-28
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
    <link href="inc/css/custom.css" rel="stylesheet">
    <!-- Bootstrap core CSS -->
    <link href="inc/css/bootstrap.min.css" rel="stylesheet">
    <!-- metisMenu CSS -->
    <link href="inc/metisMenu/metisMenu.min.css" rel="stylesheet">
    <!-- Font Awesome-->
    <link href="inc/font-awesome/css/font-awesome.min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="inc/css/ie10-viewport-bug-workaround.css" rel="stylesheet">
    <link href="inc/css/dashboard.css" rel="stylesheet">

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
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="inc/js/ie10-viewport-bug-workaround.js"></script>
    <script src="inc/metisMenu/metisMenu.min.js"></script>
    <!--clear remote modal forms -->
    <script type="text/javascript">
	$(document).on("hidden.bs.modal", function (e) {
	    $(e.target).removeData("bs.modal").find(".modal-content").empty();
	});
    </script>
    <script>
	function countdown(filepath,container) {
	    var $container = $(container);
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
    </script>
    <script>
	function handleSnapshot(checkbox) {
	    window.location = "snapshot.php?action=single&vm="+checkbox.id;
	}
	function handleMaintenance(checkbox) {
	    window.location = "maintenance.php?action=single&source="+checkbox.id;
	}
    </script>

    <script>
	function confirmation() {
	    if (confirm("<?php echo _("All virtual machines will be powered off and their initial snapshots recreated.\\nProceed?");?>")) {
		$('#populatealert').show();
		return true;
	     }
        return false;
	}
	function confirmation1() {
	    if (confirm("<?php echo _("All virtual machines will be powered off and their initial snapshots recreated.\\nProceed?");?>")) {
		$('#copyalert').show();
		return true;
	     }
        return false;
	}
	function confirmBox(text) {
    	    return confirm(text);
	}
    </script>
  </head>
<?php 
    require_once('functions/functions.php');
    $sql_reply=get_SQL_array("SELECT * FROM hypervisors");
?>
<body>
<!-- Modal vm info-->
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

    <nav class="navbar navbar-inverse navbar-fixed-top">
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
            <li><a href="#" onclick="draw_table();"  data-toggle="hover"><button type="button" class="btn btn-info" aria-label="Refresh" title="<?php echo _("Refresh VM info");?>">
		<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button></a>
	    </li>
          </ul>
        </div>
      </div>
    </nav>

<div class="container-fluid">
    <div class="row">
	<div class="col-md-2 sidebar nopadding">
    	    <ul class="metismenu nav" id="left-menu">
    	        <li><a href="showxml.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-sitemap fa-fw"></i><?php echo _("Edit clients.xml");?></a></li>
    	        <li><a href="new_vm.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-laptop fa-fw"></i><?php echo _("Create Virtual Machines");?></a></li>
		<li>
	    	    <a href="#" aria-expanded="false"><span class="fa arrow"></span><i class="fa fa-cogs fa-fw"></i><?php echo _("Configuration");?></a>
		    <ul aria-expanded="false">
			<li class="nav"><a href="add_hypervisor.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-database fa-fw"></i><?php echo _("Add hypervisor");?></a></li>
	    		<li class="nav"><a href="list_hypervisors.php" data-toggle="modal" data-target="#modalWm"><i class="fa fa-cloud fa-fw"></i><?php echo _("Modify hypervisors");?></a></li>
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
		<div id="main_table"></div>
	    </div>
	</div>
    </div>
</div>
</body>
<script>
function draw_table(){
    $( "#main_table" ).load( "draw_table.php" );
}
draw_table();
</script>
<script>
$("#left-menu").metisMenu();
</script>

</html>
