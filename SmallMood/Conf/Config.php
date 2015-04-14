<?php

/**
 * 惯例配置文件
 */

return array(
    /* 应用设定 */
    'MODULE_DENY_LIST'              => array('Common', 'Runtime'),      //不允许访问的MODULE列表

    /* 默认设定 */
    'DEFAULT_MODULE'                => 'Home',      // 默认模块
    'DEFAULT_CONTROLLER'            => 'Default',   // 默认控制器名称
    'DEFAULT_ACTION'                => 'index',     // 默认操作名称
    'DEFAULT_TIMEZONE'              => 'PRC',       // 默认时区


    /* SESSION设置 */
    'SESSION_OPTIONS' => [
        'auto_start'    => true,            // 是否自动开启session
        'type'          => 'redis',         // session存储类别
        'name'          => '',              // 设置session_name的值
        'path'          => '',              // 存储路径
        'expire'        => 0,               // 有效期
        'domain'        => '',              // 有效域
    ],


    /* Cookie设置 */
    'COOKIE_ENCRYPT_TYPE'   => 'Crypt',                             //　采用哪种方式加密
    'COOKIE_ENCRYPT_KEY'    => '6Y#H&^G4!~5SF)*M>HK%N?MC@H7',       //　加密串
    'COOKIE_OPTIONS' => [
        'httponly'      => true,                                // http only 为TRUE时浏览器无法通过js操作cookie
        'path'          => '/',                                 // Cookie路径
        'domain'        => '',                                  // Cookie有效域名
        'expire'        => 0,                                   // Cookie有效期
        'secure'        => false,                               // Cookie安全传输
    ],


    /* 错误设置 */
    'ERROR_PAGE' => '',    // 错误定向页面




    /* 数据库设置 */
    'DB_TYPE' => '',     // 数据库类型
    'DB_HOST' => '', // 服务器地址
    'DB_NAME' => '',          // 数据库名
    'DB_USER' => '',      // 用户名
    'DB_PWD' => '',          // 密码
    'DB_PORT' => '',        // 端口
    'DB_PREFIX' => '',    // 数据库表前缀
    'DB_PARAMS' => array(), // 数据库连接参数
    'DB_DEBUG' => TRUE, // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE' => true,        // 启用字段缓存
    'DB_CHARSET' => 'utf8',      // 数据库编码默认采用utf8


    /* 数据缓存设置 */
    'DATA_CACHE_TIME' => 0,      // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_COMPRESS' => false,   // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK' => false,   // 数据缓存是否校验缓存
    'DATA_CACHE_PREFIX' => '',     // 缓存前缀
    'DATA_CACHE_TYPE' => 'File',  // 数据缓存类型,支持:File|Db|Memcache
    'DATA_CACHE_PATH' => TEMP_PATH,// 缓存路径设置 (仅对File方式缓存有效)





    /* 日志设置 */
    'LOG_RECORD' => false,   // 默认不记录日志
    'LOG_PATH' => RUNTIME_PATH . 'Logs/',
    'LOG_TYPE' => 'File', // 日志记录类型 默认为文件方式
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR',// 允许记录的日志级别
    'LOG_FILE_SIZE' => 2097152,    // 日志文件大小限制
    'LOG_EXCEPTION_RECORD' => false,    // 是否记录异常信息日志


    /* 模板引擎设置 */
    'TMPL_ERROR' => SMALL_PATH . 'Tpl/success.tpl', // 默认错误跳转对应的模板文件
    'TMPL_SUCCESS' => SMALL_PATH . 'Tpl/success.tpl', // 默认成功跳转对应的模板文件
    'TMPL_EXCEPTION_FILE' => SMALL_PATH . 'Tpl/exception.tpl',// 异常页面的模板文件
    'TMPL_THEME' => 'default',       // 默认的模板主题
    'TMPL_SUFFIX' => '.html',     // 模板文件后缀
    'TMPL_USE_ENGINE' => true,     // 是否使用模板引擎，flase时使用原生php代码
    'TMPL_CACHFILE_SUFFIX' => '.php',      // 默认模板缓存后缀
    'TMPL_L_DELIM' => '{',            // 模板引擎普通标签开始标记
    'TMPL_R_DELIM' => '}',            // 模板引擎普通标签结束标记
    'TMPL_VAR_IDENTIFY' => 'array',     // 模板变量识别。留空自动判断,参数为'obj'则表示对象
    'TMPL_STRIP_SPACE' => true,       // 是否去除模板文件里面的html空格与换行
    'TMPL_CACHE_ON' => true,        // 是否开启模板编译缓存,设为false则每次都会重新编译
    'TMPL_CACHE_TIME' => 0,         // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
    'TMPL_LAYOUT_NAME' => 'layout', // 当前布局名称 默认为layout

    /* URL设置 */
    'URL_MODEL' => 1,       // URL访问模式：0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
    'URL_HTML_SUFFIX' => 'html',  // URL伪静态后缀设置
    'URL_DENY_SUFFIX' => 'ico|png|gif|jpg', // URL禁止访问的后缀设置
    'URL_PARAMS_BIND' => true, // URL变量绑定到Action方法参数
    'URL_PARAMS_FILTER' => false, // URL变量绑定过滤
    'URL_PARAMS_FILTER_TYPE' => '', // URL变量绑定过滤方法 如果为空 调用DEFAULT_FILTER



    /*＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊ 系统变量名称设置 ＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊*/
    'VAR_ADDON'                 => 'addon',         // 默认的插件控制器命名空间变量
    'VAR_MODULE'                => 'm',             // 默认模块获取变量
    'VAR_CONTROLLER'            => 'c',             // 默认控制器获取变量
    'VAR_ACTION'                => 'a',             // 默认操作获取变量
    'VAR_SESSION_ID'            => 'DI_NOISSES',    //sessionID的提交变量
    'VAR_JSONP_HANDLER'         => 'jsonp_handler',              //默认处理JSONP数据的函数名


    'DATA_CRYPT_TYPE'           => 'Crypt',                         // 数据加密方式
    'DATA_CRYPT_KEY'            => '6Y#H&^G4!~5SF)*M>HK%N?MC@H7',   // 数据加密Key



    // memcache服务器
    'MEMCACHE_SERVERS' => [
        // host:port:weight
        '127.0.0.1:11211:1000',
    ],

    // redis服务器
    'REDIS_SERVERS' => [
        // host:port
        'default' => '127.0.0.1:6379',
    ],

);
