<?php
use Sweatshop\Message\Message;

use Sweatshop\Worker\Worker;

class BackgroundPrintWorker extends Worker{
	
	function _doExecute(Message $message){
		$params =  $message->getParams();
		$topic = $message->getTopic();
		
		printf("Processed job for topic '%s', value was '%s'".PHP_EOL,$topic,$params['value']);
		
	}
}