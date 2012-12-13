<?php



require_once '../main.php';

use Evently\Message\Message;
use Evently\Evently;
use Evently\Worker\Worker;

Evently::getInstance()->configure(array());

class Pet{
	function __construct(){
		Evently::getInstance()->dispatch(new Message('sys.obj.new', array(), $this));
	}
	
	function say(){
		return "Pet is saying...";
	}
}


class Dog extends Pet{
	
	
}

class SimpleWorker extends Worker{
	function _doExecute(Message $message){
		//var_dump($message);
		echo "I'm busy!!!";
	}
	function _doTearUp(){
		
	}
	function _doTearDown(){
		
	}
}

Evently::getInstance()->registerWorker('sys.obj.new', new SimpleWorker());
//var_dump(Evently::getInstance());

$dog = new Dog();
echo $dog->say();
