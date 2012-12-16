<?php
namespace Evently\Queue;

use Evently\Worker\Worker;

use Evently\Message\Message;

use Evently\Queue\Drivers\GearmanDriver;

use Evently\Queue\Drivers\Driver;

class ExternalQueue extends Queue {
	protected $_driver = NULL;
	protected $_config;
	
	function __construct($config=array()){
		$this->_config = $config;
	}
	
	function run(){
		$this->getDriver()->run();
	}
	
	protected function _doPushMessage(Message $message){
		return $this->getDriver()->newMessage($message);
	}
	protected function _doRegisterWorker($topic, Worker $worker){
		return $this->getDriver()->registerWorker($topic, $worker);
	}
	protected function _doRunWorkers(){
		return $this->getDriver()->runWorkers();
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