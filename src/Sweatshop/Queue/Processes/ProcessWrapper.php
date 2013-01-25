<?php
namespace Sweatshop\Queue\Processes;

use Sweatshop\Sweatshop;

use Sweatshop\Queue\Queue;

class ProcessWrapper{
	protected $_queue;
	protected $_queueClass;
	protected $_options;
	protected $_workers ;
	protected $_di;
	protected $_PIDs=array();
	
	function __construct(Sweatshop $sweatshop, $queueClass, $workers, $options = array()){
		$this->setDependencies($sweatshop->getDependencies());
		$this->_queueClass = $queueClass;
		$this->_options = array_merge(array(
				'min_processes' => 1,
		) , $options);
		$this->_workers = $workers;
		
	}
	
	public function fork(){
		declare(ticks=1);
		$pid = pcntl_fork();
		if ($pid == -1) {
			$this->getLogger()->fatal(sprintf('%s: Queue "%s" Cannot fork a new thread', get_class($this), get_class($this->_queue)));
		} else if ($pid) {
			// we are the parent - PID>0
		} else {
			//We're the child. PID=0
		}
		return $pid;
	}
	
	public function runWorkers(){
		$this->_queue = $this->createQueue($this->_queueClass, $this->_workers, $this->_options);
		$this->_queue->runWorkers();
	}
	
	
	
	protected function createQueue($queueClass, $workers , $options){
		$this->getLogger()->debug('Adding queue: '.$queueClass);
		$queue = new $queueClass($this->_di['sweatshop'], $options);
		foreach($workers as $workerClass=> $options){
			if (!$workerClass) continue;
			$topics = $options['topics'];
			$worker = new $workerClass($this->_di['sweatshop']);
			foreach($topics as $topic){
				$queue->registerWorker($topic , $worker);
			}
		}
		return $queue;
	}
	
	protected function setDependencies(\Pimple $di){
		$this->_di = $di;
	}
	/**
	 * @return Logger
	 */
	protected function getLogger(){
		return $this->_di['logger'];
	}
	
	
	
	
	
}