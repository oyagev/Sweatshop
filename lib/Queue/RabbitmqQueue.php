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
		$this->getChannel()->exchange_declare($message->getTopic(), 'fanout',false,false,false);
		$msg = new AMQPMessage(serialize($message));
		$this->getChannel()->basic_publish($msg, $message->getTopic());
	}
	function _doRegisterWorker($topic, Worker $worker){
		$this->getChannel()->exchange_declare($topic, 'fanout',false,false,false);
		list($queue_name, ,) = $this->getChannel()->queue_declare("", false, false, true, false);
		$this->getChannel()->queue_bind($queue_name, $topic);
		$this->getChannel()->basic_consume($queue_name, '', false, true, false, false, array($this,'_executeWorkerBackground'));
	}
	function _doRunWorkers($options=array()){
		while(count($this->getChannel()->callbacks)) {
			$this->getChannel()->wait();
		}
	}
	
	public function _executeWorkerBackground($msg){
		var_dump($msg);
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
	
}