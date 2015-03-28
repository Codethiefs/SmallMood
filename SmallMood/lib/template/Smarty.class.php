<?php
/**
 * @version 3.1.8
 */

/**
 * 定义目录分割符
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * set SMARTY_DIR to absolute path to Smarty library files.
 * Sets SMARTY_DIR only if user application has not already defined it.
 */
if (!defined('SMARTY_DIR')) {
    define('SMARTY_DIR', dirname(__FILE__) . DS);
}

/**
 * set SMARTY_SYSPLUGINS_DIR to absolute path to Smarty internal plugins.
 * Sets SMARTY_SYSPLUGINS_DIR only if user application has not already defined it.
 */
if (!defined('SMARTY_SYSPLUGINS_DIR')) {
    define('SMARTY_SYSPLUGINS_DIR', SMARTY_DIR . 'sysplugins' . DS);
}
if (!defined('SMARTY_PLUGINS_DIR')) {
    define('SMARTY_PLUGINS_DIR', SMARTY_DIR . 'plugins' . DS);
}
if (!defined('SMARTY_MBSTRING')) {
    define('SMARTY_MBSTRING', function_exists('mb_strlen'));
}
if (!defined('SMARTY_RESOURCE_CHAR_SET')) {
    // UTF-8 can only be done properly when mbstring is available!
    /**
     * @deprecated in favor of Smarty::$_CHARSET
     */
    define('SMARTY_RESOURCE_CHAR_SET', SMARTY_MBSTRING ? 'UTF-8' : 'ISO-8859-1');
}
if (!defined('SMARTY_RESOURCE_DATE_FORMAT')) {
    /**
     * @deprecated in favor of Smarty::$_DATE_FORMAT
     */
    define('SMARTY_RESOURCE_DATE_FORMAT', '%b %e, %Y');
}

/**
 * register the class autoloader
 */
if (!defined('SMARTY_SPL_AUTOLOAD')) {
    define('SMARTY_SPL_AUTOLOAD', 0);
}



/**
 * Load always needed external class files
 */
include_once SMARTY_SYSPLUGINS_DIR.'smarty_internal_data.php';
include_once SMARTY_SYSPLUGINS_DIR.'smarty_internal_templatebase.php';
include_once SMARTY_SYSPLUGINS_DIR.'smarty_internal_template.php';
include_once SMARTY_SYSPLUGINS_DIR.'smarty_resource.php';
include_once SMARTY_SYSPLUGINS_DIR.'smarty_internal_resource_file.php';
include_once SMARTY_SYSPLUGINS_DIR.'smarty_cacheresource.php';
include_once SMARTY_SYSPLUGINS_DIR.'smarty_internal_cacheresource_file.php';

/**
 * @package Smarty
 */
class Smarty extends Smarty_Internal_TemplateBase {

    //版本
    const SMARTY_VERSION = 'Smarty-3.1.8';

    //变量作用域
    const SCOPE_LOCAL = 0;
    const SCOPE_PARENT = 1;
    const SCOPE_ROOT = 2;
    const SCOPE_GLOBAL = 3;
    //缓存模式
    const CACHING_OFF = 0;
    const CACHING_LIFETIME_CURRENT = 1;
    const CACHING_LIFETIME_SAVED = 2;
    //编译检测模式
    const COMPILECHECK_OFF = 0;
    const COMPILECHECK_ON = 1;
    const COMPILECHECK_CACHEMISS = 2;
    //PHP代码处理模式
    const PHP_PASSTHRU = 0; //-> 原样输出
    const PHP_QUOTE = 1; //-> 作为html实体引用标记
    const PHP_REMOVE = 2; //-> 从模板中移出标记
    const PHP_ALLOW = 3; //-> 作为PHP代码执行标记
    /**
     * filter types
     */
    const FILTER_POST = 'post';
    const FILTER_PRE = 'pre';
    const FILTER_OUTPUT = 'output';
    const FILTER_VARIABLE = 'variable';
    //插件类别
    const PLUGIN_FUNCTION = 'function';
    const PLUGIN_BLOCK = 'block';
    const PLUGIN_COMPILER = 'compiler';
    const PLUGIN_MODIFIER = 'modifier';
    const PLUGIN_MODIFIERCOMPILER = 'modifiercompiler';


    /**
     * assigned global tpl vars
     */
    public static $global_tpl_vars = array();

