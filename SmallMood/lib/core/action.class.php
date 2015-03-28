<?php

class action extends Smarty
{
	
	function __construct(){
		parent::__construct();
		$this->setTemplateDir(APPPA.'tpl');
		$this->setCompileDir(ROOT.'runtime/coms');
		$this->setCacheDir(ROOT.'runtime/cache');
		$this->left_delimiter	= LEFT_DELIMITER;
		$this->right_delimiter	= RIGHT_DELIMITER;
		$this->caching		= smarty::CACHING_OFF;
		$this->compile_check= smarty::COMPILECHECK_ON;
		$this->force_compile=true;
		
	}
	
	
	public function display($tpl = null, $cache_id = null, $compile_id = null, $parent = null){
		
		//分配全局变量
		$this->assign('root',ROOT);
		
		if (empty($tpl))
		{
			$tpl = $_GET['module'].'/'.$_GET['action'].'.'.TPL_SUFFIX;
		}elseif (strstr($tpl,'/')){
			$tpl = $tpl.'.'.TPL_SUFFIX;
		}else {
			$tpl = $_GET['module'].'/'.$tpl.'.'.TPL_SUFFIX;
		}
		
		try{
			parent::display($tpl,$cache_id,$compile_id,$parent);
		 }catch (Exception $e)
		{
			if (defined('DEBUG')&&DEBUG==1)
			{
				DEBUG::addmsg('<b>警告</b>[模板加载错误]'.$e->getMessage(),0);
			}
		} 
		
	}
	
	
	public function redirect($path){
		$path = trim($path,'/');
		
		echo '<script type=text/javascript>';
		echo 'window.location.href="'.APPIN.$path.'"';
		echo '</script>';
	}
	
	public function success($mess='操作成功！',$timeout='3',$location=''){
		$this->sAe($mess, $timeout, $location);
		$this->assign('mark',true);
		$this->display('public/success');
	}
	
	public function error($mess='操作失败！',$timeout='3',$location=''){
		$this->sAe($mess, $timeout, $location);
		$this->assign('mark',false);
		$this->display('public/success');
	}
	
	//success and error 共用代码
	protected function sAe($mess,$timeout,$location){
		
		if ($location == '')
		{
			$location = 'window.history.back();';
		}else 
		{
			$location = trim($location,'/');
			if (!strstr($location, '/'))
			{
				$location = $_GET['module'].'/'.$location;
			}
			
			$location = 'window.location.href="'.$location.'"';
		}
		
		$this->assign('mess',$mess);
		$this->assign('timeout',$timeout);
		$this->assign('location',$location);
		$GLOBALS['debug'] = 0;
	}
	

}