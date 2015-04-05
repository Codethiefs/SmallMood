<?php
namespace Small\Session;

use Small\Config;

class Redis implements \SessionHandlerInterface
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
        $this->sessionName = $sessName;
        $this->handle = new \Redis;
        $servers = Config::config('REDIS_SERVERS');
        $server = isset($servers['session']) ? $servers['session'] : $servers['default'];
        list($host, $port) = explode(':', $server);
        $this->handle->connect($host, $port);
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
        return $this->handle->setex($this->sessionName . $sessID, $this->lifeTime, $sessData);
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
