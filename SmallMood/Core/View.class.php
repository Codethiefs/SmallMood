<?php
namespace Small;

use Small\Cache\File;

/**
 * 视图类
 */
class View {
    /**
     * 模板输出变量
     */
    protected $vars = [];

    /**
     * http头
     */
    protected $headers = [];

    /**
     * 模板主题
     */
    protected $theme = '';

    /**
     * 模板后缀
     * @var string
     */
    protected $tplSuffix = '.html';

    /**
     * 开启缓存
     * @var bool
     */
    protected $cacheOn = true;

    /**
     * 缓存文件后缀
     * @var string
     */
    protected $cacheSuffix = '.php';

    /**
     * 缓存文件存放位置
     * @var string
     */
    protected $cachePath = '';

    /**
     * 模板缓存时间
     * @var int
     */
    protected $cacheTime = 0;

    /**
     * 左分界符
     * @var string
     */
    protected $tplBegin = '\{';

    /**
     * 右分界符
     * @var string
     */
    protected $tplEnd = '\}';

    /**
     * 是否去除模板文件里面的html空格与换行
     * @var bool
     */
    protected $stripSpace = true;

    /**
     * 模板内容
     * @var string
     */
    protected $tplContent = '';

    /**
     * 模板中block区块
     * @var array
     */
    protected $block = [];

    /**
     * 原样输出标签
     * @var array
     */
    protected $literal = [];

    protected $tempVar = [];


    /**
     * 单例
     * @var
     */
    protected static $instance;

    /**
     * 单例
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // 读取模板主题配置
        $this->theme = Config::config('TMPL_THEME');
        // 模板文件后缀
        $this->tplSuffix = Config::config('TMPL_TEMPLATE_SUFFIX');
        // 缓存文件后缀
        $this->cacheSuffix = Config::config('TMPL_CACHFILE_SUFFIX');
        // 是否开启模板缓存
        $this->cacheOn = Config::config('TMPL_CACHE_ON');
        // 缓存文件位置
        $this->cachePath = CACHE_PATH . MODULE_NAME . DIRECTORY_SEPARATOR;
        // 缓存时间
        $this->cacheTime = Config::config('TMPL_CACHE_TIME');
        // 模板变量左分界符
        $this->tplBegin = preg_quote(Config::config('TMPL_L_DELIM'));
        // 模板变量右分界符
        $this->tplEnd = preg_quote(Config::config('TMPL_R_DELIM'));
        // 是否去除模板文件里面的html空格与换行
        $this->stripSpace = Config::config('TMPL_STRIP_SPACE');

    }

    /**
     * 设置当前输出的模板主题
     * @access public
     * @param  mixed $theme 主题名称
     * @return View
     */
    public function setTheme($theme) {
        $this->theme = $theme;
    }

    /**
     * 设置http头信息
     *
     * @param array $headers
     */
    public function setHeader(Array $headers) {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * 模板变量赋值
     *
     * @access public
     * @param array $data
     */
    public function assign(Array $data) {
        $this->vars = array_merge($this->vars, $data);
    }

    /**
     * 加载模板和页面输出
     * @param string $templateFile
     */
    public function display($templateFile = '') {
        // 解析并获取模板内容
        $content = $this->fetch($templateFile);
        // 输出模板内容
        $this->render($content);
    }

    /**
     * 输出内容文本可以包括Html
     * @access private
     * @param string $content 输出内容
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @return mixed
     */
    public function render($content) {
        // 网页字符编码
        foreach ($this->headers as $meta => $value) {
            header($meta . ': ' . $value);
        }
        // 输出模板文件
        echo $content;
    }

    /**
     * 解析和获取模板内容 用于输出
     * @access public
     * @param string $templateFile 模板文件名
     * @param string $prefix 模板缓存前缀
     * @return string
     */
    public function fetch($templateFile = '', $prefix = '') {
        $templateFile = $this->parseTplPath($templateFile);
        // 模板文件不存在
        if (!is_file($templateFile)) {
            die('模板不存在:' . $templateFile);
        }
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);
        // 读取模板内容
        $tplContent = file_get_contents($templateFile);
        // 编译模板内容
        $tplContent = $this->compiler($tplContent);
        // 生成缓存文件位置
        $tplCacheFile = $this->cachePath . $prefix . md5($templateFile) . $this->cacheSuffix;
        // 编绎好的内容存入缓存文件
        Storage::getInstance()->put($tplCacheFile, trim($tplContent));
        // 加载编绎好的模板
        Storage::getInstance()->load($tplCacheFile, $this->vars);
        // 获取并清空缓存
        $content = ob_get_clean();
        // 输出模板文件
        return $content;
    }


