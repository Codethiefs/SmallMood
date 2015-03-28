<?php



class smallmysql extends DB{
	private static $ins = null;
	private $id = null;
	private $conn = null;
	
	protected function __construct(){
		$this->connectdb(HOST, USER, PASS);
		$this->selectdb(DBNAME);
		$this->setchar();
	}
	
	public static function instance(){
		if (!self::$ins instanceof self)
		{
			self::$ins = new self;
		}
		return self::$ins;
	}
	 
	protected function connectdb($host,$user,$password){
		$this->conn = mysql_connect($host,$user,$password);
		if (!$this->conn)
		{
			die();
		}
	}
	
	protected function selectdb($dbname){
		if (!mysql_select_db($dbname)) 
		{
			debug::addmsg('<b>警告</b>:选择数据库时发生错误，'.mysql_error(),0);
			die();
		}
	}
	
	protected function setchar($charset='utf8'){
		$sql = "set names {$charset}";
		mysql_query($sql,$this->conn);
	}
	
	public function query($sql){
		$res = mysql_query($sql,$this->conn);
		if ($res===false)
		{
			$this->debug($sql,0,mysql_error($this->conn));
		}else {
			$this->debug($sql,1,'查询结果：'.mysql_num_rows($res));
		}
		return $res;
		
	}
	public function exec($sql){
		$res = mysql_query($sql,$this->conn);
		if ($res===false)
		{
			$this->debug($sql,0,mysql_error($this->conn));
		}else {
			$this->debug($sql,1,'影响行数：'.mysql_affected_rows($this->conn));
		}
		return $res;
	}
	public function getone($sql,$result_type=MYSQL_ASSOC){
		if (($res = $this->query($sql))===false)
		{
			return false;
		}
		
		$rt = mysql_fetch_array($res,$result_type);
		return $rt;
	}
	
	public function getall($sql,$result_type=MYSQL_ASSOC){
		if (($res = $this->query($sql))===false)
		{
			return false;
		}
		$rt = array();
		while (($row = mysql_fetch_array($res,$result_type))!==false){
			$rt[] = $row;
		}
		return $rt;
	}
	

	public function starttrans(){
		debug::addmsg('开启事务！',2);
		$sql = 'START TRANSACTION';
		$this->exec($sql);
	}
	
	public function commit(){
		debug::addmsg('提交事务！',2);
		$sql = 'COMMIT';
		$this->exec($sql);
	}
	
	public function rollback(){
		debug::addmsg('事务回滚！',2);
		$sql = 'ROLLBACK';
		$this->exec($sql);
	}
	
	public function getfields($tabname){
		$sql = 'SHOW COLUMNS FROM '.$tabname;
		$fields = $this->getall($sql);
		return $fields;
	}
	
	public function getlastinsID(){
		return mysql_insert_id($this->conn);
	}
	
	public function getaffectedrows()
	{
		return mysql_affected_rows($this->conn);
	}

}