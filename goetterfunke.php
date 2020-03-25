<?php
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/getClient.php';
require_once __DIR__ . '/listAccounts.php';



$client = new Google_Service_Analytics(getClient('client_credentials.json'));

listAccounts($client);