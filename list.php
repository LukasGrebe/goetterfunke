<?php

function listProperties($client){
  echo "All Properties for Users Account:";
	$properties = $client->management_webproperties->listManagementWebproperties('~all');
	foreach ($properties->getItems() as $property) {
		echo $property->getAccountId() . ' ' . $property->getId() . ' ' . $property->getName() . "\n";
  }
}
