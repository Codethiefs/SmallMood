<?php

namespace Small;
/**
 * 加密解密类
 */
class Crypt
{
    private static $instance ;
    private static $handler = '';

    public static function init($type = '')
    {

        $type = $type ?: Config::config('DATA_CRYPT_TYPE');
        $class = 'Small\\Crypt\\' . ucwords(strtolower($type));
        self::$handler = $class;

        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 加密字符串
     * @param string $str 字符串
     * @param string $key 加密key
     * @param integer $expire 有效期（秒） 0 为永久有效
     * @return string
     */
    public function encrypt($data, $key, $expire = 0)
    {
        if (empty(self::$handler)) {
            self::init();
        }
        $class = self::$handler;
        return $class::encrypt($data, $key, $expire);
    }

    /**
     * 解密字符串
     * @param string $str 字符串
     * @param string $key 加密key
     * @return string
     */
    public function decrypt($data, $key)
    {
        if (empty(self::$handler)) {
            self::init();
        }
        $class = self::$handler;
        return $class::decrypt($data, $key);
    }
}