<?php
$microtimeStart = microtime(true);
require 'config.php';
require 'database.php';
require 'functions.php';
//print("<br><h3>6 Time: ".round(microtime(true) - $microtimeStart,5)."</h3><br>");
?><!DOCTYPE html>
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>Automatic Twitch Broadcasts Downloader</title>
    <meta name="description" content="Automatically downloads Twitch broadcasts"/>
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
<div id="header-container">
    <header class="wrapper">
        <h1 id="title">Twitch Past Broadcasts Downloader</h1>
        <h3>This is just a test page for development purposes. Check *** for all past streams. Thanks.</h3>
    </header>
</div>


<?php
echo import_streams_to_db($pdo);
//print("<br><h3>35 Time: ".round(microtime(true) - $microtimeStart,5)."</h3><br>");
?>


<center>


    <h2>Downloading:</h2>
    <table border="1">
        <tr>
            <th>Progress</th>
            <th>Thumbnail</th>
            <th width="100%">Info</th>
        </tr>
        <?php

        $stmt = $pdo->query('SELECT id, title, date, game FROM streams WHERE status = "downloading" ORDER BY date ASC');
        //print("<br><h3>51 Time: ".round(microtime(true) - $microtimeStart,5)."</h3><br>");
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>";
            ?>
            <div class="progress"
                 id="progress<?php echo hash('crc32', 'https://vod-secure.twitch.tv/' . $row['id'] . '/chunked/index-dvr.m3u8', false); ?>"></div>
            <script>initPoll('<?php echo hash('crc32', 'https://vod-secure.twitch.tv/' . $row['id'] . '/chunked/index-dvr.m3u8', false);?>')</script>
            <?php
            echo "</td><td><img src='https://static-cdn.jtvnw.net/s3_vods/" . $row['id'] . "/thumb/thumb0-320x180.jpg'></td>";

            echo "<td>";
            echo "Title: <b>" . $row['title'] . "</b><br>";
            echo "Date: " . $row['date'] . "<br>";
            echo $row['game'] . "<br>";
            ?>

            <?php
            // <button onclick="alert(JSON.stringify(pollStatus('<?php echo hash('crc32', 'https://vod-secure.twitch.tv/'.$row['id'].'/chunked/index-dvr.m3u8', false);&>'),null,4))">Check status</button>
            echo "</td>";
            echo "</tr>";
        }
        //print("<br><h3>73 Time: ".round(microtime(true) - $microtimeStart,5)."</h3><br>");
        ?>
    </table>
    <br>
    <hr>
    <br>


    <div id="overlay">
        <div class="modal">
            <div id="close">X</div>
            <div id="loader"></div>
        </div>
    </div>
    <script>

        $(document).ready(function () {

            $('#videos a').click(function () {
                var data = $(this).attr('data');

                $('#loader').append('<video src="<?=OUTPUT_URL?>' + data + '.mp4" width="100%" controls autoplay></video>');
                $('#overlay').fadeIn(250);
            });

            $('#close, #overlay').click(function () {
                $('#overlay').fadeOut(250, function () {
                    $('#loader').html('');
                });
            });
        });
    </script>


    <div id="main" class="wrapper">
        <h2>Download Queue:</h2>
        <table id="source_videos" border="1">
            <tr>
                <th>Thumbnail</th>
                <th width="100%">Info</th>
            </tr>
            <?php
            $stmt = $pdo->query('SELECT id, title, date, game FROM streams WHERE status = "not downloaded" ORDER BY date ASC');
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td><img src='https://static-cdn.jtvnw.net/s3_vods/" . $row['id'] . "/thumb/thumb0-320x180.jpg'></td>";
                echo "<td>";
                echo "Title: <b>" . $row['title'] . "</b><br>";
                echo "Date: " . $row['date'] . "<br>";
                echo $row['game'] . "<br>";
                ?>
                <!-- <button class="dl" data-title="<?php echo $row['date'] . ' - ' . $row['title']; ?>" data-filename="<?php echo 'https://vod-secure.twitch.tv/' . $row['id'] . '/chunked/index-dvr.m3u8'; ?>" data-fkey="<?php echo hash('crc32', 'https://vod-secure.twitch.tv/' . $row['id'] . '/chunked/index-dvr.m3u8', false); ?>">Download</button> -->
                <?php
                echo "</td>";
                echo "</tr>";
            }
            //print("<br><h3>155 Time: ".round(microtime(true) - $microtimeStart,5)."</h3><br>");
            ?>
        </table>


    </div>
    <br>
    <hr>
    <br>
    <h2>Completed Downloads:</h2>
    <div id="videos">

        <?php
        $stmt = $pdo->query('SELECT id, title, date, game FROM streams WHERE status = "finished" ORDER BY date DESC LIMIT 48');
        while ($row = $stmt->fetch()) {
            echo "<a data='" . sanitize_file_name($row['date'] . " - " . str_replace("'", "%27", $row['title'])) . "'>";
            echo "<img src='" . OUTPUT_URL . "img/" . $row['id'] . ".jpg' onerror='this.src=\"../img-not-found.png\";'>";
            echo "Title: <b>" . $row['title'] . "</b><br>";
            echo "Date: " . $row['date'] . "<br>";
            echo $row['game'] . "<br>";
            //echo "<a href='".BASE_URL."../twitch/".sanitize_file_name($row['date']." - ".$row['title']).".mp4"."'>Watch Here</a>";
            //echo "</td>";
            echo "</a>";
        }
        //print("<br><h3>178 Time: ".round(microtime(true) - $microtimeStart,5)."</h3><br>");
        ?>
        <br>
        <p style="color:red">Older streams are hidden.</p>
    </div>
</center>
</body>
</html>