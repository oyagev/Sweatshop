<?php
namespace Sweatshop;

use Monolog\Logger;

use Sweatshop\Queue\Queue;

use Sweatshop\Message\Message;

class Sweatshop{
	
	protected $_queues = array();
	protected $_di = NULL;
	
	function __construct(){
		$this->_di = new \Pimple();
	}
	function pushMessage(Message $message){
		$result = array();
		foreach ($this->_queues as $queue){
			$result = array_merge($result, $queue->pushMessage($message));
		}
		return $result;
	}
	function addQueue(Queue $queue){
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
}