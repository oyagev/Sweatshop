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
	protected $_options = array();
	
	public function __construct(Sweatshop $sweatshop, $options=array()){
		$this->setDependencies($sweatshop->getDependencies());
		$this->_options = array_merge(array(
			'max_work_cycles' => -1
		),$this->_options , $options);
		
	}
	
	public function __destruct(){
		$this->getLogger()->info(sprintf('Queue "%s": tearing down',get_class($this)));
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
		
		try{
			return $this->_doPushMessage($message);
		}catch (\Exception $e){
			$this->getLogger()->err(sprintf('Unable to push message into queue "%s". Message was: %s',get_class($this),$e->getMessage()));
			return array();
		}
	}
		
	/**
	 * Register worker for a topic
	 * @param string $topic
	 * @param Worker $worker
	 */
	public function registerWorker($topic , Worker $worker){
		$this->getLogger()->info(sprintf('Queue "%s" Registering new worker "%s" on topic "%s"',get_class($this),get_class($worker),$topic));
		
		array_push($this->_workers , $worker);
		try{
			$res = $this->_doRegisterWorker($topic, $worker);
			return $res;
		}catch (\Exception $e){
			$this->getLogger()->err(sprintf('Unable to register worker on queue "%s". Message was: %s',get_class($this),$e->getMessage()));
		}
		
	}
	
	public function runWorkers($options){
		try{
			if ($options['threads_per_queue'] > 0){
				declare(ticks=1);
				for ($i=0;$i<$options['threads_per_queue'] ; $i++){
					
					$children = array();
					$pid = pcntl_fork();
					if ($pid == -1) {
						$this->getLogger()->fatal(sprintf('Queue "%s" Cannot fork a new thread', get_class($this)));
					} else if ($pid) {
						// we are the parent - do nothing
				
					} else {
						$this->getLogger()->info(sprintf('Queue "%s" Launching workers', get_class($this)));
						return $this->_doRunWorkers($options);
						break;
					}
				}
			}elseif ($options['threads_per_worker'] > 0){
				
			}else{
				$this->getLogger()->info(sprintf('Queue "%s" Launching workers', get_class($this)));
				return $this->_doRunWorkers($options);
			}
		
		}catch (\Exception $e){
			$this->getLogger()->err(sprintf('Unable to run workers on queue "%s". Message was: %s',get_class($this),$e->getMessage()));
		}
	}
	
	/**
	 * @return Logger
	 */
	public function getLogger(){
		return $this->_di['logger'];
	}
	
	abstract protected function _doPushMessage(Message $message);
	
	abstract protected function _doRegisterWorker($topic , Worker $worker);
	
	abstract protected function _doRunWorkers($options=array());
	
	protected function workCycleStart(){
		
	}
	protected function workCycleEnd(){
		var_dump($this->_options);
		if ($this->_options['max_work_cycles'] > 0){
			$this->_options['max_work_cycles']--;
		}
		$this->getLogger()->info(sprintf('Queue "%s" work cycle tick',get_class($this)));
		
	}
	protected function gracefulKill(){
		if ($this->_options['max_work_cycles']===0){
			return TRUE;
		}
		return FALSE;
	}
}