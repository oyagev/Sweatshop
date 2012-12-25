<?php
namespace Sweatshop\Queue;

use Sweatshop\Config\Config;

use Sweatshop\Queue\Queue;

use Sweatshop\Message\Message;
use Sweatshop\Interfaces\MessageableInterface;
use Sweatshop\Worker\Worker;

class QueueManager implements MessageableInterface{
	
	protected $inAppQueue;
	protected $offAppQueue;
	
	
	
	public function __construct($di){
		$this->inAppQueue 				= new InternalQueue($config);
		$this->offAppQueue 				= new ExternalQueue($config);
		
	}
	
	function pushMessage(Message $message){
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