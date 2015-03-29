<?php

namespace Small;

class Small
{

    // 类映射
    private static $_map = array();

    // 实例化对象
    private static $_instance = array();

    /**
     * 应用程序初始化
     */
    static public function start()
    {
        // 注册AUTOLOAD方法
        spl_autoload_register('Small\Small::autoload');
        // 设定错误和异常处理
        register_shutdown_function('Small\Small::fatalError');
        set_error_handler('Small\Small::appError');
        set_exception_handler('Small\Small::appException');
        // 加载配置文件
        Config::loadConfig();

        // 设置系统时区
        date_default_timezone_set(Config::config('DEFAULT_TIMEZONE'));

        exit;

    }


    /**
     * 类库自动加载
     */
    public static function autoload($class)
    {
       
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class, $method = '')
    {
        $identify = $class . $method;
        if (!isset(self::$_instance[$identify])) {
            if (class_exists($class)) {
                $o = new $class();
                if (!empty($method) && method_exists($o, $method))
                    self::$_instance[$identify] = call_user_func(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            } else
                self::halt(L('_CLASS_NOT_EXIST_') . ':' . $class);
        }
        return self::$_instance[$identify];
    }

    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException($e)
    {
        $error = array();
        $error['message'] = $e->getMessage();
        $trace = $e->getTrace();
        if ('E' == $trace[0]['function']) {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        } else {
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
        $error['trace'] = $e->getTraceAsString();
        Log::record($error['message'], Log::ERR);
        // 发送404信息
        header('HTTP/1.1 404 Not Found');
        header('Status:404 Not Found');
        self::halt($error);
    }

    /**
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     */
    static public function appError($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();
                $errorStr = "$errstr " . $errfile . " 第 $errline 行.";
                if (C('LOG_RECORD')) Log::write("[$errno] " . $errorStr, Log::ERR);
                self::halt($errorStr);
                break;
            default:
                $errorStr = "[$errno] $errstr " . $errfile . " 第 $errline 行.";
                self::trace($errorStr, '', 'NOTIC');
                break;
        }
    }

    // 致命错误捕获
    static public function fatalError()
    {
        Log::save();
        if ($e = error_get_last()) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    self::halt($e);
                    break;
            }
        }
    }

    /**
     * 错误输出
     * @param mixed $error 错误
     * @return void
     */
    static public function halt($error)
    {
        $e = array();
        if (APP_DEBUG || IS_CLI) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace = debug_backtrace();
                $e['message'] = $error;
                $e['file'] = $trace[0]['file'];
                $e['line'] = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace'] = ob_get_clean();
            } else {
                $e = $error;
            }
            if (IS_CLI) {
                exit(iconv('UTF-8', 'gbk', $e['message']) . PHP_EOL . 'FILE: ' . $e['file'] . '(' . $e['line'] . ')' . PHP_EOL . $e['trace']);
            }
        } else {
            //否则定向到错误页面
            $error_page = C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);
            } else {
                $message = is_array($error) ? $error['message'] : $error;
                $e['message'] = C('SHOW_ERROR_MSG') ? $message : C('ERROR_MESSAGE');
            }
        }
        // 包含异常页面模板
        $exceptionFile = C('TMPL_EXCEPTION_FILE', null, THINK_PATH . 'Tpl/think_exception.tpl');
        include $exceptionFile;
        exit;
    }

    /**
     * 添加和获取页面Trace记录
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别(或者页面Trace的选项卡)
     * @param boolean $record 是否记录日志
     * @return void|array
     */
    static public function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
    {
        static $_trace = array();
        if ('[think]' === $value) { // 获取trace信息
            return $_trace;
        } else {
            $info = ($label ? $label . ':' : '') . print_r($value, true);
            $level = strtoupper($level);

            if ((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE') || $record) {
                Log::record($info, $level, $record);
            } else {
                if (!isset($_trace[$level]) || count($_trace[$level]) > C('TRACE_MAX_RECORD')) {
                    $_trace[$level] = array();
                }
                $_trace[$level][] = $info;
            }
        }
    }
}
