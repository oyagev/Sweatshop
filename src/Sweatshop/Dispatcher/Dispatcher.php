<?php
namespace Sweatshop\Dispatcher;

use Monolog\Logger;

use Sweatshop\Config\Config;

use Sweatshop\Message\Message;
use Sweatshop\Queue\QueueManager;

class Dispatcher{
	
	
	protected $_queueManager = NULL;
	protected $_config = NULL;
	protected $_logger = NULL;
	
	function __construct(Config $config , QueueManager $queueManager){
		$this->_queueManager = $queueManager;
	}
	
	public function dispatch(Message $message){
		$response = $this->_queueManager->newMessage($message);
		return $response;
	}
	
	

	public function getQueueManager()
	{
	    return $this->_queueManager;
	}

	public function setQueueManager(QueueManager $_queueManager)
	{
	    $this->_queueManager = $_queueManager;
	}

	public function getConfig()
	{
	    return $this->_config;
	}

	public function setConfig(Config $_config)
	{
	    $this->_config = $_config;
	}

	public function getLogger()
	{
	    return $this->_logger;
	}

	public function setLogger(Logger $_logger)
	{
	    $this->_logger = $_logger;
	}
}