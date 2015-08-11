<?php
namespace Sweatshop\Worker;

use Monolog\Logger;
use Pimple\Container;
use Sweatshop\Interfaces\MessageableInterface;
use Sweatshop\Message\Message;

abstract class Worker implements MessageableInterface
{
    const WORKER_TITLE_PREFIX = 'sweatshop.workers';

    protected $_di;
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->tearUp();
    }

    public function configure()
    {

    }

    public function __destruct()
    {
        $this->getLogger()->debug(sprintf('Worker "%s": tearing down', get_class($this)));
        $this->tearDown();
    }

    public function execute(Message $message)
    {
        $this->getLogger()->debug(sprintf('Worker "%s" executing message "%s"', get_class($this), $message->getId()));
        return $this->work($message);
    }

    function pushMessage(Message $message)
    {
        return $this->execute($message);
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    protected function tearUp()
    {
        $this->getLogger()->info(sprintf('Worker "%s": tearing up', get_class($this)));
    }

    protected function tearDown()
    {

    }

    abstract protected function work(Message $message);
}