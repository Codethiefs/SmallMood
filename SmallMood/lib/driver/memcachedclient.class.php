<?php

/* Flag: indicates data is serialized*/
define("MEMCACHE_SERIALIZED", 1<<0);

/* Flag: indicates data is compressed*/
define("MEMCACHE_COMPRESSED", 1<<1);

/* Minimum savings to store data compressed*/
define("COMPRESSION_SAVINGS", 0.20);


class memcachedclient
{
   /*启动压缩阈值*/
   private $_compress_threshold = 10240;
   /* 是否持久连接 */
   private $_persistant = true;
   /* 当只有一台服务器时; 要连接的 ip:port*/
   private $_single_sock;
   /*服务器池：Array containing ip:port or array(ip:port, weight)*/
   private $_servers;
   //服务器总数
   private $_active;
   //缓存连接的socket
   private $_cache_sock = array();
   //处于休眠状态的服务器
   private $_host_dead = array();
   /**
    * Our bit buckets
    * @param     array
    */
   private $_buckets;
   
   /**
    * Total # of bit buckets we have
    *
    * @param     interger
    * @access  private
    */
   private $_bucketcount;
   
   

   /* 初始化    */
   public function __instruct ()
   {
   }

   /* 添加memcache服务器*/
   public function addServer ($host,$port,$weight=null)
   {
   		if (is_null($weight))
   		{
   			$this->_servers[] = $host.':'.$port;
   		}else 
   		{
   			$this->_servers[] = array($host.':'.$port,$weight);
   		}
	   	
	   	$this->_active = count($this->_servers);
	   	$this->_buckets = null;
	   	$this->_bucketcount = 0;
	   	$this->_single_sock = null;
	   	
	   	if ($this->_active == 1)
	   		$this->_single_sock = $this->_servers[0];
   }
   
   /**
    * 向服务器添加键值对
    * @param   string   $key     键
    * @param   mixed    $val     值
    * @param   interger $exp     超时
    * @param   bool     $compress     是否压缩
    * @return  boolean
    */
   public function add ($key, $val, $com=0, $exp = 0)
   {
      return $this->_set('add', $key, $val, $com, $exp);
   }
   
   /**
    * 向key存储一个元素值为 var（不论key是不是已存在）
    * @param   string   $key     键
    * @param   mixed    $val     值
    * @param   interger $exp     超时
    * @param   bool     $compress     是否压缩
    * @return  boolean
    */
   public function set ($key, $value, $com=0, $exp=0)
   {
   	return $this->_set('set', $key, $value, $com=0, $exp);
   }
   
   /**
    * 从服务器取回key对应的值
    */
   
   public function get($key)
   {
   		if (!$this->_active)
   			return false;
   		
	   	if (is_string($key)) {
	   		return $this->getone($key);
	   	}elseif (is_array($key)) {
	   		return $this->getmulti($key);
	   	}else
	   	{
	   		return false;
	   	}
   	
   }
   
   /**
    * 从服务端删除一个元素
    * @param   string   $key     要删除的键
    * @param   interger $time    多长时间后删除
    * @return  boolean  TRUE on success, FALSE on failure
    */
   public function delete ($key, $timeout = 0)
   {
   	if (!$this->_active)
   		return false;
   	 
   	$sock = $this->get_sock($key);
   	if (!is_resource($sock))
   		return false;
   
   	$key = is_array($key) ? $key[1] : $key;
   
   	$cmd = "delete $key $timeout\r\n";
   	if(!fwrite($sock, $cmd, strlen($cmd)))
   	{
   		$this->_dead_sock($sock);
   		return false;
   	}
   	$res = trim(fgets($sock));
   
   	debug::addmsg("MemCache: delete $key ($res)\n", 1);
   
   	if ($res == "DELETED")
   		return true;
   	return false;
   }
   
