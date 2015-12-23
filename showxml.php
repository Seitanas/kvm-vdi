<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2015-12-23
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
  <title>VM screen</title>

<style type="text/css" media="screen">
   #editor {
       height: 400px;
   }
</style>
</head>


<body>
<form method="POST" action="update_xml.php">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
		<div class="inner jumbotron" id="editor" name="editor"></div>
	    </div>
	    <input type="hidden" name="xml" id="xml">
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" type="submit"><?php echo _("Save changes");?></a>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
        </div>
    </div>

<script src="inc/js/ace.js" type="text/javascript" charset="utf-8"></script>
<script>
    var callback = function (data, status, xhr) {
        if (status == 'success') {
	    var textarea = $('#xml');
     	    var editor = ace.edit("editor");
	   editor.getSession().on('change', function () {
    		textarea.val(editor.getSession().getValue());
	    });    


            var editor = ace.edit("editor");
            editor.getSession().setMode("ace/mode/xml");
	    editor.setTheme("ace/theme/eclipse");
            editor.setValue(data);
	    editor.renderer.lineHeight=20;
        }
    };
    $.ajax(
        {
            url : 'functions/clients.xml',
            dataType : 'text', 
            success : callback
        }
    );
document.getElementById("editor").env.document.getValue();

</script>
</form>
</body>
</html>