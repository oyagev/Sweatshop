<?php
namespace Sweatshop\Queue;

use Sweatshop\Worker\Worker;

use Sweatshop\Message\Message;

class RabbitmqQueue extends Queue{
	
	private $_conn = NULL;
	private $_channel = NULL;
	private $_workersQueues = array();
	private $_queues = array();
	private $_exchange = NULL;
	
	function __construct($sweatshop,$options=array()){
		parent::__construct($sweatshop,$options);
		$this->_options = array_merge(array(
				'host' => 'localhost',
				'port' => '5672',
				'user' => 'guest',
				'password' => 'guest'
		),$this->_options,$options);
	}
	
	function __destruct(){
		//$this->getChannel()->close();
		$this->getConnection()->disconnect();
		parent::__destruct();
	}
	
	function _doPushMessage(Message $message){
		$exchange = $this->getExchange();
		$message = $exchange->publish(serialize($message), $message->getTopic(), AMQP_NOPARAM, array('delivery_mode'=>2) );
		
		/*
		$this->getChannel()->exchange_declare($this->getExchangeName(), 'direct',false,true,false);
		
		//create a durable message (survive server restart)
		$msg = new AMQPMessage(serialize($message),array(
			'delivery_mode' => 2	
		));
		$this->getChannel()->basic_publish($msg, $this->getExchangeName(), $message->getTopic());
		*/
		
		
	}
	function _doRegisterWorker($topic, Worker $worker){
		
		if (empty($this->_workersQueues[$topic])){
			$this->_workersQueues[$topic] = array();
		}
		array_push($this->_workersQueues[$topic],$worker);
		
	}
	function _doRunWorkers(){
		
		foreach($this->_workersQueues as $topic => $workers){
			foreach($workers as $worker){
				$worker_queue_name = get_class($this).':'.get_class($worker);
				
				$exchange = $this->getExchange();
				
				$queue = new \AMQPQueue($this->getChannel());
				$queue->setName($worker_queue_name);
				$queue->setFlags(AMQP_DURABLE);
				$queue->declare();
				$queue->bind($this->getExchangeName(), $topic);
				
				
				array_push($this->_queues,array(
					'queue' => $queue,
					'worker' => $worker
					
				));
			}
		}
		
		while(!$this->isCandidateForGracefulKill() ) {
			foreach($this->_queues as $q){
				$queue = $q['queue'];
				$worker = $q['worker'];
				$message = $queue->get();
				if ($message){
					$workload = unserialize($message->getBody());
		
					//$worker = (!empty($this->_workersStack[$worker_topic]) ) ? $this->_workersStack[$worker_topic] : NULL;
					if ($worker instanceof Worker){
						$results =  $worker->execute($workload);
						//$channel->basic_ack($msg->delivery_info['delivery_tag']);
					}else{
						$results=array();
						//TODO: Log error
					}
					$queue->ack($message->getDeliveryTag());
					$this->workCycleEnd();
					
				}else{
					usleep(100000);
				}
			}
			
		}
	}
	
	public function _executeWorkerBackground($msg){
		var_dump($msg);
		$message = unserialize($msg->body);
		//var_dump($message);
	} 
	
	/**
	 * @return \AMQPConnection;
	 */
	private function getConnection(){
		if (!$this->_conn){
			$this->_conn = new \AMQPConnection(array(
				'host' => $this->_options['host'], 
				'port' => $this->_options['port'], 
				'login' => $this->_options['user'], 
				'password' => $this->_options['password']
			));
			$this->_conn->connect();
			//TODO: check if connection is alive
		}
		return $this->_conn;
	}
	/**
	 * @return AMQPChannel
	 */
	private function getChannel(){
		if (!$this->_channel){
		
		$this->_channel = new \AMQPChannel($this->getConnection());
		}
		return $this->_channel;
		
	}
	
	private function getExchangeName(){
		return 'default';
	}
	private function getExchange(){
		if (!$this->_exchange){
			$exchange   = new \AMQPExchange($this->getChannel());
			$exchange->setName($this->getExchangeName());
			$exchange->setType(AMQP_EX_TYPE_DIRECT);
			$exchange->setFlags(AMQP_DURABLE);
			$exchange->declare();
			$this->_exchange = $exchange;
		}
		return $this->_exchange;
	}
	
}