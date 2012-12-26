<?php

use Sweatshop\Queue\GearmanQueue;

use Sweatshop\Message\Message;

use Sweatshop\Queue\InternalQueue;

use Sweatshop\Sweatshop;

require_once __DIR__.'/../../main.php';

$sweatshop = new Sweatshop();
$queue = new InternalQueue();

require_once 'EchoWorker.php';

$worker = new EchoWorker();
$queue->registerWorker('topic:test', $worker);
$sweatshop->addQueue($queue);
$sweatshop->addQueue(new GearmanQueue());

$message = new Message('topic:test',array(
	'value' => 3		
));

$results = $sweatshop->pushMessage($message);

print_r($results);