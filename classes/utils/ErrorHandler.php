<?php
class ErrorHandler {
    public function handleException($e) {
        if (! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = []) {
        if (error_reporting() & $level) {
            throw new Exception($message, 0, $level, $file, $line);
        }
    }

    public function handleShutdown() {
        if (! is_null($error = error_get_last()) &&
            in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])
            ) {
            $this->handleException(
                new Exception(
                    $error['message'],
                    $error['type'],
                    0,
                    $error['file'],
                    $error['line'],
                    0)
            );
        }
    }
}
?>