    /**
     * error handler returned by set_error_hanlder() in Smarty::muteExpectedErrors()
     */
    public static $_previous_error_handler = null;
    /**
     * contains directories outside of SMARTY_DIR that are to be muted by muteExpectedErrors()
     */
    public static $_muted_directories = array();
    /**
     * Flag denoting if Multibyte String functions are available
     */
    public static $_MBSTRING = SMARTY_MBSTRING;
    /**
     * The character set to adhere to (e.g. "UTF-8")
     */
    public static $_CHARSET = SMARTY_RESOURCE_CHAR_SET;
    /**
     * The date format to be used internally
     * (accepts date() and strftime())
     */
    public static $_DATE_FORMAT = SMARTY_RESOURCE_DATE_FORMAT;
    /**
     * Flag denoting if PCRE should run in UTF-8 mode
     */
    public static $_UTF8_MODIFIER = 'u';

    //是否允许定界符与变量之间有空格，true为不允许，false为允许
    public $auto_literal = true;
    /**
     * display error on not assigned variables
     * @var boolean
     */
    public $error_unassigned = false;
    /**
     * look up relative filepaths in include_path
     * @var boolean
     */
    public $use_include_path = false;
    //模板目录
    private $template_dir = array();
    /**
     * joined template directory string used in cache keys
     * @var string
     */
    public $joined_template_dir = null;
    /**
     * joined config directory string used in cache keys
     * @var string
     */
    public $joined_config_dir = null;
    //默认模板处理函数：获取模板失败时调用
    public $default_template_handler_func = null;
    //默认配置文件处理函数：配置文件出错自动调用
    public $default_config_handler_func = null;
    //默认插件处理函数：插件出错时自动调用
    public $default_plugin_handler_func = null;
    //编译目录
    private $compile_dir = null;
    //插件目录
    private $plugins_dir = array();
    //缓存目录
    private $cache_dir = null;
    //配置文件目录
    private $config_dir = array();
    //强迫编译
    public $force_compile = false;
    //编译检查
    public $compile_check = true;
    //创建编译缓存子目录
    public $use_sub_dirs = false;
    /**
     * allow ambiguous resources (that are made unique by the resource handler)
     * @var boolean
     */
    public $allow_ambiguous_resources = false;
    //是否开启缓存
    public $caching = false;
    /**
     * merge compiled includes
     * @var boolean
     */
    public $merge_compiled_includes = false;
    //缓存时间
    public $cache_lifetime = 3600;
    //强制新建缓存文件
    public $force_cache = false;
    //缓存ID
    public $cache_id = null;
    //编译id
    public $compile_id = null;
    //左右定界符
    public $left_delimiter = "<{";
    public $right_delimiter = "}>";

    /**
     * class name
     *
     * This should be instance of Smarty_Security.
     *
     * @var string
     * @see Smarty_Security
     */
    public $security_class = 'Smarty_Security';
    /**
     * implementation of security class
     *
     * @var Smarty_Security
     */
    public $security_policy = null;
    //如何处理模板中的PHP代码
    public $php_handling = self::PHP_PASSTHRU;
    //是否允许模板中出现PHP代码
    public $allow_php_templates = false;
    /**
     * Should compiled-templates be prevented from being called directly?
     *
     * {@internal
     * Currently used by Smarty_Internal_Template only.
     * }}
     *
     * @var boolean
     */
    public $direct_access_security = true;
    //调试模式
    public $debugging = false;
    //调试控制
    public $debugging_ctrl = 'NONE';
    /**
     * Name of debugging URL-param.
     *
     * Only used when $debugging_ctrl is set to 'URL'.
     * The name of the URL-parameter that activates debugging.
     *
     * @var type
     */
    public $smarty_debug_id = 'SMARTY_DEBUG';
    //调试模板
    public $debug_tpl = null;
    //报错级别设置
    public $error_reporting = null;
    /**
     * Internal flag for getTags()
     * @var boolean
     */
    public $get_used_tags = false;

    /*配置文件相关变量*/

    //配置覆盖
    public $config_overwrite = true;
    //布尔值自动转换
    public $config_booleanize = true;
    //配置文件是否可见
    public $config_read_hidden = false;

    /**#@+
     * resource locking
     */

    /**
     * locking concurrent compiles
     * @var boolean
     */
    public $compile_locking = true;
    /**
     * Controls whether cache resources should emply locking mechanism
     * @var boolean
     */
    public $cache_locking = false;
    /**
     * seconds to wait for acquiring a lock before ignoring the write lock
     * @var float
     */
    public $locking_timeout = 10;

