<?php
namespace Small\Session;

use Small\Config;
use Small\Pool;

class Memcache implements \SessionHandlerInterface
{
    protected $lifeTime = 3600;
    protected $sessionName = '';
    protected $handle = null;

    /**
     * 打开Session
     * @access public
     * @param string $savePath
     * @param mixed $sessName
     */
    public function open($savePath, $sessName)
    {
        $options = Config::config('SESSION_OPTIONS');
        $this->lifeTime = (isset($options['expire']) && $options['expire']) ? $options['expire'] : $this->lifeTime;
        $this->sessionName  = $sessName;
        $this->handle = Pool::createMemcachePool(true);
        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handle->close();
        $this->handle = null;
        return true;
    }

    /**
     * 读取Session
     * @access public
     * @param string $sessID
     */
    public function read($sessID)
    {
        return $this->handle->get($this->sessionName . $sessID);
    }

    /**
     * 写入Session
     * @access public
     * @param string $sessID
     * @param String $sessData
     */
    public function write($sessID, $sessData)
    {
        return $this->handle->set($this->sessionName . $sessID, $sessData, 0, $this->lifeTime);
    }

    /**
     * 删除Session
     * @access public
     * @param string $sessID
     */
    public function destroy($sessID)
    {
        return $this->handle->delete($this->sessionName . $sessID);
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param string $sessMaxLifeTime
     */
    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}