   /**
    * Overwrites an existing value for key; only works if key is already set
    * @param   string   $key     Key to set value as
    * @param   mixed    $value   Value to store
    * @param   interger $exp     (optional) Experiation time
    * @return  boolean
    */
   public function replace ($key, $value,$com, $exp=0)
   {
   	return $this->_set('replace', $key, $value,$com, $exp);
   }
    
   /**
    * Increments $key (optionally) by $amt
    * @param   string   $key     Key to increment
    * @param   interger $amt     (optional) amount to increment
    * @return  interger New key value?
    */
   public function increment ($key, $amt=1)
   {
   	return $this->_incrdecr('incr', $key, $amt);
   }
   
   /**
    * Decriments $key (optionally) by $amt
    * @param   string   $key     Key to decriment
    * @param   interger $amt     (optional) Amount to decriment
    * @return  mixed    FALSE on failure, value on success
    */
   public function decrement ($key, $amt=1)
   {
      return $this->_incrdecr('decr', $key, $amt);
   }


   /*
   * 开启大值自动压缩
   * @param integer bytes 控制多大值进行自动压缩
   *  */
   public function setCompressThreshold($bytes)
   {
   	$this->_compress_threshold = $bytes;
   }

   
   
   
   /**
    * 断开所有socket连接
    */
   public function disconnect_all ()
   {
      foreach ($this->_cache_sock as $sock)
         fclose($sock);
      $this->_cache_sock = array();
   }

   
   /* 取回一个值 */
   private function getone ($key)
   {
   	$sock = $this->get_sock($key);
   	 
   	if (!is_resource($sock))
   		return false;
   	 
   	 
   	$cmd = "get $key\r\n";
   	if (!fwrite($sock, $cmd, strlen($cmd)))
   	{
   		$this->_dead_sock($sock);
   		return false;
   	}
   	 
   	$val = array();
   	$this->_load_items($sock, $val);
   	 
   	if (defined('DEBUG')&&DEBUG==1)
   		foreach ($val as $k => $v)
   		debug::addmsg("MemCache: sock $sock got $k => $v\r\n", 1 );
   	 
   	return $val[$key];
   }
   
    
   /**
    * Get multiple keys from the server(s)
    * @param   array    $keys    Keys to retrieve
    * @return  array
    */
   private function getmulti ($keys)
   {
   	foreach ($keys as $key)
   	{
   		$sock = $this->get_sock($key);
   		if (!is_resource($sock)) continue;
   		$key = is_array($key) ? $key[1] : $key;
   		if (!isset($sock_keys[$sock]))
   		{
   			$sock_keys[$sock] = array();
   			$socks[] = $sock;
   		}
   		$sock_keys[$sock][] = $key;
   	}
   	// Send out the requests
   	foreach ($socks as $sock)
   	{
   		$cmd = "get";
   		foreach ($sock_keys[$sock] as $key)
   		{
   			$cmd .= " ". $key;
   		}
   		$cmd .= "\r\n";
   
   		if (fwrite($sock, $cmd, strlen($cmd)))
   		{
   			$gather[] = $sock;
   		} else
   		{
   			$this->_dead_sock($sock);
   		}
   	}
   	 
   	// Parse responses
   	$val = array();
   	foreach ($gather as $sock)
   	{
   		$this->_load_items($sock, $val);
   	}
   	 
   	if (defined('DEBUG')&&DEBUG==1)
   	{
   		foreach ($val as $k => $v)
   			{
   				debug::addmsg("MemCache: got $k => $v\r\n", 1);
   			}
   	}
   	
   	 
   	return $val;
   }
   
   
   /**
    * Close the specified socket
    * @param   string   $sock    Socket to close
    */
   private function _close_sock ($sock)
   {
      $host = array_search($sock, $this->_cache_sock);
      fclose($this->_cache_sock[$host]);
      unset($this->_cache_sock[$host]);
   }


