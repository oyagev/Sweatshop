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

$configMessageDispacher = array('gearman');


$configWorkersDispacher = array(
	'queues' => array(
		'rabbitmq' => array(
			'workers'=> array(
				'BackgroundPrintWorker' => array(
					'topics' => array('topic:test')
				),
				'BackgroundLoggerWorker' => array(
					'topics' => array('topic:test','topic:test2')
				)
			),
			'options' => array(
				'min_processes_per_queue' => 1
			)
		)
	),
	'options' => array(
		'min_processes_per_queue' => 3	
	)	
);

$sweatshop->configureWorkersDispather($configWorkersDispacher);
$sweatshop->runWorkers();


