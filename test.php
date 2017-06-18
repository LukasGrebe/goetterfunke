<?php
date_default_timezone_set('Europe/Berlin');

error_reporting(E_ALL);
require_once 'vendor/autoload.php';

putenv('GOOGLE_APPLICATION_CREDENTIALS=./credentials/Goetterfunke-a96cdd1e44e3.json');


$client = new Google_Client();
//$client->setApplicationName("Test PHP Client");
$client->useApplicationDefaultCredentials();

$client->addScope(Google_Service_Analytics::ANALYTICS);
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

$analytics = new Google_Service_Analytics($client);

try {
  $accounts = $analytics->management_accounts->listManagementAccounts();
} catch (apiServiceException $e) {
  print 'There was an Analytics API service error '
      . $e->getCode() . ':' . $e->getMessage();

} catch (apiException $e) {
  print 'There was a general API error '
      . $e->getCode() . ':' . $e->getMessage();
}

/**
 * Example #2:
 * The results of the list method are stored in the accounts object.
 * The following code shows how to iterate through them.
 */
foreach ($accounts->getItems() as $account) {
  print <<<OUT
Account id   = {$account->getId()}
Account name = {$account->getName()}
Created      = {$account->getCreated()}
Updated      = {$account->getUpdated()}
OUT;
}
