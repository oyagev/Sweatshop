<?php
namespace Sweatshop\Dispatchers;

use Monolog\Logger;
use Sweatshop\Message\Message;
use Sweatshop\Interfaces\MessageableInterface;
use Sweatshop\Queue\Queue;

class MessageDispatcher implements MessageableInterface{
	protected $_globalOptions = array();
	protected $_queues = array();
	protected $logger = NULL;

	public function __construct(Logger $logger){
		$this->setLogger($logger);
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

	public function setLogger(Logger $logger){
		$this->logger = $logger;
	}

    public function getLogger(){
		return $this->logger;

	}
}