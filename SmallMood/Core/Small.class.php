<?php

namespace Small;


class Small
{

    // 类映射
    private static $_map = array();

    // 实例化对象
    private static $_instance = array();

    /**
     * 应用程序执行
     */
    public static function start()
    {
        // 注册AUTOLOAD方法
        spl_autoload_register('Small\Small::autoload');
        // 设定错误和异常处理
        register_shutdown_function('Small\Small::shutdownHandler');
        set_error_handler('Small\Small::errorHandler');
        set_exception_handler('Small\Small::exceptionHandler');

        // 加载惯例配置文件
        Config::load(SMALL_PATH . '/Conf/Config.php');

        // 加载应用公共配置文件
        Config::load(APP_PATH . 'Common/Conf/config.php');

        // 包含基础函数库
        include SMALL_PATH . 'Common/functions.php';

        // 包含应用公共函数库
        ($commonFunctions = APP_PATH . 'Common/Common/functions.php') && is_file($commonFunctions) && include $commonFunctions;

        // 设置系统时区
        date_default_timezone_set(Config::config('DEFAULT_TIMEZONE'));
        // URL处理,解析模块、控制器、操作及参数
        Dispatcher::dispatch();
        // echo MODULE_NAME, " ", CONTROLLER_NAME, " ", ACTION_NAME . "<br>";

        // 非CLI模式时初始化Session
        if (!IS_CLI) {
            Session::init();
        }

        $controller = MODULE_NAME . '\\Controller\\' . CONTROLLER_NAME . 'Controller';
        if (!class_exists($controller)) {
            die($controller . '不存在');
        }
        $module = new $controller;
        $class = new \ReflectionClass($module);
        $method = $class->hasMethod(ACTION_NAME) ? ($class->getMethod(ACTION_NAME)) : ($class->hasMethod('_empty') ? ($class->getMethod('_empty')) : NULL);

        if (is_null($method) || !$method->isPublic() || $method->isStatic()) {
            die(ACTION_NAME . '方法不存在');
        }


        // 如果是空操作，绑定当前操作名到第一参
        if ('_empty' == $method->getName()) {
            $method->invokeArgs($module, [ACTION_NAME]);
        } else {
            $vars = array_merge($_GET, $_POST);
            $params = $method->getParameters();
            $args = [];
            foreach ($params as $param) {
                $name = $param->getName();
                if (isset($vars[$name])) {
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    die('参数不足' . $name);
                }
            }

            $method->invokeArgs($module, $args);
        }

        new \ReflectionMethod('aaa', 'bbb');

        return;

    }


    /**
     * 类自动加载方法
     */
    public static function autoload($class)
    {
        $class = str_replace('Small', 'Core', $class);
        $routeSpace = strstr($class, '\\', true);
        $path = in_array($routeSpace, ['Core', 'Utils', 'Vendor']) ? SMALL_PATH : APP_PATH;
        $filename = $path . str_replace('\\', '/', $class) . '.class.php';
        // 文件存在并且当为windows环境时大小写正确，进行文件包含
        if (!is_file($filename) || (IS_WIN && false === strpos(str_replace('/', '\\', realpath($filename)), $class . '.class.php'))) {
            return;
        }
        include $filename;

    }


    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function exceptionHandler($e)
    {
        $error = array();
        $error['message'] = $e->getMessage();
        $error['file'] = $e->getFile();
        $error['line'] = $e->getLine();
        $error['trace'] = $e->getTrace();
        dump($error);
        //Log::record($error['message'], Log::ERR);
        // 发送404信息
        //header('HTTP/1.1 404 Not Found');
        //header('Status:404 Not Found');
        //self::halt($error);
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
    static public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        echo $errorStr = "[$errno] $errstr " . $errfile . " 第 $errline 行.";
    }

    // 致命错误捕获
    static public function shutdownHandler()
    {
        //Log::save();
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
        self::halt(error_get_last());
    }

    /**
     * 错误输出
     * @param mixed $error 错误
     * @return void
     */
    static public function halt($error)
    {
        print_r($error);

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
