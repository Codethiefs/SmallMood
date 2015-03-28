<?php

class smallmemcache {
	
	static public function instance(){
		if (extension_loaded('memcache'))
		{
			$mem = new memcache;
			debug::addmsg('memcache扩展库存在，实例化memcache成功！',1);
		}else
		{
			$mem = new memcachedclient();
			debug::addmsg('memcache扩展库不存在，将尝试使用memcache-client,以下方法getExtendedStats,getServerStatus,getStats,getVersion,setServerParams,close,flush将不被支持！',1);
		}
		$servers = array();
		if (empty($GLOBALS['memservers']))
		{
			$servers[] = array('localhost',11211);
		}else {
			$servers = $GLOBALS['memservers'];
		}
		
		foreach ($servers as $server)
		{
			call_user_func_array(array($mem,'addServer'), $server);
		}
		
		return $mem;
	}
}