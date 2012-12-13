<?php
namespace Evently\Queue;

use Evently\Message\Message;
use Evently\Interfaces\MessageableInterface;
use Evently\Worker\Worker;

abstract class Queue implements MessageableInterface{
	
	public function __construct($config){
		
	}
	
	/**
	 * Push message to the Queue
	 * @param Message $message
	 */
	public function newMessage(Message $message){
		return $this->_doPushMessage($message);
	}
	
	/**
	 * Pull message from queue
	 * @param $blocking - if TRUE, block operation until message is pulled
	 * @return Message | NULL
	 * 
	 */
	public function pullMessage($blocking=FALSE){
		return $this->_doPullMessage($blocking);
	}
	
	/**
	 * Register worker for a topic
	 * @param string $topic
	 * @param Worker $worker
	 */
	public function registerWorker($topic , Worker $worker){
		return $this->_doRegisterWorker($topic, $worker);
	}
	
	
	abstract protected function _doPushMessage(Message $message);
	
	abstract protected function _doRegisterWorker($topic , Worker $worker);
	
	abstract protected function _doPullMessage($blocking=FALSE);
}