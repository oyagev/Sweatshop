<?php
namespace Sweatshop\Worker\Gearman;

use Sweatshop\Message\RoutedMessage;

use Sweatshop\Message\Message;

use Sweatshop\Worker\Worker;

class ExchangeWorker extends Worker{
	
	protected $_workers=array();
	
	function work(Message $message){
		$params = $message->getParams();
		switch ($message->getTopic()){
			case 'sweatshop/gearman/exchange/add_worker':
				$workerClass = $params['worker_class'];
				$workerTopics =  $params['worker_topics'];
				
				foreach($workerTopics as $workerTopic){
					if (empty($this->_workers[$workerTopic])){
						$this->_workers[$workerTopic] = array();
					}
					array_push($this->_workers[$workerTopic], $workerClass);
				}
				break;
			default:
				$topic = $message->getTopic();
				if (!empty($this->_workers[$topic])){
					$sweatshop = $this->_di['sweatshop'];
					
					foreach($this->_workers[$topic] as $workerClass){
						//push message with aggregator class "RoutedMessage" 
						$newTopic = $topic . ':' . $workerClass;
						$newMessage = new RoutedMessage($newTopic, $message);
						$sweatshop->pushMessage($newMessage);
					}
				}
				
		}
	}
}