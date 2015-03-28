<?php

/**
 *
 * @author HACKBOY
 *        
 *        
 */

class model {
	//自动完成及自动验证时间
	const AUTO_INSERT	= 1;//插入数据时
	const AUTO_UPDATE	= 2;//更新数据时
	const AUTO_BOTH		= 3;//插入和更新
	//自动验证条件
	const CHECK_EXISTS	= 1;//存在则验证
	const CHECK_MUST		= 2;//必须验证
	const CHECK_VALUE	= 3;//值不为空验证
	//parseOPT标记
	const PARSE_INSERT	=1;
	const PARSE_DELETE	=2;
	const PARSE_UPDATE	=3;
	const PARSE_SELECT	=4;
	//数据库对象
	protected $db;
	//当前表名
	protected $tabname = '';
	//主键
	protected $PK;
	//表字段，用于自动过滤
	protected $fields = array();
	//自动增长的字段
	protected $autoinc = array();
	//连惯操作方法
	protected $options = array();
	//数据信息
	protected $data = array();
	//自动添充规则
	protected $autofill = array();
	//自动验证规则
	protected $autocheck = array();
	//字段映射规则
	protected $fieldmap  = array();
	//
	public $error = array();
	
	
	/*  
	 * 构造函数
	 * 获取数据库对象实例；
	* */
	public function __construct($tabname='',$prefix=TABPREFIX){
		
		$this->init();
		
		if ($tabname)
		{
			$this->tabname = $prefix.$tabname;
		}
		
		$driver = 'small'.DRIVER;

		$this->db = $driver::instance();

	}
	
	
	/*  
	 * 设置数据对象的值
	* */
	public function __set($k,$v){
		$this->data[$k] = $v;
	}
	
	/*  
	 * 获取数据对象的值
	* */
	public function __get($k){
		return isset($this->data[$k])?$this->data[$k]:null;
	}
	
	/*
	 * 实现连惯操作:field,table,where,order,limit,group,having;
	* */
	public function __call($method,$args){
		$method = strtolower($method);
		$options= array('field','table','join','where','group','having','order','limit','union');
		if (in_array($method, $options))
		{
			$this->options[$method] = $args[0];
		}
		return $this;
	}
	
	
	/*
	 * 实现连惯操作中的field
	 * 接受参数：array string
	 * $except为true时，field为排除字段
	*  */
	public function field($field,$except=false){
		
		if ($except)
		{
			if (is_string($field))
			{
				$field = explode(',', $field);
			}
			$fields = $this->getfields();
			$field = array_diff($fields,$field);
		}
		$this->options['field'] = $field;
		
		return $this;
	}
	
	/* 
	 * 实现连惯操作中的join  
	*  */
	public function join($join){
		if (is_array($join))
		{
			$this->options['join'] = $join;
		}else
		{
			$this->options['join'][] = $join;
		}
		return $this;
	}
	
	/*
	 * 实现连惯操作中的union
	*  */
	public function union($union,$all=false){
		if ($all)
		{
			$this->options['union']['all'] = true;
		}
		if (is_array($union))
		{
			$this->options['union'] = $union;
		}else
		{
			$this->options['union'][] = $union;
		}
		return $this;
	}
	/*
	 * data方法用来添充数据
	 * 支持数组、对象及json参数;
	* */
	public function data($data){

		if (is_array($data))
		{
			$this->data = $data;
		}elseif (is_object($data))
		{
			$this->data = get_object_vars($data);
		}else
		{
			$data=json_decode($data,true);
			if ($data)
			{
				$this->data = $data;
			}else {
				debug::addmsg('参数解析错误：data()参数应为一个数组、对象或者json字符串!',0);
			}
		}
		return $this;
	}
	

	
/* *************************************增、删、改、查********************************************** */	
	
