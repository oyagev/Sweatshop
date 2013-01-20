<?php
namespace Sweatshop;

use Sweatshop\Dispatchers\WorkersDispatcher;

use Sweatshop\Dispatchers\MessageDispatcher;

use Sweatshop\Worker\Worker;

use Sweatshop\Queue\Threads\ThreadsManager;

use Monolog\Handler\NullHandler;

use Monolog\Logger;

use Sweatshop\Queue\Queue;

use Sweatshop\Message\Message;

class Sweatshop{
	
	protected $_di = NULL;
	protected $_messageDispatcher = NULL;
	protected $_workersDispatcher = NULL;
	
	function __construct(){
		
		$di = new \Pimple();
		$di['logger'] = $di->share(function($di){
			$logger = new Logger('Sweatshop');
			$logger->pushHandler(new NullHandler());
			return $logger;
			
		}) ;
		$di['config'] = $di->share(function($di){
			
			return array();
				
		}) ;
		$di['sweatshop'] = $this;
		
		$this->setDependencies($di);
		$this->_messageDispatcher = new MessageDispatcher($this);
		$this->_workersDispatcher = new WorkersDispatcher($this);
	}
	
	function pushMessage(Message $message){
		$result = $this->_messageDispatcher->pushMessage($message);
		return $result;
	}
	
	function pushMessageQuick($topic , $params = array()){
		$message = new Message($topic,$params);
		return $this->pushMessage($message);
	}
	
	function addQueue($queue,$options=array()){
		$queue_class = Queue::toClassName($queue); 
		$queueObj = new $queue_class($this,$options);
		$this->_messageDispatcher->addQueue($queueObj);
	}
	
	function registerWorker($queue, $topic, $worker, $options){
		$queue_class = Queue::toClassName($queue);
		$this->_workersDispatcher->registerWorker($queue_class, $topic, $worker, $options);
	}
	
	
	function runWorkers(){
		$this->getLogger()->info('Sweatshop: Launching workers');
		$this->_workersDispatcher->runWorkers();
	}
	
	function configureMessagesDispatcher($config){
		$this->_messageDispatcher->configure($config);
	}
	function configureWorkersDispather($config){
		$this->_workersDispatcher->configure($config);
	}
	
	function setDependencies(\Pimple $di){
		$this->_di = $di;
	}
	
	function getDependencies(){
		return $this->_di;
	}
	function setLogger(Logger $logger){
		$this->_di['logger'] = $logger;
	}
	function getLogger(){
		return $this->_di['logger'];
	
	}
	function setConfig($config){
		$this->_di['config'] = $config;
	}
	function getConfig(){
		return $this->_di['config'];
	}
	
	
	
	
	
}