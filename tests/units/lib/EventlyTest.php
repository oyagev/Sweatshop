<?php

use Monolog\Logger;

use Evently\Evently;

class EventlyTest extends \PHPUnit_Framework_TestCase{
	
	function testBasicBuild(){
		$evently = new Evently();
		$this->assertInstanceOf('Evently\Evently', $evently);
		
	}
	
	function testArrayConfig(){
		$evently = new Evently();
		
		$tmpfile = tempnam(sys_get_temp_dir(),'evently_');
		$evently->configure(array(
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