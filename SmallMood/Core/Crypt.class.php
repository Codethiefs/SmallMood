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
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        self::$instance->setType($type);
        return self::$instance;
    }

    public function setType($type = ''){
        $type = $type ?: Config::config('DATA_CRYPT_TYPE');
        self::$handler = 'Small\\Crypt\\' . ucwords(strtolower($type));
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
        $class = self::$handler;
        return $class::decrypt($data, $key);
    }
}