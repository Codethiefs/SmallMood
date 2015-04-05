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
defined('CORE_PATH') or define('CORE_PATH', realpath(SMALL_PATH . 'Core') . '/');
// 第三方类库目录
defined('VENDOR_PATH') or define('VENDOR_PATH', SMALL_PATH . 'Vendor/');
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
define('IS_AJAX', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false);


// 加载核心类
require CORE_PATH . 'Small.class.php';

// 应用初始化
Small\Small::start();
