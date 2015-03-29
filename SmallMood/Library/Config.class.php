<?php

namespace Small;
/**
 * 配置管理类
 */
class Config
{

    protected static $config = [];

    /**
     * 加载配置文件
     * @return array
     */
    public static function loadConfig()
    {

    }


    /**
     * 设置/获取数据对象的值
     * @access public
     * @param string $name 名称
     * @param mixd $value 值
     * @return mixed
     */
    public static function config($name = null, $value = null)
    {
        // 非字符串返回
        if (!is_string($name)) {
            return null;
        }

        $config = self::$config;
        $name = strtoupper($name);

        // 一维数组的设置和获取
        if (!strpos($name, '.')) {
            // value为空时返回值
            if (is_null($value)) {
                return isset($config[$name]) ? $config[$name] : null;
            }
            $config[$name] = $value;
            return null;
        }

        // 二维数组设置和获取
        $name = explode('.', $name);
        if (is_null($value)) {
            return isset($config[$name[0]][$name[1]]) ? $config[$name[0]][$name[1]] : null;
        }

        $_config[$name[0]][$name[1]] = $value;
        return null;

    }


}