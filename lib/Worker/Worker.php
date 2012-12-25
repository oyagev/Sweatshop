<?php
namespace Sweatshop\Worker;

use Sweatshop\Interfaces\MessageableInterface;
use Sweatshop\Message\Message;

abstract class Worker implements MessageableInterface{
	
	const ENV_INTERNAL = 'env/internal';
	const ENV_EXTERNAL = 'env/external';
	
	protected $env;
	protected $background;
	
	public function __construct($config=array()){
		$this->configure($config);
		$this->_doTearUp();
	}
	
	public function configure($config=array()){
		$defConfig = array(
			'env' 			=> self::ENV_INTERNAL , 
			'background' 	=> false
		);
		$config = array_merge($defConfig,$config);
		$this->setEnv($config['env']);
		$this->setBackground($config['background']);
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
	
	
	
	abstract protected function _doTearUp();
	abstract protected function _doExecute(Message $message);
	abstract protected function _doTearDown();

	public function getEnv()
	{
	    return $this->env;
	}

	public function setEnv($env)
	{
	    $this->env = $env;
	}

	public function getBackground()
	{
	    return $this->background;
	}

	public function setBackground($background)
	{
	    $this->background = $background;
	}
}