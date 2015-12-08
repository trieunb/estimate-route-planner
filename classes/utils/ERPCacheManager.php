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

    /**
     * Get the singeleton instance
     * @return ERPCacheManager
     */
    public static function getInstance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get the cached value or set by the callback
     * @param $key string
     * @param $callback Closure
     * @return mixed
     */
    public static function fetch($key, Closure $callback) {
        $value = static::getInstance()->getClient()->get($key);
        if ($value === false) {
            $value = $callback();
            static::getInstance()->getClient()->set($key, $value);
        }
        return $value;
    }

    /**
     * Remove the cached value by given key
     * @param $key string
     * @return mixed
     */
    public static function remove($key) {
        self::getIntance()->getClient()->delete($key);
    }

    private function getClient() {
        return $this->memcacheClient;
    }
}
?>
