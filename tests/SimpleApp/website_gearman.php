<?php

use Monolog\Handler\StreamHandler;

use Monolog\Logger;

use Sweatshop\Queue\GearmanQueue;

use Sweatshop\Message\Message;

use Sweatshop\Queue\InternalQueue;

use Sweatshop\Sweatshop;

require_once __DIR__.'/../../vendor/autoload.php';
require_once 'EchoWorker.php';

$sweatshop = new Sweatshop();
$logger = new Logger('website');
$logger->pushHandler(new StreamHandler("php://stdout"));
$sweatshop->setLogger($logger);

$queue = $sweatshop->addQueue('Sweatshop\\Queue\\InternalQueue');
$sweatshop->registerWorker($queue, 'topic:test', 'EchoWorker');

$queue = $sweatshop->addQueue('gearman');

$results = $sweatshop->pushMessageQuick('topic:test',array(
	'value' => 3		
));

print_r($results);