<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-12-08
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
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
    <form method="post" action="change_settings_do.php">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("KVM-VDI");?></h4>
        </div>
        <div class="modal-body">
     <div class="form-group">
	<div class="row">
	    <div class="col-md-2">
	    <img src="inc/img/logo.png" height="80px">
	    </div>
	    <div class="col-md-10">
	    <h5><a href="http://www.vu.lt" target="_new" class="text-warning"><?php echo _("Vilnius University.");?></a><br><a href="http://www.ittc.vu.lt" class="text-warning" target="_new"><?php echo _("Center of Information Technology Development.");?></a></h5><br>
	    <h6><div class="text-warning"><?php echo _("Project page");?>&#58;<a href="https://github.com/Seitanas/kvm-vdi" target="_new"> github</a></div></h6>
	    <h6><div class="text-right text-muted"><?php echo _("Vilnius, Litnuania");?> 2016</div></h6>
	    </div>
	</div>
    </div>

        </div>
        <div class="modal-footer">

        <div class="clearfix"></div>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
        </div>
    </div>
    </form>
</body>
</html>