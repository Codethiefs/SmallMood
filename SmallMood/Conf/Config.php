<?php
define("DEBUG", 1);							//调试模式 ：1 开启 0 关闭

//数据库配置

define("DRIVER","mysqli");				//数据库驱动，支持mysql,mysqli默认为mysqli
define("DSN","mysql:host=localhost;dbname=secb");
define("HOST", "localhost");			
define("USER", "root");              
define("PASS", "");                  
define("DBNAME","ecshop");					//数据库名
define("TABPREFIX", "ecs_");				//表前缀
define("FIELDCACHE","0");            		//是否开启表字段缓存，1开启，0关闭

define('TIMEZONE', 'Asia/Shanghai');		//时区设置；

//模板配置
define('TPL_CACHE',0);						//是否开启模板缓存，0关闭，1开启
define('TPL_SUFFIX','html');					//模板文件默认后缀
define('LEFT_DELIMITER', '<{');
define('RIGHT_DELIMITER', '}>');


//缓存配置
define('CSTART', 1);							//是否开启缓存，1开启，0关闭
define('CACHE','');

//memcache服务器配置

$memservers = array(
		array('localhost',11211),
);




?>