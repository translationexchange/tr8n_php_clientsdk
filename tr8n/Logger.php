<?php

namespace tr8n;

class Logger {
    const ERROR     = 0;  // Error: error conditions
    const WARNING   = 1;  // Warning: warning conditions
    const NOTICE    = 2;  // Notice: normal but significant condition
    const INFO      = 3;  // Informational: informational messages
    const DEBUG     = 4;  // Debug: debug messages

    //custom logging level

    /**
     * Log nothing at all
     */
    const OFF    = 8;
    /**
     * Alias for CRIT
     * @deprecated
     */
    const FATAL  = 2;

    /**
     * Internal status codes
     */
    const STATUS_LOG_OPEN    = 1;
    const STATUS_OPEN_FAILED = 2;
    const STATUS_LOG_CLOSED  = 3;

    const NO_ARGUMENTS = 'tr8n.Logger::NO_ARGUMENTS';

    /**
     * Current status of the log file
     * @var integer
     */
    private $_logStatus         = self::STATUS_LOG_CLOSED;
    /**
     * Holds messages generated by the class
     * @var array
     */
    private $_messageQueue      = array();
    /**
     * Path to the log file
     * @var string
     */
    private $_logFilePath       = null;
    /**
     * Current minimum logging threshold
     * @var integer
     */
    private $_severityThreshold = self::INFO;
    /**
     * This holds the file handle for this instance's log file
     * @var resource
     */
    private $_fileHandle        = null;

    /**
     * Default severity of log messages, if not specified
     * @var integer
     */
    private static $_defaultSeverity    = self::DEBUG;
    /**
     * Valid PHP date() format string for log timestamps
     * @var string
     */
    private static $_dateFormat         = 'Y-m-d G:i:s';
    /**
     * Octal notation for default permissions of the log file
     * @var integer
     */
    private static $_defaultPermissions = 0777;
    /**
     * Array of KLogger instances, part of Singleton pattern
     * @var array
     */
    private static $instances           = array();

	function __construct($logFilePath, $severity) {
        if ($severity === self::OFF) {
            return;
        }

        $this->_logFilePath = $logFilePath;
        $this->_severityThreshold = $severity;

//        $logDirectory = $logFilePath.split(DIRECTORY_SEPARATOR);
//        if (!file_exists($logDirectory)) {
//            mkdir($logDirectory, self::$_defaultPermissions, true);
//        }

        if (file_exists($this->_logFilePath) && !is_writable($this->_logFilePath)) {
            $this->_logStatus = self::STATUS_OPEN_FAILED;
            return;
        }

        if (($this->_fileHandle = fopen($this->_logFilePath, 'a'))) {
            $this->_logStatus = self::STATUS_LOG_OPEN;
        } else {
            $this->_logStatus = self::STATUS_OPEN_FAILED;
        }
	}

    public function __destruct() {
        if ($this->_fileHandle) {
            fclose($this->_fileHandle);
        }
    }

    public function debug($msg, $args = self::NO_ARGUMENTS) {
        $this->log($msg, self::DEBUG);
    }

    public function info($msg, $args = self::NO_ARGUMENTS) {
        $this->log($msg, self::INFO, $args);
    }

    public function notice($msg, $args = self::NO_ARGUMENTS) {
        $this->log($msg, self::NOTICE, $args);
    }

    public function warn($msg, $args = self::NO_ARGUMENTS) {
        $this->log($msg, self::WARNING, $args);
    }

    public function error($msg, $args = self::NO_ARGUMENTS) {
        $this->log($msg, self::ERROR, $args);
    }

    public function log($line, $severity, $args = self::NO_ARGUMENTS) {
        if ($this->_severityThreshold >= $severity) {
            if($args !== self::NO_ARGUMENTS) {
                /* Print the passed object value */
                $line = $line . '; ' . var_export($args, true);
            }

            $msg = $this->formatMessage($line, $severity);

            $this->write($msg . PHP_EOL);
        }
    }

    public function write($line) {
        if ($this->_logStatus == self::STATUS_LOG_OPEN
            && $this->_severityThreshold != self::OFF) {
            if (fwrite($this->_fileHandle, $line) === false) {
                $this->_messageQueue[] = $this->_messages['writefail'];
            }
        }
    }

    private function formatMessage($msg, $severity) {
        date_default_timezone_set('America/Los_Angeles');
        $time = date(self::$_dateFormat);
        return "$time: $msg";
    }
}

?>