<?php



require_once __DIR__.'/../main.php';

use Evently\Message\Message;
use Evently\Evently;
use Evently\Worker\Worker;

Evently::getInstance()->configure(array());


class SimpleWorker extends Worker{
	function _doExecute(Message $message){
		//var_dump($message);
		echo "working...";
	}
	function _doTearUp(){
		
	}
	function _doTearDown(){
		
	}
}

Evently::getInstance()->registerWorker('sys.obj.new', new SimpleWorker(array(
			'env' 			=> Worker::ENV_EXTERNAL , 
			'background' 	=> TRUE
)));
Evently::getInstance()->registerWorker('sys.obj.new', new SimpleWorker(array(
'env' 			=> Worker::ENV_EXTERNAL ,
'background' 	=> FALSE
)));
//var_dump(Evently::getInstance());
Evently::getInstance()->run();

