<?php
$config = array(
	'log' => array(
		'level' => LOG_DEBUG, 
		'output' => 'stream',
		'logfile' => '/tmp/events.log'
	),
	'queues' => array(
		array(
			'type' => 'internal'	
		),
		array(
			'type' => 'external',
			'driver' => 'GearmanDriver'
		),		
	)
	
);