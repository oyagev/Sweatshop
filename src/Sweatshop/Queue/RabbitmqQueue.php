<?php
namespace Sweatshop\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Sweatshop\Worker\Worker;

use Sweatshop\Message\Message;

class RabbitmqQueue extends Queue{

	private $_conn = NULL;
	private $_channel = NULL;
	private $_workersQueues = array();
	private $_queues = array();
	private $_exchange = NULL;

	function __construct($sweatshop,$options=array()){
		parent::__construct($sweatshop,$options);
		$this->_options = array_merge(array(
				'host' => 'localhost',
				'port' => '5672',
				'user' => 'guest',
				'password' => 'guest',
                'polling_delay_max' => 5000000,
                'polling_delay_min' => 100000
		),$this->_options,$options);
	}

	function __destruct(){
		//$this->getChannel()->close();
		$this->getConnection()->close();
		parent::__destruct();
	}

	function _doPushMessage(Message $message){
        $msg = new AMQPMessage(serialize($message),array('delivery_mode' => 2));
        $channel = $this->getChannel();
        $channel->basic_publish(
            $msg,
            $this->getExchangeName(),
            $message->getTopic()
        );

        /*
    		$exchange = $this->getExchange();
	    	$message = $exchange->publish(serialize($message), $message->getTopic(), AMQP_NOPARAM, array('delivery_mode'=>2) );
		*/
	}

	function _doRegisterWorker($topic, Worker $worker){
		if (empty($this->_workersQueues[$topic])){
			$this->_workersQueues[$topic] = array();
		}

		array_push($this->_workersQueues[$topic],$worker);
	}

	function _doRunWorkers(){
		foreach($this->_workersQueues as $topic => $workers){
			foreach($workers as $worker){
				$worker_queue_name = get_class($this).':'.get_class($worker);
                $channel = $this->getChannel();

                try{
                    $channel->queue_declare($worker_queue_name,false,true, false,false);
                    $channel->queue_bind($worker_queue_name,$this->getExchangeName(),$topic);
                } catch (\Exception $e) {
                    echo($e);
                    exit;
                }

				array_push($this->_queues,array(
					'queue' => $worker_queue_name,
					'worker' => $worker
				));
			}
		}
        $pollingDelay = $this->_options['polling_delay_min'];

		while(!$this->isCandidateForGracefulKill() ) {
			foreach($this->_queues as $q){
				$queue = $q['queue'];
				$worker = $q['worker'];

                $message = $channel->basic_get($queue);
				//$message = $queue->get();
                if ($message){
                    if (@!$workload = unserialize($message->body)){
                        $this->getLogger()->info("Sweatshop Error: Unable to process message due to corrupt serialization - " . $message->body . " || " . serialize($message));
                        $results = array();
                    } elseif ($worker instanceof Worker){
                        $results =  $worker->execute($workload);
                    }else{
                        $results=array();
                        //TODO: Log error
                    }

                    $channel->basic_ack($message->delivery_info['delivery_tag']);
                    $pollingDelay = max($pollingDelay / 2 , $this->_options['polling_delay_min']);
                    $this->workCycleEnd();
                } else {
                    $pollingDelay = min($pollingDelay * 2 , $this->_options['polling_delay_max']);
                    usleep($pollingDelay);
                }
			}
		}
	}

	public function _executeWorkerBackground($msg){
		$message = unserialize($msg->body);
	}

	/**
	 * @return AMQPConnection;
	 */
	private function getConnection(){
        //var_dump($this->_options);exit;
		if (!$this->_conn){
			$this->_conn = new AMQPConnection(
				$this->_options['host'],
				$this->_options['port'],
				$this->_options['user'],
				$this->_options['password']
			);
			//$this->_conn->connect();
			//TODO: check if connection is alive
		}

		return $this->_conn;
	}

	/**
	 * @return AMQPChannel
	 */
	private function getChannel(){
		if (!$this->_channel){
            $this->_channel = $this->getConnection()->channel();
            $this->declareExchange();
		}

		return $this->_channel;
	}

	private function getExchangeName(){
		return 'default';
	}

    private function declareExchange(){
        $this->getChannel()->exchange_declare(
            $this->getExchangeName(),
            'direct',
            false,
            true
        );
    }
}