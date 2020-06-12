<?php
defined('SOURCE_PATH') || die('SOURCE_PATH not defined!');


function sanitize_file_name($filename)
{
    $filename_raw = $filename;
    $special_chars = array('?', '/', '\\', '<', '>', ':', ',', '"', '*', '|', chr(0));
    /**
     * Filters the list of characters to remove from a filename.
     *
     * @param array $special_chars Characters to remove.
     * @param string $filename_raw Filename as it was passed into sanitize_file_name().
     * @since 2.8.0
     *
     */
    //$special_chars = apply_filters( 'sanitize_file_name_chars', $special_chars, $filename_raw );
    $filename = preg_replace("#\x{00a0}#siu", ' ', $filename);
    $filename = str_replace($special_chars, '_', $filename);
    $filename = str_replace(array('%20'), ' ', $filename);
    $filename = preg_replace('/[\r\n\t -]+/', ' ', $filename);
    $filename = trim($filename, '.-_');

    return $filename;
}

function updateConversionStatus($pdo)
{
    $return = ".";
    $stmt = $pdo->query('SELECT id, title, date, game, fkey, status FROM streams WHERE status = "converting" OR status = "converting_unlisted" ORDER BY date ASC');
    while ($row = $stmt->fetch()) {
        $data = array('fkey' => $row['fkey'], 'type' => 'status');

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $status = file_get_contents(POST_URL, true, $context);

        if (strpos($status, 'finished') !== false) {

            if($row['status'] = 'converting'){
                $sql = "UPDATE streams SET status = 'Finished_A' WHERE streams.fkey = :fkey;";
            } elseif($row['status'] = 'converting_unlisted'){
                $sql = "UPDATE streams SET status = 'Unlisted' WHERE streams.fkey = :fkey;";
            } else{
                $sql = "UPDATE streams SET status = 'Finished_C' WHERE streams.fkey = :fkey;";
            }

//            $sql = "UPDATE streams SET status = 'Finished' WHERE streams.fkey = :fkey AND status =! 'converting_unlisted';";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['fkey' => $row['fkey']]);
            $progressLog = $row['fkey'] . '.ffmpeg.log';
            //unlink(LOG_PATH . $progressLog);
            rename(LOG_PATH . $progressLog, LOG_PATH . $row['fkey'] . '.ffmpeg_done.log');
            rename(OUTPUT_PATH. 'unprocessed/' .$row['id']. ' ' .$row['title']. '.mp4', OUTPUT_PATH. 'processed/done_' .$row['id']. ' ' .$row['title']. '.mp4');
        }
        $return .= "<br>".$status;
    }
    return $return;
}

function import_streams_to_db($pdo)
{
    $return = 'Auto update failed';


    $twitch_client_key = "";
    $channelName = '';
    $url = 'https://api.twitch.tv/kraken/channels/' . $channelName . '/videos?broadcasts=true&client_id=' . $twitch_client_key . '&limit=100';

    $channelsApi = 'https://api.twitch.tv/kraken/channels/';
    
    $clientId = '';
    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $clientId
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $channelsApi . $channelName . "/videos?broadcasts=true&limit=100"
    ));

    $response = curl_exec($ch);
    curl_close($ch);
//$return = $response;


    //$json_result = file_get_contents($url, false);
    //$return .= $json_result;
    $result = json_decode($response, true);

    foreach ($result['videos'] as $vid) {

        $pieces = explode("/", $vid['animated_preview_url']);
        $fkey = hash('crc32', 'https://vod-secure.twitch.tv/' . $pieces[3] . '/chunked/index-dvr.m3u8', false);
        $data = [
            'id' => $pieces[3],
            'title' => $vid['title'],
            'game' => $vid['game'],
            'status' => 'Not Downloaded',
            'fkey' => $fkey,
            'date' => $vid['published_at'],
        ];
        $sql = "INSERT IGNORE INTO streams (id, title, game, status, fkey, date) VALUES (:id, :title, :game, :status, :fkey, :date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);


        $data = array('fkey' => $fkey, 'type' => 'status');

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $status = file_get_contents(POST_URL, true, $context);
        // If contains 'not exist'
        if (strpos($status, 'not exist') !== false) {
            $sql = "UPDATE streams SET status = 'Not Downloaded' WHERE streams.id = :id AND status = 'Downloading';";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $pieces[3]]);
        } elseif (strpos($status, 'finished') !== false) {

            $thumbnail = file_get_contents('https://static-cdn.jtvnw.net/s3_vods/' . $pieces[3] . '/thumb/thumb0-320x180.jpg');
            $img = OUTPUT_PATH . 'img' . DS . $pieces[3] . '.jpg';
            file_put_contents($img, $thumbnail);


            $sql = "UPDATE streams SET status = 'Finished' WHERE streams.fkey = :fkey;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['fkey' => $fkey]);
            $progressLog = $fkey . '.ffmpeg.log';
            //unlink(LOG_PATH . $progressLog);
            rename(LOG_PATH . $progressLog, LOG_PATH . $fkey . ".ffmpeg_done.log");
        } elseif (strpos($status, 'Forbidden') !== false) {
            $sql = "UPDATE streams SET status = 'ERROR' WHERE streams.fkey = :fkey;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['fkey' => $fkey]);
        } else {
            $sql = "UPDATE streams SET status = 'Downloading' WHERE streams.id = :id;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $pieces[3]]);
        }
        $return = 'Auto update completed';


    }
    return $return;
}

