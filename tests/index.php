<?php



use Sweatshop\Config\Config;

require_once __DIR__.'/../main.php';

use Sweatshop\Message\Message;
use Sweatshop\Sweatshop;
use Sweatshop\Worker\Worker;

include(\Sweatshop\CONFIG_PATH . '/config.php');

$config = new Config($config); 
Sweatshop::getInstance()->configure($config);

class Pet{
	function __construct(){
		$res = Sweatshop::getInstance()->dispatch(new Message('sys.obj.new', array(), $this));
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

Sweatshop::getInstance()->registerWorker('sys.obj.new', new SimpleWorker());
//var_dump(Sweatshop::getInstance());

$dog = new Dog();
echo $dog->say();

