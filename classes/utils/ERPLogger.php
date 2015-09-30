<?php
class ERPLogger {

    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';

    private $fileName;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    protected function getRealFilePath() {
        return LOG_STORAGE_PATH . '/' . $this->fileName;
    }

    /**
     * Write data to file
     * @var $data mixed
     * @var $level string
     */
    public function log($data, $level = null) {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        if (null != $level) {
            $logLine = '[' . self::LEVEL_INFO . '] ' . $data . "\n";
        } else {
            $logLine = $data . "\n";
        }

        try {
            file_put_contents(
                $this->getRealFilePath(),
                $logLine,
                FILE_APPEND | LOCK_EX
            );
        } catch (Exeption $e) {
            // Do nothing
        }
    }

    /**
     * Log with INFO annotate
     */
    public function info($data) {
        $this->log($data, self::LEVEL_INFO);
    }

    /**
     * Log with WARNING annotate
     */
    public function warn($data) {
        $this->log($data, self::LEVEL_WARNING);
    }

    /**
     * Log with ERROR annotate
     */
    public function error($data) {
        $this->log($data, self::LEVEL_ERROR);
    }
}
?>
