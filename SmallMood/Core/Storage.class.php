<?php
namespace Small;


class Storage
{

    /**
     * 操作句柄
     * @var string
     * @access protected
     */
    static protected $handler;

    static protected $instance;

    /**
     * 单例
     */
    static public function getInstance($type = 'File', $options = array())
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->setType($type, $options);
        return self::$instance;
    }

    public function setType($type = 'File', $options = array())
    {
        $class = 'Small\\Storage\\' . ucwords($type);
        self::$handler = new $class($options);
    }

    /**
     * 文件内容读取
     * @access public
     * @param string $filename 文件名
     * @return string
     */
    public function read($filename, $type = '')
    {
        return self::$handler->read($filename, $type);
    }

    /**
     * 文件写入
     * @access public
     * @param string $filename 文件名
     * @param string $content 文件内容
     * @return boolean
     */
    public function put($filename, $content, $type = '')
    {
        return self::$handler->put($filename, $content, $type = '');
    }

    /**
     * 文件追加写入
     * @access public
     * @param string $filename 文件名
     * @param string $content 追加的文件内容
     * @return boolean
     */
    public function append($filename, $content, $type = '')
    {
        return self::$handler->append($filename, $content, $type = '');
    }

    /**
     * 加载文件
     * @access public
     * @param string $filename 文件名
     * @param array $vars 传入变量
     * @return void
     */
    public function load($_filename, $vars = null)
    {
        self::$handler->load($_filename, $vars);
    }

    /**
     * 文件是否存在
     * @access public
     * @param string $filename 文件名
     * @return boolean
     */
    public function exists($filename)
    {
        return self::$handler->exists($filename);
    }

    /**
     * 文件删除
     * @access public
     * @param string $filename 文件名
     * @return boolean
     */
    public function unlink($filename)
    {
        return self::$handler->unlink($filename);
    }

    /**
     * 读取文件信息
     * @access public
     * @param string $filename 文件名
     * @param string $name 信息名 mtime或者content
     * @return boolean
     */
    public function get($filename, $name)
    {
        return self::$handler->get($filename, $name);
    }

}
