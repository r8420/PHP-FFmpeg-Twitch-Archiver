<?php
require 'database.php';
require 'config.php';
require 'functions.php';
//echo '<pre>';
//print_r($_POST);
//echo '</pre>';

//$sql = 'SELECT 1 from streams WHERE status = "Downloading" OR status = "Converting" LIMIT 1';
//$stmt = $pdo->prepare($sql);
//$stmt->execute();

//if ($stmt->fetchColumn()) echo('(Overload protection: <span style="color:red">OFF</span>) There is already a stream being converted or downloaded at the moment.<br>');



if(isset($_POST['queue']) || isset($_POST['queueUnlisted'])){
    if(isset($_POST['queueUnlisted'])){
        $unlisted = true;
    } else{
        $unlisted = false;
    }
    unset($_POST['queue'], $_POST['queueUnlisted']);
    foreach ($_POST as $stream) {
        $fkey = hash('crc32', $stream['vFilename'], false);
        $data = [
            'id' => substr($stream['vFilename'], 0, 19),
            'title' => $stream['vTitle'],
            'game' => $stream['vCategory'],
            'status' => 'Conv_queue',
            'fkey' => $fkey,
            'date' => $stream['vDate'],
        ];
        if($unlisted){
            $data['status'] = 'Conv_queue_unlisted';
        }

        $sql = "INSERT INTO streams (id, title, game, status, fkey, date) VALUES (:id, :title, :game, :status, :fkey, :date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        echo 'Added to queue: ' . $stream['vTitle'] . '<br>';
    }
}


if (isset($_POST['process'])) {
    unset($_POST['process']);
    foreach ($_POST as $stream) {
        $fkey = hash('crc32', $stream['vFilename'], false);
        $enableReplace = '';
        if (isset($stream['vReplace'])) {
            $enableReplace = ' -y';
        }

        $data = [
            'title' => $stream['vDate'] . " - " . $stream['vTitle'],
            'filename' => BASE_PATH . 'source/' . $stream['vFilename'],
            'fkey' => $fkey,
            'type' => 'convert',
            'params' => '-threads 1 -movflags +faststart -crf 24 -pix_fmt yuv420p -vcodec libx264 -b:v 0k -s 1280x720 -c:a copy -bsf:a aac_adtstoasc -r 30000/1001' . $enableReplace
        ];

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $status = file_get_contents(POST_URL, true, $context);
        if ($status) {
            $data = [
                'id' => substr($stream['vFilename'], 0, 19),
                'title' => $stream['vTitle'],
                'game' => $stream['vCategory'],
                'status' => 'Converting',
                'fkey' => $fkey,
                'date' => $stream['vDate'],
            ];

            $sql = "INSERT IGNORE INTO streams (id, title, game, status, fkey, date) VALUES (:id, :title, :game, :status, :fkey, :date)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo 'Started converting: ' . $stream['vTitle'] . '<br>';
        }

    }
}

echo '<br><a href="videoProcessor.php">Click here</a>';

