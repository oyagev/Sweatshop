<?php
namespace Sweatshop\Queue\Processes;

use Monolog\Logger;

class ProcessGroup
{

    protected $queueClass;
    protected $workerClass;
    protected $topics;
    protected $options;
    protected $_di;
    protected $PIDs = array();
    private $logger;

    function __construct(Logger $logger, $queueClass, $workerClass, $topics, $options = array())
    {
        $this->logger = $logger;
        $this->setQueueClass($queueClass);
        $this->setWorkerClass($workerClass);
        $this->setTopics($topics);
        $options = array_merge(array(
            'min_processes' => 1,
            'max_work_cycles' => -1,
            'process_title' => ''
        ), $options);
        $this->setOptions($options);
        $this->PIDs = array_fill(0, $options['min_processes'], 0);
    }

    public function syncProcesses()
    {
        $queueClass = $this->getQueueClass();
        $queueOptions = $this->getOptions();
        $workerClass = $this->getWorkerClass();
        $workerOptions = array(
            'topics' => $this->getTopics()
        );

        $this->getLogger()->debug('Launching Queue with options', array('queue' => $queueClass, 'options' => $queueOptions));

        foreach ($this->PIDs as $pid) {
            if ($pid == 0) {
                $this->forkAndRun(new ProcessWrapper(
                    $this->logger,
                    $queueClass,
                    array($workerClass => $workerOptions),
                    $queueOptions
                ));
            }
        }
    }

    protected function forkAndRun(ProcessWrapper $processWrapper)
    {
        $pid = $processWrapper->fork();
        if ($pid == 0) {
            //I'm the child!
            $this->setProcessTitle();
            //Run the workers
            $processWrapper->runWorkers();
            //Basically if we're here, this means that the processes terminated!
            exit(1);
        } else {
            //We're the parent process!
            //Keep the process wrapper with PID
            $this->addPID($pid);
        }
    }

    public function notifyDeadProcess($pid, $status)
    {
        //Check if process belongs to this group
        if (!in_array($pid, $this->PIDs)) return;

        if (pcntl_wifexited($status)) {
            $exit_status = pcntl_wexitstatus($status);
            $this->getLogger()->debug(sprintf("child PID %d exited with status %d", $pid, $exit_status));
            switch ($exit_status) {
                case 1:
                    $this->removePID($pid);
                    break;
                default:
                    //completely kill the process, no resurrection
                    $this->removePID($pid);
                    $this->removeProcessSlot();
                    break;
            }
        } else {

            $this->getLogger()->debug(sprintf("child PID %d got signal %d", $pid, $status));

            switch ($status) {
                case SIGSTOP:
                    break;
                case SIGTERM:
                    //remove PID from list, resurrection allowed
                    $this->removePID($pid);
                    break;

                case SIGKILL:
                default:
                    //completely kill the process, no resurrection
                    $this->removePID($pid);
                    $this->removeProcessSlot();
                    break;
            }
        }

        $this->syncProcesses();
    }

    public function addPID($pid)
    {
        if (in_array($pid, $this->PIDs))
            return;

        $index = array_search(0, $this->PIDs);
        if ($index === FALSE) {
            $this->PIDs[] = $pid;
        } else {
            $this->PIDs[$index] = $pid;
        }
        sort($this->PIDs);
    }

    public function removePID($pid)
    {
        $index = array_search($pid, $this->PIDs);
        if ($index === FALSE) {

        } else {
            $this->PIDs[$index] = 0;
        }
        sort($this->PIDs);
    }

    public function addProcessSlot()
    {
        $this->PIDs[] = 0;
        sort($this->PIDs);
    }

    public function removeProcessSlot()
    {
        if (count($this->PIDs) == 0) return;
        $index = array_search(0, $this->PIDs);
        if ($index === FALSE) {
            $this->killProcess($this->PIDs[0]);
            $index = 0;

        }
        unset($this->PIDs[$index]);
        sort($this->PIDs);
    }

    public function killProcess($pid)
    {
        posix_kill($pid, SIGKILL);

    }

    public function killAll()
    {
        foreach ($this->PIDs as $pid) {
            $this->getLogger()->debug(sprintf("Exiting PID %d", $pid));
            $this->killProcess($pid);
        }

    }


    public function getQueueClass()
    {
        return $this->queueClass;
    }

    public function setQueueClass($queueClass)
    {
        $this->queueClass = $queueClass;
    }

    public function getWorkerClass()
    {
        return $this->workerClass;
    }

    public function setWorkerClass($workerClass)
    {
        $this->workerClass = $workerClass;
    }

    public function getTopics()
    {
        return $this->topics;
    }

    public function setTopics($topics)
    {
        $this->topics = $topics;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options)
    {
        $this->options = $options;
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

    protected function setProcessTitle()
    {
        if (!empty($this->options['process_title'])) {
            if (function_exists('setproctitle')) {
                setproctitle($this->options['process_title']);
            } else if (function_exists('cli_set_process_title')) {
                cli_set_process_title($this->options['process_title']);
            }

        }
    }
}