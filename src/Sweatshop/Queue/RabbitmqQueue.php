<?php

namespace Sweatshop\Queue;

use Monolog\Logger;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Sweatshop\Message\Message;
use Sweatshop\Worker\Worker;

class RabbitmqQueue extends Queue
{

    private $_conn = NULL;
    private $_channel = NULL;
    private $_workersQueues = array();
    private $_queues = array();
    private $_exchange = NULL;

    function __construct(Logger $logger, $options = array())
    {
        parent::__construct($logger, $options);
        $this->_options = array_merge(array(
            'host' => 'localhost',
            'port' => '5672',
            'user' => 'guest',
            'password' => 'guest',
            'polling_delay_max' => 5000000,
            'polling_delay_min' => 100000
        ), $this->_options, $options);
    }

    function __destruct()
    {
        if ($this->_conn) {
            $this->getConnection()->close();
        }
        parent::__destruct();
    }

    function _doPushMessage(Message $message)
    {
        // serialize to array -> json
        $msgJson = array(
            'params' => $message->getParams(),
            'id' => $message->getId(),
            'originalDispatcher' => $message->getOriginalDispatcher(),
            'topic' => $message->getTopic()
        );

        $msg = new AMQPMessage(json_encode($msgJson), array('delivery_mode' => 2));
        $channel = $this->getChannel();
        $channel->basic_publish(
            $msg,
            $this->getExchangeName(),
            $message->getTopic()
        );
    }

    function _doRegisterWorker($topic, Worker $worker)
    {
        if (empty($this->_workersQueues[$topic])) {
            $this->_workersQueues[$topic] = array();
        }

        array_push($this->_workersQueues[$topic], $worker);
    }

    function _doRunWorkers()
    {
        foreach ($this->_workersQueues as $topic => $workers) {
            foreach ($workers as $worker) {
                $worker_queue_name = get_class($this) . ':' . get_class($worker);
                $channel = $this->getChannel();
                $channel->queue_declare($worker_queue_name, false, true, false, false);
                $channel->queue_bind($worker_queue_name, $this->getExchangeName(), $topic);

                array_push($this->_queues, array(
                    'queue' => $worker_queue_name,
                    'worker' => $worker
                ));
            }
        }

        $pollingDelay = $this->_options['polling_delay_min'];

        while (!$this->isCandidateForGracefulKill()) {
            foreach ($this->_queues as $q) {
                $queue = $q['queue'];
                $worker = $q['worker'];

                /* @var $message AMQPMessage */
                $message = $channel->basic_get($queue);

                if ($message) {
                    if (!$worker instanceof Worker) {
                        throw new \Exception("Not a worker"); // TODO: add worker name
                    }

                    $sweatshopWorkload = $this->convertToSweatshopMsg($message); // throws exception if corrupt
                    $results = $worker->execute($sweatshopWorkload);

                    $channel->basic_ack($message->delivery_info['delivery_tag']);
                    $pollingDelay = max($pollingDelay / 2, $this->_options['polling_delay_min']);
                    $this->workCycleEnd();
                } else {
                    $pollingDelay = min($pollingDelay * 2, $this->_options['polling_delay_max']);
                    usleep($pollingDelay);
                }
            }
        }
    }

    /**
     * @param AMQPMessage $message
     * @return Message
     * @throws \Exception
     *
     * This is regarding switching from serialization of Messages using to php to using json
     * TODO: This method should actually be inside \PhpAmqpLib\Message\AMQPMessage(Be ware, this class has lots of usages) as getBody.
     * TODO: $body should be a private member
     * TODO: In order to do this, we must fork videlalvaro/php-amqplib(See composer.json) and customize to our needs.
     * |-> \PhpAmqpLib\Message\AMQPMessage Should have a private $body member
     *
     */
    private function convertToSweatshopMsg(AMQPMessage $message)
    {
        $data = json_decode($message->body);
        if ($data == null) {
            throw new \Exception("Unable To Deserialize.");
        }

        $msg = new Message($data->topic, $data->params, $data->originalDispatcher);
        $msg->setId($data->id);

        return $msg;
    }

    public function _executeWorkerBackground($msg)
    {
        $message = unserialize($msg->body);
    }

    /**
     * @return AMQPConnection;
     */
    private function getConnection()
    {
        if (!$this->_conn) {
            $this->_conn = new AMQPConnection(
                $this->_options['host'],
                $this->_options['port'],
                $this->_options['user'],
                $this->_options['password']
            );
        }

        return $this->_conn;
    }

    /**
     * @return AMQPChannel
     */
    private function getChannel()
    {
        if (!$this->_channel) {
            $this->_channel = $this->getConnection()->channel();
            $this->declareExchange();
        }

        return $this->_channel;
    }

    private function getExchangeName()
    {
        return 'default';
    }

    private function declareExchange()
    {
        $this->getChannel()->exchange_declare(
            $this->getExchangeName(),
            'direct',
            false,
            true
        );
    }
}