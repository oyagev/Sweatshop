<?php

use Sweatshop\Message\Message;

use Sweatshop\Worker\Worker;

class BackgroundLoggerWorker extends Worker{
	function tearUp(){
		
	}
	function work(Message $message){
		
		$this->getLogger()->info("Message was: " . json_encode($message->getParams()));
	}
}