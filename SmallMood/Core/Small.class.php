<?php

namespace Small;


class Small
{

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
        Config::load(APP_PATH . 'Common/Conf/Config.php');

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
        $method = $class->hasMethod(ACTION_NAME) ? $class->getMethod(ACTION_NAME) :  $class->getMethod('__call');

        if (!$method->isPublic() || $method->isStatic()) {
            die(ACTION_NAME . '方法不存在');
        }

        // 如果是空操作，绑定当前操作名到第一参
        if ('__call' == $method->getName()) {
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
        $error['type'] = 'EXCEPTION';
        $error['message'] = $e->getMessage();
        $error['file'] = $e->getFile();
        $error['line'] = $e->getLine();
        //Log::record($error['message'], Log::ERR);
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
    static public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $error['type'] = $errno;
        $error['message'] = $errstr;
        $error['file'] = $errfile;
        $error['line'] = $errline;
        ob_end_clean();
        self::halt($error);
    }

    // 致命错误捕获
    static public function shutdownHandler()
    {
        //Log::save();
        $e = error_get_last();
        if($e){
            ob_end_clean();
            self::halt($e);
        }

    }

    /**
     * 错误输出
     * @param mixed $error 错误
     * @return void
     */
    static public function halt($error)
    {
        // 命令行下错误输出
        if (IS_CLI) {
            ob_start();
            debug_print_backtrace();
            $error['trace'] = ob_get_clean();
            exit(iconv('UTF-8', 'gbk', $error ['message']) . PHP_EOL . 'FILE: ' . $error['file'] . '(' . $error['line'] . ')' . PHP_EOL . $error['trace']);
        }

        // 调试模式下
        if (APP_DEBUG) {
            ob_start();
            debug_print_backtrace();
            $trace = ob_get_clean();
            $error['trace'] = nl2br(htmlspecialchars($trace));
            include SMALL_PATH . 'Tpl/exception.tpl';
            exit;

        }

        // 非调试模式尝试重定向到错误页面
        $error_page = Config::config('ERROR_PAGE');
        if (!empty($error_page)) {
            redirect($error_page);
        }
        // 没有设置重定向错误页面显示默认错误页面
        include SMALL_PATH . 'Tpl/error.tpl';
        exit;
    }


}
