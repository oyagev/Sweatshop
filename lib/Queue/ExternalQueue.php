<?php
namespace Evently\Queue;

use Evently\Queue\Drivers\GearmanDriver;

use Evently\Queue\Drivers\Driver;

class ExternalQueue extends Queue {
	protected $_driver = NULL;
	protected $_config;
	
	function __construct($config=array()){
		$this->_config = $config;
	}
	
	function _doPullMessage($blocking=FALSE){
		return $this->getDriver()->pullMessage($blocking);
	}
	function _doPushMessage(Message $message){
		return $this->getDriver()->newMessage($message);
	}
	function _doRegisterWorker($topic, Worker $worker){
		return $this->getDriver()->registerWorker($topic, $worker);
	}
	

	/**
	 * @return Driver
	 */
	protected function getDriver()
	{
		if (!$this->_driver){
			$this->_driver = new GearmanDriver($this->_config);
		}
	    return $this->_driver;
	}

	protected function setDriver(Driver $_driver)
	{
	    $this->_driver = $_driver;
	}
}