<?php
namespace Sweatshop\Dispatchers;

use Sweatshop\Queue\Queue;

use Sweatshop\Sweatshop;

use Sweatshop\Queue\Processes\ProcessWrapper;

use Monolog\Logger;

use Sweatshop\Queue\Threads\ThreadsManager;

class WorkersDispatcher {
	
	protected $_defaultOptions = array(
			'min_processes' => 1,
			'max_work_cycles' => -1
	);
	protected $_di = NULL;
	protected $_childPIDs = array();
	protected $_processes = array();
	
	public function __construct(Sweatshop $sweatshop){
		$this->setDependencies($sweatshop->getDependencies());
	
	}

	
	public function registerWorker($queue_class, $topics, $worker, $options){
		
		if (!is_array($topics)){
			$topics = array($topics);
		}
		
		$processArr = array(
			'queue' 	=> $queue_class,
			'topics'	=> $topics,
			'worker'	=> $worker,
			'options' 	=> array_merge($this->_defaultOptions,$options) 	
		);
		array_push($this->_processes, $processArr);
		
	}
	
	public function runWorkers(){
		
		
		foreach($this->_processes as $processArr){
			$queueOptions = $processArr['options'];
			$queueClass = $processArr['queue'];
			$workerClass = $processArr['worker'];
			$workerOptions = array(
				'topics'	=> $processArr['topics']
			);
			$this->getLogger()->debug('Launching Queue with options',array('queue'=>$queueClass,'options'=>$queueOptions));
			
			for($i=0; $i<$queueOptions['min_processes']; $i++){
				$process = new ProcessWrapper($this->_di['sweatshop'], $queueClass, array($workerClass => $workerOptions), $queueOptions);
				$this->forkAndRun($process);
			}
		}

		while ( ($pid=pcntl_wait($status)) !=-1){
			if (!empty($this->_childPIDs[$pid])){
				$process = $this->_childPIDs[$pid];
				unset($this->_childPIDs[$pid]);
				$this->forkAndRun($process);
			}
		}
	}
	
	
	
	
	
	public function setDependencies(\Pimple $di){
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
			exit(0);
		}else{
			//We're the parent process!
			//Keep the process wrapper with PID
			$this->_childPIDs[$pid] = $processWrapper;
		}
	}
	
	
}