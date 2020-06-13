<?php

require 'database.php';
require 'config.php';
require 'functions.php';

updateConversionStatus($pdo);

$stmt = $pdo->query('SELECT count(*) FROM streams WHERE status = "converting" OR status = "converting_unlisted" ORDER BY date ASC');
$amountConverting = $stmt->fetch(PDO::FETCH_COLUMN);
echo('Converting: ' . $amountConverting . '<br>');

$stmt = $pdo->query('SELECT id, title, date, game, status FROM streams WHERE status = "Conv_queue" OR status = "Conv_queue_unlisted" ORDER BY date ASC');

for (; $amountConverting < 4; $amountConverting++) {
    $stream = $stmt->fetch();
    $streamStatus = $stream['status'];

    if (true) {
        $filename = $stream['id'] . ' ' . $stream['title'] . '.mp4';
        $fkey = hash('crc32', $filename, false);

        $data = [
            'title' => $stream['date'] . " - " . $stream['title'],
            'filename' => $filename,
            'fkey' => $fkey,
            'type' => 'convert',
            'params' => '-threads 1 -movflags +faststart -crf 24 -pix_fmt yuv420p -vcodec libx264 -b:v 0k -s 1280x720 -c:a copy -bsf:a aac_adtstoasc -r 30000/1001 -max_muxing_queue_size 4096 -y'
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
//        $status = true;
        if ($status) {
            if ($streamStatus = 'Conv_queue_unlisted') {
                $sql2 = 'UPDATE streams SET status = "converting_unlisted" WHERE id = :id';
            } elseif ($streamStatus = 'Conv_queue') {
                $sql2 = 'UPDATE streams SET status = "converting" WHERE id = :id';
            }

            $stmt2 = $pdo->prepare($sql2);
            if ($stmt2->execute(['id' => $stream['id']])) {
                echo 'Started converting: ' . $stream['title'] . '<br>';
            }
        }
        print_r($stream);
    }


//    print_r($stream);
    echo '<br>';
}

