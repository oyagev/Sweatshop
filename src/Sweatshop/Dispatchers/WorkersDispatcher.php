<?php
namespace Sweatshop\Dispatchers;

use Monolog\Logger;
use Sweatshop\Queue\Processes\ProcessGroup;
use Sweatshop\Queue\Processes\ProcessWrapper;
use Sweatshop\Worker\Worker;

class WorkersDispatcher
{
    protected $logger = NULL;
    protected $_childPIDs = array();
    protected $_processes = array();
    protected $_processGroups = array();

    /* @var $injectedWorkers Worker[] */
    protected $injectedWorkers;

    /**
     * @param Logger $logger
     */
    /**
     * @param Logger $logger
     * @param Worker[]|null $injectedWorkers array of all workers from pimple container
     */
    public function __construct(Logger $logger, $injectedWorkers)
    {
        $this->setLogger($logger);
        $this->injectedWorkers = $injectedWorkers;
    }

    public function registerWorker($queue_class, $topics = array(), $worker = NULL, Worker $injectedWorker = null, $options = array())
    {
        if (!is_array($topics)) {
            $topics = array($topics);
        }

        $processGroup = new ProcessGroup($this->logger, $queue_class, $worker, $injectedWorker, $topics, $options);
        array_push($this->_processGroups, $processGroup);
    }

    public function runWorkers()
    {
        declare(ticks = 1);

        /* @var $processGroup ProcessGroup */
        foreach ($this->_processGroups as $processGroup) {
            $processGroup->syncProcesses();
        }

        pcntl_signal(SIGINT, array($this, 'signal_handlers'), false);
        pcntl_signal(SIGTERM, array($this, 'signal_handlers'), false);

        while (($pid = pcntl_wait($status)) != -1) {
            foreach ($this->_processGroups as $processGroup) {
                $processGroup->notifyDeadProcess($pid, $status);
            }
        }
    }

    public function signal_handlers($signo)
    {
        $this->getLogger()->debug(sprintf("Sweatshop got signal %d", $signo));
        foreach ($this->_processGroups as $processGroup) {
            $processGroup->killAll();
        }
        exit;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    protected function forkAndRun(ProcessWrapper $processWrapper)
    {
        $pid = $processWrapper->fork();
        if ($pid == 0) {
            //I'm the child!
            //Run the workers
            $processWrapper->runWorkers();

            //Basically if we're here, this means that the processes terminated!

            exit(1);
        } else {
            //We're the parent process!
            //Keep the process wrapper with PID
            $this->_childPIDs[$pid] = $processWrapper;
        }
    }

    /**
     * @return \Sweatshop\Worker\Worker[]
     */
    public function getInjectedWorkers()
    {
        return $this->injectedWorkers;
    }
}