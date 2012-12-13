<?php
namespace Evently\Dispatcher;

use Evently\Message\Message;
use Evently\Queue\QueueManager;

class Dispatcher{
	
	protected $queueManager = NULL;
	function __construct($config= array() , QueueManager $queueManager){
		$this->queueManager = $queueManager;
	}
	
	public function dispatch(Message $message){
		$response = $this->queueManager->newMessage($message);
		return $response;
	}
	
	
}