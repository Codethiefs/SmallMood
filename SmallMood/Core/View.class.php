<?php
namespace Small;
/**
 * 视图类
 */
class View
{
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


    protected static $instance;

    /**
     * 单例
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 设置当前输出的模板主题
     * @access public
     * @param  mixed $theme 主题名称
     * @return View
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * 模板变量赋值
     * @access public
     * @param array $data
     */
    public function assign(Array $data)
    {
        $this->vars = array_merge($this->vars, $data);
    }

    public function setHeader(Array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function display($templateFile = '')
    {
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
    private function render($content)
    {
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
    public function fetch($templateFile = '', $prefix = '')
    {
        $templateFile = $this->parseTplPath($templateFile);
        // 模板文件不存在
        if (!is_file($templateFile)) {
            die('模板不存在:' . $templateFile);
        }

        // 页面缓存
        ob_start();
        ob_implicit_flush(0);
        // 使用PHP原生模板
        if (!Config::config('TMPL_USE_ENGINE')) {
            // 模板阵列变量分解成为独立变量
            extract($this->vars, EXTR_OVERWRITE);
            // 直接载入PHP模板
            include $templateFile;
        } else {
            // 解析标签
            Template::getInstance()->setTplFile($templateFile)->setTplVars($this->vars)->fetch($prefix);
        }
        // 获取并清空缓存
        $content = ob_get_clean();
        // 输出模板文件
        return $content;
    }

    /**
     * 自动定位模板文件
     * @access protected
     * @param string $template 模板文件规则(/module/controller/action)
     * @return string
     */
    public function parseTplPath($template = '')
    {
        if (is_file($template)) {
            return $template;
        }

        $theme = $this->theme ? $this->theme : Config::config('TMPL_THEME');
        $template = explode('/', trim($template, '/'));
        $module = empty($template[0]) ? MODULE_NAME : array_shift($template);
        $controller = empty($template[0]) ? CONTROLLER_NAME : array_shift($template);
        $action = empty($template[0]) ? ACTION_NAME : array_shift($template);

        $subdir = count($template) ? implode('/', $template) : '';

        $tplPath = APP_PATH . $module . '/View/' . ($theme ? $theme . '/' : '') . $controller . '/' . $action . ($subdir ? '/' . $subdir : '') . Config::config('TMPL_SUFFIX');

        return $tplPath;
    }


}