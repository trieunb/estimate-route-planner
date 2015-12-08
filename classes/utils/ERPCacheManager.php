<?php
class ERPCacheManager {

    /* @var Memcache */
    protected $memcacheClient;

    /* @var ERPCacheManager */
    private static $instance;

    protected function __construct() {
        $this->memcacheClient = new Memcache;
        $this->memcacheClient->pconnect(ERP_MEMCACHED_HOST, ERP_MEMCACHED_PORT);
    }

    public static function getIntance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function fetch($key, Closure $callback) {
        $value = self::getIntance()->getClient()->get($key);
        if ($value === false) {
            $value = $callback();
            self::getIntance()->getClient()->set($key, $value);
        }
        return $value;
    }

    public static function remove($key) {
        self::getIntance()->getClient()->delete($key);
    }

    public function getClient() {
        return $this->memcacheClient;
    }
}
?>
