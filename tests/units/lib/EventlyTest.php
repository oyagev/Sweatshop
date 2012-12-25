<?php

use Monolog\Logger;

use Sweatshop\Sweatshop;

class SweatshopTest extends \PHPUnit_Framework_TestCase{
	
	function testBasicBuild(){
		$Sweatshop = new Sweatshop();
		$this->assertInstanceOf('Sweatshop\Sweatshop', $Sweatshop);
		
	}
	
	function testArrayConfig(){
		$Sweatshop = new Sweatshop();
		
		$tmpfile = tempnam(sys_get_temp_dir(),'Sweatshop_');
		$Sweatshop->configure(array(
			'log' => array(
				'output' => 'stream',
				'logfile' => $tmpfile,
				'level' => Logger::DEBUG
			)
		));
		$this->assertRegExp('!Done config!', file_get_contents($tmpfile));
		unlink($tmpfile);
	}
	
}