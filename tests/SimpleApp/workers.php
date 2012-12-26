<?php
use Sweatshop\Queue\GearmanQueue;

use Sweatshop\Message\Message;

use Sweatshop\Queue\InternalQueue;

use Sweatshop\Sweatshop;

require_once __DIR__.'/../../main.php';

$sweatshop = new Sweatshop();
$queue = new GearmanQueue($sweatshop);

require_once 'BackgroundPrintWorker.php';

$worker = new BackgroundPrintWorker($sweatshop);
$queue->registerWorker('topic:test', $worker);
$sweatshop->addQueue($queue);

$sweatshop->runWorkers();