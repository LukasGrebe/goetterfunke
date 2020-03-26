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

$client = getClient('client_credentials.json');
$analyticsService = new Google_Service_Analytics($client);


switch($argv[1]){
    case 'listCDs':
        listCustomDimensionsAsJSON($analyticsService,$argv[2],$argv[3]);
    break;
    case 'setCDs': 
        setCustomDiemensionsFromJSON($analyticsService,$client,$argv[2],$argv[3],$argv[4],!isset($argv[5]));       
        
    break;
    case 'listProperties':
        listProperties($analyticsService);
    break;
}

