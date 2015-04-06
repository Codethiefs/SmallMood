<?php





function dump($var){
	echo '<pre>';
	print_r($var);
	echo '<pre>';
}

function redirect($url, $time=0, $msg='') {
    //多行URL地址支持
    $url        = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}


/**
 * 递归方式的对变量中的特殊字符进行转义
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


