<?php
//定义框架目录
define('SMALL',dirname(str_replace("\\","/",__FILE__)).'/');
//定义根目录
define('ROOT',dirname($_SERVER['SCRIPT_FILENAME']).'/');
//定义应用目录
define('APPPA', ROOT.APPNA.'/');
//应用程序入口文件
define('APPIN', $_SERVER["SCRIPT_NAME"].'/');
//运行时目录
define('RUNTIME', ROOT.'runtime/');

//引入配置文件
require SMALL.'config.php';
//包含核心函数库
require SMALL.'common/base.func.php';
//注册自动加载函数
spl_autoload_register('smartyAutoload');
spl_autoload_register('smallAutoload');

//字符集设置
header('content-type:text/html;charset=utf-8');
//设置时区
date_default_timezone_set(TIMEZONE);
//包含路径
$include_path  = get_include_path();
$include_path .= PATH_SEPARATOR.SMALL.'lib/core/';
$include_path .= PATH_SEPARATOR.SMALL.'lib/driver/';
$include_path .= PATH_SEPARATOR.SMALL.'lib/template/';
$include_path .= PATH_SEPARATOR.APPPA.'model/';
$include_path .= PATH_SEPARATOR.APPPA.'action/';
set_include_path($include_path);


@ini_set('display_errors', 1);//禁止在页面上显示错误信息;
//设置报错级别
if (defined('DEBUG')&&DEBUG==1)
{
	$GLOBALS['debug'] = 1;
	error_reporting(E_ALL);
	debug::start();
	set_error_handler(array('debug','catcher'));
	register_shutdown_function(array('debug','shutdown'));
}else
{
	error_reporting(0);
}

//

//对用户输入的变量进行转义
if (!get_magic_quotes_gpc())
{
	if (!empty($_GET))
	{
		$_GET = addslashes_deep($_GET);
	}
	if (!empty($_POST))
	{
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
$module = $_GET['module'].'Action';
//检测该模块是否存在
if (file_exists(APPPA.'action/'.$module.'.class.php'))
{
	$controller = new $module();		//创建控制器对象
	$action = $_GET['action'];			//获取方法名
	$controller->$action();				//执行方法
}else 
{
	debug::addmsg('当前模块不存在',0);
}
