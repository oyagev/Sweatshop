<?php
namespace Sweatshop;

use Monolog\Logger;
use Pimple\Container;
use Sweatshop\Dispatchers\MessageDispatcher;
use Sweatshop\Dispatchers\WorkersDispatcher;
use Sweatshop\Message\Message;
use Sweatshop\Queue\Queue;
use Sweatshop\Worker\Worker;

//use Sweatshop\Queue\Threads\ThreadsManager;

class Sweatshop
{
    protected $_di = NULL;
    /* @var Logger */
    private $logger;
    private $config;

    /**
     * @var MessageDispatcher
     */
    protected $_messageDispatcher = NULL;
    /**
     * @var WorkersDispatcher
     */
    protected $_workersDispatcher = NULL;

    function __construct(Logger $logger, $config = array())
    {
        $this->logger = $logger;
        $this->_messageDispatcher = new MessageDispatcher($logger);
        $this->_workersDispatcher = new WorkersDispatcher($logger, $this->getDependencies(Worker::WORKER_TITLE_PREFIX));
    }

    function pushMessage(Message $message)
    {
        $result = $this->_messageDispatcher->pushMessage($message);
        return $result;
    }

    function pushMessageQuick($topic, $params = array())
    {
        $message = new Message($topic, $params);
        return $this->pushMessage($message);
    }

    function addQueue($queue, $options = array())
    {
        $queue_class = Queue::toClassName($queue);
        $queueObj = new $queue_class($this->getLogger(), $options);
        $this->_messageDispatcher->addQueue($queueObj);
    }

    function registerWorker($queue, $topic = '', $worker = NULL, $options = array())
    {
        $queue_class = Queue::toClassName($queue);
        $injectedWorkers = $this->getDependencies(Worker::WORKER_TITLE_PREFIX);

        $injectedTopicSlug = preg_replace("/[\/]/", ".", $topic);
        $injectedWorker = isset($injectedWorkers[$injectedTopicSlug]) ? $injectedWorkers[$injectedTopicSlug] : null;

        $this->_workersDispatcher->registerWorker($queue_class, $topic, $worker, $injectedWorker, $options);
    }

    function runWorkers()
    {
        $this->getLogger()->info('Sweatshop: Launching workers');
        $this->_workersDispatcher->runWorkers();
    }

    function configureMessagesDispatcher($config)
    {
        $this->_messageDispatcher->configure($config);
    }

    function configureWorkersDispather($config)
    {
        $this->_workersDispatcher->configure($config);
    }

    function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    function getLogger()
    {
        return $this->logger;
    }

    function setConfig($config)
    {
        $this->config = $config;
    }

    function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Container $di
     */
    function setDependencies(Container $di){
        $this->_di = $di;
    }

    /**
     * @param bool $dependence
     * @return null
     */
    function getDependencies($dependence = false){
        return $dependence ? (isset($this->_di[$dependence]) ? $this->_di[$dependence] : null) : $this->_di;
    }
}