    //全局模板函数
    public $template_functions = array();
    //默认资源类型
    public $default_resource_type = 'file';
    //缓存方式
    public $caching_type = 'file';
    /**
     * internal config properties
     * @var array
     */
    public $properties = array();
    //配置文件类型
    public $default_config_type = 'file';
    /**
     * cached template objects
     * @var array
     */
    public $template_objects = array();
    /**
     * check If-Modified-Since headers
     * @var boolean
     */
    public $cache_modified_check = false;
    /**
     * registered plugins
     * @var array
     */
    public $registered_plugins = array();
    /**
     * plugin search order
     * @var array
     */
    public $plugin_search_order = array('function', 'block', 'compiler', 'class');
    /**
     * registered objects
     * @var array
     */
    public $registered_objects = array();
    /**
     * registered classes
     * @var array
     */
    public $registered_classes = array();
    /**
     * registered filters
     * @var array
     */
    public $registered_filters = array();
    /**
     * registered resources
     * @var array
     */
    public $registered_resources = array();
    /**
     * resource handler cache
     * @var array
     */
    public $_resource_handlers = array();
    /**
     * registered cache resources
     * @var array
     */
    public $registered_cache_resources = array();
    /**
     * cache resource handler cache
     * @var array
     */
    public $_cacheresource_handlers = array();
    /**
     * autoload filter
     * @var array
     */
    public $autoload_filters = array();
    //默认调节器
    public $default_modifiers = array();
    /**
     * autoescape variable output
     * @var boolean
     */
    public $escape_html = false;
    /**
     * global internal smarty vars
     * @var array
     */
    public static $_smarty_vars = array();
    /**
     * start time for execution time calculation
     * @var int
     */
    public $start_time = 0;
    /**
     * default file permissions
     * @var int
     */
    public $_file_perms = 0644;
    /**
     * default dir permissions
     * @var int
     */
    public $_dir_perms = 0771;
    /**
     * block tag hierarchy
     * @var array
     */
    public $_tag_stack = array();
    /**
     * self pointer to Smarty object
     * @var Smarty
     */
    public $smarty;
    /**
     * required by the compiler for BC
     * @var string
     */
    public $_current_file = null;
    /**
     * internal flag to enable parser debugging
     * @var bool
     */
    public $_parserdebug = false;
    /**
     * Saved parameter of merged templates during compilation
     *
     * @var array
     */
    public $merged_templates_func = array();


