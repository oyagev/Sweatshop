<?php
namespace Sweatshop\Queue;

use Sweatshop\Config\Config;

use Sweatshop\Worker\Worker;

use Sweatshop\Message\Message;

class GearmanQueue extends Queue{
	
	protected $_gmclient = NULL;
	protected $_gmworker = NULL;
	protected $_workersQueue = array();
	protected $_workersStack = array();
	
	protected function _doPushMessage(Message $message){
		$results = array();
		//$task = $this->client()->addTask($message->getTopic() , serialize($message) , $results );
		//$this->client()->runTasks();
		//var_dump($task);
		$res =  $this->client()->doBackground($message->getTopic() , serialize($message));
		
		return array();
	}
	protected function _doRegisterWorker($topic, Worker $worker){
		
		$workerClass = get_class($worker);
		$worker_topic = "$topic:$workerClass" ;
		if (empty($this->_workersQueue[$topic])){
			$this->_workersQueue[$topic] = array();
		}
		if (empty($this->_workersStack[$workerClass])){
			$this->_workersStack[$workerClass] = array();
		}
		$this->_workersQueue[$topic][] = $worker;
		$this->_workersStack[$worker_topic] = $worker;
		
		//Register a function on gearnam for every worker
		$this->worker()->addFunction($worker_topic , array($this,'_executeWorkerBackground') );
		
		//Register a global "worker function" that invokes all workers
		$this->worker()->addFunction($topic , array($this,'_executeWorkers'));
	}
	public function _doRunWorkers(){
		while($this->worker()->work()){
				
		}
	}
	
	protected function client(){
		if (!$this->_gmclient){
			$this->_gmclient = new \GearmanClient();
			$this->_gmclient->addServer();
		}
		return $this->_gmclient;
	}
	/**
	 * @return \GearmanWorker
	 */
	protected function worker(){
		if (!$this->_gmworker){
			$this->_gmworker = new \GearmanWorker();
			$this->_gmworker->addServer();
		}
		return $this->_gmworker;
	}
	
	public function _executeWorkers(\GearmanJob $job){
		$workloadStr = $job->workload();
		$topic = $job->functionName();
		
		$results = array();
		if (!empty($this->_workersQueue[$topic])){
			//send the message to each worker on the queue
			foreach ($this->_workersQueue[$topic] as $worker){
				$workerClass = get_class($worker);
				$this->client()->doBackground("$topic:$workerClass" , $workloadStr);
				$results[] = NULL;
			}
		}
		return serialize($results);
	}
	
	public function _executeWorkerBackground(\GearmanJob $job){
		$workloadStr = $job->workload();
		$worker_topic = $job->functionName();
		list($topic,$workerClass) = explode(':',$worker_topic);
		
		
		$workload = unserialize($workloadStr);
		
		$worker = (!empty($this->_workersStack[$worker_topic]) ) ? $this->_workersStack[$worker_topic] : NULL;
		if ($worker instanceof Worker){
			$results =  $worker->execute($workload);
		}else{
			$results=array();
			//TODO: Log error
		}
		
	
		return serialize($results);
	}
	
	
	
	
	
	
	
	
	
	
	
	
}