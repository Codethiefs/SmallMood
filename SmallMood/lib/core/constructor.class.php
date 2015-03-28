<?php

/* 
 * 项目构造器
 *  
 *  */

class constructor {
	
	static $info = array();
	
	/*
	 * 创建文件
	* @param	string	$fileName	文件名
	* @param	string	$data		写入数据
	*/
	public static function touch($filename,$data=''){
		if (!file_exists($filename))
		{
			if (file_put_contents($filename, $data))
				self::$info[] = '<font color=green >创建文件'.$filename.'成功.</font>';
			else 
				self::$info[] = '<font color=red >创建文件'.$filename.'失败.</font>';
		}else 
		{
			self::$info[] = '<font color="#ccc" >文件'.$filename.'已存在-跳过.</font>';
		}
		
	}
	/*
	 * 创建目录
	* @param	array	$dirs		目录数组；
	*/
	static function mkdir($dirs){
		foreach ($dirs as $dir)
		{
			if (file_exists($dir))
			{
				self::$info[] = '<font color="#ccc" >目录'.$dir.'已存在-跳过.</font>';
			}else 
			{
				if (mkdir($dir,'0755'))
				{
					self::$info[] = '<font color=green >目录'.$dir.'创建成功.</font>';
				}else
				{
					self::$info[] = '<font color=red >目录'.$dir.'成功失败.</font>';
				}
			}
		}
	}
	
	/**
	 *部署项目录结构
	 */
	public static function construct(){
		
		$lockfile = SMALL.APPNA.'.lock';
		if (file_exists($lockfile))
		{
			return ;
		}
		
		//创建目录结构
		$dirs = array(
				//应用相关目录
				APPPA,
				APPPA.'model/',
				APPPA.'action/',
				APPPA.'tpl/',
				APPPA.'tpl/public/',

				//公共目录
				ROOT.'public/',
				ROOT.'public/upload/',
				ROOT.'common/',
		);
		self::mkdir($dirs);
		
		//生成公共函数库文件
		$data = <<<data
<?php
		/*用户自定义全局函数库*/	
data;
		self::touch(ROOT.'common/common.func.php',$data);
		
		//生成index控制器
		$data = <<<data
<?php
class indexAction {
	public function index(){
		echo '<div style="width:400px; border:1px solid #ccc; margin:200px auto; height:50px;background:#eee;padding:40px;color:green;text-align:center;"><p>欢迎使用小心情PHP框架，代码神偷祝你一路小心情！</p><p style="text-align:right;">-- ITBOYS&nbsp;&nbsp;&nbsp;&nbsp;</p></div>';
	}
}
data;
		$filename = APPPA.'action/indexAction.class.php';
		$success = APPPA.'tpl/public/success.'.TPL_SUFFIX;
		copy(SMALL.'lib/template/success.tpl', $success);
		self::touch($filename,$data);		
		self::touch($lockfile,implode("\r\n",self::$info));
		self::showdetials();
	}
	
	/* 显示安装信息 */
	protected static function showdetials(){
		echo '<div style="width:600px;margin:50px auto;">';
		foreach (self::$info as $info)
		{
			echo $info.'<br/>';
		}
		die('安装结束');
		echo '</div>';
	}
}