    public function __construct()
    {
        // selfpointer needed by some other class methods
        $this->smarty = $this;
        if (is_callable('mb_internal_encoding')) {
            mb_internal_encoding(Smarty::$_CHARSET);
        }
        $this->start_time = microtime(true);
        // set default dirs
        $this->setPluginsDir(SMARTY_PLUGINS_DIR);

        $this->debug_tpl = 'file:' . dirname(__FILE__) . '/debug.tpl';
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $this->assignGlobal('SCRIPT_NAME', $_SERVER['SCRIPT_NAME']);
        }
    }

    public function __destruct()
    {
        // intentionally left blank
    }

    public function __clone()
    {
        $this->smarty = $this;
    }


    //检测模板是否存在
    public function templateExists($resource_name)
    {
        // create template object
        $save = $this->template_objects;
        $tpl = new $this->template_class($resource_name, $this);
        // check if it does exists
        $result = $tpl->source->exists;
        $this->template_objects = $save;
        return $result;
    }

    //获取一个或全部全局变量
    public function getGlobal($varname = null)
    {
        if (isset($varname)) {
            if (isset(self::$global_tpl_vars[$varname])) {
                return self::$global_tpl_vars[$varname]->value;
            } else {
                return '';
            }
        } else {
            $_result = array();
            foreach (self::$global_tpl_vars AS $key => $var) {
                $_result[$key] = $var->value;
            }
            return $_result;
        }
    }

    //清空缓存文件夹
    function clearAllCache($exp_time = null, $type = null)
    {
        // load cache resource and call clearAll
        $_cache_resource = Smarty_CacheResource::load($this, $type);
        Smarty_CacheResource::invalidLoadedCache($this);
        return $_cache_resource->clearAll($this, $exp_time);
    }

    //清除缓存
    public function clearCache($template_name, $cache_id = null, $compile_id = null, $exp_time = null, $type = null)
    {
        // load cache resource and call clear
        $_cache_resource = Smarty_CacheResource::load($this, $type);
        Smarty_CacheResource::invalidLoadedCache($this);
        return $_cache_resource->clear($this, $template_name, $cache_id, $compile_id, $exp_time);
    }

    //开启模板安全检测
    public function enableSecurity($security_class = null)
    {
        if ($security_class instanceof Smarty_Security) {
            $this->security_policy = $security_class;
            return $this;
        } elseif (is_object($security_class)) {
            throw new SmartyException("Class '" . get_class($security_class) . "' must extend Smarty_Security.");
        }
        if ($security_class == null) {
            $security_class = $this->security_class;
        }
        if (!class_exists($security_class)) {
            throw new SmartyException("Security class '$security_class' is not defined");
        } elseif ($security_class !== 'Smarty_Security' && !is_subclass_of($security_class, 'Smarty_Security')) {
            throw new SmartyException("Class '$security_class' must extend Smarty_Security.");
        } else {
            $this->security_policy = new $security_class($this);
        }

        return $this;
    }

    //关闭模板安全检测
    public function disableSecurity()
    {
        $this->security_policy = null;

        return $this;
    }

    //设置模板目录
    public function setTemplateDir($template_dir)
    {
        $this->template_dir = array();
        foreach ((array) $template_dir as $k => $v) {
            $this->template_dir[$k] = rtrim($v, '/\\') . DS;
        }

        $this->joined_template_dir = join(DIRECTORY_SEPARATOR, $this->template_dir);
        return $this;
    }

    //添加模板目录
    public function addTemplateDir($template_dir, $key=null)
    {
        // make sure we're dealing with an array
        $this->template_dir = (array) $this->template_dir;

        if (is_array($template_dir)) {
            foreach ($template_dir as $k => $v) {
                if (is_int($k)) {
                    // indexes are not merged but appended
                    $this->template_dir[] = rtrim($v, '/\\') . DS;
                } else {
                    // string indexes are overridden
                    $this->template_dir[$k] = rtrim($v, '/\\') . DS;
                }
            }
        } elseif ($key !== null) {
            // override directory at specified index
            $this->template_dir[$key] = rtrim($template_dir, '/\\') . DS;
        } else {
            // append new directory
            $this->template_dir[] = rtrim($template_dir, '/\\') . DS;
        }
        $this->joined_template_dir = join(DIRECTORY_SEPARATOR, $this->template_dir);
        return $this;
    }

    //获取模板目录
    public function getTemplateDir($index=null)
    {
        if ($index !== null) {
            return isset($this->template_dir[$index]) ? $this->template_dir[$index] : null;
        }

        return (array)$this->template_dir;
    }

    //设置配置文件目录
    public function setConfigDir($config_dir)
    {
        $this->config_dir = array();
        foreach ((array) $config_dir as $k => $v) {
            $this->config_dir[$k] = rtrim($v, '/\\') . DS;
        }

        $this->joined_config_dir = join(DIRECTORY_SEPARATOR, $this->config_dir);
        return $this;
    }

    //添加配置文件目录
    public function addConfigDir($config_dir, $key=null)
    {
        // make sure we're dealing with an array
        $this->config_dir = (array) $this->config_dir;

        if (is_array($config_dir)) {
            foreach ($config_dir as $k => $v) {
                if (is_int($k)) {
                    // indexes are not merged but appended
                    $this->config_dir[] = rtrim($v, '/\\') . DS;
                } else {
                    // string indexes are overridden
                    $this->config_dir[$k] = rtrim($v, '/\\') . DS;
                }
            }
        } elseif( $key !== null ) {
            // override directory at specified index
            $this->config_dir[$key] = rtrim($config_dir, '/\\') . DS;
        } else {
            // append new directory
            $this->config_dir[] = rtrim($config_dir, '/\\') . DS;
        }

        $this->joined_config_dir = join(DIRECTORY_SEPARATOR, $this->config_dir);
        return $this;
    }

    //获取配置文件目录
    public function getConfigDir($index=null)
    {
        if ($index !== null) {
            return isset($this->config_dir[$index]) ? $this->config_dir[$index] : null;
        }

        return (array)$this->config_dir;
    }

    //设置插件目录
    public function setPluginsDir($plugins_dir)
    {
        $this->plugins_dir = array();
        foreach ((array)$plugins_dir as $k => $v) {
            $this->plugins_dir[$k] = rtrim($v, '/\\') . DS;
        }

        return $this;
    }

    //添加插件目录
    public function addPluginsDir($plugins_dir)
    {
        // make sure we're dealing with an array
        $this->plugins_dir = (array) $this->plugins_dir;

        if (is_array($plugins_dir)) {
            foreach ($plugins_dir as $k => $v) {
                if (is_int($k)) {
                    // indexes are not merged but appended
                    $this->plugins_dir[] = rtrim($v, '/\\') . DS;
                } else {
                    // string indexes are overridden
                    $this->plugins_dir[$k] = rtrim($v, '/\\') . DS;
                }
            }
        } else {
            // append new directory
            $this->plugins_dir[] = rtrim($plugins_dir, '/\\') . DS;
        }

        $this->plugins_dir = array_unique($this->plugins_dir);
        return $this;
    }

    //获取插件目录
    public function getPluginsDir()
    {
        return (array)$this->plugins_dir;
    }

    //设置编译目录
    public function setCompileDir($compile_dir)
    {
        $this->compile_dir = rtrim($compile_dir, '/\\') . DS;
        if (!isset(Smarty::$_muted_directories[$this->compile_dir])) {
            Smarty::$_muted_directories[$this->compile_dir] = null;
        }
        return $this;
    }

    //获取编译目录
    public function getCompileDir()
    {
        return $this->compile_dir;
    }

    //设置缓存目录
    public function setCacheDir($cache_dir)
    {
        $this->cache_dir = rtrim($cache_dir, '/\\') . DS;
        if (!isset(Smarty::$_muted_directories[$this->cache_dir])) {
            Smarty::$_muted_directories[$this->cache_dir] = null;
        }
        return $this;
    }

    //获取缓存目录
    public function getCacheDir()
    {
        return $this->cache_dir;
    }

    /*Set default modifiers*/
    public function setDefaultModifiers($modifiers)
    {
        $this->default_modifiers = (array) $modifiers;
        return $this;
    }

    /* Add default modifiers*/
    public function addDefaultModifiers($modifiers)
    {
        if (is_array($modifiers)) {
            $this->default_modifiers = array_merge($this->default_modifiers, $modifiers);
        } else {
            $this->default_modifiers[] = $modifiers;
        }

        return $this;
    }

    /* Get default modifiers*/
    public function getDefaultModifiers()
    {
        return $this->default_modifiers;
    }


    /*Set autoload filters*/
    public function setAutoloadFilters($filters, $type=null)
    {
        if ($type !== null) {
            $this->autoload_filters[$type] = (array) $filters;
        } else {
            $this->autoload_filters = (array) $filters;
        }

        return $this;
    }

    /*Add autoload filters*/
    public function addAutoloadFilters($filters, $type=null)
    {
        if ($type !== null) {
            if (!empty($this->autoload_filters[$type])) {
                $this->autoload_filters[$type] = array_merge($this->autoload_filters[$type], (array) $filters);
            } else {
                $this->autoload_filters[$type] = (array) $filters;
            }
        } else {
            foreach ((array) $filters as $key => $value) {
                if (!empty($this->autoload_filters[$key])) {
                    $this->autoload_filters[$key] = array_merge($this->autoload_filters[$key], (array) $value);
                } else {
                    $this->autoload_filters[$key] = (array) $value;
                }
            }
        }

        return $this;
    }

    /*Get autoload filters*/
    public function getAutoloadFilters($type=null)
    {
        if ($type !== null) {
            return isset($this->autoload_filters[$type]) ? $this->autoload_filters[$type] : array();
        }

        return $this->autoload_filters;
    }

    /*获取调试模板*/
    public function getDebugTemplate()
    {
        return $this->debug_tpl;
    }

    /*设置调试模板*/
    public function setDebugTemplate($tpl_name)
    {
        if (!is_readable($tpl_name)) {
            throw new SmartyException("Unknown file '{$tpl_name}'");
        }
        $this->debug_tpl = $tpl_name;

        return $this;
    }

    /*建立模板对象*/
    public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true)
    {
        if (!empty($cache_id) && (is_object($cache_id) || is_array($cache_id))) {
            $parent = $cache_id;
            $cache_id = null;
        }
        if (!empty($parent) && is_array($parent)) {
            $data = $parent;
            $parent = null;
        } else {
            $data = null;
        }
        // default to cache_id and compile_id of Smarty object
        $cache_id = $cache_id === null ? $this->cache_id : $cache_id;
        $compile_id = $compile_id === null ? $this->compile_id : $compile_id;
        // already in template cache?
        if ($this->allow_ambiguous_resources) {
            $_templateId = Smarty_Resource::getUniqueTemplateName($this, $template) . $cache_id . $compile_id;
        } else {
            $_templateId = $this->joined_template_dir . '#' . $template . $cache_id . $compile_id;
        }
        if (isset($_templateId[150])) {
            $_templateId = sha1($_templateId);
        }
        if ($do_clone) {
            if (isset($this->template_objects[$_templateId])) {
                // return cached template object
                $tpl = clone $this->template_objects[$_templateId];
                $tpl->smarty = clone $tpl->smarty;
                $tpl->parent = $parent;
                $tpl->tpl_vars = array();
                $tpl->config_vars = array();
            } else {
                $tpl = new $this->template_class($template, clone $this, $parent, $cache_id, $compile_id);
            }
        } else {
            if (isset($this->template_objects[$_templateId])) {
                // return cached template object
                $tpl = $this->template_objects[$_templateId];
                $tpl->parent = $parent;
                $tpl->tpl_vars = array();
                $tpl->config_vars = array();
            } else {
                $tpl = new $this->template_class($template, $this, $parent, $cache_id, $compile_id);
            }
        }
        // fill data if present
        if (!empty($data) && is_array($data)) {
            // set up variable values
            foreach ($data as $_key => $_val) {
                $tpl->tpl_vars[$_key] = new Smarty_variable($_val);
            }
        }
        return $tpl;
    }


    /**
     * Takes unknown classes and loads plugin files for them
     * class name format: Smarty_PluginType_PluginName
     * plugin filename format: plugintype.pluginname.php
     *
     * @param string $plugin_name    class plugin name to load
     * @param bool   $check          check if already loaded
     * @return string |boolean filepath of loaded file or false
     */
    public function loadPlugin($plugin_name, $check = true)
    {
        // if function or class exists, exit silently (already loaded)
        if ($check && (is_callable($plugin_name) || class_exists($plugin_name, false))) {
            return true;
        }
        // Plugin name is expected to be: Smarty_[Type]_[Name]
        $_name_parts = explode('_', $plugin_name, 3);
        // class name must have three parts to be valid plugin
        // count($_name_parts) < 3 === !isset($_name_parts[2])
        if (!isset($_name_parts[2]) || strtolower($_name_parts[0]) !== 'smarty') {
            throw new SmartyException("plugin {$plugin_name} is not a valid name format");
            return false;
        }
        // if type is "internal", get plugin from sysplugins
        if (strtolower($_name_parts[1]) == 'internal') {
            $file = SMARTY_SYSPLUGINS_DIR . strtolower($plugin_name) . '.php';
            if (file_exists($file)) {
                require_once($file);
                return $file;
            } else {
                return false;
            }
        }
        // plugin filename is expected to be: [type].[name].php
        $_plugin_filename = "{$_name_parts[1]}.{$_name_parts[2]}.php";
        
        $_stream_resolve_include_path = function_exists('stream_resolve_include_path');

        // loop through plugin dirs and find the plugin
        foreach($this->getPluginsDir() as $_plugin_dir) {
            $names = array(
                $_plugin_dir . $_plugin_filename,
                $_plugin_dir . strtolower($_plugin_filename),
            );
            foreach ($names as $file) {
                if (file_exists($file)) {
                    require_once($file);
                    return $file;
                }
                if ($this->use_include_path && !preg_match('/^([\/\\\\]|[a-zA-Z]:[\/\\\\])/', $_plugin_dir)) {
                    // try PHP include_path
                    if ($_stream_resolve_include_path) {
                        $file = stream_resolve_include_path($file);
                    } else {
                        $file = Smarty_Internal_Get_Include_Path::getIncludePath($file);
                    }
                    
                    if ($file !== false) {
                        require_once($file);
                        return $file;
                    }
                }
            }
        }
        // no plugin loaded
        return false;
    }

    /*编译所有模板*/
    public function compileAllTemplates($extention = '.tpl', $force_compile = false, $time_limit = 0, $max_errors = null)
    {
        return Smarty_Internal_Utility::compileAllTemplates($extention, $force_compile, $time_limit, $max_errors, $this);
    }

    /*编译所有配置文件*/
    public function compileAllConfig($extention = '.conf', $force_compile = false, $time_limit = 0, $max_errors = null)
    {
        return Smarty_Internal_Utility::compileAllConfig($extention, $force_compile, $time_limit, $max_errors, $this);
    }

    /**
     * Delete compiled template file
     *
     * @param string $resource_name template name
     * @param string $compile_id compile id
     * @param integer $exp_time expiration time
     * @return integer number of template files deleted
     */
    public function clearCompiledTemplate($resource_name = null, $compile_id = null, $exp_time = null)
    {
        return Smarty_Internal_Utility::clearCompiledTemplate($resource_name, $compile_id, $exp_time, $this);
    }


    /**
     * Return array of tag/attributes of all tags used by an template
     *
     * @param object $templae template object
     * @return array of tag/attributes
     */
    public function getTags(Smarty_Internal_Template $template)
    {
        return Smarty_Internal_Utility::getTags($template);
    }

    /**
     * Run installation test
     *
     * @param array $errors Array to write errors into, rather than outputting them
     * @return boolean true if setup is fine, false if something is wrong
     */
    public function testInstall(&$errors=null)
    {
        return Smarty_Internal_Utility::testInstall($this, $errors);
    }

    /**
     * Error Handler to mute expected messages
     */
    public static function mutingErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $_is_muted_directory = false;

        // add the SMARTY_DIR to the list of muted directories
        if (!isset(Smarty::$_muted_directories[SMARTY_DIR])) {
            $smarty_dir = realpath(SMARTY_DIR);
            Smarty::$_muted_directories[SMARTY_DIR] = array(
                'file' => $smarty_dir,
                'length' => strlen($smarty_dir),
            );
        }

        // walk the muted directories and test against $errfile
        foreach (Smarty::$_muted_directories as $key => &$dir) {
            if (!$dir) {
                // resolve directory and length for speedy comparisons
                $file = realpath($key);
                $dir = array(
                    'file' => $file,
                    'length' => strlen($file),
                );
            }
            if (!strncmp($errfile, $dir['file'], $dir['length'])) {
                $_is_muted_directory = true;
                break;
            }
        }

        // pass to next error handler if this error did not occur inside SMARTY_DIR
        // or the error was within smarty but masked to be ignored
        if (!$_is_muted_directory || ($errno && $errno & error_reporting())) {
            if (Smarty::$_previous_error_handler) {
                return call_user_func(Smarty::$_previous_error_handler, $errno, $errstr, $errfile, $errline, $errcontext);
            } else {
                return false;
            }
        }
    }

    /**
     * Enable error handler to mute expected messages
     *
     * @return void
     */
    public static function muteExpectedErrors()
    {
        /*
            error muting is done because some people implemented custom error_handlers using
            http://php.net/set_error_handler and for some reason did not understand the following paragraph:

                It is important to remember that the standard PHP error handler is completely bypassed for the
                error types specified by error_types unless the callback function returns FALSE.
                error_reporting() settings will have no effect and your error handler will be called regardless -
                however you are still able to read the current value of error_reporting and act appropriately.
                Of particular note is that this value will be 0 if the statement that caused the error was
                prepended by the @ error-control operator.

            Smarty deliberately uses @filemtime() over file_exists() and filemtime() in some places. Reasons include
                - @filemtime() is almost twice as fast as using an additional file_exists()
                - between file_exists() and filemtime() a possible race condition is opened,
                  which does not exist using the simple @filemtime() approach.
        */
        $error_handler = array('Smarty', 'mutingErrorHandler');
        $previous = set_error_handler($error_handler);

        // avoid dead loops
        if ($previous !== $error_handler) {
            Smarty::$_previous_error_handler = $previous;
        }
    }

    /**
     * Disable error handler muting expected messages
     */
    public static function unmuteExpectedErrors()
    {
        restore_error_handler();
    }
}

// let PCRE (preg_*) treat strings as ISO-8859-1 if we're not dealing with UTF-8
if (Smarty::$_CHARSET !== 'UTF-8') {
    Smarty::$_UTF8_MODIFIER = '';
}

/**
 * Smarty exception class
 */
class SmartyException extends Exception {
}

/**
 * Smarty compiler exception class
 */
class SmartyCompilerException extends SmartyException  {
}



?>
