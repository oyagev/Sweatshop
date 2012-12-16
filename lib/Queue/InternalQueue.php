<?php
namespace Evently\Queue;

use Evently\Message\Message;
use Evently\Worker\Worker;

class InternalQueue extends Queue{
	
	protected $_workers=array();
	
	protected function _doPushMessage(Message $message){
		$topic = $message->getTopic();
		$results = array();
		if (!empty($this->_workers[$topic])){
			foreach($this->_workers[$topic] as $worker){
				$results[] = $worker->newMessage($message);
			}
		}
		return $results;
		
	}
	protected function _doRegisterWorker($topic , Worker $worker){
		
		if (empty($this->_workers[$topic])){
			$this->_workers[$topic] = array();
		}
		$this->_workers[$topic][] =  $worker;
		return TRUE;
	}
	
	protected function _doRunWorkers(){
		;
	}
	
	
}