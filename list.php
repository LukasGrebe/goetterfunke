<?php

function listProperties($service){
	$properties = $service->management_webproperties->listManagementWebproperties('~all');
	foreach ($properties->getItems() as $property) {
		echo $property->getAccountId() . ' ' . $property->getId() . ' ' . $property->getName() . "\n";
  }
}

function listAccounts(){
  try{
    $accounts = $service->management_accounts->listManagementAccounts();
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
}
