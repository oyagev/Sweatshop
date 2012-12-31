<?php
namespace Sweatshop;

use Sweatshop\Queue\Threads\ThreadsManager;

use Monolog\Handler\NullHandler;

use Monolog\Logger;

use Sweatshop\Queue\Queue;

use Sweatshop\Message\Message;

class Sweatshop{
	
	protected $_queues = array();
	protected $_di = NULL;
	protected $_threadManagers = array();
	
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
			$res = $queue->pushMessage($message);
			if (is_array($res)){
				$result = array_merge($result, $res);
			}
			
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
			'min_threads_per_queue' => 1, 
			'min_threads_per_worker' => 0, 
			'wait_threads_exit' => true , 
			'max_work_cycles' => -1
		) , $options);
		
		if ($options['min_threads_per_queue'] == 0 && $options['min_threads_per_queue'] == 0){
			$this->getLogger()->warn('Launching workers without threads support is not recommended');
		}
		
		foreach ($this->_queues as $queue){
			$manager = new ThreadsManager($this,$queue,$options);
			array_push($this->_threadManagers, $manager);
			$manager->run();
			//$queue->runWorkers($options);
		}
		
		
		
		while ($options['wait_threads_exit'] &&  ($pid=pcntl_wait($status)) !=-1){
			foreach($this->_threadManagers as $manager){
				$manager->notifyExitPID($pid);
			}
		}
	}
	
	function setDependencies(\Pimple $di){
		$this->_di = $di;
	}
	
	function getDependencies(){
		return $this->_di;
	}
	
}