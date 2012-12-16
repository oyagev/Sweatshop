<?php
namespace Evently\Dispatcher;

use Evently\Config\Config;

use Evently\Message\Message;
use Evently\Queue\QueueManager;

class Dispatcher{
	
	protected $queueManager = NULL;
	function __construct(Config $config , QueueManager $queueManager){
		$this->queueManager = $queueManager;
	}
	
	public function dispatch(Message $message){
		$response = $this->queueManager->newMessage($message);
		return $response;
	}
	
	
}