<?php
use Sweatshop\Message\Message;

use Sweatshop\Worker\Worker;

class EchoWorker extends Worker{
	function _doTearDown(){}
	function _doTearUp(){}
	
	function _doExecute(Message $message){
		$params =  $message->getParams();
		return $params['value'];
	}
}