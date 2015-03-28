<?php
/* 
 * url解析器
 *  
 *  */
class url{
	
	
	public static function parseurl(){
		
		if (isset($_SERVER['PATH_INFO'])){
			
			$pathinfo = explode('/',trim($_SERVER['PATH_INFO'],'/'));
			
			$_GET['module'] = (!empty($pathinfo[0]) ? $pathinfo[0]:'index');
			array_shift($pathinfo);
			$_GET['action'] = (!empty($pathinfo[0]) ? $pathinfo[0]:'index');
			array_shift($pathinfo);
			
			$len = count($pathinfo);
			for ($i=0;$i<$len;$i+=2){
				$_GET[$pathinfo[$i]] = (!empty($pathinfo[$i+1]) ? $pathinfo[$i+1]:'');
			}
		}else 
		{
			$_GET['module'] = (!empty($_GET['m']) ? $_GET['m']:'index');
			$_GET['action'] = (!empty($_GET['a']) ? $_GET['a']:'index');
		}
	}
}