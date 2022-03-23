<?php

namespace DebugBar\Loader;

use Helper;
use Memcached;
use Memcache;
use StringHelper;

class ProxyGlobalCache {

    /** @var Memcache|Memcached */
    private static $memcache = null;

    private static function load() {
        if ( self::$memcache ) return true;
        $memcacheHost = getenv('CACHE_HOST') ?: 'localhost';
        if ( Helper::isAWS() ) {
            $memcacheHost = 'memcached-main-node1.internal.dns';
        }elseif (Helper::isDigitalRisk() ){
            if ( defined( 'ENV_NAME' ) && ENV_NAME == "Bayview_UAT" ){
                $memcacheHost = "10.0.9.54";
            } elseif(APP_ENV == 'development'){
                $memcacheHost = 'VORLLFXDEVMID01.drprod.local';
            }elseif(APP_ENV == 'staging'){
                $memcacheHost = 'VORLLFXSTGMID01.drprod.local';
            }else {
                $memcacheHost = ['VORLLFXMID01.drprod.local', 'VORLLFXMID02.drprod.local', 'VORLLFXMID03.drprod.local', 'VORLLFXMID04.drprod.local'];
            }
        }

        if (class_exists('Memcached')) {
            self::$memcache = new Memcached();
            self::$memcache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_PHP);
        } elseif (class_exists('Memcache')) {
            self::$memcache = new Memcache();
        }

        if ( self::$memcache ) {
            if( is_array($memcacheHost)){
                foreach($memcacheHost as $host){
                    self::$memcache->addServer( $host, 11211, 1 );
                }
            }else {
                self::$memcache->addServer($memcacheHost, 11211, 1);
            }
        }
    }

    /**
     * Return the value stored in Memcache
     * Return false if key not found
     * @param $key
     * @return mixed|false
     */
    public static function get( $key ) {
        self::load();
        $value = self::$memcache->get( $key );
        //debugBar event cache.get
        event()->dispatch('cache.get', ['cache.get', $key, $value]);
        return $value;
    }

    /**
     * return all keys stored in GlobalCache
     * @return array|false
     */
    public static function getAllKeys() {
        self::load();
        if ( is_a( self::$memcache, 'Memcache' ) ) {
            $list = [];
            $allSlabs = self::$memcache->getExtendedStats( 'slabs' );
            foreach ( $allSlabs as $server => $slabs ) {
                foreach ( $slabs as $slabId => $slabMeta ) {
                    if ( !is_int( $slabId ) ) {
                        continue;
                    }
                    $cdump = self::$memcache->getExtendedStats( 'cachedump', (int)$slabId, 100000000 );
                    foreach ( $cdump as $server => $entries ) {
                        if ( $entries ) {
                            foreach ( $entries as $eName => $eData ) {
                                $list[] = $eName;
                            }
                        }
                    }
                }
            }
            return $list;
        }
        if ( is_a( self::$memcache, 'Memcached' ) ) {
            return self::$memcache->getAllKeys();
        }
        return [];
    }

    /**
     * Set Memcache value
     * @param $key
     * @param $data
     * @param int $timeToLive Number of seconds before expire
     */
    public static function set( $key, $data, $timeToLive = 3600 ) {
        self::load();
        if ( is_a( self::$memcache, 'Memcache' ) ) {
            self::$memcache->set( $key, $data, false, $timeToLive);
        } elseif ( is_a( self::$memcache, 'Memcached' ) ) {
            self::$memcache->set( $key, $data, $timeToLive);
        }
        //debugBar event cache.set
        event()->dispatch('cache.set', ['cache.set', $key, $data, $timeToLive]);
    }

    /**
     * Return true if Memcache value is deleted successfully
     * @param $key
     * @return bool
     */
    public static function delete($key) {
        self::load();
        event()->dispatch('cache.delete', ['cache.delete', $key]);
        return self::$memcache->delete( $key );
    }

    /**
     * @return Memcache|Memcached
     */
    public static function getInstance()
    {
        self::load();
        return self::$memcache;
    }

    /**
     * This function to test if caching works
     * @return bool
     */
    public static function test()
    {
        $key = "test-" . StringHelper::uuid();
        $instance = self::getInstance();
        if ($instance && $instance->set($key, '1')) {
            $instance->delete($key);
            return true;
        }

        return false;
    }
}
