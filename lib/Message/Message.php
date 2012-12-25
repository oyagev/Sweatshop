<?php
namespace Sweatshop\Message;

class Message{
	
	protected $topic;
	protected $params;
	protected $dispatcher;
	
	function __construct($topic, $params=array(), $originalDispatcher=NULL){
		$this->setTopic($topic);
		$this->setParams($params);
		$this->setOriginalDispatcher($originalDispatcher);
	}
	

	public function getTopic()
	{
	    return $this->topic;
	}

	public function setTopic($topic)
	{
	    $this->topic = $topic;
	}

	public function getParams()
	{
	    return $this->params;
	}

	public function setParams($params)
	{
	    $this->params = $params;
	}

	public function getOriginalDispatcher()
	{
	    return $this->dispatcher;
	}

	public function setOriginalDispatcher($dispatcher)
	{
	    $this->dispatcher = $dispatcher;
	}
}