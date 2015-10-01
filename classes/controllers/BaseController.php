<?php
class BaseController {
    const PAGE_SIZE = 30;

    /* @var array */
    protected $data;

    /* @var ERPLogger */
    protected $logger;

    /* @var WP_User */
    protected $currentUser;

    public function __construct($data, ERPLogger $logger) {
        $this->data = $data;
        $this->logger = $logger;
        $this->currentUser = wp_get_current_user();
    }

    /**
     * Short way to encode and print in JSON format for given
     * @see http://php.net/manual/en/function.json-encode.php
     * @param $jsonData mixed
     * @param $jsonOption
     * @return null
     */
    protected function renderJson($jsonData, $jsonOption = null) {
        header('Content-Type: application/json');
        $this->render(json_encode($jsonData, $jsonOption));
    }

    protected function renderEmpty() {
        header('Content-Type: application/json');
        $this->render(json_encode(new stdClass));
    }

    protected function render404() {
        header('Content-Type: application/json');
        $this->render(json_encode(new stdClass), 404);
    }

    protected function render($rawResponse = '', $statusCode = 200) {
        http_response_code($statusCode);
        echo $rawResponse;
    }

    protected function redirect($to) {
        header('Location: ' . $to);
        exit;
    }

    protected function getPageParam() {
        $page = "";
        if (isset($_REQUEST['page'])) {
            $page = $_REQUEST['page'];
        } else {
            $page = 1;
        }
        return $page;
    }

    protected function getKeywordParam() {
        if (isset($_REQUEST['keyword'])) {
            $keyword = $_REQUEST['keyword'];
        } else {
            $keyword = "";
        }
        return $keyword;
    }

    protected function currentUserHasCap($capability) {
        if ($this->currentUser) {
            return $this->currentUser->has_cap($capability);
        } else {
            return false;
        }
    }

    protected function getCurrentUserName() {
        if ($this->currentUser) {
            return ERPWordpress::getNameOfUser($this->currentUser);
        }
    }
}
?>
