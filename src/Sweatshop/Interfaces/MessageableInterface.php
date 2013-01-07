<?php

namespace Sweatshop\Interfaces; 

use Sweatshop\Message\Message;


interface MessageableInterface{
	public function pushMessage(Message $message);
}