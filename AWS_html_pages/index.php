<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>www.faute.ch ~=^..^=~</title>
<style>
        body {
            background-color: lightblue;
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .bottom-right {
            position: fixed;
            bottom: 10px;
            right: 10px;
            color: blue;
            margin-top: 50px;
            padding: 10px;
            border-radius: 5px;

        }
    </style>
</head>


<body>
Your IP adress is :
<?php echo $_SERVER['REMOTE_ADDR'] ?>
<p> </p>

Server IP adres is :
<?php echo $_SERVER['SERVER_ADDR'] ?>
<p> </p>

<div id="time"></div>

<div class="bottom-right"> <b>PosteR<b> </div>

<script>
        // Function to update the current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('time').textContent = 'Current Time: ' + timeString;
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime();  // Initial call to set the time immediately

</script>       
</body>
</html>