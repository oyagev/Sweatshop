<?php
require_once __DIR__.'/../../sweatshop.php';

use Sweatshop\Sweatshop;
use Sweatshop\Worker\Worker;
use Sweatshop\Queue\InternalQueue;
use Sweatshop\Message\Message;


//Define the worker class
//here or somewhere else...
class EchoWorker extends Worker{
    function _doExecute(Message $message){
        $params =  $message->getParams();
        return $params['value'];
    }
}

//Setup Sweatshop, Queue and Worker
$sweatshop = new Sweatshop();
$queue = new InternalQueue($sweatshop);
$worker = new EchoWorker($sweatshop);
$queue->registerWorker('topic:test:echo', $worker);
$sweatshop->addQueue($queue);

//Create a new Message
$message = new Message('topic:test:echo',array(
    'value' => 3        
));

//Invoke Workers for the message
$results = $sweatshop->pushMessage($message);
print_r($results);

/*
Expected Result:

Array
(
    [0] => 3
)
 */