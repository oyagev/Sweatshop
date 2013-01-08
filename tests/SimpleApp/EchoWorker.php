<?php
use Sweatshop\Message\Message;

use Sweatshop\Worker\Worker;

class EchoWorker extends Worker{
	
	
	function work(Message $message){
		$params =  $message->getParams();
		return $params['value'];
	}
}