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
        if (ERP_ENABLE_DEBUG) {
            file_put_contents(
                $this->getRealFilePath(),
                $message . "\n",
                FILE_APPEND | LOCK_EX
            );
        }
    }
}
?>
