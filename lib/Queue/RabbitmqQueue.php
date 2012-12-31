<?php
namespace Sweatshop\Queue;


use PhpAmqpLib\Message\AMQPMessage;

use PhpAmqpLib\Channel\AMQPChannel;

use PhpAmqpLib\Connection\AMQPConnection;

use Sweatshop\Worker\Worker;

use Sweatshop\Message\Message;

class RabbitmqQueue extends Queue{
	
	private $_conn = NULL;
	private $_channel = NULL;
	private $_workersQueues = array();
	
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
		$this->getChannel()->close();
		$this->getConnection()->close();
		parent::__destruct();
	}
	
	function _doPushMessage(Message $message){
		$this->getChannel()->exchange_declare($this->getExchangeName(), 'direct',false,true,false);
		
		//create a durable message (survive server restart)
		$msg = new AMQPMessage(serialize($message),array(
			'delivery_mode' => 2	
		));
		$this->getChannel()->basic_publish($msg, $this->getExchangeName(), $message->getTopic());
	}
	function _doRegisterWorker($topic, Worker $worker){
		
		if (empty($this->_workersQueues[$topic])){
			$this->_workersQueues[$topic] = array();
		}
		array_push($this->_workersQueues[$topic],$worker);
		
	}
	function _doRunWorkers($options=array()){
		foreach($this->_workersQueues as $topic => $workers){
			foreach($workers as $worker){
				$worker_queue_name = get_class($this).':'.get_class($worker);
				$this->getChannel()->exchange_declare($this->getExchangeName(), 'direct',false,true,false);
				$this->getChannel()->queue_declare($worker_queue_name, false, true, false, false);
				$this->getChannel()->queue_bind($worker_queue_name, $this->getExchangeName(),$topic);
				$channel = $this->getChannel();
				$closure = function($msg) use ($worker,$channel){
					$workload = unserialize($msg->body);
						
					//$worker = (!empty($this->_workersStack[$worker_topic]) ) ? $this->_workersStack[$worker_topic] : NULL;
					if ($worker instanceof Worker){
						$results =  $worker->execute($workload);
						$channel->basic_ack($msg->delivery_info['delivery_tag']);
					}else{
						$results=array();
						//TODO: Log error
					}
				};
				$this->getChannel()->basic_consume($worker_queue_name, '', false, false, false, false, $closure);
			}
		}
		
		
		while(!$this->isCandidateForGracefulKill() && count($this->getChannel()->callbacks)) {
			$this->getChannel()->wait();
			$this->workCycleEnd();
		}
	}
	
	public function _executeWorkerBackground($msg){
		var_dump($msg);
		$message = unserialize($msg->body);
		//var_dump($message);
	} 
	
	/**
	 * @return AMQPConnection;
	 */
	private function getConnection(){
		if (!$this->_conn){
			$this->_conn = new AMQPConnection($this->_options['host'], $this->_options['port'], $this->_options['user'], $this->_options['password']);
		}
		return $this->_conn;
	}
	/**
	 * @return AMQPChannel
	 */
	private function getChannel(){
		if (!$this->_channel){
		$this->_channel = $this->getConnection()->channel();
		}
		return $this->_channel;
		
	}
	
	private function getExchangeName(){
		return 'default';
	}
	
}