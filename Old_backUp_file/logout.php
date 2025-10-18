<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php"); 
http_response_code(200);
exit();
?>
