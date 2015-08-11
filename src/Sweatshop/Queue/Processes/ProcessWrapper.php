<?php
namespace Sweatshop\Queue\Processes;

use Monolog\Logger;
use Pimple\Container;
use Sweatshop\Queue\Queue;

class ProcessWrapper
{
    protected $_queue;
    protected $_queueClass;
    protected $_options;
    protected $_workers;

    /**
     * @var Container
     */
    protected $_di;
    protected $_PIDs = array();
    protected $logger;

    function __construct(Logger $logger, $queueClass, $workers, $options = array())
    {
        $this->logger = $logger;
        $this->_queueClass = $queueClass;
        $this->_options = array_merge(array(
            'min_processes' => 1,
        ), $options);
        $this->_workers = $workers;
    }

    public function fork()
    {
        declare(ticks = 1);
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->getLogger()->fatal(sprintf('%s: Queue "%s" Cannot fork a new thread', get_class($this), get_class($this->_queue)));
        } else if ($pid) {
            // we are the parent - PID>0
        } else {
            //We're the child. PID=0
        }
        return $pid;
    }

    public function runWorkers()
    {
        $this->_queue = $this->createQueue($this->_queueClass, $this->_workers, $this->_options);
        $this->_queue->runWorkers();
    }

    /**
     * @param $queueClass
     * @param $workers
     * @param $options
     * @return Queue
     */
    protected function createQueue($queueClass, $workers, $options)
    {
        $this->getLogger()->debug('Adding queue: ' . $queueClass);

        /* @var $queue Queue */
        $queue = new $queueClass($options);

        foreach ($workers as $workerClass => $options) {
            if (!$workerClass) continue;

            /* try to load worker from the container */
            $worker = new $workerClass($this->getLogger());
            foreach ($options['topics'] as $topic) {
                $queue->registerWorker($topic, $worker);
            }
        }

        $this->getLogger()->debug('Queue: ' . $queueClass . " was added.");

        return $queue;
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->logger;
    }
}