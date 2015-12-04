<?php
include ("functions/config.php");
require_once("functions/functions.php");
close_session();
header("Location: $serviceurl");
?>