    /**
     * 模板编绎
     *
     * @param $templateFile
     * @return bool|mixed|string
     */
    public function compiler($tplContent) {
        // 内容为空不解析
        if (empty($tplContent)) {
            return '';
        }

        // 解析标签
        $content = $this->parse($tplContent);

        // 添加安全代码
        $content = '<?php if (!defined(\'SMALL_PATH\')) exit();?>' . $content;
        // 优化生成的php代码
        $content = str_replace('?><?php', '', $content);
        return $content;
    }

    public function parse($tplContent) {
        // 整合 extend 继承及 include 语法包含的文件
        $content = $this->merge($tplContent);

        // 检查PHP语法
        $content = $this->parsePHP($content);

        // 首先替换literal标签内容
        $content = preg_replace_callback('/<literal>(.*?)<\/literal>/is', [$this, 'storeLiteral'], $content);

        // 内置标签库解析
        $this->parseTagLib($content);

        //解析普通模板标签 {$tagName}
        $content = preg_replace_callback('/(' . $this->tplBegin . ')([^\d\w\s' . $this->tplBegin . $this->tplEnd . '].+?)(' . $this->tplEnd . ')/is', [$this, 'parseTag'], $content);

        // 还原被替换的Literal标签
        $content = preg_replace_callback('/<!--###literal(\d+)###-->/is', [$this, 'restoreLiteral'], $content);

        return $content;
    }

    /**
     * 解析 extend include标签
     * @param $tplContent
     * @return bool|mixed|string
     */
    private function merge($tplContent) {
        // 解析继承的布局文件
        $content = $this->parseExtend($tplContent);
        // 检查include语法
        $content = $this->parseInclude($content);
        return $content;
    }

    // 解析模板中的extend标签
    private function parseExtend($content) {
        // 读取模板中的继承标签
        $find = preg_match('/<extend\s(.+?)\s*?\/>/is', $content, $extendInfo);
        if (!$find) {
            return $content;
        }
        //替换extend标签
        $content = str_replace($extendInfo[0], '', $content);
        // 记录页面中的block标签
        preg_replace_callback('/<block\sname=[\'"](.+?)[\'"]\s*?>(.*?)<\/block>/is', [$this, 'storeBlock'], $content);

        // 分析继承的模板信息
        $extendInfo = $this->parseAttrs($extendInfo[1]);
        // 解析继承模板路径
        $extendFile = $this->parseTplPath($extendInfo['file']);
        if (!file_exists($extendFile)) {
            die('被继承的文件不存在：' . $extendFile);
        }
        // 读取继承模板
        $content = file_get_contents($extendFile);
        //对继承模板中的include进行分析
        $content = $this->parseInclude($content);
        // 替换block标签
        $content = $this->replaceBlock($content);
        return $content;

    }

    /**
     * 解析模板中的include标签
     * @param $content
     * @return mixed
     */
    private function parseInclude($content) {
        // 读取模板中的include标签
        $find = preg_match_all('/<include\s(.+?)\s*?\/>/is', $content, $matches);
        if (!$find) {
            return $content;
        }
        // 用include的文件内容替换include标签
        for ($i = 0; $i < $find; $i++) {
            $includeInfo = $this->parseAttrs($matches[1][$i]);
            if (!isset($includeInfo['file'])) {
                die('include 标签错误：' . htmlspecialchars($matches[0][$i]));
            }
            $content = str_replace($matches[0][$i], $this->parseIncludeItem($includeInfo), $content);
        }
        return $content;
    }

