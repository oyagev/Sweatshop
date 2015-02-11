<?php
namespace Sweatshop\Dispatchers;

use Pimple\Container;
use Sweatshop\Message\Message;

use Sweatshop\Interfaces\MessageableInterface;

use Sweatshop\Queue\Queue;

use Sweatshop\Sweatshop;

class MessageDispatcher implements MessageableInterface{
	protected $_globalOptions = array();
	protected $_queues = array();
	protected $_di = NULL;
	
	public function __construct(Sweatshop $sweatshop){
		$this->setDependencies($sweatshop->getDependencies());
		
	}
	
	public function configure($config=array()){
		foreach($config as $queueName){
			$queueClass = Queue::toClassName($queueName);
			$queue = new $queueClass($this->_di['sweatshop']);
			$this->addQueue($queue);
		}
	}
		
	public function addQueue(Queue $queue){
		array_push($this->_queues, $queue);
		return $this;
	} 
	
	public function pushMessage(Message $message){
		$this->getLogger()->debug(sprintf('Sweatshop pushing message id "%s"',$message->getId()), array('message_id'=>$message->getId(), "topic" => $message->getTopic()));
		$result = array();
		foreach ($this->_queues as $queue){
			$res = $queue->pushMessage($message);
			if (is_array($res)){
				$result = array_merge($result, $res);
			}
				
		}
		return $result;
	}
	public function setDependencies(Container $di){
		$this->_di = $di;
	}
	
	public function getDependencies(){
		return $this->_di;
	}
	public function setLogger(Logger $logger){
		$this->_di['logger'] = $logger;
	}
	public function getLogger(){
		return $this->_di['logger'];
	
	}
	
}