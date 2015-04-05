<?php

namespace Small;


class Session
{


    static public function init()
    {
        $options = Config::config('SESSION_OPTIONS');
        $varSessionId = Config::config('VAR_SESSION_ID');

        // 通过变量提交session_id,供flash上传时使用
        if ($varSessionId && isset($_REQUEST[$varSessionId])) {
            session_id($_REQUEST[$varSessionId]);
        }
        // session变量名
        if (isset($options['name']) && !empty($options['name'])) {
            session_name($options['name']);
        }
        // 重置session存储路径
        if (isset($options['path']) && !empty($options['path'])) {
            session_save_path($options['path']);
        }
        // session有效域，可以用来解决session跨子域问题
        if (isset($options['domain']) && !empty($options['domain'])) {
            ini_set('session.cookie_domain', $options['domain']);
        }
        // session有效期
        if (isset($options['expire']) && !empty($options['expire'])) {
            ini_set('session.gc_maxlifetime', $options['expire']);
            ini_set('session.cookie_lifetime', $options['expire']);
        }
        // 客户端禁用Cookie时自动通过ＵＲＬ传递session_id
        if (isset($options['use_trans_sid']) && !empty($options['expire'])) {
            ini_set('session.use_trans_sid', $options['use_trans_sid'] ? 1 : 0);
        }
        // 使用cookie来保存session_id
        if (isset($options['use_cookies']) && !empty($options['expire'])) {
            ini_set('session.use_cookies', $options['use_cookies'] ? 1 : 0);
        }

        if (isset($options['type']) && !empty($options['type'])) {
            $class = 'Small\\Session\\' . ucwords(strtolower($options['type']));
            $handler = new $class();
            session_set_save_handler($handler, true);
        }

        // 启动session
        if (isset($options['auto_start']) && $options['auto_start']) {
            session_start();
        }


    }


    /**
     * 设置session
     */
    static public function set($name, $value)
    {
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            $_SESSION[$name1][$name2] = $value;
        } else {
            $_SESSION[$name] = $value;
        }
    }


    /**
     * 获取session
     */
    static public function get($name = '')
    {
        if ('' == $name) {
            return $_SESSION;
        }

        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
        } else {
            return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
        }

    }

    /**
     * 删除session
     */
    static public function del($name)
    {
        // 清空session
        if (is_null($name)) {
            $_SESSION = [];
        }

        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            unset($_SESSION[$name1][$name2]);
        } else {
            unset($_SESSION[$name]);
        }
    }

    /**
     * 开启session
     */
    static public function start()
    {
        return session_start();
    }

    /**
     * 关闭session文件
     */
    static public function close()
    {
        session_write_close();
    }

    /**
     * 查看会话状态
     */
    static public function status()
    {
        return session_status();
    }

    /**
     * 彻底销毁当前会话
     */
    static public function destroy()
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    /**
     * 使用新生成的会话 ID 更新现有会话 ID
     */
    static public function regenerate()
    {
        return session_regenerate_id(true);
    }


}
