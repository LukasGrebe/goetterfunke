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
    case 'getCDsJSON':
        $customDimensions = getCustomDimensions($analyticsService,$argv[2],$argv[3]);
        print json_encode($customDimensions,JSON_PRETTY_PRINT);
    break;
    case 'setCDs': 
        //get a delta
        $is = getCustomDimensions($analyticsService,$argv[2],$argv[3]);
        $should = json_decode(file_get_contents(__DIR__ . '/' . $argv[4]),true);

        $client->setUseBatch(true);
        $changes = createChangeRequets($analyticsService,$is,$should);
        executeThrottledBatch($client, $analyticsService, $changes);
        
    break;
    case 'listProperties':
        listProperties($analyticsService);
    break;
}

