<?php
namespace Sweatshop\Worker;

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
		$this->_doTearDown();
	}
	
	public function execute(Message $message){
		return $this->_doExecute($message);
	}
	
	function pushMessage(Message $message){
		return $this->execute($message);
	}
	
	
	
	protected function _doTearUp(){
		
	}
	protected function _doTearDown(){
	
	}
	abstract protected function _doExecute(Message $message);
}