	/*
	 * 添加数据
	* */
	public function insert($data=array(),$opts=array()){
		if (empty($data))
		{
			if(!empty($this->data))
			{
				$data = $this->data;
				//清空数据信息
				$this->data = array();
			}else {
				debug::addmsg('数据插入错误：insert()方法插入数据为空',1);
				return false;
			}
		}
		//自动处理数据
		//dump($data);
		$data = $this->auto($data,self::AUTO_INSERT);
		if (!$data)
		{
			return false;
		}
		//dump($data);
		//分析组装SQL
		$sql = $this->OPTStoSQL($opts,self::PARSE_INSERT,$data);
		
		if (!$sql)
		{
			return false;
		}
		if($this->before_insert($data,$opts)===false)
		{
			return false;
		}
		$result = $this->execute($sql);
		
		if (false!==$result)
		{
			$this->after_insert($data, $opts);
			return $this->getlastinsID();
		}
		return false;
		
	}
	
	/*
	 * 删除数据
	 * 接受opts做为删除条件；
	 * opts为数字或字串时按主键删除
	* */
	public function delete($opts=array()){
		//如果删除条件为空，则根据data中的数据进行删除;
		if (empty($opts)&&empty($this->options['where']))
		{
			if (!empty($this->data[$this->getPK()]))
			{
				return $this->delete($this->data[$this->getPK()]);
			}
			//如果data中存在主键对应的值则将其做为条件递归删除;
			return false;
		}
		//如果参数是数字或者字符串则将其做为主键的值根据主键进行删除
		if (is_numeric($opts)||is_string($opts))
		{
			$where[] = $this->getPK();
			$where[] = $opts;
			$opts = array();
			$opts['where'][] = $where;
		}
		
		$sql = $this->OPTStoSQL($opts,self::PARSE_DELETE);
		
		if (!$sql)
		{
			return false;
		}
		
		if ($this->before_delete($opts)===false)
		{
			return false;
		}

		if($this->db->exec($sql))
		{
			$this->after_delete($opts);
			return $this->affectedrows();
		}else {
			return false;
		}
		
	}
	/*
	 * 更新数据
	* */
	public function update($data=array(),$opts=array()){
		if (empty($data))
		{
			if(!empty($this->data))
			{
				$data = $this->data;
				//清空数据信息
				$this->data = array();
			}else {
				debug::addmsg('数据更新错误：update()方法传入空值',1);
				return false;
			}
		}
		
		$data = $this->auto($data, self::AUTO_UPDATE);
		if (!$data)
		{
			return false;
		}
		
		$sql = $this->OPTStoSQL($opts, self::PARSE_UPDATE,$data);
		
		if (!$sql)
		{
			return false;
		}
		if ($this->before_update($data, $opts)===false)
		{
			return false;
		}
		$result = $this->execute($sql);
		
		if (false!==$result)
		{
			$this->after_update($data, $opts);
			return $this->affectedrows();
		}else {
			return false;
		}
		
	}
	
	/*
	 * 取多条数据
	* */
	public function select($opts=array()){
		if (is_numeric($opts)||is_string($opts))
		{
			$where[] = array($this->getPK(),$opts);
			$opts['where'] = $where;
		}
		$sql = $this->OPTStoSQL($opts, self::PARSE_SELECT);
		if (!$sql)
			return false;
		$result = $this->db->getall($sql);
		if ($result===false)
			return false;
		
		$this->after_select($result, $opts);
		
		if (empty($result))
			return null;
		return $this->data = $result;
	}
	
	/*
	 * 取一条数据
	* */
	public function find($opts=array()){
		if (is_numeric($opts)||is_string($opts))
		{
			$where[]=array($this->getPK(),$opts);
			$opts['where'] = $where;
		}
		$this->options['limit'] = 1;
		$sql = $this->OPTStoSQL($opts, self::PARSE_SELECT);
		if (!$sql)
		{
			return false;
		}
		$result = $this->db->getone($sql);
		
		if ($result===false)
		{
			return false;
		}
		
		$this->after_find($result, $opts);
		
		if (empty($result))
		{
			return null;
		}
		return $this->data = $result;
	}
	
	
/* ***************************************四大自动******************************************** */	