/**
 * Get Directory Files
 *
 * Scans a given directory and returns an array containing any files in that directory.
 *
 * @param string $path The full path of the directory we want the files of
 * @return  array           Either an empty array (if no files exist) or an array of files
 * @since   0.1
 */
function _get_files($path)
{
    $cont = scandir($path);
    $files = array();

    foreach ($cont as $item) {
        // Skip directories ...
        if ($item == '.' || $item == '..' || is_dir($path . $item))
            continue;
        $files[] = $item;
    }
    return $files;
}

/**
 * Get Source Files
 *
 * Retrieves all files in the {@link SOURCE_PATH} directory, defined in config.php.
 *
 * @param void
 * @return  array
 * @since   0.1
 */
function _source_files()
{
    return _get_files(SOURCE_PATH);
}

/**
 * Get Converted Files
 *
 * Retrieves all files in the {@link OUTPUT_PATH} directory, defined in config.php.
 *
 * @param void
 * @return  array
 * @since   0.1
 */
function _converted_files()
{
    return _get_files(OUTPUT_PATH);
}

/**
 * JSON-encoded response
 *
 * Outputs a JSON-encoded array of data and sends the correct headers as well.
 * It will send an Error 500 if the second parameter is set to true, otherwise
 * it sends 200 OK header.
 *
 * This function also haults the script, preventing any additional output that might
 * cause errors.
 *
 * @param mixed $data Data can be a string, integer, or an array to be encoded.
 * @param bool $error Whether or not the response is an error or not.
 * @return  void
 * @since   0.1
 */
function json_response($data, $error = false)
{
    if ($error)
        header('HTTP/1.1 500 JSON Error');
    else
        header('HTTP/1.1 200 OK');

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1970 00:00:00 GMT');
    header('Content-type: application/json');

    // Convert strings/integers into an array before outputting data...
    if (!is_array($data))
        echo json_encode(array($data), true);
    else
        echo json_encode($data, true);
    exit;
}

/**
 * Check $_POST Variable
 *
 * This checks to see if the given array key is set in the $_POST array and if not,
 * it returns the given default value.
 *
 * @param string $key The key to check the $_POST array for
 * @param mixed $default A default value to use if it's not set
 * @return  mixed
 * @since   0.1
 */
