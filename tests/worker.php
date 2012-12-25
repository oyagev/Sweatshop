<?php



require_once __DIR__.'/../main.php';

use Sweatshop\Message\Message;
use Sweatshop\Sweatshop;
use Sweatshop\Worker\Worker;

Sweatshop::getInstance()->configure(array());


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

Sweatshop::getInstance()->registerWorker('sys.obj.new', new SimpleWorker(array(
			'env' 			=> Worker::ENV_EXTERNAL , 
			'background' 	=> TRUE
)));
Sweatshop::getInstance()->registerWorker('sys.obj.new', new SimpleWorker(array(
'env' 			=> Worker::ENV_EXTERNAL ,
'background' 	=> FALSE
)));
//var_dump(Sweatshop::getInstance());
Sweatshop::getInstance()->runWorkers();

