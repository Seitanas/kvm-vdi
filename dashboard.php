<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2015-12-10
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
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

    <!-- Bootstrap core CSS -->
    <link href="inc/css/bootstrap.min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="inc/css/ie10-viewport-bug-workaround.css" rel="stylesheet">
    <link href="inc/css/dashboard.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="inc/js/vendor/jquery.min.js"><\/script>')</script>
    <script src="inc/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="inc/js/ie10-viewport-bug-workaround.js"></script>
    <!--clear remote modal forms -->
    <script type="text/javascript">
	$(document).on("hidden.bs.modal", function (e) {
	    $(e.target).removeData("bs.modal").find(".modal-content").empty();
	});
    </script>
    <script>
	function readposition(filepath) {
	    var allText=-1;
	    var rawFile = new XMLHttpRequest();
	    rawFile.open("GET", filepath, false);
	    rawFile.onreadystatechange = function ()
	        {
	        if(rawFile.readyState === 4)
    		    {
	            if(rawFile.status === 200 || rawFile.status == 0)
    		        {
            		allText = rawFile.responseText;
        		}
    		    }
		}	
    	    rawFile.send(null);
	    return allText;
    	}
	function countdown(filepath,container) {
	    var $container = $(container);
	    (function step() {
		count=readposition(filepath);
		$container.css('width', count+'%').attr('aria-valuenow', count);  
		if (count < 100) {                    
		    $container.parent('div').show();
    		    setTimeout(step, 3000);              
		}
		if (count == 100 || count == -1) {
		    $container.parent('div').hide();
		}
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
	    if (confirm("All virtual machines will be powered off and their initial snapshots recreated.\nProceed?")) {
    	    // your deletion code
		$('#populatealert').show();
	     }
        return false;
	}

	function confirmation1() {
	    if (confirm("All virtual machines will be powered off and their initial snapshots recreated.\nProceed?")) {
    	    // your deletion code
		$('#copyalert').show();
	     }
        return false;
	}
    </script>
    <script>
</script>
  </head>
<?php 
    require_once('functions/functions.php');
    $sql_reply=get_SQL_array("SELECT * FROM hypervisors");
?>
  <body>

<!-- Modal vm info-->
<div class="modal fade" id="vmInfo" tabindex="-1" role="dialog" aria-labelledby="vmInfo" aria-hidden="true">
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
            <li><a href="reload_vm_info.php" data-toggle="hover"><button type="button" class="btn btn-info" aria-label="Refresh" title="Refresh VM info">
  <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
</button></a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
          <ul class="nav nav-sidebar">
            <li class="active"><a href="#">Overview <span class="sr-only">(current)</span></a></li>
            <li><a href="showxml.php" data-toggle="modal" data-target="#vmInfo">Edit clients.xml</a></li>
            <li><a href="change_password.php" data-toggle="modal" data-target="#vmInfo">Change password</a></li>
            <li><a href="logout.php">Logout</a></li>
	    <li></li>
          </ul>
        </div>
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
<div class="alert alert-info" id="populatealert" style="display:none;">
  <strong>Please wait!</strong> Populating virtual machines.
</div>
<div class="alert alert-info" id="copyalert" style="display:none;">
  <strong>Please wait!</strong> Copying image.
</div>
	<?php
	    $x=0;
	    while ($sql_reply[$x]['id']){
//		$vms_query=get_SQL_array("SELECT * FROM vms WHERE hypervisor='{$sql_reply[$x][id]}' ORDER BY name");
		$vms_query=get_SQL_array("SELECT vms.id,vms.name,vms.hypervisor,vms.machine_type,vms.source_volume,vms.snapshot,vms.maintenance,vms.filecopy,vms.state,vms_tmp.name AS sourcename  FROM vms LEFT JOIN vms AS vms_tmp ON vms.source_volume=vms_tmp.id WHERE vms.hypervisor='{$sql_reply[$x][id]}' ORDER BY vms.name");
	?>
          <h1 class="sub-header"><?php echo "Hypervisor: " . $sql_reply[$x]['ip']; ?></h1>
      <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Machine name</th>
		  <th>Machine type</th>
		  <th>Source image</th>
		  <th>Virt-snapshot</th>
		  <th>Maintenance</th>
                  <th>Operations</th>
                </tr>
              </thead>
              <tbody>
		<?php 
		    $y=0;
		    $machine_type['simplemachine']="Simple machine";
                    $machine_type['initialmachine']="Initial machine";
                    $machine_type['sourcemachine']="Source machine";
                    $machine_type['vdimachine']="VDI machine";
		    while ($vms_query[$y]['id']){
			$pwr_status="off";
			if ($vms_query[$y]['state']=="shut")
				$pwr_button="btn-default";
			else{
			    $pwr_button="btn-success";
			    $pwr_status="on";
			}
			$vms_query[$y]['snapshot']=str_replace("true","checked",$vms_query[$y]['snapshot']);
			$vms_query[$y]['snapshot']=str_replace("false","",$vms_query[$y]['snapshot']);
			$vms_query[$y]['maintenance']=str_replace("true","checked",$vms_query[$y]['maintenance']);
			$vms_query[$y]['maintenance']=str_replace("false","",$vms_query[$y]['maintenance']);
			echo '<tr> 
	                      <td class="col-md-1">' . ($y+1) . '</td> 
                	      <td class="col-md-2"><a data-toggle="modal" href="vm_info.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id']  . '" data-target="#vmInfo">' . $vms_query[$y]['name'] . '</a> </td> 
			      <td class="col-md-2">' . $machine_type[$vms_query[$y]['machine_type']] . '</td>
			      <td class="col-md-2">' . $vms_query[$y]['sourcename'] . '</td>
			      <td class="col-md-1"><input type="checkbox" '. $vms_query[$y]['snapshot'] . " onclick='handleSnapshot(this);' " . 'id="' . $vms_query[$y]['id'] .  '"></td>
			      <td class="col-md-1"><input type="checkbox" '. $vms_query[$y]['maintenance']. " onclick='handleMaintenance(this);' " . 'id="' . $vms_query[$y]['id'] .  '">';
			if (!empty($vms_query[$y]['filecopy'])){
				echo '<div class="progress">
					 <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="' . $vms_query[$y]['filecopy'] . '">
				         </div>
				     </div>
				     <script>
					countdown("' . $serviceurl . '/tmp/' . $vms_query[$y]['filecopy'] . '.txt","#' . $vms_query[$y]['filecopy'] . '");
				    </script>';
			}
			echo  '</td>
	                      <td class="col-md-3">';
			if ($vms_query[$y]['machine_type']=="initialmachine"){
				echo '<!-- Single button -->
				    <div class="btn-group">
					<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					    VDI <span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
					    <li><a href="copy_disk.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] . '" onclick=' . "'confirmation1();'"  .  '>Copy disk from source</a></li>
					    <li role="separator" class="divider"></li>
					    <li><a href="maintenance.php?action=mass_on&source=' . $vms_query[$y]['id'] .  '">Turn maintenance on</a></li>
					    <li><a href="maintenance.php?action=mass_off&source=' . $vms_query[$y]['id'] .  '">Turn maintenance off</a></li>
					    <li role="separator" class="divider"></li>
					    <li><a href="populate.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '" onclick=' . "'confirmation();'"  .  '>Populate machines</a></li>
					    <li role="separator" class="divider"></li>
					    <li><a href="power.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">Mass power on</a></li>
					    <li><a href="power.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">Mass shut down (soft)</a></li>
					    <li><a href="power.php?action=mass_destroy&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">Mass shut down (forced)</a></li>
					    <li role="separator" class="divider"></li>
					    <li><a href="snapshot.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">Turn on snapshots</a></li>
					    <li><a href="snapshot.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">Turn off snapshots</a></li>
					</ul>
				    </div>';

			}
			echo  '<a href="power.php?action=single&state=up&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="btn ' . $pwr_button . '" aria-label="Power up" title="Power up">
			      <span class="glyphicon glyphicon-play" aria-hidden="true"></span></a>
			      <a href="power.php?action=single&state=down&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="btn btn-default" aria-label="Shut down" title="Shut down (soft)">
			      <span class="glyphicon glyphicon-off" aria-hidden="true"></span></a>
			      <a href="power.php?action=single&state=destroy&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-danger" aria-label="Power down" title="Shut down (forced)">
			      <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span></a>';
			      if ($pwr_status=="on"){
				    echo' <a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-info" aria-label="Open console" title="Open console">
			    		<span class="glyphicon glyphicon-modal-window" aria-hidden="true"></span></a>';
			      }
			echo  '</td> 
        		      </tr>'; 
			++$y;
		    }
		?>
              </tbody>
            </table>
	    <?php
		++$x;
	     }
	    ?>
          </div>
        </div>
      </div>
    </div>


  </body>
</html>
