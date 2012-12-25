<?php
namespace Sweatshop;

use Monolog\Logger;

use Sweatshop\Queue\Queue;

use Sweatshop\Message\Message;

class Sweatshop{
	
	protected $_queues = array();
	
	function __construct(){
		
	}
	function pushMessage(Message $message){
		$result = array();
		foreach ($this->_queues as $queue){
			$result = array_merge($result, $queue->pushMessage($message));
		}
		return $result;
	}
	function addQueue(Queue $queue){
		array_push($this->_queues, $queue);
	}
	function addLogger(Logger $logger){
		
	}
	
	function run(){
		
	}
}