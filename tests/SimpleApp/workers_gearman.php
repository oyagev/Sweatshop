<?php
use Monolog\Handler\StreamHandler;

use Monolog\Logger;

use Sweatshop\Queue\GearmanQueue;

use Sweatshop\Message\Message;

use Sweatshop\Queue\InternalQueue;

use Sweatshop\Sweatshop;

require_once __DIR__.'/../../vendor/autoload.php';

$sweatshop = new Sweatshop();
$logger = new Logger('website');
$logger->pushHandler(new StreamHandler("php://stdout"));
$sweatshop->setLogger($logger);



require_once 'BackgroundPrintWorker.php';
require_once 'BackgroundLoggerWorker.php';


$sweatshop->addQueue('gearman',array());

$sweatshop->registerWorker('gearman', 'topic:test', 'BackgroundPrintWorker', array());
$sweatshop->registerWorker('gearman', array('topic:test','topic:test2'), 'BackgroundLoggerWorker', array('min_processes' => 2));


$sweatshop->runWorkers();

