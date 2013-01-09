<?php
namespace Sweatshop\Queue\Threads;

use Sweatshop\Sweatshop;

use Sweatshop\Queue\Queue;

class ThreadsManager{
	protected $_queue;
	protected $_options;
	protected $_di;
	protected $_PIDs=array();
	
	function __construct(Sweatshop $sweatshop, Queue $queue, $globalOptions = array()){
		$this->setDependencies($sweatshop->getDependencies());
		$this->_queue = $queue;
		$this->_options = array_merge(array(
				'min_threads_per_queue' => 1,
				'min_threads_per_worker' => 0,
				'wait_threads_exit' => true
		) , $globalOptions, $queue->getOptions());
	}
	
	function run(){
		$options = $this->_options;
		try{
			if ($options['min_threads_per_queue'] > 0){
				declare(ticks=1);
				for ($i=0;$i<$options['min_threads_per_queue'] ; $i++){
					$res = $this->createNewChildThread();
					if (is_int($res) && $res != 0){
						//do nothing - we're the parent
					}else{
						//we're probably the child - so break the loop and exit
						exit(0);
					}
				}
			}elseif ($options['min_threads_per_worker'] > 0){
		
			}else{
				return $this->_queue->runWorkers($options);
			}
		
		}catch (\Exception $e){
			$this->getLogger()->err(sprintf('Unable to run workers on queue "%s". Message was: %s',get_class($this),$e->getMessage()));
		}
	}
	
	public function notifyExitPID($pid, $status=0){
		$index = array_search($pid, $this->_PIDs);
		if ( FALSE !== $index ){
			$this->getLogger()->info(sprintf('%s: Queue "%s" exited, launching replacement thread', get_class($this),get_class($this->_queue)));
			array_splice($this->_PIDs, $index,1);
			$res = $this->createNewChildThread();
		}
	}
	
	/**
	 * @return -1 for error, $pid>0 if parent, 0 if child
	 */
	protected function createNewChildThread(){
		$pid = pcntl_fork();
		if ($pid == -1) {
			$this->getLogger()->fatal(sprintf('%s: Queue "%s" Cannot fork a new thread', get_class($this), get_class($this->_queue)));
		} else if ($pid) {
			// we are the parent - do nothing
			array_push($this->_PIDs, $pid);
		} else {
			return $this->_queue->runWorkers($this->_options);
			
		}
		return $pid;
		
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