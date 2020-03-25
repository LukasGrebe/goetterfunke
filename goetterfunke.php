<?php
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require 'vendor/autoload.php';
require_once 'getClient.php';
require_once 'list.php';
require_once 'customDimensions.php';

$client = new Google_Service_Analytics(getClient('client_credentials.json'));


switch($argv[1]){
    case 'cd':
        listCustomDimensionsAsJSON($client,$argv[2],$argv[3]);
    break;
    case 'list':
        listProperties($client);
    break;
}

