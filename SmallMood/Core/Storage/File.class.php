<?php
namespace Small\Storage;

use Small\Storage;

class File
{

    private $contents = array();

    /**
     * 架构函数
     * @access public
     */
    public function __construct()
    {
    }

    /**
     * 文件内容读取
     * @access public
     * @param string $filename 文件名
     * @return string
     */
    public function read($filename)
    {
        return $this->get($filename, 'content');
    }

    /**
     * 文件写入
     * @access public
     * @param string $filename 文件名
     * @param string $content 文件内容
     * @return boolean
     */
    public function put($filename, $content)
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (false === file_put_contents($filename, $content)) {
            die('写文件失败');
        } else {
            $this->contents[$filename] = $content;
            return true;
        }
    }

    /**
     * 文件追加写入
     * @access public
     * @param string $filename 文件名
     * @param string $content 追加的文件内容
     * @return boolean
     */
    public function append($filename, $content)
    {
        if (is_file($filename)) {
            $content = $this->read($filename) . $content;
        }
        return $this->put($filename, $content);
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
        if (!is_null($vars)) {
            extract($vars, EXTR_OVERWRITE);
        }
        include $_filename;
    }

    /**
     * 文件是否存在
     * @access public
     * @param string $filename 文件名
     * @return boolean
     */
    public function exists($filename)
    {
        return is_file($filename);
    }

    /**
     * 文件删除
     * @access public
     * @param string $filename 文件名
     * @return boolean
     */
    public function unlink($filename)
    {
        unset($this->contents[$filename]);
        return is_file($filename) ? unlink($filename) : false;
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
        if (!isset($this->contents[$filename])) {
            if (!is_file($filename)) {
                return false;
            }
            $this->contents[$filename] = file_get_contents($filename);
        }
        $info = array(
            'mtime' => filemtime($filename),
            'content' => $this->contents[$filename],
        );
        return $info[$name];
    }
}