	protected function auto($data,$crud){
		//自动验证
		if (!$this->autocheck($data,$crud))
			return false;;
		//自动映射
		$data = $this->automap($data);
		//自动完成
		$data = $this->autofill($data,$crud);
		//自动过滤
		$data = $this->autofilter($data);
		
		return $data;
		
	}
	/*
	 * 第一自动
	 * 自动过滤表中不存在的字段数据；
	* */
	protected function autofilter($data){
		if (empty($this->fields))
		{
			$this->autofields();
		}
		foreach ($data as $k=>$v)
		{
			if (!in_array($k, $this->fields))
			{
				unset($data[$k]);
			}
		}
	
		return $data;
	}
	
	
	/*
	 * 第二自动
	* 自动完成即自动填充；
	* 配置说明:0填充字段，1填充规则，2附加规则，3填充时间(AUTO_INSERT,AUTO_UPDATE,AUTO_BOTH)，4回调参数
	* */
	protected function autofill($data,$crud){
		if (empty($this->autofill))
			return $data;
		foreach ($this->autofill as $autofill)
		{
			if (empty($autofill[3]))
				$autofill[3] = self::AUTO_INSERT;
			if ($autofill[3]==$crud || $autofill[3]==self::AUTO_BOTH)
			{
				$args = isset($autofill[4])?(array)$autofill[4]:array();
				if (isset($data[$autofill[0]]))
				{
					array_unshift($args,$data[$autofill[0]]);
				}
				if (empty($autofill[2]))
				{
					$autofill[2] = 'string';
				}
				switch($autofill[2])
				{
					case 'function' ://使用函数进行填充；
						$data[$autofill[0]] = call_user_func_array($autofill[1],$args);
						break;
					case 'callback' ://使用回调函数进行填充
						$data[$autofill[0]] = call_user_func_array(array(&$this,$autofill[1]),$args);
						break;
					case 'field'    ://使用其它字段填充
						$data[$autofill[0]] = $data[$autofill[1]];
						break;
					default:
						$data[$autofill[0]] = $autofill[1];
				}
			}
		}
		return $data;
	}
	
	
	/*
	 * 第三自动
	* 自动验证表单数据合法性；
	* 配置说明:0验证字段，1验证规则，2错误提示，3验证条件，4附加规则，5验证时间，
	* 
	* */
	protected function autocheck($data,$crud){
		if (!empty($this->autocheck))
		{
			foreach($this->autocheck as $rule)
			{
				//如果验证时间没有设置，则添加和更新数据都进行验证
				if (empty($rule[5]))
					$rule[5] = self::AUTO_BOTH;
				if ($crud==$rule[5] || $rule[5]==self::AUTO_BOTH)
				{
					//验证时间附合则进入
					$rule[3] = !empty($rule[3])?$rule[3]:self::CHECK_EXISTS;
					switch ($rule[3])
					{
						case self::CHECK_MUST :
							return $this->startcheck($data,$rule);
							break;
						case self::CHECK_VALUE :
							if (!empty($data[$rule[0]]))
								return $this->startcheck($data,$rule);
							break;
						default:
							if (isset($data[$rule[0]]))
								return $this->startcheck($data,$rule);
							
					}
				}
			}
		}
		return $data;
	}
	
	protected function startcheck($data,$rule){
		if ($this->checkdata($data, $rule)==false)
		{
			$this->error[] = $rule[2];
			debug::addmsg('自动验证失败：'.$rule[2],1);
			return false;
		}
		return true;
	}
	