function _chkVal($key, $default = false)
{
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

/**
 * Convert Seconds to Minutes
 *
 * Converts given seconds into HH:MM:SS format
 *
 * @param integer $sec The number of seconds to convert
 * @return  string          HH:MM:SS string
 * @since   0.1
 */
function sec2min($sec)
{
    return sprintf('%02d:%02d:%02d', floor($sec / 3600), floor(($sec / 60) % 60), $sec % 60);
}

/**
 * FFMPEG Convert Video
 *
 * This is a class used to process requests for converting videos via FFMPEG,
 * although at this time it doesn't actually execute the command.
 *
 * It handles status logging, error logging, etc.
 *
 * @author      Amereservant <david@amereservant.com>
 * @copyright   2012 Amereservant
 * @license     http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @version     0.1
 * @link        http://myownhomeserver.com
 * @since       0.1
 */
class ffmpegConvert
{
    /**
     * File Key (Used mainly to identify the file log)
     *
     * This should only be set via the {@link _setFkey()} method!
     *
     * @var      string
     * @access   protected
     * @since    0.1
     */
    protected $fkey;

    /**
     * Log File(s) Path
     *
     * @var      string
     * @access   protected
     * @since    0.1
     */
    protected $logPath = '';

    /**
     * Errors
     *
     * @var      array
     * @access   protected
     * @since    0.1
     */
    protected $error = array();

    /**
     * FFMPEG Execution Path (includes directory/ffmpeg.exe)
     *
     * @var      string
     * @access   protected
     * @since    0.1
     */
    protected $ffmpegPath = '';

    /**
     * Progress Log (stores ffmpeg progress data only!)
     *
     * This is the file the output from ffmpeg.exe will be written to.
     * Nothing else should be written to this file or it can cause parse errors.
     *
     * This is set by the {@link _setProgressLogFkey()} method.
     *
     * @var      string
     * @access   protected
     * @since    0.1
     */
    protected $progressLog = '';

    /**
     * Constructor
     *
     * The {@link $fkey} can be set via this parameter (it's recommended).
     * This method sets the paths from CONSTANTS defined in config.php and registers
     * shutdown functions to automatically write log errors when
     * the script ends.
     *
     * @param string $fkey The file key to identify the log/file by.
     * @return   void
     * @access   public
     * @since    0.1
     */
    public function __construct($fkey = '')
    {
        $this->logPath = LOG_PATH;
        $this->ffmpegPath = FFMPEG_PATH;
        $this->_setFkey($fkey);

        register_shutdown_function(array(&$this, 'logErrors'));
    }

    /**
     * Set Fkey
     *
     * Sets the {@link $fkey} property and calls the {@link _setStatusLogFkey()} method
     * to set the Status Log file.
     *
     * This method should be the ONLY way the {@link $fkey} property is set!
     *
     * @param string $fkey The file key to assign to the {@link $fkey} property.
     * @return   void
     * @access   private
     * @since    0.1
     */
    private function _setFkey($fkey)
    {
        $this->fkey = $fkey;
        $this->_setProgressLog();
    }

    /**
     * Set Progress Log File
     *
     * Set's the {@link $progressLog} property based on the current {@link $fkey} value.
     *
     * @param void
     * @return   void
     * @access   private
     * @since    0.1
     */
    private function _setProgressLog()
    {
        $this->progressLog = $this->fkey . '.ffmpeg.log';
    }

    /**
     * Execute FFMPEG Conversion
     *
     * This checks the input data for valid values, then via POST to the execution URL
     * (Defined as EXEC_URL in config.php), it initiates the ffmpeg command and begins
     * the video conversion.
     *
     * The script located at EXEC_URL actually calls PHP's exec() function...
     *
     * @param string $inFile The input filename (without path)
     * @param string $outFile The output filename (without path)
     * @param string $params The ffmpeg parameters to use for converting the video
     * @return   void
     * @access   public
     * @since    0.1
     */
    public function exec($inFile, $outFile, $params)
    {
        // Set an fkey if it hasn't already been set.  This probably should be removed...
        if (strlen($this->fkey) < 1) {
            $fkey = hash('crc32', time() . $inFile, false);
            $this->_setFkey($fkey);
        }

        // Verify the ffmpeg path is valid
        if (strlen($this->ffmpegPath) < 1 || !file_exists($this->ffmpegPath))
            $this->error[] = 'Invalid FFMPEG Path `' . $this->ffmpegPath . '`!';

        // Verify the source file exists
        //if( strlen($inFile) < 1 || !file_exists(SOURCE_PATH . $inFile) )
        //    $this->error[] = 'Invalid input file `'. $inFile .'`!';

        // Verify the output filename has been given
        if (strlen($outFile) < 1)
            $this->error[] = 'Invalid output file!';

        // Verify the conversion parameters have been given
        if (strlen($params) < 1)
            $this->error[] = 'No parameters were given!  Please specify conversion parameters.';

        // Check if there are any errors and stop if so...
        if (count($this->error) > 0) {
            $this->logErrors();
            return false;
        }

        // Write status message updating us on where the script is at ...
        $this->writeStatus("Sending FFMPEG exec command to " . EXEC_URL . " ...");


        $cmd = '-i "' . SOURCE_PATH . $inFile . '" ' . $params . ' "' . OUTPUT_PATH . $outFile . '" 2> ' . $this->logPath . $this->progressLog;

        // Write the execution command to the status log
        $this->writeStatus($cmd);

        $data = array(
            'cmd' => $cmd,
            'ffmpegpw' => FFMPEG_PW,
            'fkey' => $this->fkey
        );

        // Form the POST data string
        $pdata = http_build_query($data);


        $url = EXEC_URL;
        //$data = array('key1' => 'value1', 'key2' => 'value2');

        // use key 'http' even if you send the request to https://...
        //$options = array(
        //	'http' => array(
        //		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        //		'method'  => 'POST',
        //		'content' => $pdata,
        //		'timeout' => 30
        //	)
        //);
        //$context  = stream_context_create($options);
        //$result = file_get_contents($url, false, $context);
        //if ($result === FALSE) { /* Handle error */ }

        //var_dump($result);
        //file_put_contents('log.txt', 'result: '.$result.EXEC_URL);

        $fp = fsockopen("localhost", 80, $errno, $errstr, 30);
        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
        } else {
            $out = 'POST ' . EXEC_URL . " HTTP/1.1\r\n";
            //$out = "GET / HTTP/1.1\r\n";
            $out .= 'Host: ' . $_SERVER['HTTP_HOST'] . "\r\n";

            $out .= "Content-type: application/x-www-form-urlencoded\r\n";
            $out .= "Content-length: " . strlen($pdata) . "\r\n";
            $out .= "User-agent: FFmpeg PHP Progress script\r\n";

            $out .= "Connection: Close\r\n\r\n";
            $out .= $pdata;
            fwrite($fp, $out);
            //while (!feof($fp)) {
            //	echo fgets($fp, 128);
            //}
            fclose($fp);
        }


        return;

    }

    /**
     * Validate Progress
     *
     * This validates that the progress file DOES exist and the {@link $fkey} has
     * been set.
     *
     * @param void
     * @return   bool        true if it does exist, false if not or fkey not set
     * @access   protected
     * @since    0.1
     */
    protected function validateProgress()
    {
        if (strlen($this->fkey) < 1)
            $this->addError('The `$fkey` property is not set! LINE:' . __LINE__);

        if (file_exists($this->logPath . $this->progressLog)) {
            $lastModified = filemtime($this->logPath . $this->progressLog);
        } else {
            $lastModified = 0;
        }
        $now = strtotime("now");
        return strlen($this->progressLog) > 0 && file_exists($this->logPath . $this->progressLog) && ($now - $lastModified < 10);
    }

    protected function checkIfFinished()
    {
        $isFinished = false;
        if (file_exists($this->logPath . $this->progressLog)) {
            if (exec('grep ' . escapeshellarg('second pass') . ' ' . $this->logPath . $this->progressLog)) {
                $isFinished = true;
            }
        }
        return strlen($this->progressLog) > 0 && file_exists($this->logPath . $this->progressLog) && $isFinished;
    }

    protected function checkIfForbidden()
    {
        $isforbidden = false;
        if (file_exists($this->logPath . $this->progressLog)) {
            if (exec('grep ' . escapeshellarg('HTTP error') . ' ' . $this->logPath . $this->progressLog)) {
                $isforbidden = true;
            }
        }
        return strlen($this->progressLog) > 0 && file_exists($this->logPath . $this->progressLog) && $isforbidden;
    }

    /**
     * JSON-encoded Status
     *
     * Retrieves the current encoding time and total time, then outputs the data
     * in a JSON-encoded array.
     *
     * This is used when polling for progress updates.
     *
     * @param void
     * @return   void
     * @access   public
     * @since    0.1
     */
    public function jsonStatus()
    {
        // Get the current Encoded time
        $eTime = $this->getEncodedTime();
        // Get the total Length time
        $tTime = $this->getTotalTime();

        $array = array(
            'time_encoded' => $eTime,
            'time_total' => $tTime,
            'time_encoded_min' => sec2min($eTime),
            'time_total_min' => sec2min($tTime)
        );

        json_response($array);
    }

    /**
     * Get Encoded Time
     *
     * Sort of like "elapsed" time, this retrieves the number of seconds that have
     * been processed of the video so far.
     * This gets called by the {@link jsonStatus()} method.
     *
     * @param void
     * @return   integer     Number of encoded seconds of the video
     * @access   protected
     * @since    0.1
     */
    protected function getEncodedTime()
    {
        return $this->parseLogTime('encoded');
    }

    /**
     * Get Total Time
     *
     * Retrieves the total number of seconds of the video's length.
     * This gets called by the {@link jsonStatus()} method.
     *
     * @param void
     * @return   integer     Number of total seconds of the video
     * @access   protected
     * @since    0.1
     */
    protected function getTotalTime()
    {
        return $this->parseLogTime('total');
    }

    /**
     * Parse Log Time
     *
     * Parses the {@link $progressLog} time and returns the requested type of seconds.
     *
     * @param string $type Either 'total' or 'encoded'
     * @return   integer         Number of seconds for requested value
     * @access   protected
     * @since    0.1
     */
    protected function parseLogTime($type)
    {
        // Make sure a valid type is being requested
        if ($type != 'total' && $type != 'encoded') {
            $err = 'Invalid Log time type `' . $type . '`!';
            $this->addError($err);
            exit($err);
        }

        // Validate the progress file
        if (!$this->validateProgress()) {
            if (!$this->checkIfFinished()) {
                if (!$this->checkIfForbidden()) {
                    $err = 'ffmpeg-progress: FFMPEG progress log does not exist! FILE: `' .
                        $this->logPath . $this->progressLog . '`';

                    $this->addError($err);
                    exit($err);
                } else {
                    $err = 'ffmpeg-progress: HTTP error 403 Forbidden! FILE: `' .
                        $this->logPath . $this->progressLog . '`';

                    $this->addError($err);
                    exit($err);
                }
            } else {
                $err = 'ffmpeg-progress: The download has finished! FILE: `' .
                    $this->logPath . $this->progressLog . '`';
                $this->addError($err);
                exit($err);
            }

        }

        // Determine the correct set of separation values
        if ($type == 'encoded')
            $eKey = array('time=', ' bitrate=');
        else
            $eKey = array('Duration: ', ', start: ');

        // Open and parse the log file
        $contents = file_get_contents($this->logPath . $this->progressLog);
        $times = explode($eKey[0], $contents);
        $ctime = count($times) - 1;
        $timed = explode($eKey[1], $times[$ctime]);
        $tt = explode(':', $timed[0]);

        // Calculate total seconds ... cannot do $tt[0] * 3600 + $tt[1] * 60 + $tt[2]
        // since this was returning invalid values...
        $hsec = $tt[0] * 3600;
        $msec = $tt[1] * 60;
        $sec = $tt[2];
        $ttsec = $hsec + $msec + $sec;

        // Return rounded seconds
        return round($ttsec);
    }

    /**
     * Log Errors
     *
     * Writes any current errors in the {@link $errors} property to the log and clears
     * the {@link $errors} property so duplicates won't be written.
     *
     * @param void
     * @return   void
     * @access   public
     * @since    0.1
     */
    public function logErrors()
    {
        foreach ($this->error as $errMsg) {
            $this->writeLog($errMsg, date('d-m-y') . '.error.log');
        }

        // Reset error message array since they've been logged ...
        $this->error = array();
    }

    /**
     * Write Status Message to Log
     *
     * Writes informative messages to a status log (NOT the progress log) that may
     * be helpful for debugging purposes.
     *
     * @param string  The status message to write.
     * @return   void
     * @access   public
     * @since    0.1
     */
    public function writeStatus($msg)
    {
        if (strlen($msg) < 1)
            return;

        $this->writeLog($msg, date('d-m-y') . '.status.log');
        return;
    }

    /**
     * Add Error Message
     *
     * Adds an error message to the {@link $error} property so it will be added to
     * the error log when it is written.
     *
     * @param string $msg The error message to add
     * @return   void
     * @access   public
     * @since    0.1
     */
    public function addError($msg)
    {
        $this->error[] = $msg;
        $this->logErrors();
    }

    /**
     * Write Log Data
     *
     * Used to write the log data based on given params.
     * It's responsible for formatting the log file entry and then writing it to
     * the logfile specified by the $file parameter.
     *
     * @param string $msg The message to write to the log file.
     * @param string $file The log file's filename to write given message to.
     *                           This should NOT contain the path, just filename only.
     * @return   void
     * @access   protected
     * @since    0.1
     */
    protected function writeLog($msg, $file)
    {
        // Log file entry format
        $logf = '[' . date('d.m.y H:i:s') . '] [' . $_SERVER['REMOTE_ADDR'] . "] %s \n";

        // If the log file wasn't specified, append a log entry alerting us of it ...
        if (strlen($file) < 4) {
            $filename = $this->logPath . date('d-m-y') . '.error.log';
            $this->addError('writeLog() called without valid $file parameter! ' .
                'LINE:' . __LINE__ . ' FILE: ' . __FILE__);
        } else {
            $filename = $this->logPath . $file;
        }

        $logStr = sprintf($logf, $msg);

        // Write the log file data
        $hdl = fopen($filename, 'a+');
        fwrite($hdl, $logStr);
        fclose($hdl);
        return;
    }
}
