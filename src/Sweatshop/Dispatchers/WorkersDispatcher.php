<?php
namespace Sweatshop\Dispatchers;

use Sweatshop\Queue\Queue;

use Sweatshop\Sweatshop;

use Sweatshop\Queue\Processes\ProcessWrapper;

use Monolog\Logger;

use Sweatshop\Queue\Threads\ThreadsManager;

class WorkersDispatcher {
	
	protected $_globalOptions = array(
			'min_processes_per_queue' => 1,
			'max_work_cycles' => -1
	);
	protected $_queues = array();
	protected $_di = NULL;
	protected $_config = array();
	protected $_childPIDs = array();
	
	public function __construct(Sweatshop $sweatshop){
		$this->setDependencies($sweatshop->getDependencies());
	
	}
	
	public function configure($config){
		$this->_config = $config;
	}
	
	public function runWorkers(){
		$this->setupConfigurations();
		
		
		
		if ($this->_globalOptions['min_processes_per_queue'] == 0 ){
			$this->getLogger()->warn('Launching workers without processes support is not recommended');
		}
		
		foreach ($this->_queues as $queueClass => $options){
			$workers = $options['workers'];
			$queueOptions = array_merge($this->_globalOptions , !empty($options['options']) ? $options['options'] : array()  );
			
			foreach ($workers as $workerClass => $workerOptions){
				$this->getLogger()->debug('Launching Queue with options',array('queue'=>$queueClass,'options'=>$queueOptions));
				for($i=0; $i<$queueOptions['min_processes_per_queue']; $i++){
					$process = new ProcessWrapper($this->_di['sweatshop'], $queueClass, array($workerClass => $workerOptions), $queueOptions);
					$this->forkAndRun($process);
				}
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
	
	protected function setupConfigurations(){
		if (!empty($this->_config['options'])){
			$this->_globalOptions = array_merge($this->_globalOptions, $this->_config['options']);
		}
		if (!empty($this->_config['queues'])){
			foreach($this->_config['queues'] as $queueName => $options){
				$queueClass=  Queue::toClassName($queueName);
				$this->_queues[$queueClass] = $options;
			}	
		}
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