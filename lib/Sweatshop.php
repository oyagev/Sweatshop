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
	
	function runWorkers($options=array()){
		$options = array_merge(array(
			'threads_per_queue' => 1, 
			'threads_per_worker' => 0, 
			'wait_threads_exit' => true
		) , $options);
		
		if ($options['threads_per_queue'] == 0 && $options['threads_per_queue'] == 0){
			$this->getLogger()->warn('Launching workers without threads support is not recommended');
		}
		
		foreach ($this->_queues as $queue){
			$queue->runWorkers($options);
		}
		
		
		
		while ($options['wait_threads_exit'] && pcntl_wait($status)!=-1){
			var_dump($status);
		}
		
		
		return;
		
		declare(ticks=1);
		$children = array();
		foreach ($this->_queues as $queue){
			
			
			$pid = pcntl_fork();
			if ($pid == -1) {
				die("could not fork");
			} else if ($pid) {
				// we are the parent
				
			} else {
				$queue->runWorkers($threads_per_queue, $threads_per_worker);
				break;
			}
			
		}
		
		while ($wait_exit && pcntl_wait($status)!=-1){
			var_dump($status);
		}
	}
	
	function setDependencies(\Pimple $di){
		$this->_di = $di;
	}
	
	function getDependencies(){
		return $this->_di;
	}
	
}