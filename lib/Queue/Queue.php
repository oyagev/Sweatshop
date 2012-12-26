<?php
namespace Sweatshop\Queue;

use Monolog\Logger;

use Sweatshop\Sweatshop;

use Sweatshop\Config\Config;

use Sweatshop\Message\Message;
use Sweatshop\Interfaces\MessageableInterface;
use Sweatshop\Worker\Worker;

abstract class Queue implements MessageableInterface{
	
	protected $_config;
	protected $_di;
	private $_workers = array();
	
	public function __construct(Sweatshop $sweatshop){
		$this->setDependencies($sweatshop->getDependencies());
	}
	
	function setDependencies(\Pimple $di){
		$this->_di = $di;
	}
	
	/**
	 * Push message to the Queue
	 * @param Message $message
	 */
	public function pushMessage(Message $message){
		$this->getLogger()->info(sprintf('Queue "%s" Pushing message id "%s"', get_class($this),$message->getId()));
		
		return $this->_doPushMessage($message);
	}
		
	/**
	 * Register worker for a topic
	 * @param string $topic
	 * @param Worker $worker
	 */
	public function registerWorker($topic , Worker $worker){
		$this->getLogger()->info(sprintf('Queue "%s" Registering new worker "%s" on topic "%s"',get_class($this),get_class($worker),$topic));
		
		array_push($this->_workers , $worker);
		$res = $this->_doRegisterWorker($topic, $worker);
		
		return $res;
	}
	
	public function runWorkers(){
		$this->getLogger()->info(sprintf('Queue "%s" Launching workers', get_class($this)));
		return $this->_doRunWorkers();
	}
	
	/**
	 * @return Logger
	 */
	public function getLogger(){
		return $this->_di['logger'];
	}
	
	abstract protected function _doPushMessage(Message $message);
	
	abstract protected function _doRegisterWorker($topic , Worker $worker);
	
	abstract protected function _doRunWorkers();
}