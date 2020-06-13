<?php
require 'config.php';
require 'database.php';
require 'functions.php';
updateConversionStatus($pdo);
?><!DOCTYPE html>
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>Twitch Broadcasts Converter Status</title>
    <meta name="description" content="Status of the conversion of Twitch broadcasts"/>
    <meta name="author" content="Richard Gonlag"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <script src="./js/jquery-latest.min.js"></script>
    <script src="./js/jquery.percentageloader-0.1.js"></script>
    <script src="./js/jquery.timer.js"></script>
    <script>jsNS = {'base_url': '<?php echo BASE_URL; ?>', 'post_url': '<?php echo POST_URL; ?>'}</script>
    <link rel="stylesheet" href="style.css"/>
    <script src="./js/scripts.js"></script>
</head>
<body>
<center>
    <div id="header-container">
        <header class="wrapper">
            <h1 id="title">Twitch Broadcasts Converter Status</h1>
        </header>
    </div>


    <h2>Converting:</h2>
    <table border="1">
        <tr>
            <th>Progress</th>
            <th width="100%">Info</th>
        </tr>
        <?php

        $stmt = $pdo->query('SELECT id, title, date, game FROM streams WHERE status = "converting" ORDER BY date ASC');
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>";
            ?>
            <div class="progress"
                 id="progress<?php echo hash('crc32', ($row['id'] . " " . $row['title'] . ".mp4"), false); ?>"></div>
            <script>initPoll('<?php echo hash('crc32', ($row['id'] . " " . $row['title'] . ".mp4"), false);?>')</script>
            <?php


            echo "<td>";
            echo "Title: <b>" . $row['title'] . "</b><br>";
            echo "Date: " . $row['date'] . "<br>";
            echo $row['game'] . "<br>";
            ?>

            <?php
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</center>


</body>
</html>