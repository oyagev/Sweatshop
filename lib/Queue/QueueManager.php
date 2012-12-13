<?php
namespace Evently\Queue;

use Evently\Queue\Queue;

use Evently\Message\Message;
use Evently\Interfaces\MessageableInterface;
use Evently\Worker\Worker;

class QueueManager implements MessageableInterface{
	
	protected $inAppQueue;
	protected $offAppSyncQueue;
	protected $offAppBackgroundQueue;
	
	
	
	public function __construct($config=array()){
		$this->inAppQueue 				= new InternalQueue($config);
		$this->offAppSyncQueue 			= new InternalQueue($config);
		$this->offAppBackgroundQueue 	= new InternalQueue($config);
	}
	
	function newMessage(Message $message){
		$responses = array_merge(
				array() , 
				$this->offAppBackgroundQueue->newMessage($message),
				$this->inAppQueue->newMessage($message), 
				$this->offAppSyncQueue->newMessage($message) );
		
		return $responses;
	}
	
	function registerWorker($topic, Worker $worker){
		if ($worker->getBackground()){
			$this->offAppBackgroundQueue->registerWorker($topic, $worker);
		}elseif($worker->getEnv()==Worker::ENV_EXTERNAL){
			$this->offAppSyncQueue->registerWorker($topic, $worker);
		}else{
			$this->inAppQueue->registerWorker($topic, $worker);
		}
		return true;
	}
	
	
}