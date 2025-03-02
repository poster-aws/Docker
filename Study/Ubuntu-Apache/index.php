<html>
<head>
<title>~~IP-Adress~~</title>
</head>
<body>
Your IP adress is : 
<?php echo $_SERVER['REMOTE_ADDR'] ?>
<p> </p>

Server IP adres is :
<?php echo $_SERVER['SERVER_ADDR'] ?>
<p> </p>

<p>Time : </p>
<?php echo $_SERVER['date_default_timezone_get()'] ?>


</body>
</html>