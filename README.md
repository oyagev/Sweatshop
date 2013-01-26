# Sweatshop

Sweatshop is a framework for executing asynchronous tasks in PHP with external separate workers.

Just create Worker classes, attach them to various Queues and dispatch your events and messages.   
Sweatshop supports and monitors processes! Thus it's easy to create multiple workers and prevent queue locks and infinite loops.

## Installation

Fetch this library with Composer.

## Concept 
In its core, Sweatshop defines "Queues", "Workers" and "Messages". A Worker is a processing unit, an entity that's responsible to execute a defined job, based on a Message it receives. 

A Queue is basically the manager. Each Queue has its own allocated workers, for specific "job topics" it supports. The Queue is responsible for delivering Messages to the appropriate Workers, monitor job execution and return the expected reply (if any) to the dispatcher.

The Queue is asynchronous, delivering messages to Workers via message brokers such as RabbitMQ, Gearman and others, thus allow non-blocking operation and background processing.

## Usage

The most basic usage of Sweatshop, is asynchronous message processing.

Lets start with dispatching messages to Sweatshop.
First instantiate the class:
    
    <?php
    $sweatshop = new Sweatshop();

Declare a listnening Queue:

    $sweatshop->addQueue('rabbitmq',array());

And dispatch messages:

    $results = $sweatshop->pushMessageQuick('topic:test',array(
        'value' => 3		
    ));
    
Thats basically it!
Here is the full example:

    <?php
    use Sweatshop\Sweatshop;
        
    $sweatshop = new Sweatshop();    
    $sweatshop->addQueue('rabbitmq',array());
    $results = $sweatshop->pushMessageQuick('topic:test1',array(
        'value' => 3		
    ));
    $results = $sweatshop->pushMessageQuick('topic:test2',array(
    		'value' => 5
    ));
    
Above we've dispatched two messages. At this point, we do not care which or how many workers will work on each message, we just want it delivered.

To actually work on messages, we have to deine workers. 
Consider the following (simple) Worker:

    <?php
    use Sweatshop\Message\Message;
    use Sweatshop\Worker\Worker;
    class BackgroundPrintWorker extends Worker{
        function work(Message $message){
            $params =  $message->getParams();
            $topic = $message->getTopic();
            printf("Processed job for topic '%s', value was '%s'".PHP_EOL,$topic,$params['value']);
        }
	}

The worker must extends the abstract "Worker" class and implement function "work".
The above worker merely takes a predefined value from the received message and print it.

Now to the more interesting part, lets run some workers!

We first instantiate the class:
    
    <?php
    $sweatshop = new Sweatshop();

Again, declare a listening Queue as before:

    $sweatshop->addQueue('rabbitmq',array());
    
And register a worker on a Queue, with a specific topic:

    $sweatshop->registerWorker('rabbitmq', 'topic:test', 'BackgroundPrintWorker', array());
    
Above, the BackgroundPrintWorker will be registered with the RabbitMQ Queue to receive messages with topic "topic:test".
Notice that we use the Worker class name, not actual instance. This is important for process management.

And lastly, we launch workers:
    
    $sweatshop->runWorkers();
    
Complete code:

    /run-workers.php
    <?php
    use Sweatshop\Sweatshop;
    
    $sweatshop = new Sweatshop();
    $sweatshop->addQueue('rabbitmq',array());
    $sweatshop->registerWorker('rabbitmq', 'topic:test', 'BackgroundPrintWorker', array());
    $sweatshop->runWorkers();
    

All you need to do now is launch this script from command-line:

    $ php run-workers.php

We're launching workers!

### Creating your own Workers
Having demo Workers run for you is easy, but it doesn't really help you ;-)
To create your own Workers, you simply inherit from Sweatshop's Worker class. For example:

    <?php
    use Sweatshop\Message\Message;
    use Sweatshop\Worker\Worker;
    
    class MyLoggingWorker extends Worker{
        
        //define this method to specify the work to be done
    	function work(Message $message){
    		$params =  $message->getParams(); //get the message parameters
    		$topic = $message->getTopic(); //get the topic
    		
    		$this->getLogger()->info(sprintf("Processed job for topic '%s', value was '%s'",$topic,$params['value']));
    		
    	}
        
        // This method is called once as the Worker instantiate
        function tearUp(){
            ;
        }
        
        // This method is called once on Worker destruction
        function tearDown(){
            ;
        }
    }
This Worker will output a log record for every Message it processes.

### Logging
Sweatshop uses the excellent [monolog](https://github.com/Seldaek/monolog) library for logging.
To enable logging, just create a logger and attach it to Sweatshop. 
For example:


    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;
    
    $logger = new Logger('SweatshopExample');
    $logger->pushHandler(new StreamHandler("php://stdout")); // log to command-line 
    $sweatshop->setLogger($logger);

Once Logger is setup, you can use it withing you Workers and Queues classes:

    $logger = $this->getLogger(); // inside Worker and Queue


### Process Management
Sweatshop supports running workers in separate processes. 
Basically, every asynchronous Queue run on a separate process. For example, run the previous script:
    
    $ php run-workers.php

And from a separate terminal run:
    
    $ ps aux | grep run-workers
    
Surprisingly, you'll notice that you have 2 processes running. One for the process that you invoked and one child process that is actually running workers. 
The parent process itself is not running any worker, rather it's responsible for launching child processes and monitoring their activity.
The child process is responsible for a single Queue instance, running all attached Workers.


#### Adding processes for each Queue
You can set the minimum number of processes active for each Queue:

    $sweatshop->registerWorker('rabbitmq', 'topic:test', 'BackgroundPrintWorker', array(
        'min_processes' => 3
    ));
    

Running this script should yield 4 processes: 1 parent process and 3 child processes, each running the same Worker.

#### Refreshing Processes
We can also set some conditions, by which processes will be killed gracefully and new processes will replace them:

    $sweatshop->registerWorker('rabbitmq', 'topic:test', 'BackgroundPrintWorker', array(
        'max_work_cycles' => 3, //maximum work cycles this process will execute
        'max_process_memory'=> 10000000 //maximum memory this process can consume, in bytes
    ));

Here we set 2 parameters that are evaluated after every work cycle.
Once a condition is met, the process will exit and be replaced by a new, fresh process.

Please notice that the last settings are defined per Worker.


