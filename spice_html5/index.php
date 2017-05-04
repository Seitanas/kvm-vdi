<?php
include dirname(__FILE__) . '/../functions/config.php';
require_once(dirname(__FILE__) . '/../functions/functions.php');
if (!check_client_session() && !check_session()){
    header ("Location: $serviceurl/client_index.php");
    exit;
}
$vmname=$_GET['vmInfoToken'];
?>
<!DOCTYPE html>
    <head>
        <title>KVM-VDI - <?php echo "$vmname";?></title>
        <meta charset="utf-8">
        <!-- libs -->

 <script src="lib/modernizr.js"></script>
        <script src="lib/jquery-2.0.3.js"></script>
        <script src="lib/jquery-mousewheel.js"></script>
        <script src="lib/jgestures.min.js"></script>
        <script src="lib/pixastic.js"></script>
        <script src="lib/base64.js"></script>
        <script src="lib/biginteger.js"></script>
        <script src="lib/virtualjoystick.js"></script>
        <script src="lib/prettyprint.js"></script>
        <!-- ticketing -->
        <script src="lib/jsbn.js"></script>
        <script src="lib/jsbn2.js"></script>
        <script src="lib/prng4.js"></script>
        <script src="lib/rng.js"></script>
        <script src="lib/sha1.js"></script>
        <script src="lib/encrypt.js"></script>
        <!-- end libs -->
        <!-- core -->
        <script src="swcanvas/swcanvas.js"></script>
        <script src="lib/bowser.js"></script>
        <script src="lib/utils.js"></script>
        <script src="lib/flipper.js"></script>
        <script src="lib/CollisionDetector.js"></script>
        <script src="lib/GlobalPool.js"></script>
        <script src="lib/GenericObjectPool.js"></script>
        <script src="lib/AsyncConsumer.js"></script>
        <script src="lib/AsyncWorker.js"></script>
        <script src="lib/PacketWorkerIdentifier.js"></script>
        <script src="spiceobjects/spiceobjects.js"></script>
        <script src="spiceobjects/generated/protocol.js"></script>
        <script src="lib/graphicdebug.js"></script>
        <script src="lib/images/lz.js"></script>
        <script src="lib/images/bitmap.js"></script>
        <script src="lib/images/png.js"></script>
        <script src="lib/runqueue.js"></script>
        <script src="lib/graphic.js"></script>
        <script src="lib/queue.js"></script>
        <script src="lib/ImageUncompressor.js"></script>
        <script src="lib/SyncAsyncHandler.js"></script>
        <script src="lib/IntegrationBenchmark.js"></script>
        <script src="lib/stuckkeyshandler.js"></script>
        <script src="lib/timelapsedetector.js"></script>
        <script src="lib/displayRouter.js"></script>
        <script src="lib/rasterEngine.js"></script>
        <script src="lib/DataLogger.js"></script>
        <script src="network/socket.js"></script>
        <script src="network/clusternodechooser.js"></script>
        <script src="network/socketqueue.js"></script>
        <script src="network/packetcontroller.js"></script>
        <script src="network/packetextractor.js"></script>
        <script src="network/packetreassembler.js"></script>
        <script src="network/reassemblerfactory.js"></script>
        <script src="network/sizedefiner.js"></script>
        <script src="network/packetlinkfactory.js"></script>
        <script src="network/spicechannel.js"></script>
        <script src="network/busconnection.js"></script>
        <script src="network/websocketwrapper.js"></script>
        <script src="network/connectioncontrol.js"></script>
        <script src="application/agent.js"></script>
        <script src="application/spiceconnection.js"></script>
        <script src="application/clientgui.js"></script>
        <script src="application/packetprocess.js"></script>
        <script src="application/packetfilter.js"></script>
        <script src="application/packetfactory.js"></script>
        <script src="application/application.js"></script>
        <script src="application/virtualmouse.js"></script>
        <script src="application/imagecache.js"></script>
        <script src="application/rasteroperation.js"></script>
        <script src="application/stream.js"></script>
        <script src="application/inputmanager.js"></script>
        <script src="process/busprocess.js"></script>
        <script src="process/displayprocess.js"></script>
        <script src="process/displaypreprocess.js"></script>
        <script src="process/inputprocess.js"></script>
        <script src="process/cursorprocess.js"></script>
        <script src="process/playbackprocess.js"></script>
        <script src="process/mainprocess.js"></script>
        <script src="keymaps/keymapes.js"></script>
        <script src="keymaps/keymaplt.js"></script>
        <script src="keymaps/keymapit.js"></script>
        <script src="keymaps/keymapus.js"></script>
        <script src="keymaps/keymap.js"></script>
        <script src="application/WorkerProcess.js"></script>
        <script src="run.js"></script>
        <!-- end core -->
        <meta content="yes" name="apple-mobile-web-app-capable" />
	<link href="../inc/css/kvm-vdi.css" rel="stylesheet">
	<!-- Bootstrap core CSS -->
	<link href="../inc/css/bootstrap.min.css" rel="stylesheet">
	<!-- metisMenu CSS -->
	<link href="../inc/metisMenu/metisMenu.min.css" rel="stylesheet">
	<!-- Font Awesome-->
	<link href="../inc/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<link href="../inc/css/ie10-viewport-bug-workaround.css" rel="stylesheet">
	<link href="../inc/css/dashboard.css" rel="stylesheet">
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
        <script src="../https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="../https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<!-- Bootstrap core JavaScript ================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script src="../inc/js/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="inc/js/vendor/jquery.min.js"><\/script>')</script>
	<script src="../inc/js/bootstrap.min.js"></script>
	<script src="../inc/js/multiselect.min.js"></script>
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<script src="../inc/js/ie10-viewport-bug-workaround.js"></script>
	<script src="../inc/metisMenu/metisMenu.min.js"></script>
	<script src="../inc/js/kvm-vdi.js"></script>
    </head>
    <style>
	body {background-color: black;}
    </style>
    <body>
	    <div class="dropdown" style="position: absolute; top: 0px; left: 0px; margin-top: 0;line-height:0;">
		<button style="margin-top:0; top:0;" class="btn btn-default dropdown-toggle btn-xs fa fa-bars" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" id="ctrlMenu">
		</button>
		<ul class="dropdown-menu" aria-labelledby="ctrlMenu">
		    <li><a href="#" id="ctrlaltdel">Send Ctrl+Alt+Del</a></li>
	        </ul>
	    </div>
    	    <div id="KVM-VDI-screen" style="position: absolute; top: 20px; left: 0px;">
	    </div>
	</div>
    </body>
<script>
var vmname="<?php echo $vmname?>";
function vm_heartbeat(){
    $.ajax({
    	    type : 'POST',
    	    url : '../client_hb.php',
    	    data: {
    	    'vmname': vmname,
	    }
	})
}
$('#ctrlaltdel').click(function(e) {
    ctrl_alt_del();
    document.getElementById("inputmanager").focus();
});
$(document).ready(function(){
    var heartbeat_object = setInterval(function(){vm_heartbeat();},30000);

});
</script>
</html>
