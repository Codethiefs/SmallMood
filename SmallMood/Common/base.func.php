<?php


/**
 * smallAutoload
 * 自动加载类函数
 *
 */
function smallAutoload($classname){
	require $classname.'.class.php';
	$msg = '<b>smallAutoload:</b><font color=green>'.$classname.'.class.php</font>';
	debug::addmsg($msg,2);
}
/**
 * smartyAutoloader
 * smarty的自动加载类
 */
function smartyAutoload($class)
{
	$_class = strtolower($class);
	$_classes = array(
			'smarty_config_source' => true,
			'smarty_config_compiled' => true,
			'smarty_security' => true,
			'smarty_cacheresource' => true,
			'smarty_cacheresource_custom' => true,
			'smarty_cacheresource_keyvaluestore' => true,
			'smarty_resource' => true,
			'smarty_resource_custom' => true,
			'smarty_resource_uncompiled' => true,
			'smarty_resource_recompiled' => true,
	);

	if (!strncmp($_class, 'smarty_internal_', 16) || isset($_classes[$_class])) {
		include SMARTY_SYSPLUGINS_DIR . $_class . '.php';
		$msg = '<b>smartyAutoload:</b><font color=green>'.SMARTY_SYSPLUGINS_DIR . $_class .'.php</font>';
		debug::addmsg($msg,2);
	}
}
/**
 * 输出函数；
 *
 */
function dump($v){
	echo '<pre>';
	print_r($v);
	echo '<pre>';
}

/**
 * 递归方式的对变量中的特殊字符进行转义
 *
 */
function addslashes_deep($value)
{
	if (empty($value))
		return $value;
	else 
		return is_array($value)?array_map('addslashes_deep', $value):addslashes($value);
}

/*  
 * 实例化一个模型；
 * 
 * */

function M($model='',$prefix=TABPREFIX){
	if (!$model){
		return new model();
	}else{
		$modelname = strtolower($model).'Model';
		if (file_exists(APPPA.'model/'.$modelname.'.class.php'))
		{
			return new $modelname;
		}else
		{
			return new model($model,$prefix);
		}
		
	}
}


/* 
 * 文件缓存和读取
 *  */

function F($name,$value='',$path=RUNTIME)
{
	$filename = $path.$name.'.php';
	//如果$value为空则试着读取缓存
	if ($value==='')
	{
		if (is_file($filename))
		{
			$v = include $filename;
		}else 
		{
			$v = false;
		}
		return $v;
	//如果$value=null则 删除对应的缓文件	
	}elseif (is_null($value))
	{
		return unlink($filename);
	//其他情况写缓存
	}else
	{
		$dir = dirname($filename);
		if (!is_dir($dir))
			mkdir($dir,0755,true);
		$value = var_export($value,true);
		return file_put_contents($filename, "<?php \n return ".$value.";\n?>");
	}
}


function cache(){
	if (!defined('CACHE') || CACHE=='')
	{
		$cachetype = 'smallmemcache';
	}else 
	{
		$cachetype = 'small'.CACHE;
	}
	if (class_exists($cachetype))
	{
		return $cachetype::instance();
	}
}