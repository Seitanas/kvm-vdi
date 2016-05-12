<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-04-25
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
set_lang();
reload_vm_info();
$sql_reply=get_SQL_array("SELECT * FROM hypervisors");
$x=0;
while ($sql_reply[$x]['id']){
    $table_status="";
    $vms_query=get_SQL_array("SELECT vms.id,vms.name,vms.hypervisor,vms.machine_type,vms.source_volume,vms.snapshot,vms.maintenance,vms.filecopy,vms.state,vms_tmp.name AS sourcename  FROM vms LEFT JOIN vms AS vms_tmp ON vms.source_volume=vms_tmp.id WHERE vms.hypervisor='{$sql_reply[$x]['id']}' ORDER BY vms.name");
?>
    <h1 class="sub-header"><?php echo _("Hypervisor: ") . $sql_reply[$x]['ip']; ?> 
    <?php
    if (!$sql_reply[$x]['maintenance']) echo '<a href="hypervisor.php?maintenance=1&id=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ok-circle btn-success"> ' . _("Enabled") . '</a>';
    else {
	echo '<a href="hypervisor.php?maintenance=0&id=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ban-circle btn-default"> ' . _("Disabled") . '</a>';
        $table_status="disabled";
    }?>
    </h1>
    <div class="table-responsive <?php echo $table_status;?>">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th><?php echo _("Machine name");?></th>
              <th><?php echo _("Machine type");?></th>
              <th><?php echo _("Source image");?></th>
              <th><?php echo _("Virt-snapshot");?></th>
              <th><?php echo _("Maintenance");?></th>
              <th><?php echo _("Operations");?></th>
            </tr>
          </thead>
          <tbody>
	    <?php
            $y=0;
            $machine_type['simplemachine']=_("Simple machine");
            $machine_type['initialmachine']=_("Initial machine");
            $machine_type['sourcemachine']=_("Source machine");
            $machine_type['vdimachine']=_("VDI machine");
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
                                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="' . $vms_query[$y]['filecopy'] . '">
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
                                            <li><a href="copy_disk.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] . '" onclick="return confirmation1();">' . _("Copy disk from source") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="maintenance.php?action=mass_on&source=' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance on") . '</a></li>
                                            <li><a href="maintenance.php?action=mass_off&source=' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance off") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="populate.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '" onclick="return confirmation();" >' . _("Populate machines") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="power.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass power on") . '</a></li>
                                            <li><a href="power.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (soft)") . '</a></li>
                                            <li><a href="power.php?action=mass_destroy&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (forced)") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="snapshot.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Turn on snapshots") . '</a></li>
                                            <li><a href="snapshot.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Turn off snapshots") . '</a></li>
                                        </ul>
                                    </div>';

                        }
                        echo  '<a href="power.php?action=single&state=up&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="btn ' . $pwr_button . '" aria-label="' . _("Power up") . '" title="' . _("Power up") . '">
                              <span class="glyphicon glyphicon-play" aria-hidden="true"></span></a>';
                        if ($pwr_status=="on"){
                            echo' <a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-info" aria-label="' . _("Open console") . '" title="' . _("Open console") . '">
                                <span class="glyphicon glyphicon-modal-window" aria-hidden="true"></span></a>';
                        }
                              echo '<a href="power.php?action=single&state=down&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" class="btn btn-default" aria-label="' . _("Shut down") . '" title="' . _("Shut down (soft)") . '"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                              <span class="glyphicon glyphicon-off" aria-hidden="true"></span></a>
                              <a href="power.php?action=single&state=destroy&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-danger" aria-label="' . _("Power down") . '" title="Shut down (forced)"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                              <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span></a>';
                        if ($vms_query[$y]['machine_type']=='vdimachine'){
                            echo' <a href="delete_vm.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn btn-danger" aria-label="' . _("Delete VM") . '" title="' . _("Delete VM") .  '"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                              <span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>';
                        }
                        echo    '</td> 
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
