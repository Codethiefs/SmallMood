<?php
namespace Small;

use Small\Cache\Memcached;
use Small\Config;

class Pool
{

    static public function createMemcachePool($persistent = false)
    {
        if (!extension_loaded('memcache')) {
            die('不支持memcahe扩展，请先安装该扩展');
        }

        $mem = new \Memcache;
        $servers = Config::config('MEMCACHE_SERVERS');
        foreach ($servers as $server) {
            list($host, $port, $weight) = explode(':', $server);
            $mem->addServer($host, $port, $persistent, $weight);
        }
        // 使用一致性哈希算法
        ini_set('memcache.hash_strategy', 'consistent');
        return $mem;
    }

    static public function createMemcachedPool($persistent = false)
    {
        if (!extension_loaded('memcached')) {
            die('不支持memcahed扩展，请先安装该扩展');
        }

        $mem = new \Memcached;
        $servers = Config::config('MEMCACHE_SERVERS');
        foreach ($servers as $server) {
            list($host, $port, $weight) = explode(':', $server);
            $mem->addServer($host, $port, $weight);
        }

        $mem->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
        $mem->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        return $mem;
    }



}
