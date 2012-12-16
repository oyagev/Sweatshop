<?php
namespace Evently\Queue;

use Evently\Config\Config;

use Evently\Queue\Queue;

use Evently\Message\Message;
use Evently\Interfaces\MessageableInterface;
use Evently\Worker\Worker;

class QueueManager implements MessageableInterface{
	
	protected $inAppQueue;
	protected $offAppQueue;
	
	
	
	public function __construct(Config $config){
		$this->inAppQueue 				= new InternalQueue($config);
		$this->offAppQueue 				= new ExternalQueue($config);
		
	}
	
	function newMessage(Message $message){
		$responses = array_merge(
				array() , 
				$this->inAppQueue->newMessage($message), 
				$this->offAppQueue->newMessage($message) );
		
		return $responses;
	}
	
	function registerWorker($topic, Worker $worker){
		if ($worker->getBackground() || $worker->getEnv()==Worker::ENV_EXTERNAL){
			$this->offAppQueue->registerWorker($topic, $worker);
		}else{
			$this->inAppQueue->registerWorker($topic, $worker);
		}
		return true;
	}
	
	function runWorkers(){
		$this->offAppQueue->runWorkers();
	}
	
}