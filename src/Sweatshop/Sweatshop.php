<?php
namespace Sweatshop;

use Sweatshop\Worker\Worker;

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
	
	function pushMessageQuick($topic , $params = array()){
		$message = new Message($topic,$params);
		return $this->pushMessage($message);
	}
	function addQueue($queue, $queueOptions = array()){
		$queueObj = $queue;
		
		if (is_string($queue)){
			if (class_exists($queue)){
				$queueObj = new $queue($this,$queueOptions);
			}else{
				$newname = 'Sweatshop\\Queue\\'.ucfirst($queue) . 'Queue';
				if (class_exists($newname)){
					$queueObj = new $newname($this,$queueOptions);
				}
			}
		}
		
		
		if ($queueObj instanceOf Queue){
			$this->getLogger()->info('Adding queue: '.get_class($queueObj));
			array_push($this->_queues, $queueObj);
			return $queueObj;
		}else{
			throw new \InvalidArgumentException("Unable to instantiate queue: ".$queue);
		}
		
	}
	
	function registerWorker(Queue $queue, $topic, $worker){
		
		if (is_string($worker) && class_exists($worker)){
			$workerObj = new $worker($this);
		}else{
			$workerObj = $worker;
		}
		
		if ($workerObj instanceOf Worker){
			$queue->registerWorker($topic, $workerObj);
			return $worker;
		}else{
			throw new \InvalidArgumentException("Unable to instantiate worker: ".$worker);
		}
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