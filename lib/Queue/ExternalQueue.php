<?php
namespace Sweatshop\Queue;

use Sweatshop\Config\Config;

use Sweatshop\Worker\Worker;

use Sweatshop\Message\Message;

use Sweatshop\Queue\Drivers\GearmanDriver;

use Sweatshop\Queue\Drivers\Driver;

class ExternalQueue extends Queue {
	protected $_driver = NULL;
	protected $_config;
	
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