<?php
use Monolog\Handler\StreamHandler;

use Monolog\Logger;

use Sweatshop\Queue\GearmanQueue;

use Sweatshop\Message\Message;

use Sweatshop\Queue\InternalQueue;

use Sweatshop\Sweatshop;

require_once __DIR__.'/../../main.php';

$sweatshop = new Sweatshop();
$logger = new Logger('website');
$logger->pushHandler(new StreamHandler("php://stdout"));
$sweatshop->setLogger($logger);

$queue = new GearmanQueue($sweatshop);

require_once 'BackgroundPrintWorker.php';

$worker = new BackgroundPrintWorker($sweatshop);
$queue->registerWorker('topic:test', $worker);
$sweatshop->addQueue($queue);

$sweatshop->runWorkers();