    /**
     * 加载公共模板并缓存 和当前模板在同一路径，否则使用相对路径
     */
    private function parseIncludeItem($includeInfo) {
        // 分析模板文件名并读取内容
        $includeFile = $this->parseTplPath($includeInfo['file']);
        if (!file_exists($includeFile)) {
            die('包含的模板文件不存在：' . $includeFile);
        }
        $includeContent = file_get_contents($includeFile);
        // 替换变量
        unset($includeInfo['file']);
        foreach ($includeInfo as $key => $val) {
            $includeContent = str_replace('[' . $key . ']', $val, $includeContent);
        }
        // 再次对包含文件进行模板分析
        $includeContent = $this->parseExtend($includeContent);
        $includeContent = $this->parseInclude($includeContent);
        return $includeContent;
    }

    /**
     * 分析XML属性
     * @access private
     * @param string $attrs XML属性字符串
     * @return array
     */
    private function parseAttrs($attrs) {
        $xml = '<tag ' . $attrs . ' />';
        $xml = simplexml_load_string($xml);
        if (!$xml) {
            die('XML属性错误');
        }
        $attributes = (array)($xml->attributes());
        return array_change_key_case($attributes['@attributes']);
    }

    /**
     * 检查PHP语法
     * @param $content
     * @return mixed
     */
    private function parsePHP($content) {
        // 开启短标签的情况要将 <? 标签替换为 <?php echo方式输出
        if (ini_get('short_open_tag')) {
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);
        }
        return $content;
    }

    /**
     * 保存模板中的 block 标签
     * @param $match
     * @return string
     */
    private function storeBlock($match) {
        $this->block[$match[1]] = $match[2];
        return '';
    }

    /**
     * 保存页面中的literal标签
     * @access private
     * @param string $content 模板内容
     * @return string|false
     */
    private function storeLiteral($match) {
        $content = $match[1];
        if (trim($content) == '') {
            return '';
        }
        $i = count($this->literal);
        $parseStr = "<!--###literal{$i}###-->";
        $this->literal[$i] = $content;
        return $parseStr;
    }


    /**
     * 替换继承模板中的block标签
     * @access private
     * @param string $content 模板内容
     * @return string
     */
    private function replaceBlock($content) {
        static $parse = 0;
        $reg = '/(<block\sname=[\'"](.+?)[\'"]\s*?>)(.*?)<\/block>/is';
        if (is_string($content)) {
            do {
                $content = preg_replace_callback($reg, [$this, 'replaceBlock'], $content);
            } while ($parse && $parse--);
            return $content;
        } elseif (is_array($content)) {
            if (preg_match('/<block\sname=[\'"](.+?)[\'"]\s*?>/is', $content[3])) { //存在嵌套，进一步解析
                $parse = 1;
                $content[3] = preg_replace_callback($reg, [$this, 'replaceBlock'], "{$content[3]}</block>");
                return $content[1] . $content[3];
            } else {
                $name = $content[2];
                $content = $content[3];
                $content = isset($this->block[$name]) ? $this->block[$name] : $content;
                return $content;
            }
        }
    }


    /**
     * 还原 literal 内容
     * @param $match
     * @return mixed
     */
    private function restoreLiteral($match) {
        $tag = $match[1];
        $parseStr = $this->literal[$tag];
        unset($this->literal[$tag]);
        return $parseStr;
    }


    /**
     * 自动定位模板文件
     * @access protected
     * @param string $template 模板文件规则([module@theme]:[controller]:[action])
     * @return string
     */
    private function parseTplPath($templateFile = '') {
        // 使用:分解为数组
        $template = empty($templateFile) ? [] : explode(':', trim($templateFile, ':'));
        // 由尾向头解析
        $action = empty($template) ? ACTION_NAME : array_pop($template);
        $controller = empty($template) ? CONTROLLER_NAME : array_pop($template);
        // 解析模块和主题
        if (!empty($template)) {
            $temp = explode('@', array_pop($template));
            $module = empty($temp[0]) ? MODULE_NAME : $temp[0];
            $theme = empty($temp[1]) ? $this->theme : $temp[1];
        } else {
            $module = MODULE_NAME;
            $theme = $this->theme;
        }

        $tplPath = APP_PATH . $module . '/View/' . ($theme ? $theme . '/' : '') . $controller . '/' . $action . $this->tplSuffix;

        return $tplPath;
    }


    /**
     * TagLib库解析
     */
    private function parseTagLib(&$content) {
        $tagLib = TagLib::getInstance();

        foreach ($tagLib->getTags() as $tag => $val) {
            // 嵌套层次
            $level = isset($val['level']) ? $val['level'] : 1;
            // 是否闭合标签
            $closeTag = isset($val['close']) ? $val['close'] : true;

            // 匹配属性的正则
            $attrReg = empty($val['attr']) ? '(\s*?)' : '\s([^>]*)';

            $that = $this;
            if (!$closeTag) {
                $patterns = '/<' . $tag . $attrReg . '\/(\s*?)>/is';
                $content = preg_replace_callback($patterns, function ($matches) use ($tag, $that) {
                    return $that->parseXmlTag($tag, $matches[1], $matches[2]);
                }, $content);
            } else {
                $patterns = '/<' . $tag . $attrReg . '>(.*?)<\/' . $tag . '(\s*?)>/is';
                for ($i = 0; $i < $level; $i++) {
                    $content = preg_replace_callback($patterns, function ($matches) use ($tag, $that) {
                        return $that->parseXmlTag($tag, $matches[1], $matches[2]);
                    }, $content);
                }
            }

        }
    }

    /**
     * 解析标签库的标签
     * 需要调用对应的标签库文件解析类
     * @access public
     * @param string $tag 标签名
     * @param string $attr 标签属性
     * @param string $content 标签内容
     * @return string|false
     */
    private function parseXmlTag($tag, $attr, $content) {
        $tagLib = TagLib::getInstance();
        $parse = '_' . $tag;
        $content = trim($content);
        $attrs = $this->parseAttrs($attr);
        return $tagLib->$parse($attrs, $content);
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access public
     * @param string $tagStr 标签内容
     * @return string
     */
    private function parseTag($tagStr) {
        if (is_array($tagStr)) {
            $tagStr = $tagStr[2];
        }
        $tagStr = stripslashes($tagStr);
        $flag = substr($tagStr, 0, 1);
        $flag2 = substr($tagStr, 1, 1);
        $name = substr($tagStr, 1);
        if ('$' == $flag && '.' != $flag2 && '(' != $flag2) { //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        } elseif ('-' == $flag || '+' == $flag) { // 输出计算
            return '<?php echo ' . $flag . $name . ';?>';
        } elseif (':' == $flag) { // 输出某个函数的结果
            return '<?php echo ' . $name . ';?>';
        } elseif ('~' == $flag) { // 执行某个函数
            return '<?php ' . $name . ';?>';
        } elseif (substr($tagStr, 0, 2) == '//' || (substr($tagStr, 0, 2) == '/*' && substr(rtrim($tagStr), -2) == '*/')) {
            //注释标签
            return '';
        }
        // 未识别的标签直接返回
        return Config::config('TMPL_L_DELIM') . $tagStr . Config::config('TMPL_R_DELIM');
    }

    /**
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $varStr 变量数据
     * @return string
     */
    private function parseVar($varStr) {
        $varStr = trim($varStr);
        static $_varParseList = [];
        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($_varParseList[$varStr])) {
            return $_varParseList[$varStr];
        }
        $parseStr = '';
        if (!empty($varStr)) {
            $varArray = explode('|', $varStr);
            //取得变量名称
            $var = array_shift($varArray);
            $name = $this->autoBuildVar($var);
            //对变量使用函数
            if (count($varArray) > 0) {
                $name = $this->parseVarFunction($name, $varArray);
            }
            $parseStr = '<?php echo (' . $name . '); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    public function autoBuildVar($var) {
        if ('Small.' == substr($var, 0, 6)) {
            // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
            $name = $this->parseSmallVar($var);
        } elseif (false !== strpos($var, '.')) {
            //支持 {$var.property}
            $vars = explode('.', $var);
            $var = array_shift($vars);
            $name = '$' . $var;
            foreach ($vars as $key => $val) {
                $name .= '["' . $val . '"]';
            }
        } elseif (false !== strpos($var, '[')) {
            //支持 {$var['key']} 方式输出数组
            $name = "$" . $var;
            preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
            $var = $match[1];
        } elseif (false !== strpos($var, ':') && false === strpos($var, '(') && false === strpos($var, '::') && false === strpos($var, '?')) {
            //支持 {$var:property} 方式输出对象的属性
            $vars = explode(':', $var);
            $var = str_replace(':', '->', $var);
            $name = "$" . $var;
            $var = $vars[0];
        } else {
            $name = "$$var";
        }
        return $name;
    }

    /**
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $name 变量名
     * @param array $varArray 函数列表
     * @return string
     */
    public function parseVarFunction($name, $varArray) {
        //对变量使用函数
        $length = count($varArray);
        for ($i = 0; $i < $length; $i++) {
            $args = explode('=', $varArray[$i], 2);
            //模板函数过滤
            $fun = trim($args[0]);
            switch ($fun) {
                case 'default':  // 特殊模板函数
                    $name = '(isset(' . $name . ') && (' . $name . ' !== ""))?(' . $name . '):' . $args[1];
                    break;
                default:
                    // 通用模板函数
                    if (isset($args[1])) {
                        if (strstr($args[1], '###')) {
                            $args[1] = str_replace('###', $name, $args[1]);
                            $name = "$fun($args[1])";
                        } else {
                            $name = "$fun($name,$args[1])";
                        }
                    } else if (!empty($args[0])) {
                        $name = "$fun($name)";
                    }
            }
        }
        return $name;
    }

    /**
     * 特殊模板变量解析
     * 格式 以 $Think. 打头的变量属于特殊模板变量
     * @access public
     * @param string $varStr 变量字符串
     * @return string
     */
    public function parseSmallVar($varStr) {
        $vars = explode('.', $varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if (count($vars) >= 3) {
            $vars[2] = trim($vars[2]);
            switch ($vars[1]) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . strtoupper($vars[2]) . '\']';
                    break;
                case 'GET':
                    $parseStr = '$_GET[\'' . $vars[2] . '\']';
                    break;
                case 'POST':
                    $parseStr = '$_POST[\'' . $vars[2] . '\']';
                    break;
                case 'COOKIE':
                    if (isset($vars[3])) {
                        $parseStr = '$_COOKIE[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } else {
                        $parseStr = 'cookie(\'' . $vars[2] . '\')';
                    }
                    break;
                case 'SESSION':
                    if (isset($vars[3])) {
                        $parseStr = '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } else {
                        $parseStr = 'session(\'' . $vars[2] . '\')';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . strtoupper($vars[2]) . '\']';
                    break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\'' . $vars[2] . '\']';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);
                    break;
                case 'LANG':
                    $parseStr = 'L("' . $vars[2] . '")';
                    break;
                case 'CONFIG':
                    if (isset($vars[3])) {
                        $vars[2] .= '.' . $vars[3];
                    }
                    $parseStr = 'C("' . $vars[2] . '")';
                    break;
                default:
                    break;
            }
        } else if (count($vars) == 2) {
            switch ($vars[1]) {
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'THINK_VERSION';
                    break;
                case 'TEMPLATE':
                    $parseStr = "'" . $this->templateFile . "'";//'C("TEMPLATE_NAME")';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if (defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }


}