   /**
    * Connects $sock to $host, timing out after $timeout
    * @param   interger $sock    Socket to connect
    * @param   string   $host    Host:IP to connect to
    * @param   float    $timeout (optional) Timeout value, defaults to 0.25s
    * @return  boolean
    */
   private function _connect_sock (&$sock, $host, $timeout = 0.25)
   {
      list ($ip, $port) = explode(":", $host);
      if ($this->_persistant == 1)
      {
         $sock = @pfsockopen($ip, $port, $errno, $errstr, $timeout);
      } else
      {
         $sock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
      }
      
      if (!$sock)
         return false;
      return true;
   }


   /**
    * Marks a host as dead until 30-40 seconds in the future
    * @param   string   $sock    Socket to mark as dead
    */
   private function _dead_sock ($sock)
   {
      $host = array_search($sock, $this->_cache_sock);
      list ($ip, $port) = explode(":", $host);
      $this->_host_dead[$ip] = time() + 30 + intval(rand(0, 10));
      $this->_host_dead[$host] = $this->_host_dead[$ip];
      unset($this->_cache_sock[$host]);
   }


   /**
    * get_sock
    * @param   string   $key     Key to retrieve value for;
    * @return  mixed    resource on success, false on failure
    */
   private function get_sock ($key)
   {
      if (!$this->_active)
         return false;

      if ($this->_single_sock !== null)
         return $this->sock_to_host($this->_single_sock);
      
      $hv = is_array($key) ? intval($key[0]) : $this->_hashfunc($key);
      
      if ($this->_buckets === null)
      {
         foreach ($this->_servers as $v)
         {
            if (is_array($v))
            {
               for ($i=0; $i<$v[1]; $i++)
                  $bu[] = $v[0];
            } else
            {
               $bu[] = $v;
            }
         }
         $this->_buckets = $bu;
         $this->_bucketcount = count($bu);
      }
      
      $realkey = is_array($key) ? $key[1] : $key;
      for ($tries = 0; $tries<20; $tries++)
      {
         $host = $this->_buckets[$hv % $this->_bucketcount];
         $sock = $this->sock_to_host($host);
         if (is_resource($sock))
            return $sock;
         $hv += $this->_hashfunc($tries . $realkey);
      }
      
      return false;
   }


   /**
    * Creates a hash interger based on the $key
    * @param   string   $key     Key to hash
    * @return  interger Hash value
    */
   private function _hashfunc ($key)
   {
      $hash = 0;
      for ($i=0; $i<strlen($key); $i++)
      {
         $hash = $hash*33 + ord($key[$i]);
      }
      
      return $hash;
   }


   /**
    * Perform increment/decriment on $key
    * @param   string   $cmd     Command to perform
    * @param   string   $key     Key to perform it on
    * @param   interger $amt     Amount to adjust
    * @return  interger    New value of $key
    */
   private function _incrdecr ($cmd, $key, $amt=1)
   {
      if (!$this->_active)
         return null;
         
      $sock = $this->get_sock($key);
      if (!is_resource($sock))
         return null;
         
      $key = is_array($key) ? $key[1] : $key;
      if (!fwrite($sock, "$cmd $key $amt\r\n"))
         return $this->_dead_sock($sock);
         
      stream_set_timeout($sock, 1, 0);
      $line = fgets($sock);
      if (!preg_match('/^(\d+)/', $line, $match))
         return null;
      return $match[1];
   }


