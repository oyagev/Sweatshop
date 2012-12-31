<?php
namespace Sweatshop;

define('Sweatshop\ROOT_PATH', realpath(dirname(__FILE__)));
define('Sweatshop\LIB_PATH', \Sweatshop\ROOT_PATH . '/lib');
define('Sweatshop\VENDOR_PATH', \Sweatshop\ROOT_PATH . '/vendor');
define('Sweatshop\CONFIG_PATH', \Sweatshop\ROOT_PATH . '/config');

include 'autoload.php';

