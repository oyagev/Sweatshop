<?php

namespace Sweatshop\Queue;

use Sweatshop\Worker\Worker;

use Sweatshop\Message\Message;

class GearmanExchangeQueue extends GearmanQueue{
	const TOPIC_ALL = 'sweatshop/gearman/exchange/*';
	const TOPIC_ADD_WORKER = 'sweatshop/gearman/exchange/add_worker';
	
	static public $directTopics = array(
			self::TOPIC_ALL,
			self::TOPIC_ADD_WORKER
	);
	
	protected $_topic_exchange = array();
	
	function __construct($sweatshop,$options=array()){
		parent::__construct($sweatshop,$options);
		
	}
	
	function _doPushMessage(Message $message){
		//Route all messages to the exchange topic
		$res =  $this->client()->doBackground(self::TOPIC_ALL , serialize($message));
		return array();
	}
	
	
	
	protected function _doRegisterWorker($topic, Worker $worker){
		
	}
	
	public function _doRunWorkers(){
		$this->_setupExchange();
		if ($this->isCandidateForGracefulKill()){
			$this->getLogger()->err(sprintf('Queue "%s" is exiting without performing any work. Please check configurations.', get_class($this)));
			return;
		}
		
		
		while(!$this->isCandidateForGracefulKill() && $this->worker()->work()){
				
			$this->workCycleEnd();
		}
	}
	
	private function addExchangeTopic($from,$to){
		if (empty($this->_topic_exchange[$from])){
			$this->_topic_exchange[$from]=array();
		}
		if (!in_array($to, $this->_topic_exchange[$from])){
			$this->getLogger()->debug("Gearman Exchange: Adding exchange topic", array(
					'from' 	=> $from,
					'to' 	=> $to
			));
			array_push($this->_topic_exchange[$from],$to);
		}
	}
	private function dispatchToExchangeTopics(Message $message){
		$sourceTopic = $message->getTopic();
		if (!empty($this->_topic_exchange[$sourceTopic])){
			foreach($this->_topic_exchange[$sourceTopic] as $new_topic){
				$this->getLogger()->debug("Gearman Exchange: Routing message to ", array(
					'from' 	=> $sourceTopic,
					'to' 	=> $new_topic
				));
				$res = $this->client()->doBackground($new_topic, serialize($message));
			}
		}
	}
	private function _setupExchange(){
		$this->worker()->addFunction(self::TOPIC_ADD_WORKER , array($this,'_callbackAddExchangeTopics') );
		$this->worker()->addFunction(self::TOPIC_ALL , array($this,'_callbackDoExchange') );
	}
	
	/**
	 * Declate an new topic route
	 * @param \GearmanJob $job
	 */
	public function _callbackAddExchangeTopics(\GearmanJob $job){
	
		$workloadStr = $job->workload();
		$message = unserialize($workloadStr);
		$params = $message->getParams();
		foreach($params['topics'] as $from=>$to){
			$this->addExchangeTopic($from,$to);
		}
	}
	
	public function _callbackDoExchange(\GearmanJob $job){
		$workloadStr = $job->workload();
		$message = unserialize($workloadStr);
		$params = $message->getParams();
		
		$this->dispatchToExchangeTopics($message);
	}
}