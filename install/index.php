<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-05-10
*/
include dirname(__FILE__) . '/../functions/config.php';
require_once(dirname(__FILE__) . '/../functions/functions.php');
$abort=0;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Tadas Ustinavičius">
    <title>Installation</title>
    <link href="../inc/css/bootstrap.min.css" rel="stylesheet">
    <link href="../inc/css/bootstrap-theme.min.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script>
	function redirect_login(){
	    window.setTimeout(function(){
    		window.location.href = "<?php echo $serviceurl;?>";
	    }, 3000);
	}
    </script>
  </head>
  <body>
<div style="Width:300px;margin:300px auto;" align="center">
    <h2>KVM-VDI Installation</h2>
    <?php if (check_db()==1){
	$abort=1;
	echo '<div class="alert alert-danger" role="alert">
	    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
	    <span class="sr-only">Error:</span>
	    It seems that KVM-VDI is already installed.
	    </div>';
	}
	else {
	echo '<div class="alert alert-info" role="alert">
	    <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
	    <span class="sr-only">Info:</span>
	    Populating database.
	    </div>';
	}
    if (!$abort){
        $result=populate_db();
	if ($result)
    	    echo '<div class="alert alert-danger" role="alert">
		    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
		    <span class="sr-only">Error:</span>
		    Whoops, something happened while populating database. Check PHP logs.
		</div>';
	else{
	    $password=crypt('password',$salt);
	    add_SQL_line("INSERT INTO users (username, password) VALUES ('admin','$password')");
	    echo '<div class="alert alert-success" role="alert">
		    <span class="glyphicon glyphicon glyphicon-ok-circle" aria-hidden="true"></span>
		    <span class="sr-only">Success:</span>
		    Installation is successful. Redirecting to <a href="' . $serviceurl . '">login page</a> in 3 seconds.
		</div>
		<script type="text/javascript">
		    redirect_login();
		</script>';
	    }
    }
    ?>
</div>
    <script src="../inc/js/jquery.min.js"></script>
    <script src="../inc/js/bootstrap.min.js"></script>
  </body>
</html>
