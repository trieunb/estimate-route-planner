<?php
class ERPLogger {

    private $fileName;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    protected function getRealFilePath() {
        return LOG_STORAGE_PATH . '/' . $this->fileName;
    }

    public function log($message) {
        try {
            file_put_contents(
                $this->getRealFilePath(),
                $message . "\n",
                FILE_APPEND | LOCK_EX
            );
        } catch (Exeption $e) {
            // Do nothing
        }
    }
}
?>
