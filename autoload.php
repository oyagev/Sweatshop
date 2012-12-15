<?php
namespace Evently;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;



include \Evently\VENDOR_PATH.'/autoload.php';

set_include_path(\Evently\LIB_PATH . PATH_SEPARATOR . get_include_path());
//require_once \Evently\VENDOR_PATH . '/zendframework/zendframework1/library/Zend/Loader/Autoloader.php';

spl_autoload_register(function($name){
	if (preg_match('!^\\\\?Evently\\\\(.+)$!', $name,$m)){
		$path = str_replace('\\', '/',$m[1]) . '.php';
		
		require_once $path;
		
		
	}
});


