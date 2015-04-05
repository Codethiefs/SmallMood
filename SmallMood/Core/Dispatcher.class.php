<?php

namespace Small;

class Dispatcher
{

    /**
     * URL映射到控制器
     * @access public
     * @return void
     */
    static public function dispatch()
    {

        $varModule = Config::config('VAR_MODULE');
        $varController = Config::config('VAR_CONTROLLER');
        $varAction = Config::config('VAR_ACTION');

        $pathInfo = self::getPathInfo();

        $module = (isset($pathInfo[0]) ? array_shift($pathInfo) : (isset($_GET[$varModule]) ? $_GET[$varModule] : Config::config('DEFAULT_MODULE')));
        $controller = (isset($pathInfo[0]) ? array_shift($pathInfo) : (isset($_GET[$varController]) ? $_GET[$varController] : Config::config('DEFAULT_CONTROLLER')));
        $action = (isset($pathInfo[0]) ? array_shift($pathInfo) : (isset($_GET[$varAction]) ? $_GET[$varAction] : Config::config('DEFAULT_ACTION')));

        // 获取模块名称
        define('MODULE_NAME', ucfirst($module));
        define('CONTROLLER_NAME', ucfirst($controller));
        define('ACTION_NAME', $action);

        // 模块检查
        if (in_array(MODULE_NAME, Config::config('MODULE_DENY_LIST')) || !is_dir(APP_PATH . MODULE_NAME)) {
            die('模块不存在');
        }

        // 如果pathInfo还有剩余按键/值分配到$_GET
        for ($i = 0; $i < count($pathInfo); $i += 2) {
            echo $pathInfo[$i];
            $_GET[$pathInfo[$i]] = !empty($pathInfo[$i + 1]) ? $pathInfo[$i + 1] : '';
        }

        // 定义当前模块路径
        define('MODULE_PATH', APP_PATH . MODULE_NAME . '/');

        // 加载模块配置文件
        Config::load(MODULE_PATH . 'Conf/config.php');

        // 加载模块函数文件
        ($moduleFunctions = MODULE_PATH . 'Common/functions.php') && is_file($moduleFunctions) && include $moduleFunctions;

        // 加载模块的扩展配置文件
        Config::load_ext_file(MODULE_PATH);

        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST, $_GET, $_COOKIE);

    }

    /**
     * 获取pathinfo信息
     */
    static private function getPathInfo()
    {
        // CLI模式下 index.php module/controller/action/params/...
        if (IS_CLI) {
            $_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        }
        $_SERVER['PATH_INFO'] = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';

        // 开启子域名部署时自动绑定对应的模块
        // 子域名部署规则 '子域名'=>'模块名'
        if (Config::config('APP_SUB_DOMAIN_DEPLOY')) {
            $rules = Config::config('APP_SUB_DOMAIN_RULES');
            $module = isset($rules[$_SERVER['HTTP_HOST']]) ? $rules[$_SERVER['HTTP_HOST']] : '';
            $_SERVER['PATH_INFO'] = empty($module) ? $_SERVER['PATH_INFO'] : $module . '/' . $_SERVER['PATH_INFO'];
        }

        $pathInfo = empty($_SERVER['PATH_INFO']) ? [] : explode('/', $_SERVER['PATH_INFO']);
        return $pathInfo;
    }


}
