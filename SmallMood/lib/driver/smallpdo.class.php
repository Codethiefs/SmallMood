<?php

class smallpdo extends DB
{
	protected static $smallpdo = null;
	protected $pdo ;
	
	protected function __construct(){
		if (defined('DSN'))
		{
			$dsn = DSN;
		}else{
			$dsn = 'mysql:host='.HOST.';dbname='.DBNAME;
		}
		
		try {
			$this->pdo = new PDO($dsn, USER, PASS);
		}catch (PDOException $e){
			echo "连接数据库出错！",$e->getMessage();
		}
		$this->charset();
	}
	
	public static function instance(){
		if (!self::$smallpdo instanceof self )
		{	
			self::$smallpdo = new self;
		}
		return self::$smallpdo;
		
	}
	
	public function charset($charset='utf8'){
		$this->pdo->exec("set names ".$charset);
	}
	public function getfields($tabname){
		$sql = 'SHOW COLUMNS FROM '.$tabname;
		$fields = $this->getall($sql);
		return $fields;
	}
	
	public function query($sql){
		try {
			$res = $this->pdo->query($sql);
		} catch (PDOException $e) {
			$this->debug($sql,0,$e->getMessage());
		}
		$this->debug($sql);
		
		return $res;
	}
	
	public function exec($sql){
		try {
			$res = $this->pdo->exec($sql);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		
		$this->debug($sql);
		return $res;
	}
	public function getone($sql,$rt_type=PDO::FETCH_ASSOC){
		$res = $this->query($sql);
		if($res)
		{
			if(($rt = $res->fetch($rt_type))!=false);
			{
				return $rt;
			}
		}
		return false;
	}
	
	public function getall($sql,$rt_type=PDO::FETCH_ASSOC){
		$res = $this->query($sql);
		if($res)
		{
			if(($rt = $res->fetchall($rt_type))!=false);
			{
				return $rt;
			}
		}
		return false;
	}
	
	public function geterror(){
		$errors = $this->pdo->errorInfo();
		dump($errors);
	}
}