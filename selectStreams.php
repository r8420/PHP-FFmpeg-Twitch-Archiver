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
            width: 22px;
            margin-left: 10%;
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

        input:focus,
        select:focus, .form-control:focus,
        textarea:focus,
        button:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .input-group-text {
            padding: 0.075rem .75rem;
        }

        label {
            margin-bottom: unset;
            flex: 1 1 auto;
            margin-right: unset;
        }

        .checkbox-label {
            width: 1%;
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
            <a class="nav-item nav-link" href="./videoProcessor.php">Status</a>
            <a class="nav-item nav-link active" href="./selectStreams.php">Add Streams <span
                        class="sr-only">(current)</span></a>
            <a class="nav-item nav-link" href="./index.php">Downloader</a>
        </div>
    </div>
</nav>
<div class="container">
    <?php
    if (count($_GET) >= 1) { ?>
        <div class="my-3 p-3 bg-white rounded box-shadow">
            <h6 class="pb-2 mb-0">Enter Categories:</h6>
            <form method="post" action="videoProcess.php">
                <table class="table" width="100%">
                    <tr>
                        <th>Title</th>
                        <th style="max-width:175px">Date</th>
                        <th>Game</th>
                        <th>OVR</th>
                        <th>Check</th>
                    </tr>
                    <?php
                    $processButton = "";
                    foreach ($_GET as $value) {

                        $vTitle = substr($value, 20, -4);
                        $vDate1 = substr($value, 0, 19);
                        $format = 'Y m d H_i_s';
                        $vDate = DateTime::createFromFormat($format, $vDate1);
                        $vDate = $vDate->format('Y-m-d H:i:s');
                        $vFilenameLength = strlen($value);


                        $integrity = "";

                        //Get the file size in bytes.
                        $fileSizeBytes = filesize(SOURCE_PATH . $value);

                        //Convert the bytes into GB.
                        $fileSizeGB = ($fileSizeBytes / 1024 / 1024 / 1024);

                        if ($fileSizeGB > 20) {
                            $integrity = "Thats a huge bitch";
                        } else {
                            try {
                                $file_info = $getID3->analyze(SOURCE_PATH . $value);
                                if (isset($file_info['video']) && $file_info['video']['resolution_y'] > 1) {
                                    $integrity = "<span style='color:darkgreen'>OK, " . $file_info['video']['resolution_y'] . "p video</span>";
                                } else {
                                    if (!file_exists(SOURCE_PATH . $value)) {
                                        $integrity = 'File doesn\'t exist';
                                        $processButton = " disabled";
                                    } elseif (!is_readable(SOURCE_PATH . $value)) {
                                        $integrity = 'File can\'t be read';
                                        $processButton = " disabled";
                                    } elseif ($file_info[mime_type] === "video/MP2T") {
                                        $integrity = "<span style='color:darkorange'>H265 video</span>";
                                    } elseif (isset($file_info['error'])) {
//                                    $integrity = "<span style='color:darkred'>FAILED: " . $file_info['error'][0] . "</span>";
                                        $integrity = "<span style='color:darkred'>FAILED</span>";
                                        $processButton = " disabled";
                                    } elseif (isset($file_info[mime_type])) {
                                        $integrity = "<span style='color:darkred'>Corrupted or " . explode('/', $file_info[mime_type])[1] . " video</span>";
                                        $processButton = " disabled";
                                    } else {
                                        $integrity = "<span style='color:darkred'>I don't even know</span>";
//                                    echo "<pre>";
//                                    print_r($file_info);
//                                    echo "</pre>";
                                        $processButton = " disabled";
                                    }


                                }
                            } catch (\Exception $e) {
                                $integrity = "<span style='color:darkred'>FAILED: WEIRD ERROR</span>";
                                $processButton = " disabled";
                            }
                        }


                        echo <<<HTML
        <tr>
            <input type="hidden" class="form-control" id="vTitle" name="{$value}[vFilename]" value="$value" size="$vFilenameLength" required readonly>
            <td><input type="text" class="form-control" id="vTitle" name="{$value}[vTitle]" value="$vTitle" required readonly></td>
            <td><input type="text" class="form-control" id="vDate" name="{$value}[vDate]" value="$vDate" required readonly></td>
            <td><input type="text" class="form-control" id="vCategory" name="{$value}[vCategory]" placeholder="Game Title / Category" required></td>
            <td><input type="checkbox" class="form-control" id="vReplace" name="{$value}[vReplace]"></td>
            <td>$integrity</td>
        </tr>
HTML;

                    }
                    ?>
                </table>
                <br>
                <?php
                $stmt = $pdo->query('SELECT count(*) FROM streams WHERE status = "converting" OR status = "converting_unlisted" ORDER BY date ASC');
                $processCount = $stmt->fetch(PDO::FETCH_COLUMN);
                if ($processCount + count($_GET) <= 4) {
                    echo '<input name="process" class="btn btn-primary" type="submit" value="Start Processing"' . $processButton . '>';
                } else {
                    echo 'Already converting ' . $processCount . ' streams';
                }

                ?>

                <input name="queue" class="btn btn-secondary" type="submit"
                       value="Add To Queue"<?php echo $processButton; ?>>
                <input name="queueUnlisted" class="btn btn-secondary" type="submit"
                       value="Add To Queue as unlisted"<?php echo $processButton; ?>>
                <br>
            </form>
        </div>
        <?php
    }
    ?>
    <div class="my-3 p-3 bg-white rounded box-shadow">
        <h6 class="pb-2 mb-0">Select Twitch Broadcasts:</h6>
        <form>
            <?php
            $stmt = $pdo->query('SELECT date FROM streams WHERE status = "Conv_queue" OR status = "Conv_queue_unlisted" ORDER BY date ASC');
            $queueDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $pdo->query('SELECT date FROM streams WHERE status = "converting" OR status = "converting_unlisted" ORDER BY date ASC');
            $streamDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($files as $key => $file) {
                if ($file[16] === "_") {

                    $vDate1 = substr($file, 0, 19);
                    $format = 'Y m d H_i_s';
                    $vDate = DateTime::createFromFormat($format, $vDate1);
                    $vDate = $vDate->format('Y-m-d H:i:s');

                    $checkboxDisabled = 'disabled';
                    if (in_array($vDate, $streamDates, true)) {
                        $checkboxInfo = "<span style='color:deepskyblue'>Already Converting </span>";
                    } elseif (in_array($vDate, $queueDates, true)) {
                        $checkboxInfo = "<span style='color:forestgreen'>In Queue </span>";
                    } else {
                        $checkboxDisabled = $checkboxInfo = '';
                    }
                    ?>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <input type="checkbox" class="form-control" id="video<?= $key ?>"
                                       name="video<?= $key ?>"
                                       value="<?= $file ?>" <?= $checkboxDisabled ?>>
                            </div>
                        </div>
                        <label for="video<?= $key ?>"
                               class="checkbox-label py-2 pl-2 border rounded-right"><?= $checkboxInfo . ' ' . $file ?></label>
                    </div>
                    <?php
                    if (false) {
                        if (in_array($vDate, $streamDates)) {
                            echo "<input type=\"checkbox\" class=\"form-control\" id=\"video$key\" name=\"video$key\" value=\"$file\" disabled>";
                            echo "<label for=\"video$key\">&nbsp;<span style='color:deepskyblue'>Already Converting </span>$file</label><br>";
                        } elseif (in_array($vDate, $queueDates)) {
                            echo "<input type=\"checkbox\" class=\"form-control\" id=\"video$key\" name=\"video$key\" value=\"$file\" disabled>";
                            echo "<label for=\"video$key\">&nbsp;<span style='color:forestgreen'>In Queue </span>$file</label><br>";
                        } else {
                            echo "<input type=\"checkbox\" class=\"form-control\" id=\"video$key\" name=\"video$key\" value=\"$file\">";
                            echo "<label for=\"video$key\">&nbsp;$file</label><br>";
                        }
                    }


                }

            }
            ?>
            <input class="btn btn-primary" type="submit" value="Next">
        </form>
    </div>
</div>