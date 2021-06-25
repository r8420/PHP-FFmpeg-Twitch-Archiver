<?php
/**
 * Configuration File
 *
 * @ver     0.2
 */

error_reporting(E_ERROR | E_PARSE);

define('DS', DIRECTORY_SEPARATOR);

define('BASE_PATH', realpath(__DIR__) . DS);

$_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";

define('BASE_URL', $_protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/');

// Videos to convert path
define('SOURCE_PATH', BASE_PATH . 'source' . DS);

$outputFolder = 'output';
// Converted videos output path
define('OUTPUT_PATH', BASE_PATH . $outputFolder . DS);

// Converted videos output URL
define('OUTPUT_URL', $_protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $outputFolder . '/');

// Original unprocessed files output path
define('ORIGINAL_PATH', BASE_PATH . 'original' . DS);

// Logs Path
define('LOG_PATH', BASE_PATH . 'logs' . DS);

// POST URL for Javascript Queries
define('POST_URL', BASE_URL . 'process.php');

// Execution Script URL (Where the ffmpeg command will be posted to)
define('EXEC_URL', BASE_URL . 'ffmpegExec.php');

// Enable if running this on Windows
define('WINDOWS', false);
// Maximum concurrent stream conversion
define('MAX_CONCURRENT_STREAMS', 2);
define('TWITCH_ID', 'CHANGE_THIS');

// Set url of twitch CDN
define('TWITCH_URL', 'https://'.TWITCH_ID.'.cloudfront.net/');

// FFMPEG Path
if (WINDOWS) {
    define('FFMPEG_PATH', 'C:\ffmpeg\bin\ffmpeg.exe');
} else {
    define('FFMPEG_PATH', '/bin/ffmpeg');
}


// FFMPEG Password (Change the value 't^$bG1c4=9u63yyKLmW7Q71tu17p5q' with something new!)
define('FFMPEG_PW', sha1('t^$bG1c4=9u63yyKLmW7Q71tu17p5q'));

// Twitch API Client-ID https://dev.twitch.tv/console/apps/
define('TWITCH_CLIENT_ID', 'CHANGE_THIS');

// Twitch Channel-ID (From the Twitch channel you want to archive)
// To get Channel-ID run: curl -H "Accept: application/vnd.twitchtv.v5+json" -H "Client-ID: YOUR_CLIENT_ID" -X GET https://api.twitch.tv/kraken/users?login=USERNAME_OF_TWITCH_USER
define('CHANNEL_ID', 'CHANGE_THIS');

date_default_timezone_set('America/Los_Angeles');


if (!file_exists(OUTPUT_PATH) && !mkdir($concurrentDirectory = OUTPUT_PATH, 0755, true) && !is_dir($concurrentDirectory)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
}

if (!file_exists(LOG_PATH) && !mkdir($concurrentDirectory = LOG_PATH, 0755, true) && !is_dir($concurrentDirectory)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
}
