<?php

function listCustomDimensionsAsJSON($client, $account, $property){
	$customDimensions = $client->management_customDimensions->listManagementCustomDimensions($account, $property);
	echo "[\n";
	$i = 1;
	foreach($customDimensions->getItems() as $cd){
		echo "{\n".' "name":"' . $cd->getName() . '",'."\n";
		echo ' "scope":"' . $cd->getScope() . '",'."\n";
		echo ' "active":"' . ($cd->getActive()?'true':'false') . "\n}\n";
	}
	echo "]\n";
}
