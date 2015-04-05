<?php

namespace Small;


class Cookie
{

    /**
     * 整理cookie参数
     */
    static private function options($option = [])
    {

        // 读取配置文件
        $config = Config::config('COOKIE_OPTIONS');
        // 如果options是数字设置有效期
        if (is_numeric($option)) {
            $option = ['cookie_expire' => $option];
        }
        // 合并配置项
        if (is_array($option)) {
            $config = array_merge($config, array_change_key_case($option));
        }
        // 开启httponly
        if ($config['httponly']) {
            ini_set("session.cookie_httponly", 1);
        }

        return $config;
    }


    /**
     * 设置cookie
     */
    static public function set($name, $value, $option = [])
    {
        // 整合配置项
        $config = self::options($option);
        // 加密value
        $value = self::encrypt($value);
        $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
        setcookie($name, $value, $expire, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        $_COOKIE[$name] = $value;

    }


    /**
     * 获取cookie
     */
    static public function get($name)
    {
        $value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : NULL;
        return self::decrypt($value);
    }

    /**
     * 删除Cookie
     */
    static public function del($name)
    {
        // 删除指定cookie
        $config = self::options();
        setcookie($name, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        unset($_COOKIE[$name]);

    }

    /**
     * 加密cookie值
     */
    static private function encrypt($value)
    {
        // 如果是数组先转为json并加Array前缀
        if (is_array($value)) {
            $value = 'Array:' . json_encode(array_map('urlencode', $value));
        }
        // 获取配置文件中加密方式和加密密钥
        $key = Config::config('COOKIE_ENCRYPT_KEY');
        $encrypt = Config::config('COOKIE_ENCRYPT_TYPE');
        // 如果有配置加密方式进行加密
        if ($encrypt) {
            $value = Crypt::init($encrypt)->encrypt($value, $key);
        }

        return $value;
    }

    /**
     * 解密cookie值
     */
    static private function decrypt($value)
    {
        // 获取配置文件中加密方式和加密密钥
        $key = Config::config('COOKIE_ENCRYPT_KEY');
        $encrypt = Config::config('COOKIE_ENCRYPT_TYPE');
        // 如果有配置加密方式进行解密
        if ($encrypt) {
            $value = Crypt::init($encrypt)->decrypt($value, $key);
        }
        // 如果前Array前缀去掉并解码为数组
        if (0 === strpos($value, 'Array:')) {
            $value = substr($value, 6);
            $value = array_map('urldecode', json_decode($value, true));
        }

        return $value;

    }


}
