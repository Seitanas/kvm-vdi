<?php 

/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-09-05
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
set_lang();
while ($upgradedfrom=check_upgrade()){
    echo '<div class="row">
	    <div class="col-md-6 col-md-offset-2 text-center">
		<div class="alert alert-info" role="alert">' . _("Database upgraded from: $upgradedfrom") . '
    		    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		</div>
	    </div>
	</div>';
    }
reload_vm_info();
$sql_reply=get_SQL_array("SELECT * FROM hypervisors ORDER BY name,ip ASC");
$x=0;
if (sizeof($sql_reply)<1){
    ?>
    <div class="row">
	<div class="col-md-6 col-md-offset-2" style="margin-top:200px;text-align:center;">
	    <div class="alert alert-success" role="alert"><?php echo _("Congratulations! Installation was successfull. Now you should add hypervisors to your dashboard.");?></div>
	    <i class="fa fa-hand-o-up fa-5x text-info"></i>
	</div>
    </div>
<?php
}
while ($x<sizeof($sql_reply)){
    $table_status="";
    $vms_query=get_SQL_array("SELECT vms.id,vms.name,vms.hypervisor,vms.machine_type,vms.source_volume,vms.snapshot,vms.maintenance,vms.filecopy,vms.state,vms.os_type, vms.locked, vms_tmp.name AS sourcename  FROM vms LEFT JOIN vms AS vms_tmp ON vms.source_volume=vms_tmp.id WHERE vms.hypervisor='{$sql_reply[$x]['id']}' AND vms.machine_type <> 'vdimachine' ORDER BY vms.name");
    if (!empty($sql_reply[$x]['name']))
	$hypervisor_name=$sql_reply[$x]['name'];
    else
	$hypervisor_name=$sql_reply[$x]['ip'];
?>
    <h1 class="sub-header"><?php echo _("Hypervisor: ") . $hypervisor_name; ?> 
    <?php
    if (!$sql_reply[$x]['maintenance']) echo '<a href="hypervisor.php?maintenance=1&id=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ok-circle btn-success"> ' . _("Enabled") . '</a>';
    else {
	echo '<a href="hypervisor.php?maintenance=0&id=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ban-circle btn-default"> ' . _("Disabled") . '</a>';
        $table_status="hypervisor-screen-disabled";
    }?>
    </h1>
    <div class="table-responsive"  style="overflow: inherit;">
        <table class="table table-striped table-hover <?php echo $table_status;?>">
          <thead>
            <tr>
              <th>#</th>
              <th></th>
              <th><?php echo _("Machine name");?></th>
              <th><?php echo _("Machine type");?></th>
              <th><?php echo _("Source image");?></th>
              <th><?php echo _("Virt-snapshot");?></th>
              <th><?php echo _("Maintenance");?></th>
              <th><?php echo _("Operations");?></th>
              <th><?php echo _("OS type");?></th>
            </tr>
          </thead>
          <tbody>
	    <?php
            $y=0;
            $machine_type['simplemachine']=_("Simple machine");
            $machine_type['initialmachine']=_("Initial machine");
            $machine_type['sourcemachine']=_("Source machine");
            $machine_type['vdimachine']=_("VDI machine");
            while ($y<sizeof($vms_query)){
		$pwr_button="btn-success";
                $pwr_status="on";
		$lockstatus='';
		switch ($vms_query[$y]['state']) {
		    case "shut":
			$vms_status_display='<i class="text-danger">' . _("Shutoff") . '</i>';
                	$pwr_button="btn-default";
			$pwr_status="off";
			break;
		    case "running":
			$vms_status_display='<i class="text-success">' . _("Running") . '</i>';
			break;
		    case "paused":
		    	$vms_status_display='<i class="text-warning">' . _("Paused") . '</i>';
			break;
		    case "pmsuspended":
		    	$vms_status_display='<i class="text-warning">' . _("Suspended") . '</i>';
			break;
		    default:
			$vms_status_display='<i class="text-muted">' . _("Unknown") . '</i>';
		}
                $vms_query[$y]['snapshot']=str_replace("true","checked",$vms_query[$y]['snapshot']);
                $vms_query[$y]['snapshot']=str_replace("false","",$vms_query[$y]['snapshot']);
                $vms_query[$y]['maintenance']=str_replace("true","checked",$vms_query[$y]['maintenance']);
                $vms_query[$y]['maintenance']=str_replace("false","",$vms_query[$y]['maintenance']);
		$VDI_query=array();
		$vdi_table_section_collapse='in';
		$vdi_collapse_button='fa-minus';
		if ($vms_query[$y]['machine_type']=='initialmachine'){
		    $VDI_query=get_SQL_array("SELECT vms.id,vms.name,vms.hypervisor,vms.machine_type,vms.source_volume,vms.snapshot,vms.maintenance,vms.filecopy,vms.state,vms.os_type,vms_tmp.name AS sourcename  FROM vms LEFT JOIN vms AS vms_tmp ON vms.source_volume=vms_tmp.id WHERE vms.source_volume='{$vms_query[$y]['id']}' AND vms.machine_type = 'vdimachine' ORDER BY vms.name");
		    if ($_SESSION['table_section-'.$vms_query[$y]['id']] == 'hide'){
			$vdi_table_section_collapse='';
			$vdi_collapse_button='fa-plus';
			}
		}
            	echo '<tr class="table-stripe-bottom-line">
                	  <td colspan="2" class="col-md-1 clickable parent" id="' . $vms_query[$y]['id'] . '" data-toggle="collapse" data-target=".child-' . $vms_query[$y]['id'] . '" >' . ($y+1);
		if (!empty($VDI_query))
		    echo '<i class="fa ' . $vdi_collapse_button . ' fa-fw" id="childof-' . $vms_query[$y]['id'] . '"></i>';
		echo '</td> 
                    <td class="col-md-2"><a data-toggle="modal" href="vm_info.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id']  . '" data-target="#modalWm">' . $vms_query[$y]['name'] . '</a> </td> 
                    <td class="col-md-1">', (!empty($vms_query[$y]['machine_type'])) ? $machine_type[$vms_query[$y]['machine_type']]  : "", '</td>
                    <td class="col-md-1">' . $vms_query[$y]['sourcename'] . '</td>
                    <td class="col-md-1"><input type="checkbox" '. $vms_query[$y]['snapshot'] . " onclick='handleSnapshot(this);' " . 'id="' . $vms_query[$y]['id'] .  '"></td>
                    <td class="col-md-1"><input type="checkbox" '. $vms_query[$y]['maintenance']. " onclick='handleMaintenance(this);' " . 'id="' . $vms_query[$y]['id'] .  '">';
                if (is_numeric($vms_query[$y]['filecopy'])){
            	    echo '<div class="progress">
                            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progress-' . $vms_query[$y]['id'] . '" style="width:100%">
                            </div>
                    	  </div>
                          <script>
                            countdown("' . $serviceurl . '/progress.php?vm=' . $vms_query[$y]['id']  . '","#progress-' . $vms_query[$y]['id'] . '");
                          </script>';
                        }
                        echo  '</td>
                              <td class="col-md-3">';
                        if ($vms_query[$y]['machine_type']=="initialmachine"){
			    if ($vms_query[$y]['locked']=='true')
				$lockstatus='disabled';
                	    echo '<div class="btn-group">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                VDI <span class="caret"></span>
                                </button>
                                        <ul class="dropdown-menu">
                                            <li class="' . $lockstatus . '" id="copy-disk-from-source-button-' . $vms_query[$y]['id'] . '"><a href="copy_disk.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] . '" onclick="return confirmation1();">' . _("Copy disk from source") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="maintenance.php?action=mass_on&source=' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance on") . '</a></li>
                                            <li><a href="maintenance.php?action=mass_off&source=' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance off") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li class="' . $lockstatus . '" id="populate-machines-button-' . $vms_query[$y]['id'] . '"><a href="populate.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '" onclick="return confirmation();" >' . _("Populate machines") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="power.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass power on") . '</a></li>
                                            <li><a href="power.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (soft)") . '</a></li>
                                            <li><a href="power.php?action=mass_destroy&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (forced)") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="snapshot.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Turn on snapshots") . '</a></li>
                                            <li><a href="snapshot.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Turn off snapshots") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="delete_vm.php?action=mass_delete&hypervisor=' . $sql_reply[$x]['id'] .  '&parent=' . $vms_query[$y]['id'] .  '" onclick="return confirmation2();">' . _("Delete all child VMs") . '</a></li>
			                    <li role="separator" class="divider"></li>';
					    if ($vms_query[$y]['locked']=='false')
						echo '<li><a href="#" id="lock-vm-button-' . $vms_query[$y]['id'] . '" class="lock-vm-button-click" data-id=' . $vms_query[$y]['id'] . '>' . _("VM locked:") . '<i class="fa fa-fw fa-square-o" aria-hidden="true"></i></a></li>';
					    else
						echo '<li><a href="#" id="lock-vm-button-' . $vms_query[$y]['id'] . '" class="lock-vm-button-click" data-id=' . $vms_query[$y]['id'] . '>' . _("VM locked:") . '<i class="fa fa-fw fa-check-square-o" aria-hidden="true"></i></a></li>';

                                echo '</ul>
                                    </div>';

                        }
                        echo  '<a href="power.php?action=single&state=up&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="lockable-vm-buttons-' . $vms_query[$y]['id'] . ' ' . $lockstatus . ' btn ' . $pwr_button . '" aria-label="' . _("Power up") . '" title="' . _("Power up") . '">
                              <span class="glyphicon glyphicon-play" aria-hidden="true"></span></a>';
                        if ($pwr_status=="on"){
                            echo' <a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-info" aria-label="' . _("Open console") . '" title="' . _("Open console") . '">
                                <span class="glyphicon glyphicon-modal-window" aria-hidden="true"></span></a>';
                            echo '<a href="power.php?action=single&state=down&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="btn btn-default" aria-label="' . _("Shut down") . '" title="' . _("Shut down (soft)") . '"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                              <span class="glyphicon glyphicon-off" aria-hidden="true"></span></a>
                              <a href="power.php?action=single&state=destroy&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-danger" aria-label="' . _("Power down") . '" title="Shut down (forced)"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                              <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span></a>';
			    }
                              if ($vms_query[$y]['machine_type']!='simplemachine')
				    echo' <a href="delete_vm.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="lockable-vm-buttons-' . $vms_query[$y]['id'] . ' ' .  $lockstatus . ' btn btn-danger" aria-label="' . _("Delete VM") . '" title="' . _("Delete VM") .  '"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                            		<span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>';

                        echo    '</td>';
			    if (empty($vms_query[$y]['os_type']))
				echo '<td class="col-md-1 text-danger">' . _("Unknown") . ' &#47; ' . $vms_status_display . '</td>';
			    else
				echo '<td class="col-md-2">' . ucfirst($vms_query[$y]['os_type']) . ' &#47; ' . $vms_status_display . '</td>';
                              echo '</tr>'; 
			if ($vms_query[$y]['machine_type']=='initialmachine'){
			    $q=0;
			    if (!empty($VDI_query))
				echo '<thead class="vdi-font">
    				<tr class="table-stripe-static child-' . $vms_query[$y]['id'] . ' collapse ' . $vdi_table_section_collapse . '">
				    <th class="table-stripe-clear"></th>
            			    <th>#</th>
            			    <th>' . _("VDI name") . '</th>
            			    <th>' . _("Machine type") . '</th>
            			    <th>' . _("Source image") . '</th>
            			    <th>' . _("Virt-snapshot") . '</th>
            			    <th>' . _("Maintenance") . '</th>
            			    <th>' . _("Operations") . '</th>
            			    <th>' . _("OS type") . '</th>
        			</tr>
        			</thead>';
			    while ($q<sizeof($VDI_query)){
            			$VDI_query[$q]['snapshot']=str_replace("true","checked",$VDI_query[$q]['snapshot']);
            			$VDI_query[$q]['snapshot']=str_replace("false","",$VDI_query[$q]['snapshot']);
            			$VDI_query[$q]['maintenance']=str_replace("true","checked",$VDI_query[$q]['maintenance']);
            			$VDI_query[$q]['maintenance']=str_replace("false","",$VDI_query[$q]['maintenance']);
                		$pwr_button="btn-success";
		                $pwr_status="on";
				switch ($VDI_query[$q]['state']) {
				    case "shut":
					$vdi_status_display='<i class="text-danger">' . _("Shutoff") . '</i>';
					$pwr_button="btn-default";
		    			$pwr_status="off";
					break;
				    case "running":
					$vdi_status_display='<i class="text-success">' . _("Running") . '</i>';
					break;
				    case "paused":
				    	$vdi_status_display='<i class="text-warning">' . _("Paused") . '</i>';
					break;
				    case "pmsuspended":
				    	$vdi_status_display='<i class="text-warning">' . _("Suspended") . '</i>';
					break;
				    default:
					$vdi_status_display='<i class="text-muted">' . _("Unknown") . '</i>';;
				}
				echo '<tr class="table-stripe-ani vdi-font child-' . $vms_query[$y]['id'] . ' collapse ' . $vdi_table_section_collapse . '"> 
                    		<td class="col-md-1 table-stripe-clear"></td> 
                    		<td class="col-md-1">' . ($y+1) . "-" . ($q+1) . '</td> 
                    		<td class="col-md-2"><a data-toggle="modal" href="vm_info.php?vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id']  . '" data-target="#modalWm">' . $VDI_query[$q]['name'] . '</a> </td> 
                    		<td class="col-md-1">' . $machine_type[$VDI_query[$q]['machine_type']] . '</td>
                    		<td class="col-md-1">' . $VDI_query[$q]['sourcename'] . '</td>
                    		<td class="col-md-1"><input type="checkbox" '. $VDI_query[$q]['snapshot'] . " onclick='handleSnapshot(this);' " . 'id="' . $VDI_query[$q]['id'] .  '"></td>
                    		<td class="col-md-1"><input type="checkbox" '. $VDI_query[$q]['maintenance']. " onclick='handleMaintenance(this);' " . 'id="' . $VDI_query[$q]['id'] .  '"></td>
				<td class="col-md-3">';
		                echo  '<a href="power.php?action=single&state=up&vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="btn ' . $pwr_button . '" aria-label="' . _("Power up") . '" title="' . _("Power up") . '">
                            	    <span class="glyphicon glyphicon-play" aria-hidden="true"></span></a>';
                    		if ($pwr_status=="on"){
                        	    echo' <a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-info" aria-label="' . _("Open console") . '" title="' . _("Open console") . '">
                            		<span class="glyphicon glyphicon-modal-window" aria-hidden="true"></span></a>';
                            	    echo '<a href="power.php?action=single&state=down&vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="btn btn-default" aria-label="' . _("Shut down") . '" title="' . _("Shut down (soft)") . '"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                        	        <span class="glyphicon glyphicon-off" aria-hidden="true"></span></a>
                            	        <a href="power.php?action=single&state=destroy&vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-danger" aria-label="' . _("Power down") . '" title="Shut down (forced)"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                            		<span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span></a>';
				}
                        	echo' <a href="delete_vm.php?vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-danger" aria-label="' . _("Delete VM") . '" title="' . _("Delete VM") .  '"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                            		<span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>';
				echo '</td>';
				if (empty($VDI_query[$q]['os_type']))
				    echo '<td class="col-md-1 text-danger">' . _("Unknown") . ' &#47; ' . $vdi_status_display . '</td>';
				else
    				    echo '<td class="col-md-2">' . ucfirst($VDI_query[$q]['os_type']) . ' &#47; ' . $vdi_status_display . '</td>';
				echo '</tr>';
				++$q;
			    }
			}
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
<script>
$(".parent").click(function() {
if ($('#childof-'+this.id).hasClass('fa-minus')){
    $('#childof-'+this.id).removeClass('fa-minus');
    $('#childof-'+this.id).addClass('fa-plus');
    show_hide_table_section(this.id,'hide');
}
else {
    $('#childof-'+this.id).removeClass('fa-plus');
    $('#childof-'+this.id).addClass('fa-minus');
    show_hide_table_section(this.id,'show');
}
});
$('a.lock-vm-button-click').click(function() {
    lock_VM($(this).data('id'));
});
</script>