<?php
namespace Sweatshop;

use Monolog\Handler\NullHandler;

use Monolog\Logger;

use Sweatshop\Queue\Queue;

use Sweatshop\Message\Message;

class Sweatshop{
	
	protected $_queues = array();
	protected $_di = NULL;
	
	function __construct(){
		
		$di = new \Pimple();
		$di['logger'] = $di->share(function($di){
			$logger = new Logger('Sweatshop');
			$logger->pushHandler(new NullHandler());
			return $logger;
			
		}) ;
		$this->setDependencies($di);
	}
	function pushMessage(Message $message){
		$this->getLogger()->info(sprintf('Sweatshop pushing message id "%s"',$message->getId()), array('message_id'=>$message->getId()));
		$result = array();
		foreach ($this->_queues as $queue){
			$result = array_merge($result, $queue->pushMessage($message));
		}
		return $result;
	}
	function addQueue(Queue $queue){
		$this->getLogger()->info('Adding queue: '.get_class($queue));
		$queue->setDependencies($this->_di);
		array_push($this->_queues, $queue);
	}
	function setLogger(Logger $logger){
		$this->_di['logger'] = $logger;
	}
	function getLogger(){
		return $this->_di['logger'];
		
	}
	
	function runWorkers(){
		foreach ($this->_queues as $queue){
			$queue->runWorkers();
		}
	}
	
	function setDependencies(\Pimple $di){
		$this->_di = $di;
	}
	
	function getDependencies(){
		return $this->_di;
	}
	
}