<?php
namespace Sweatshop\Worker;

use Monolog\Logger;

use Sweatshop\Sweatshop;

use Sweatshop\Interfaces\MessageableInterface;
use Sweatshop\Message\Message;

abstract class Worker implements MessageableInterface{
	
	
	
	protected $_di;
	
	
	public function __construct(Sweatshop $sweatshop){
		$this->setDependencies($sweatshop->getDependencies());
		$this->_doTearUp();
	}
	
	function setDependencies(\Pimple $di){
		$this->_di = $di;
	}
	
	public function configure(){
		
	}
	
	public function __destruct(){
		$this->getLogger()->info(sprintf('Worker "%s": tearing down',get_class($this)));
		$this->_doTearDown();
	}
	
	public function execute(Message $message){
		$this->getLogger()->info(sprintf('Worker "%s" executing message "%s"',get_class($this),$message->getId()));
		return $this->_doExecute($message);
	}
	
	function pushMessage(Message $message){
		return $this->execute($message);
	}
	/**
	 * @return Logger
	 */
	public function getLogger(){
		return $this->_di['logger'];
	}
	
	
	
	protected function _doTearUp(){
		
	}
	protected function _doTearDown(){
	
	}
	abstract protected function _doExecute(Message $message);
}