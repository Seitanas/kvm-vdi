<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius,Lithuania.
2018-03-26
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (check_db()<1){
    header("Location:  $serviceurl/install/");
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
    <meta name="author" content="Tadas Ustinavičius">
    <title>KVM-VDI</title>
    <link href="inc/css/bootstrap.min.css" rel="stylesheet">
    <link href="inc/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="inc/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="inc/css/sb-admin-2.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo _("KVM-VDI administrator portal");?>
			    <span class="pull-right">
                        	<a href="https://github.com/Seitanas/kvm-vdi" target="_new">
                            	    <span class="fa fa-info-circle glyphicon glyphicon-collapse-up"></span>
                                </a>
                    	    </span>
			</h3>
	    <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <form role="form" method="post" action="login.php">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Username" name="username" type="username" autofocus required>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="password" type="password" value="" required>
                                </div>
                                <input type="submit" value="<?php echo _("Sign In");?>" class="btn btn-lg btn-success btn-block">
                                <a class="btn btn-sm btn-info btn-block" href="client_index.php"><?php echo _("Go to client area");?></a>
				<?php if (isset($_GET['error'])){?>
				    <div class="alert alert-danger" role="alert">Wrong username/password
	    				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				    </div>
				<?php }?>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="inc/js/jquery.min.js"></script>
    <script src="inc/js/bootstrap.min.js"></script>
  </body>
</html>