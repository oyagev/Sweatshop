# Sweatshop

Sweatshop is a framework for executing asynchronous tasks in PHP with external separate workers.   
Just create Worker classes, attach them to various Queues and dispatch your events and messages.   
Sweatshop supports and monitors processes! Thus it's easy to create multiple workers and prevent queue locks and infinite loops.

## Installation

Fetch this library with Composer.

## Concept 
In its core, Sweatshop defines "Queues", "Workers" and "Messages". A Worker is a processing unit, an entity that's responsible to execute a defined job, based on a Message it receives. 

A Queue is basically the manager. Each Queue has its own allocated workers, for specific "job topics" it supports. The Queue is responsible for delivering Messages to the appropriate Workers, monitor job execution and return the expected reply (if any) to the dispatcher.

A Queue can be synchronous, thus working inside the application and blocking its operation until Workers' tasks are complete. 
A Queue can also be asynchronous, delivering messages to Workers via message brokers such as RabbitMQ, Gearman and others, thus allow non-blocking operation and background processing.

## Usage

The most basic usage of Sweatshop, though uncommon, is synchronous message processing.
Lets consider the following (simple) Worker:

    <?php
    use Sweatshop\Message\Message;
    use Sweatshop\Worker\Worker;
    class EchoWorker extends Worker{
        function work(Message $message){
            $params =  $message->getParams();
            return $params['value'];
        }
    }
    
This worker merely takes a predefined value from the received message and returns it.
To use Sweatshop, we first instantiate the class:
    
    <?php
    $sweatshop = new Sweatshop();

Instantiate a new Queue:

    $queue = $sweatshop->addQueue('internal');
    
Instantiate a new Worker and register it to the Queue:
    
    //Don't forget to include the Worker class
    $sweatshop->registerWorker($queue, 'topic:test', 'EchoWorker');
    
Once we're done setting Sweatshop, we're ready to start dispatching messages:

    
    $results = $sweatshop->pushMessageQuick('topic:test',array(
        'value' => 3		
    ));
    print_r($results);
    
Complete code:

    <?php
    
    use Sweatshop\Sweatshop;
    
    //Define the worker class
    //here or somewhere else...
    class EchoWorker extends Worker{
        function work(Message $message){
            $params =  $message->getParams();
            return $params['value'];
        }
    }
    
    //Setup Sweatshop, Queue and Worker
    $sweatshop = new Sweatshop();
    $queue = $sweatshop->addQueue('internal');
    $sweatshop->registerWorker($queue, 'topic:test', 'EchoWorker');
    
    //Invoke Workers for the message
    $results = $sweatshop->pushMessageQuick('topic:test',array(
        'value' => 3        
    ));
    print_r($results);
    
    /*
    Expected Result:
    
    Array
    (
        [0] => 3
    )
     */


### Running Asynchronous Tasks

Above we used "InternalQueue" class to register workers that are invoked internally, as part of the application. To invoke the same Worker asynchronously, all we need is to attach it to a different Queue. 

Consider the following (shortened) example:

    //Setup Sweatshop, Queue and Worker
    $sweatshop = new Sweatshop();
    $sweatshop->addQueue('gearman');
    
    //Dispatch message to Workers
    $results = $sweatshop->pushMessageQuick('topic:test',array(
        'value' => 3        
    ));

We replace the "internal" Queue with "gearman", which naturally uses Gearman server as job server. Also, since workers will be executed in a separate process, we don't need to define them now!

This time, the application will dispatch the message as a "background job" to an external gearman server, not waiting for Workers execution.
Hence, we can expect "$results" array to be empty.

To actually execute this worker, we will create a command-line script:

    run-workers.php
    <?php
    use Sweatshop\Queue\GearmanQueue;
    use Sweatshop\Message\Message;
    use Sweatshop\Queue\InternalQueue;
    use Sweatshop\Sweatshop;
    
    $sweatshop = new Sweatshop();
    $queue = $sweatshop->addQueue('gearman',array());
    $sweatshop->registerWorker($queue, 'topic:test', 'EchoWorker');

    $sweatshop->runWorkers(); //run those workers!

Here we define the Queue and Worker.
Notice the last line that makes all the difference... 

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

    $sweatshop->runWorkers(array(
        'min_threads_per_queue' => 3		
    ));

Running this script should yield 4 processes: 1 parent process and 3 child processes, each running the same Queue.

#### Refreshing Processes
We can also set some conditions, by which processes will be killed gracefully and new processes will replace them:

    $queue = $sweatshop->addQueue('gearman', array(
        'max_work_cycles' => 3, //maximum work cycles this process will execute
        'max_memory_per_thread'=> 10000000 //maximum memory this process can consume
    ));

Here we set 2 parameters that are evaluated after every work cycle.
Once a condition is met, the process will exit and be replaced by a new, fresh process.

Please notice that the last settings are defined per Queue.


