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
            static::$instance = new static;
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
        $value = static::getInstance()
            ->getClient()
            ->get(ERP_CACHE_PREFIX . $key);
        if ($value === false) {
            $value = $callback();
            static::getInstance()
                ->getClient()
                ->set(ERP_CACHE_PREFIX . $key, $value);
        }
        return $value;
    }

    /**
     * Clear the cached value by given key
     * @param $key string
     * @return mixed
     */
    public static function clear($key) {
        return self::getInstance()
            ->getClient()
            ->delete(ERP_CACHE_PREFIX . $key);
    }

    /**
     * Set the cache value by given key
     * @param $key string
     * @return bool
     */
    public static function set($key) {
        return self::getInstance()
            ->getClient()
            ->set(ERP_CACHE_PREFIX . $key);
    }

    /**
     * Set the cache value by given key
     * @param $key string
     * @return mixed
     */
    public static function get($key) {
        return self::getInstance()
            ->getClient()
            ->set(ERP_CACHE_PREFIX . $key);
    }

    /**
     * Flush all caches
     * @return void
     */
    public static function flush() {
        return self::getInstance()
            ->getClient()
            ->flush();
    }

    private function getClient() {
        return $this->memcacheClient;
    }
}
?>
