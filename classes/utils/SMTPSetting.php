<?php
class SMTPSetting {

    public $username;

    public $password;

    public $server;

    public $port;

    public function __construct($username, $password, $server, $port) {
        $this->username = $username;
        $this->password = $password;
        $this->server   = $server;
        $this->port     = $port;
    }
}
?>
