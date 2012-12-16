<?php
namespace Evently;

define('Evently\ROOT_PATH', realpath(dirname(__FILE__)));
define('Evently\LIB_PATH', \Evently\ROOT_PATH . '/lib');
define('Evently\VENDOR_PATH', \Evently\ROOT_PATH . '/vendor');
define('Evently\CONFIG_PATH', \Evently\ROOT_PATH . '/config');

include 'autoload.php';

