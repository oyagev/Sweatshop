<?php
namespace Sweatshop\Queue;

use Sweatshop\Config\Config;

use Sweatshop\Message\Message;
use Sweatshop\Interfaces\MessageableInterface;
use Sweatshop\Worker\Worker;

abstract class Queue implements MessageableInterface{
	
	protected $_config;
	public function __construct(Config $config){
		$this->_config = $config;
	}
	
	/**
	 * Push message to the Queue
	 * @param Message $message
	 */
	public function pushMessage(Message $message){
		return $this->_doPushMessage($message);
	}
		
	/**
	 * Register worker for a topic
	 * @param string $topic
	 * @param Worker $worker
	 */
	public function registerWorker($topic , Worker $worker){
		return $this->_doRegisterWorker($topic, $worker);
	}
	
	public function runWorkers(){
		return $this->_doRunWorkers();
	}
	
	abstract protected function _doPushMessage(Message $message);
	
	abstract protected function _doRegisterWorker($topic , Worker $worker);
	
	abstract protected function _doRunWorkers();
}