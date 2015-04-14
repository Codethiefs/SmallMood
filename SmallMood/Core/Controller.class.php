<?php

namespace Small;


abstract class Controller
{

    /**
     * 视图实例对象
     * @var view
     * @access protected
     */
    protected $view = null;

    /**
     * 控制器参数
     * @var config
     * @access protected
     */
    protected $config = array();

    /**
     * 架构函数 取得模板对象实例
     * @access public
     */
    public function __construct()
    {
        //实例化视图类
        $this->view = View::getInstance();
        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }

    }

    /**
     * 魔术方法 有不存在的操作的时候执行,解析空操作或直接输出模板
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (method_exists($this, '_empty')) {
            // 如果定义了_empty操作 则调用
            $this->_empty($method, $args);
        } elseif (file_exists($this->view->parseTemplate())) {
            // 检查是否存在默认模版 如果有直接输出模版
            $this->display();
        } else {
            die('方法不存在');
        }
    }

    /**
     * 模板主题设置
     * @access protected
     * @param string $theme 模版主题
     * @return Controller
     */
    protected function setTheme($theme)
    {
        $this->view->setTheme($theme);
        return $this;
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param array $data 模板变量数组
     * @return Controller
     */
    protected function assign($data = [])
    {
        $this->view->assign($data);
        return $this;
    }

    protected function setHeaders($headers = [])
    {
        $this->view->setHeader($headers);
        return $this;
    }

    public function setContentType($value)
    {
        $this->view->setHeader(['Content-Type' => $value]);
        return $this;
    }

    /**
     * 禁用缓存
     */
    public function setNoCache()
    {
        $header['Cache-Control'] = 'no-store, no-cache, must-revalidate';
        $header['Pragma'] = 'no-cache';
        $this->view->setHeader($header);
        return $this;
    }

    protected function display($templateFile = '', $prefix = '')
    {
        $this->view->display($templateFile, $prefix);
    }


    protected function fetch($templateFile = '', $prefix = '')
    {
        return $this->view->fetch($templateFile, $prefix);
    }

    protected function buildHtml($htmlFile = '', $htmlPath = '', $templateFile = '', $prefix = '')
    {
        $content = $this->fetch($templateFile, $prefix);
        $htmlpath = !empty($htmlPath) ? $htmlPath : HTML_PATH;
        $htmlFile = $htmlpath . $htmlFile . Config::config('HTML_FILE_SUFFIX');
        Storage::getInstance()->put($htmlFile, $content, 'html');
        return $content;
    }


    public function returnJson($data)
    {
        // 返回JSON数据格式到客户端 包含状态信息
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    public function returnXml($data)
    {
        // 返回xml格式数据
        header('Content-Type:text/xml; charset=utf-8');
        exit(xml_encode($data));
    }

    public function returnJSONP($data, $handler = '')
    {
        // 返回JSON数据格式到客户端 包含状态信息
        header('Content-Type:application/json; charset=utf-8');
        $handler = empty($handler) ? $_GET[Config::config('VAR_JSONP_HANDLER')] : $handler;
        exit($handler . '(' . json_encode($data) . ');');
    }


    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     * @access protected
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     * @return void
     */
    protected function redirect($url, $params = array(), $delay = 0, $msg = '')
    {
        $url = urlgen($url, $params);
        redirect($url, $delay, $msg);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $type 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function error($message = '', $jumpUrl = '', $type = '')
    {
        if ($type) {
            $return = 'return' . ucfirst(strtolower($type));
            $this->$return(['status' => 0, 'error' => true, 'code' => 1000, 'info' => $message]);
        } else {
            $this->jump($message, 0, $jumpUrl, $type);
        }

    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $type 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function success($message = '', $jumpUrl = '', $type = '')
    {
        if ($type) {
            $return = 'return' . ucfirst(strtolower($type));
            $this->$return(['status' => 1, 'error' => false, 'code' => 0, 'info' => $message]);
        } else {
            $this->jump($message, 1, $jumpUrl, $type);
        }
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @access private
     * @return void
     */
    private function jump($message, $status = 1, $jumpUrl = NULL, $delay = NULL)
    {
        $data['status'] = $status;
        $data['message'] = $message;
        // 跳转页面停留时间，如果没有设置，操作成功停留1秒，失败停留3秒；
        $data['delay'] = is_null($delay) ? ($status ? 1 : 3) : $delay;
        $data['jumpUrl'] = is_null($jumpUrl) ? ($status ? $_SERVER["HTTP_REFERER"] : "javascript:history.back(-1);") : $jumpUrl;
        // 提示标题
        $data['msgTitle'] = $status ? '操作成功！' : '操作失败';

        $this->assign($data)->display($status ? Config::config('TMPL_SUCCESS') : Config::config('TMPL_ERROR'));

    }

}