   /**
    * Load items into $ret from $sock
    * @param   resource $sock    Socket to read from
    * @param   array    $ret     Returned values
    */
   private function _load_items ($sock, &$ret)
   {
      while (1)
      {
         $decl = fgets($sock);
         if ($decl == "END\r\n")
         {
            return true;
         } elseif (preg_match('/^VALUE (\S+) (\d+) (\d+)\r\n$/', $decl, $match))
         {
            list($rkey, $flags, $len) = array($match[1], $match[2], $match[3]);
            $bneed = $len+2;
            $offset = 0;
            
            $ret[$rkey] = '';
            
            while ($bneed > 0)
            {
               $data = fread($sock, $bneed);
               $n = strlen($data);
               if ($n == 0)
                  break;
               $offset += $n;
               $bneed -= $n;
               $ret[$rkey] .= $data;
            }
            
            if ($offset != $len+2)
            {
               $tmpl = $len+2;
               debug::addmsg("Something is borked!  key $rkey expecting {$tmpl} got $offset length\n", 0);

               unset($ret[$rkey]);
               $this->_close_sock($sock);
               return false;
            }
            
            $ret[$rkey] = rtrim($ret[$rkey]);
            if ($flags & MEMCACHE_COMPRESSED)
               $ret[$rkey] = gzuncompress($ret[$rkey]);

            if ($flags & MEMCACHE_SERIALIZED)
               $ret[$rkey] = unserialize($ret[$rkey]);

         } else 
         {
            debug::addmsg("解析memcache返回信息出错",0);
            return 0;
         }
      }
   }


   /**
    * 向服务器端存储数据
    *
    * @param   string   $cmd     要执行的方法：add,set...
    * @param   string   $key     键
    * @param   mixed    $val     值
    * @param   bool     $com     压缩
    * @param   interger $exp     超时
    * @return  boolean
    */
   private function _set ($cmd, $key, $val,$com, $exp)
   {
      if (!$this->_active)
         return false;
         
      $sock = $this->get_sock($key);
      if (!is_resource($sock))
         return false;
         
      
      $flags = 0;
      
      if (!is_scalar($val))
      {
         $val = serialize($val);
         $flags |= MEMCACHE_SERIALIZED;
         debug::addmsg("memcache: 由于数据非标量，已对其进行序列化\n",1);
      }
      
      $len = strlen($val);
      
      if ($com)
      {
      	$c_val = gzcompress($val, 9);
      	$c_len = strlen($c_val);
      	debug::addmsg("memcache: 压缩数据：$key  $len －> $c_len 字节\n", 1 );
      	$val = $c_val;
      	$len = $c_len;
      	
      	$flags |= MEMCACHE_COMPRESSED;
      	
      }elseif(($this->_compress_threshold && $len >= $this->_compress_threshold))
      {
         $c_val = gzcompress($val, 9);
         $c_len = strlen($c_val);
         
         if ($c_len < $len*(1 - 0))
         {
            debug::addmsg("memcache: 压缩数据：$key  $len －> $c_len 字节\n", 1 );
            $val = $c_val;
            $len = $c_len;
            $flags |= MEMCACHE_COMPRESSED;
         }
         
      }

      if (!fwrite($sock, "$cmd $key $flags $exp $len\r\n$val\r\n"))
         return $this->_dead_sock($sock);
         
      $line = trim(fgets($sock));
      

     if ($flags & MEMCACHE_COMPRESSED)
         $val = '压缩数据';
      debug::addmsg("MemCache: $cmd $key => $val ($line)\n", 1);
      if ($line == "STORED")
         return true;
      return false;
   }


   /**
    * 返回服务器socket
    * @param   string   $host    Host:IP to get socket for
    * @return  mixed    IO Stream or false
    */
   private function sock_to_host ($host)
   {
      if (isset($this->_cache_sock[$host]))
         return $this->_cache_sock[$host];
      
      $now = time();
      list ($ip, $port) = explode (":", $host);
      if (isset($this->_host_dead[$host]) && $this->_host_dead[$host] > $now ||
          isset($this->_host_dead[$ip]) && $this->_host_dead[$ip] > $now)
         return null;
         
      if (!$this->_connect_sock($sock, $host))
         return $this->_dead_sock($host);
         
      // Do not buffer writes
      stream_set_write_buffer($sock, 0);
      
      $this->_cache_sock[$host] = $sock;
      
      return $this->_cache_sock[$host];
   }


}

?>