	protected function checkdata($data, $rule){
		$rule[4] = strtolower(!empty($rule[4])?$rule[4]:'regex');
		switch ($rule[4])
		{
			case 'function' :
				$args = array();
				if (isset($data[$rule[0]]))
					array_unshift($args, $data[$rule[0]]);
				return call_user_func_array($rule[1], $args);
			case 'callback' :
				$args = array();
				if (isset($data[$rule[0]]))
					array_unshift($args, $data[$rule[0]]);
				return call_user_func_array(array(&$this,$rule[1]), $args);
			case 'confirm'  :
				return $data[$rule[0]] == $data[$rule[1]];
			case 'length'   :
				$len = mb_strlen($data[$rule[0]],'utf-8');
				if (strpos(','))
				{
					list($min,$max) = explode(',', $rule[1]);
					return ($len>=$min && $len<=$max);
				}else {
					return $len == $rule[1];
				}
			case 'equal'    :
				return $data[$rule[0]]==$rule[1];
			case 'in'       :
				$range = is_array($rule[1])?$rule[1]:explode(',',$rule[1]);
				return in_array($data[$rule[0]], $range);
			case 'between'  :
				list($min,$max) = explode(',', $rule[1]);
				return ($data[$rule[0]]>=$min && $data[$rule[0]]<=$max);
			default:
				return $this->regex($data[$rule[0]],$rule[1]);
				
			
		}
	}
	
	protected function regex($value,$rule){
		$patterns=array(
				'require'=>'/.+/',
				'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
				'url' => '/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',
				'number' => '/^\d+$/',
				'double' => '/^[-\+]?\d+(\.\d+)?$/',
				'idcard'=>'/^\d{15}|\d{18}$/',//匹配身份证号码
				'qq'=>'/^[1-9][0-9]{4,}$/',   //腾讯QQ
				'ip'=>'/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/',//匹配IP地址
			);
			$pattern = isset($patterns[$rule])?$patterns[$rule]:$rule;
			
			return preg_match($pattern, $value)===1;
	}
	/*
	 * 第四自动
	* 字段映射；
	* 配置说明:0表单字段，1表字段，
	* */
	protected function automap($data){
		if (!empty($this->fieldmap))
		{
			foreach ($this->fieldmap as $formkey=>$tablekey)
			{
				if (isset($data[$formkey]))
				{
					$data[$tablekey] = $data[$formkey];
					unset($data[$formkey]);
				}
			}
		}
		
		return $data;
	}
/* ****************************************工具方法********************************************** */	
	
	/* 
	 * 取得表名
	 * 
	* */
	protected function gettable(){
		if (empty($this->tabname))
		{
			$table = substr(get_class($this),0,-5);
			$this->tabname = TABPREFIX.$table;
		}
		return $this->tabname;
	}
	/*
	 * 获取表字段
	* */
	protected function getfields(){
		if (empty($this->fields))
		{
			$this->autofields();
		}
		return $this->fields;
	}
	
	/*
	 * 获取表主键
	* */
	protected function getPK(){
		//如果没有设置主键则自动执行autofields来获取
		if (empty($this->PK))
		{
			$this->autofields();
		}
		return $this->PK;
	}
	
