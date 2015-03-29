<?php
/**********************************************************************/
/************************框架入口文件**********************************/
/**********************************************************************/

// 版本信息
const SMALL_VERSION = '2.0.0';

// 记录开始运行时间
$GLOBALS['_timeBegin'] = microtime(TRUE);

// 记录内存初始使用
$GLOBALS['_memoryStart'] = memory_get_usage();

// 定义框架目录
defined('SMALL_PATH') or define('SMALL_PATH', __DIR__ . '/');
// 定义应用目录
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/');
// 调试模式
defined('APP_DEBUG') or define('APP_DEBUG', false); // 是否调试模式
// 编绎缓存目录
defined('RUNTIME_PATH') or define('RUNTIME_PATH', APP_PATH . 'Runtime/');
// 应用模式 默认为普通模式
defined('APP_MODE') or define('APP_MODE', 'common');
// 存储类型 默认为File
defined('STORAGE_TYPE') or define('STORAGE_TYPE', 'File');
// 系统核心类库目录
defined('LIB_PATH') or define('LIB_PATH', realpath(SMALL_PATH . 'Library') . '/');
// 系统应用模式目录
defined('MODE_PATH') or define('MODE_PATH', SMALL_PATH . 'Mode/');
// 第三方类库目录
defined('VENDOR_PATH') or define('VENDOR_PATH', LIB_PATH . 'Vendor/');
// 应用公共目录
defined('COMMON_PATH') or define('COMMON_PATH', APP_PATH . 'Common/');
// 应用配置目录
defined('CONF_PATH') or define('CONF_PATH', COMMON_PATH . 'Conf/');
// 应用静态目录
defined('HTML_PATH') or define('HTML_PATH', APP_PATH . 'Html/');
// 应用日志目录
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'Logs/');
// 应用缓存目录
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'Temp/');
// 应用数据目录
defined('DATA_PATH') or define('DATA_PATH', RUNTIME_PATH . 'Data/');
// 应用模板缓存目录
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'Cache/');
// 插件目录
defined('ADDON_PATH') or define('ADDON_PATH', APP_PATH . 'Addon');

// 判断PHP运行时环境
define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0);
define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);
// 判断是否是Windows系统
define('IS_WIN', stripos(PHP_OS, 'WIN') !== false);

// 定义当前请求的系统常量
define('NOW_TIME', $_SERVER['REQUEST_TIME']);
define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) ? true : false);


// 加载核心类
require LIB_PATH . 'Small.class.php';

// 应用初始化
Small\Small::start();



/*//引入配置文件
require SMALL . 'config.php';
//包含核心函数库
require SMALL . 'common/base.func.php';
//注册自动加载函数
spl_autoload_register('smartyAutoload');
spl_autoload_register('smallAutoload');

//字符集设置
header('content-type:text/html;charset=utf-8');
//设置时区
date_default_timezone_set(TIMEZONE);
//包含路径
$include_path = get_include_path();
$include_path .= PATH_SEPARATOR . SMALL . 'lib/core/';
$include_path .= PATH_SEPARATOR . SMALL . 'lib/driver/';
$include_path .= PATH_SEPARATOR . SMALL . 'lib/template/';
$include_path .= PATH_SEPARATOR . APPPA . 'model/';
$include_path .= PATH_SEPARATOR . APPPA . 'action/';
set_include_path($include_path);


@ini_set('display_errors', 1);//禁止在页面上显示错误信息;
//设置报错级别
if (defined('DEBUG') && DEBUG == 1) {
    $GLOBALS['debug'] = 1;
    error_reporting(E_ALL);
    debug::start();
    set_error_handler(array('debug', 'catcher'));
    register_shutdown_function(array('debug', 'shutdown'));
} else {
    error_reporting(0);
}

//

//对用户输入的变量进行转义
if (!get_magic_quotes_gpc()) {
    if (!empty($_GET)) {
        $_GET = addslashes_deep($_GET);
    }
    if (!empty($_POST)) {
        $_POST = addslashes_deep($_POST);
    }

    $_COOKIE = addslashes_deep($_COOKIE);
    $_REQUEST = addslashes_deep($_REQUEST);
}

//检查部署项目
constructor::construct();

//解析URL;
url::parseurl();

//获取模块名如：indexAction
$module = $_GET['module'] . 'Action';
//检测该模块是否存在
if (file_exists(APPPA . 'action/' . $module . '.class.php')) {
    $controller = new $module();        //创建控制器对象
    $action = $_GET['action'];            //获取方法名
    $controller->$action();                //执行方法
} else {
    debug::addmsg('当前模块不存在', 0);
}*/
