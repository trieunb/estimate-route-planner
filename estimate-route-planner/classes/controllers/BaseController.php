<?php
class BaseController {
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Short way to encode and print in JSON format for given
     * @see http://php.net/manual/en/function.json-encode.php
     * @param $jsonData mixed
     * @param $jsonOption
     * @return null
     */
    protected function renderJson($jsonData, $jsonOption = null) {
        echo json_encode($jsonData, $jsonOption);
        exit;
    }
}
?>
