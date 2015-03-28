<?php

class smallmysqli extends DB
{
	protected static $ins = null;
	protected $mysqli;
	
	protected function __construct(){
		$this->connectdb(HOST, USER, PASS, DBNAME);
		$this->setchar();
	}
	
	public static function instance(){
		if (!self::$ins instanceof self)
		{
			self::$ins = new self;
		}
		return self::$ins;
	}
	
	protected function connectdb($host,$user,$pass,$dbname){
		$this->mysqli = new mysqli($host,$user,$pass,$dbname);
		if (mysqli_connect_error())
		{
			die();
		}
	}
	
	protected function setchar($charset = 'utf8'){
		$this->mysqli->set_charset($charset);
	}
	
	public function query($sql){
		$res = $this->mysqli->query($sql);
		if ($res===false)
		{
			$this->debug($sql,0,mysqli_error());
		}else 
		{
			$this->debug($sql,1,'查询结果：'.$res->num_rows);
		}
		return $res;
	}
	
	public function exec($sql){
		$res = $this->mysqli->query($sql);
		if ($res===false) {
			$this->debug($sql,0,$this->mysqli->error);
		}else {
			$this->debug($sql,1,'影响行数：'.$this->mysqli->affected_rows);
		}
	}
	
	public function getone($sql,$result_type=MYSQLI_ASSOC){
		if (($res = $this->query($sql))===false)
		{
			return false;
		}
		return $res->fetch_array($result_type);
	}
	
	public function getall($sql,$result_type=MYSQLI_ASSOC){
		if (($res = $this->query($sql))===false)
		{
			return false;
		}
		$rt = array();
		while (!!$row = $res->fetch_array($result_type))
		{
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
		return $this->mysqli->insert_id;
	}
	public function getaffectedrows(){
		return $this->mysqli->affected_rows;
	}
}