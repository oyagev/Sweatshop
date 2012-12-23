<?php
namespace Evently;

use Monolog\Handler\SyslogHandler;

use Monolog\Handler\StreamHandler;

use Monolog\Logger;

use Evently\Config\Exception;

use Evently\Config\Config;

use Evently\Queue\QueueManager;

use Evently\Dispatcher\Dispatcher;

use Evently\Message\Message;

use Evently\Worker\Worker;


class Evently{
	
	protected $_di = NULL;
	static protected $instance;
	
	/**
	 * @return Evently\Evently
	 */
	static public function getInstance(){
		if (!static::$instance) {
			$cls = __CLASS__;
			static::$instance = new $cls();
		}
		return static::$instance ;
			
	}
	
	public function __construct($config=NULL){
		if ($config){
			$this->configure($config);
		}
		
	}
	
	public function configure($config){
		$di = new \Pimple();
		$di['config'] = $this->buildConfigObj($config);
		$di['log'] = $di->share(function($di){
			$config = $di['config'];
			$log = new Logger(__CLASS__);
			switch($config['log']['output']){
				case 'stream' :
					if ($config['log']['logfile']){
						$logHandler = new StreamHandler($config['log']['logfile'], $config['log']['level']);
						break;
					}
					
				case 'syslog':
					$logHandler = new SyslogHandler(__CLASS__);
					break;
				default:
					$logHandler = new StreamHandler("php://stdout", $config['log']['level']);
			}
			$log->pushHandler($logHandler);
			return $log;
		});
		
		$this->_di = $di;
		
		$log = $di['log'];
		$log->debug('Done configuring');
		
	}
	
	
	
	public function dispatch(Message $message){
		return $this->dispatcher()->dispatch($message);
	}
	
	public function registerWorker($topic, Worker $worker){
		$this->queueManager()->registerWorker($topic , $worker);
	}
	
	public function runWorkers(){
		$this->queueManager()->runWorkers();
	}
	
	/**
	 * @return Dispatcher
	 */
	protected function dispatcher(){
		if (!$this->dispatcher){
			$this->dispatcher = new Dispatcher($this->config , $this->queueManager());
		}
		return $this->dispatcher;
	}
	
	/**
	 * @return QueueManager
	 */
	protected function queueManager(){
		if (!$this->queueManager){
			$this->_di['log']->debug('Setting up Queue Manager');
			$this->queueManager = new QueueManager($this->_di);
		}
		return $this->queueManager;
	}
	
	
	private function buildConfigObj($mixedConfigParam){
		if (is_string($mixedConfigParam) ){
			if (file_exists($mixedConfigParam)){
				include $config;
				$config = new Config($config);
			}else{
				throw new Exception("Unable to find config file: ". $config);
			}
		}elseif(is_array($mixedConfigParam)){
			$config = new Config($mixedConfigParam);
		}elseif($mixedConfigParam instanceOf Config){
			$config = $mixedConfigParam;
		}else{
			throw new Exception("Unable to read configuration");
		}
		return $config;
	}
	
	
	
	
}