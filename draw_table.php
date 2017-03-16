<?php 

/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2017-03-16
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
$userConfig=get_userconf();
draw_dashboard_table();

