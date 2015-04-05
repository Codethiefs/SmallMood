<?php

namespace Small;

/**
 * 配置管理类
 */
class Config
{

    protected static $_config = [];

    /**
     * 加载配置文件
     * @return array
     */
    public static function load($file)
    {
        if (!is_file($file)) {
            return false;
        }
        $config = include $file;
        self::config($config);

    }

    public static function load_ext_file($path)
    {

        // 加载自定义的动态配置文件
        $configs = self::config('LOAD_EXT_CONFIG');
        if ($configs) {
            if (is_string($configs)) {
                $configs = explode(',', $configs);
            }
            foreach ($configs as $key => $config) {
                $file = $path . 'Conf/' . $config;
                if (is_file($file)) {
                    $temp = include $file;
                    is_numeric($key) ? self::config($temp) : self::config($key, include $file);
                }
            }
        }
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
        // 如果name为空返回所有配置
        if (is_null($name)) {
            return self::$_config;
        }

        // 数组的情况
        if (is_array($name)) {
            self::$_config = array_merge(self::$_config, array_change_key_case($name, CASE_UPPER));
        }

        if (is_string($name)) {
            $name = strtoupper($name);

            // 一维数组的设置和获取
            if (!strpos($name, '.')) {
                // value为空时返回值
                if (is_null($value)) {
                    return isset(self::$_config[$name]) ? self::$_config[$name] : null;
                }
                self::$_config[$name] = $value;
                return null;
            }

            // 二维数组设置和获取
            $name = explode('.', $name);
            if (is_null($value)) {
                return isset(self::$_config[$name[0]][$name[1]]) ? self::$_config[$name[0]][$name[1]] : null;
            }

            self::$_config[$name[0]][$name[1]] = $value;
        }

        return null;

    }


}