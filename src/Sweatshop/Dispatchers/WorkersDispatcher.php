<?php
namespace Sweatshop\Dispatchers;

use Pimple\Container;
use Sweatshop\Queue\Processes\ProcessGroup;

use Sweatshop\Queue\Queue;

use Sweatshop\Sweatshop;

use Sweatshop\Queue\Processes\ProcessWrapper;

use Monolog\Logger;

use Sweatshop\Queue\Threads\ThreadsManager;

class WorkersDispatcher {
	
	
	protected $_di = NULL;
	protected $_childPIDs = array();
	protected $_processes = array();
	protected $_processGroups = array();
	
	public function __construct(Sweatshop $sweatshop){
		$this->setDependencies($sweatshop->getDependencies());
	
	}

	
	public function registerWorker($queue_class, $topics=array(), $worker=NULL, $options=array()){
		
		if (!is_array($topics)){
			$topics = array($topics);
		}
		
		$processGroup = new ProcessGroup($this->_di['sweatshop'], $queue_class, $worker, $topics,$options);
		array_push($this->_processGroups, $processGroup);
		
		/*
		$processArr = array(
			'queue' 	=> $queue_class,
			'topics'	=> $topics,
			'worker'	=> $worker,
			'options' 	=> array_merge($this->_defaultOptions,$options) 	
		);
		array_push($this->_processes, $processArr);
		*/
	}
	
	public function runWorkers(){
		
		declare(ticks = 1);
		
		
		/* @var $processGroup ProcessGroup */
		foreach($this->_processGroups as $processGroup){
			$processGroup->syncProcesses();
		}
		
		pcntl_signal(SIGINT, array($this,'signal_handlers'),false);
		pcntl_signal(SIGTERM, array($this,'signal_handlers'),false);
		
		while ( ($pid=pcntl_wait($status)) !=-1){
			
			
			foreach($this->_processGroups as $processGroup){
				$processGroup->notifyDeadProcess($pid,$status);
			}
			
		}
	}
	
	public function signal_handlers($signo){
		
		$this->getLogger()->debug(sprintf("Sweatshop got signal %d",$signo));
		foreach($this->_processGroups as $processGroup){
			$processGroup->killAll();
		}
		exit;
	}
	
	
	
	
	
	public function setDependencies(Container $di){
		$this->_di = $di;
	}
	
	public function getDependencies(){
		return $this->_di;
	}
	public function setLogger(Logger $logger){
		$this->_di['logger'] = $logger;
	}
	/**
	 * @return Logger
	 */
	public function getLogger(){
		return $this->_di['logger'];
	
	}
	
	
	protected function forkAndRun(ProcessWrapper $processWrapper){
		$pid = $processWrapper->fork();
		if ($pid == 0){
			//I'm the child!
			//Run the workers
			$processWrapper->runWorkers();
			
			//Basically if we're here, this means that the processes terminated!
			
			exit(1);
		}else{
			//We're the parent process!
			//Keep the process wrapper with PID
			$this->_childPIDs[$pid] = $processWrapper;
		}
	}
	
	
}