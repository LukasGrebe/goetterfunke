<?php

function getCustomDimensions($analyticsService, $account, $property){
	$customDimensions = $analyticsService->management_customDimensions->listManagementCustomDimensions($account,$property);
	return $customDimensions->getItems();
}

function parseFromJSON($json){
	function arrayToDimension($arr){
		$_properties = array('Scope','Active','Index');
		$cd = new Google_Service_Analytics_CustomDimension();
		$cd->setScope($arr['scope']);
		$cd->setActive($arr['active']);
		$cd->setIndex($arr['index']);
		return $cd;
	}
	return array_map('arrayToDimension', json_decode($json,true));
}


function createChangeRequets($analyticsService,$currentCDconfiguration,$targetCDconfiguration){
	$requests = [];
	foreach ($currentCDconfiguration as $cd) {
		$index = $cd->getIndex();
		$changeset = [];
		
		if(!array_key_exists($index-1, $targetCDconfiguration)){
			if($cd->getActive()){
				$cd->setActive(false);
				$update = true;
				$changeset[] = 'status (set to inactive)';
			}
		}else{
			//check if active, name and scope are set as they should be
			$should = $targetCDconfiguration[$index-1];

			if($should['index'] != $index){
				var_dump($should);
				throw new Exception("Configuration index {$should['index']} (see above) does not match array position {$index}");
			}

			if(!$cd->getActive() != $should['active']){
				$cd->setActive($should['active']);
				$update = true;
				$changeset[] = 'status ('.($should['active']?'t':'f').')';
			}
	
			if($cd->getName() != $should['name']){
				$cd->setName($should['name']);
				$update = true;
				$changeset[] = "name ({$should['name']})";
			}
	
			if($cd->getScope() != $should['scope']){
				$cd->setScope($should['scope']);
				$update = true;
				$changeset[] = "scope ({$should['scope']})";
			}
		}
		if($update){
			echo 'updating ' . implode(', ', $changeset) . " of ga:dimension$index\n";
			$requests[$index] = $analyticsService->management_customDimensions->patch($account, $property, ('ga:dimension' . $index), $cd);
		}else{
			echo "ga:dimension$index ok\n";
		}
		// remove dimension from known dimensions so its easy to add any remaning after this loop
		unset($dimensionsSetup[$index-1]);
	}

	foreach($dimensionsSetup AS $index=>$should){
		$cd = new Google_Service_Analytics_CustomDimension();
		$cd->setActive($should['active']);
		$cd->setName($should['name']);
		$cd->setScope($should['scope']);
	
		$requests[$index+1] = $analyticsService->management_customDimensions->insert($account, $property, $cd);
	}
	return $requests;
}