	/*  
	 * 自动获取表字段、主键、自增字段
	 * 
	* */
	protected function autofields(){
		$name = 'data/'.DBNAME.'.'.$this->gettable();
		if (FIELDCACHE)
		{
			$this->fields = F($name);
			if ($this->fields)
			{
				$this->pk = $this->fields['pk'];
				unset($this->fields['pk']);
				return true;
			}
		}
		//未开启缓存，或开启缓存但读取缓存失败则从数据库获取
		$fields = $this->db->getfields($this->gettable());
		if (!$fields)
			return false;
		foreach ($fields as $v)
		{
			$this->fields[]=$v['Field'];
			
			if(strtolower($v['Extra'])=='auto_increment')
			{
				$this->autoinc[]=$v['Field'];
			}
			
			if (strtolower($v['Key'])=='pri')
			{
				$this->PK = $v['Field'];
			}
		}
		
		if (FIELDCACHE)
		{
			$value = $this->fields;
			$value['pk'] = $this->PK;
			F($name,$value);
		}
		return true;		
	}
	

	
	/*  
	 * 将options转换为SQL语句
	* */
	protected function OPTStoSQL($opts,$type,$data=array()){
		//将传过来的opts与$this->options合并
		$opts = array_merge($this->options,$opts);
		//dump($opts);
		//dump($data);
		//清空对象的options
		$this->options = array();
		if (empty($opts['table']))
		{
			$opts['table'] = $this->gettable();
		}
		switch ($type)
		{
			case self::PARSE_INSERT :
				$fields = implode(',',array_keys($data));
				$values = '\''.implode('\',\'', array_values($data)).'\'';
				$sql = "INSERT INTO {$opts['table']}({$fields}) VALUES({$values})";
				break;				
			case self::PARSE_DELETE :
				$where = $this->parseWhere(empty($opts['where'])?'':$opts['where']);
				$order = $this->parseOrder(empty($opts['order'])?'':$opts['order']);
				$limit = $this->parseLimit(empty($opts['limit'])?'':$opts['limit']);
				$sql = "DELETE FROM {$opts['table']} {$where} {$order} {$limit}";
				break;
			case self::PARSE_UPDATE :
				$setdata = $this->parseData($data);
				$where = $this->parseWhere(empty($opts['where'])?'':$opts['where']);
				$order = $this->parseOrder(empty($opts['order'])?'':$opts['order']);
				$limit = $this->parseLimit(empty($opts['limit'])?'':$opts['limit']);
				$sql = "UPDATE {$opts['table']} SET {$setdata} {$where} {$order} {$limit}";
				break;
			case self::PARSE_SELECT :
				$field = $this->parseField(empty($opts['field'])?'':$opts['field']);
				$join  = $this->parseJoin(empty($opts['join'])?'':$opts['join']);
				$where = $this->parseWhere(empty($opts['where'])?'':$opts['where']);
				$group = $this->parseGroup(empty($opts['group'])?'':$opts['group']);
				$having= $this->parseHaving(empty($opts['having'])?'':$opts['having']);
				$order = $this->parseOrder(empty($opts['order'])?'':$opts['order']);
				$limit = $this->parseLimit(empty($opts['limit'])?'':$opts['limit']);
				$union = $this->parseUnion(empty($opts['union'])?'':$opts['union']);
				$sql = "SELECT {$field} FROM {$opts['table']} {$join} {$where} {$group} {$having} {$order} {$limit} {$union}";
				break;
		}
		
		return $sql;
		
	}
	

	
	
/* **************************************各种解析*********************************************** */	


	/* 
	 * 解析where条件
	 * 参数：字符串、二维数组
	 * 参数为数组时格式如下：
	 * array(
	 * 		array('字段名','值','表达式'),
	 * 		'logic'=>'OR'
	 * )
	 * 其中表达式为可选参数，默认为"="
	 * logic默认为‘AND’
	 *  */
	protected function parseWhere($wheres){
		$str = '';
		if (is_string($wheres))
		{
			$str .= $wheres;
		}else {
			if (!empty($wheres['logic']))
			{
				$operate = $wheres['logic'];
				unset($wheres['logic']);
			}else {
				$operate = 'AND';
			}
			$str .= '(';
			foreach ($wheres as $k=>$where)
			{
				if (empty($where[2]))
				{
					$where[2] = '=';
				}
				
				$cmp = strtoupper($where[2]);

				if ($cmp=='BETWEEN'||$cmp=='NOT BETWEEN')
				{
					list($min,$max) = explode(',', $where[1]);
					$str .= $where[0].' '.$cmp.' \''.$min.'\' AND \''.$max.'\'';
				}elseif ($cmp=='IN'||$cmp=='NOT IN')
				{
					$str .= $where[0].' '.$cmp.' ('.$where[1].')';
				}else
				{
					$str .= $where[0].' '.$cmp.' \''.$where[1].'\'';
				}
				
				
				
				
				$str .= " {$operate} ";
				
			}
			$str = substr($str,0,strripos($str,$operate)-1);
			$str .= ')';
		}
		
		return empty($str)?'':'WHERE '.$str;
	}
	/*  
	 * 解析order
	 * 参数  字符串  或   数组
	 * */
	protected function parseOrder($orders){
		if (is_array($orders))
		{
			$temp = array();
			foreach($orders as $k=>$v)
			{
				if (is_numeric($k))
				{
					$temp[] = $v;
				}else {
					$temp[] = $k.' '.$v;
				}
			}
			$orders = implode(',', $temp);
		}
		
		return empty($orders)?'':'ORDER BY '.$orders;
	}

