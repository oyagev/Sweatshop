<?php
namespace Evently;

use Evently\Queue\QueueManager;

use Evently\Dispatcher\Dispatcher;

use Evently\Message\Message;

use Evently\Worker\Worker;


class Evently{
	
	protected $dispatcher = NULL ;
	protected $queueManager = NULL;
	protected $config = NULL;
	static protected $instance;
	
	/**
	 * @return Evently\Evently
	 */
	static public function getInstance(){
		if (!static::$instance) {
			$cls = __CLASS__;
			static::$instance = new $cls();
		}
		return static::$instance ;
			
	}
	
	public function __construct($config=array()){
		
	}
	
	public function configure($config){
		$this->config = $config;
	}
	
	
	
	public function dispatch(Message $message){
		return $this->dispatcher()->dispatch($message);
	}
	
	public function registerWorker($topic, Worker $worker){
		$this->queueManager()->registerWorker($topic , $worker);
	}
	
	public function run(){
		$this->queueManager()->run();
	}
	
	/**
	 * @return Dispatcher
	 */
	protected function dispatcher(){
		if (!$this->dispatcher){
			$this->dispatcher = new Dispatcher($this->config , $this->queueManager());
		}
		return $this->dispatcher;
	}
	
	/**
	 * @return QueueManager
	 */
	protected function queueManager(){
		if (!$this->queueManager){
			$this->queueManager = new QueueManager($this->config);
		}
		return $this->queueManager;
	}
	
	
	
	
	
}