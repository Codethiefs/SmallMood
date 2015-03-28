<?php
/* 
 * 数据库基类
 *  
 * */

abstract class DB
{	

	abstract public function query($sql);
	abstract public function exec($sql);
	abstract public function getone($sql,$result_type);
	abstract public function getall($sql,$result_type);
	abstract public function starttrans();
	abstract public function commit();
	abstract public function rollback();
	abstract public function getfields($tabname);
	abstract public function getlastinsID();
	abstract public function getaffectedrows();
	
	
	protected function debug($sql='',$status=1,$msg=''){
		if (!defined('DEBUG') || DEBUG!=1)
		{
			return ;
		}
		
		if (!$sql)
		{
			debug::addmsg($msg,0);
		}else
		{
			if ($status)

			{
				debug::addmsg('执行语句：'.$sql,1);
				debug::addmsg('执行状态：语句执行成功！'.$msg,1);
			}else {
				debug::addmsg('执行语句：'.$sql,0);
				debug::addmsg('执行状态：语句执行失败！'.$msg,0);
			}
		}
		
	}

	
	
	
	
}