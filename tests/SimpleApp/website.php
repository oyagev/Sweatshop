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

$queue = new InternalQueue($sweatshop);

require_once 'EchoWorker.php';

$worker = new EchoWorker($sweatshop);
$queue->registerWorker('topic:test', $worker);
$sweatshop->addQueue($queue);
$sweatshop->addQueue(new GearmanQueue($sweatshop));



$message = new Message('topic:test',array(
	'value' => 3		
));

$results = $sweatshop->pushMessage($message);

print_r($results);