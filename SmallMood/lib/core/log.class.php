<?php



class log{
	
	public static function write($short,$detial=''){
		$logfile = ROOT.'data/logs/log.temp';
		self::checkbak($logfile);
		if ($detial=='')
		{
			$msg = date('Y-m-d H:i:s').$short."\r\n\r\n";
		}else 
		{
			$msg = date('Y-m-d H:i:s').$short."\r\n".'detial:'.$detial."\r\n\r\n";
		}
		
 		$f = fopen($logfile,'ab');
		fwrite($f, $msg);
		fclose($f);
	}
	
	
	public static function checkbak($filename){
		//如果文件存在则判断是否需要备份；
		if (file_exists($filename))
		{
			clearstatcache(true,$filename);
			$size = filesize($filename)/(1024*1024);
			if ($size>1)
				self::backup($filename);		
		}
	}
	
	public static function backup($filename){
		$newname = ROOT.'data/logs/'.date('Y-m-d_H-i-s').'.log';
		rename($filename, $newname);
	}
	
	
}
