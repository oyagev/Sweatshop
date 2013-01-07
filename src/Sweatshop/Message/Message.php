<?php
namespace Sweatshop\Message;

class Message{
	
	protected $id;
	protected $topic;
	protected $params;
	protected $dispatcher;
	
	function __construct($topic, $params=array(), $originalDispatcher=NULL){
		$this->setId($this->generateRandomId());
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

	public function getId()
	{
	    return $this->id;
	}

	public function setId($id)
	{
	    $this->id = $id;
	}
	
	private function generateRandomId(){
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
	}
}