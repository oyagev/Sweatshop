<?php
namespace Sweatshop\Message;

class RoutedMessage extends Message{
	protected $routeTopic;
	protected $originalMessage ;

	function __construct($topic, Message $message){
		$this->setRouteTopic($topic);
		$this->setOriginalMessage($message);
	}

	public function getRouteTopic()
	{
	    return $this->routeTopic;
	}

	public function setRouteTopic($routeTopic)
	{
	    $this->routeTopic = $routeTopic;
	}

	/**
	 * @return Message
	 */
	public function getOriginalMessage()
	{
	    return $this->originalMessage;
	}

	public function setOriginalMessage($originalMessage)
	{
	    $this->originalMessage = $originalMessage;
	}
	
	function getId(){
		return $this->getOriginalMessage()->getId();
	}
	function getOriginalDispatcher(){
		return $this->getOriginalMessage()->getOriginalDispatcher();
	}
	function getParams(){
		return $this->getOriginalMessage()->getParams();
	}
	function getTopic(){
		return $this->getOriginalMessage()->getTopic();
	}
	
	function setId($id){
		return $this->getOriginalMessage()->setId($id);
	}
	function setOriginalDispatcher($dispatcher){
		return $this->getOriginalMessage()->setOriginalDispatcher($dispatcher);
	}
	function setParams($params){
		return $this->getOriginalMessage()->setParams($params);
	}
	function setTopic($topic){
		return $this->getOriginalMessage()->setTopic($topic);
	}
}