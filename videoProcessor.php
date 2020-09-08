<?php

require 'database.php';
require 'config.php';
require 'functions.php';

include_once(BASE_PATH . 'getid3/getid3.php');

updateConversionStatus($pdo);

$getID3 = new getID3;
$files = array_diff(scandir(SOURCE_PATH), array('.', '..'));

?>
<!DOCTYPE html>
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>Twitch Broadcasts Converter Status</title>
    <meta name="description" content="Status of the conversion of Twitch broadcasts"/>
    <meta name="author" content="Richard Gonlag"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script src="./js/jquery-latest.min.js"></script>
    <script src="./js/jquery.percentageloader-0.1.js"></script>
    <script src="./js/jquery.timer.js"></script>
    <script>jsNS = {'base_url': '<?php echo BASE_URL; ?>', 'post_url': '<?php echo POST_URL; ?>'}</script>
    <link rel="stylesheet" href="style.css"/>
    <script src="./js/scripts.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"
            integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI"
            crossorigin="anonymous"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        td:first-child {
            width: 10%;
            /*white-space: nowrap;*/
        }

        td:last-child {
            width: 10%;
            /*white-space: nowrap;*/
        }

        input[type="text"] {
            width: 100%;
        }

        input[type="checkbox"] {
            vertical-align: unset;
        }

        label {
            float: unset;
        }

        tbody {
            background-color: unset;
        }

        .progress {
            height: 10rem;
            background-color: unset;
        }

        table {
            /*max-width: unset;*/
        }

        td:first-child {
            width: unset;
            white-space: nowrap;
        }

        .table td, .table th {
            padding: .25rem;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Twitch Converter</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup"
            aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
        <div class="navbar-nav">
            <a class="nav-item nav-link active" href="./videoProcessor.php">Status <span
                        class="sr-only">(current)</span></a>
            <a class="nav-item nav-link" href="./selectStreams.php">Add Streams</a>
            <a class="nav-item nav-link" href="./index.php">Downloader</a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="my-3 p-3 bg-white rounded box-shadow">
        <h6 class="border-bottom border-gray pb-2 mb-0">Disk Space</h6>

        <?php
        $diskSpaceGB = round(disk_free_space("/") / 1000000000, 2);

        if (!WINDOWS) {
            $io = popen('/usr/bin/du -sk ' . ORIGINAL_PATH, 'r');
            $size = fgets($io, 4096);
            $size = substr($size, 0, strpos($size, "\t"));
            pclose($io);

            echo 'Manually freeable space (by cleaning up the original unprocessed files): <b>' . round($size / 1000000, 2) . ' GB</b>';

            $io = popen('/usr/bin/du -sk ' . SOURCE_PATH, 'r');
            $size = fgets($io, 4096);
            $size = substr($size, 0, strpos($size, "\t"));
            pclose($io);

            echo '<br>Total size of unprocessed streams: <b>' . round($size / 1000000, 2) . ' GB</b>';
        }


        echo '<br>Free disk space: <b>' . $diskSpaceGB . ' GB</b><br>';
        if ($diskSpaceGB < 10) {
            die('SERVER STORAGE ALMOST FULL. ONLY ' . $diskSpaceGB . ' GB LEFT!');
        }
        ?>
    </div>
    <div class="my-3 p-3 bg-white rounded box-shadow">
        <h6 class="pb-2 mb-0">Currently Converting<a style="float:right" href="refreshProcessorQueue.php">Update queue now</a></h6>
        <table class="table">
            <tr>
                <th>Progress</th>
                <th width="100%">Info</th>
            </tr>
            <?php
            $processCount = 0;
            $streamDates = [];
            $stmt = $pdo->query('SELECT id, title, date, game FROM streams WHERE status = "converting" OR status = "converting_unlisted" ORDER BY date ASC');
            while ($row = $stmt->fetch()) {
                $processCount++;
                $streamDates[] = $row['date'];
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
    </div>


    <br>
</div>
</body>

