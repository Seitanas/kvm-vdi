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
include ('functions/config.php');
require_once('functions/functions.php');
if (check_db()<1){
    header("Location:  $serviceurl/install/");
    exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Tadas Ustinavičius">
    <title>Login</title>
    <link href="inc/css/bootstrap.min.css" rel="stylesheet">
    <link href="inc/css/bootstrap-theme.min.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
<form method="post" action="login.php">
<div style="Width:300px;margin:300px auto;" align="center">
    <h2>Login</h2>
    <input type="text" name="username" class="form-control" required autofocus placeholder="Username">
    <input type="password" name="password" class="form-control" required placeholder="Password">
    <input type="submit" value="Login" class="btn btn-lg btn-default">
    <?php if ($_GET['error']==1){?>   
	<div class="alert alert-danger" role="alert">Wrong username/password
	     <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	</div>
	<?php }?>
</div>
</form>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="inc/js/bootstrap.min.js"></script>
  </body>
</html>