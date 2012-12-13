<?php

namespace Evently\Interfaces; 

use Evently\Message\Message;


interface MessageableInterface{
	public function newMessage(Message $message);
}