	/*
	 *解析LIMIT；
	*
	* */
	protected function parseLimit($limit){
		return empty($limit)?'':'LIMIT '.$limit;
	}
	
	/*
	 * 解析field
	*  用于查询的字段，如果为空则默认取回全部字段
	* */
	protected function parseField($fields){
		$str ='';
		if (is_array($fields))
		{
			$arr = array();
			foreach ($fields as $k=>$v)
			{
				if (!is_numeric($k))
				{
					$arr[] .= $k.' AS '.$v;
				}else {
					$arr[] .= $v;
				}
			}
			
			$str = implode(',', $arr);
		}else 
		{
			$str = $fields;
		}
		return empty($str)?'*':$str;
	}
	
	/*
	 * 解析group
	*  参数为字符串，用于分组的字段
	* */
	protected function parseGroup($field){
		return empty($field)?'':'GROUP BY '.$field;
	}
	
	
	/*
	 * 解析having
	*  参数为字符串，用于分组的字段
	* */
	protected function parseHaving($opts){
		return empty($opts)?'':'HAVING '.$opts;
	}
	
	/* 
	 * 解析data数据
	 * 将data数据解析为格式： name='lisi',age='18'
	*  */
	protected function parseData($data){
		$str = '';
		foreach ($data as $k=>$v)
		{
			$str .= $k.'=\''.$v.'\',';
		}
		$str = rtrim($str,',');
		return $str;
	}
	
	
	/*
	 * 解析join
	* 
	*  */
	protected function parseJoin($joins){
		$str = '';
		if (!empty($joins)) {
			foreach ($joins as $join)
			{
				if (stripos($join,'join')!==false)
				{
					$str .= $join;
				}else 
				{
					$str .= ' LEFT JOIN '.$join;
				}
			}
		}
		
		return empty($str)?'':$str;
	}
	
	/*
	 * 解析union
	*
	*  */
	protected function parseUnion($unions){
		$str = '';
		if (!empty($unions))
		{
			if (isset($unions['all']))
			{
				$u = ' UNION ALL ';
				unset($unions['all']);
			}else
			{
				$u = ' UNION ';
			}
			
			foreach ($unions as $union)
			{
				$str .= $u.'('.$union.')';
			}
		}
		
		return empty($str)?'':$str;
	}
	
/* **************************************************************************** */	
	
	/*
	 *执行inset,update,delete语句
	* */
	public function execute($sql){
		return $this->db->exec($sql);
	}
	
	/*
	 *执行select语句
	* */
	public function query($sql){
		return $this->db->query($sql);
	}
	
	/* 
	 * 开启事务
	 *  */
	public function starttrans(){
		$this->db->starttrans();
	}
	
	public function rollback(){
		$this->db->rollback();
	}
	
	public function commit(){
		$this->db->commit();
	}

/* *************************************************************************** */
	public function geterror(){
		return $this->error;
	}
	
	
	public function getlastinsID(){
		return $this->db->getlastinsID();
	}
	
	public function affectedrows(){
		return $this->db->getafftectedrows();
	}
	
/* ******************************供子类改写的回调方法*********************************** */
	
	protected function init(){}
	protected function before_insert($data,$opts){}
	protected function after_insert($data,$opts){}
	protected function before_delete($opts){}
	protected function after_delete($opts){}
	protected function before_update($data,$opts){}
	protected function after_update($data,$opts){}
	protected function after_select($data,$opts){}
	protected function after_find($data,$opts){}
	
}


?>