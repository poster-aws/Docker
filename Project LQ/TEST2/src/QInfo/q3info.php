<?php
require_once "../db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: sans-serif;
    }

    h2 {
      text-align: center;
      margin: 8px 0;
      font-size: 1.1em;
    }

    #infoBlock {
      max-width: 800px;
      margin: 14px auto;
      padding: 8px 16px;
      background: rgba(245, 245, 245, 0);
      border-left: 4px solid #007BFF;
      font-size: 0.95em;
      line-height: 1.3;
      color: #333;
    }

    .digit {
      display: inline-flex;
      width: 20px;
      height: 20px;
      margin-right: 5px;
      border-radius: 50%;
      background-color: #7eb0ea;
      color: #000;
      font-weight: bold;
      justify-content: center;
      align-items: center;
      text-align: center;
      font-family: Arial, sans-serif;
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);

    }
        /* Скрытие полос прокрутки */
    body {
      overflow: auto;
      scrollbar-width: none; /* Firefox */
    }

    body::-webkit-scrollbar {
      display: none;
    }

  </style>
</head>
<body>

  <div id="infoBlock">
    <p>
      <span class="digit">1</span>
      <span class="digit">2</span>
      <span class="digit">3</span>
      Nombre de combinaisons dans Order – 1000
    </p>
    <p>
      <span class="digit">1</span>
      <span class="digit">3</span>
      <span class="digit">2</span>
      Nombre de combinaisons dans N'importe quel order – ???
    </p>
    <p>
      <span class="digit">2</span>
      <span class="digit">1</span>
      <span class="digit">2</span>
      Nombre de combinaisons dans N'importe quel order avec doublons ?
    </p>
  </div>

</body>
</html>