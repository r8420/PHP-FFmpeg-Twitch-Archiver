<?php
require 'database.php';
require 'config.php';
require 'functions.php';

echo import_streams_to_db($pdo);
echo "\n";
$sql = 'SELECT 1 from streams WHERE status = "Downloading" OR status = "Converting" LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute();

if ($stmt->fetchColumn())
//    echo('There is already a stream being downloaded at the moment.');
    die('There is already a stream being downloaded at the moment.');

$sql = 'SELECT * from streams WHERE status = "Not Downloaded" ORDER BY date ASC LIMIT 1';
$stmt = $pdo->query($sql);
$streams = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($streams as $stream) {
    # code...

    $fkey = hash('crc32', 'https://vod-secure.twitch.tv/' . $stream['id'] . '/chunked/index-dvr.m3u8', false);
    $data = [

        'title' => $stream['date'] . " - " . $stream['title'],
        'filename' => 'https://vod-secure.twitch.tv/' . $stream['id'] . '/chunked/index-dvr.m3u8',
        'fkey' => $fkey,
        'type' => 'convert',
        'params' => '-threads 2 -movflags +faststart -crf 24 -pix_fmt yuv420p -vcodec libx264 -b:v 0k -s 1280x720 -c:a copy -bsf:a aac_adtstoasc -r 30000/1001 -max_muxing_queue_size 4096 -y'
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
        echo "Queued: " . $stream['title'];
    }
}
