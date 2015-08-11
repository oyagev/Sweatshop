<?php

use Monolog\Handler\StreamHandler;

use Monolog\Logger;

use Sweatshop\Queue\GearmanQueue;

use Sweatshop\Message\Message;

use Sweatshop\Queue\InternalQueue;

use Sweatshop\Sweatshop;

require_once __DIR__.'/../../vendor/autoload.php';


$logger = new Logger('website');
$logger->pushHandler(new StreamHandler("php://stdout"));
$sweatshop = new Sweatshop($logger);



require_once 'BackgroundPrintWorker.php';
require_once 'BackgroundLoggerWorker.php';

$sweatshop->addQueue('rabbitmq',array());

$sweatshop->registerWorker('rabbitmq', 'topic:test', 'BackgroundPrintWorker', array('process_title'=> 'Sweatshop:test-printer', 'max_work_cycles'=>4));
$sweatshop->registerWorker('rabbitmq', 'topic:test', 'BackgroundPrintWorker', array('process_title'=> 'Sweatshop:test-printer', 'max_work_cycles'=>4));
//$sweatshop->registerWorker('rabbitmq', array('topic:test','topic:test2'), 'BackgroundLoggerWorker', array('min_processes' => 2, 'process_title'=> 'Sweatshop:test-logger'));*/



$sweatshop->runWorkers();


