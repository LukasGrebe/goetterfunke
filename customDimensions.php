<?php

function getCustomDimensions($analyticsService, $account, $property){
	$customDimensions = $analyticsService->management_customDimensions->listManagementCustomDimensions($account,$property);
	return $customDimensions->getItems();
}

function arrayToDimensionObject($arr){
	$cd = new Google_Service_Analytics_CustomDimension();
	$cd->setScope($arr['scope']);
	$cd->setActive($arr['active']);
	$cd->setIndex($arr['index']);
	$cd->setName($arr['name']);
	return $cd;
}

function configDiffGenerator($currentCDconfiguration,$targetCDconfiguration){
	echo "hello world\n";
	foreach ($currentCDconfiguration as $cd) {
		$index = $cd->getIndex();
		$targetCDIndex = $index-1; //because unlike Analytic's CDs, arrays are 0 based
		$changeset = [];
		echo "--- checking CD {$index}\n";
		if(!array_key_exists($targetCDIndex, $targetCDconfiguration)){
			//this CD should not be active
			if($cd->getActive()){
				$cd->setActive(false);
				$cd->setName('-not in use-');
				echo "Deactivate ga:dimension$index\nSet Name to: -not in use-";
				yield 'patch' => $cd;
			}
		}else{
			$target = $targetCDconfiguration[$targetCDIndex];
			$target['scope'] = strtoupper($target['scope']);

			if($target['index'] != $index){
				var_dump($should);
				throw new Exception("Configuration index {$target['index']} (see above) does not match array position {$index}");
			}

			$target['scope'] = strtoupper($target['scope']);
			//check if active, name and scope are set as they should be
			if( $cd->getActive() != $target['active'] or
				$cd->getName()   != $target['name'] or
				$cd->getScope()  != $target['scope']){
					echo "Update ga:dimension$index: Active({$cd->getActive()}) to {$target['active']}\n";
					echo "Name '{$cd->getName()}' to '{$target['name']}'\n";
					echo "Scope '{$cd->getScope()}' to '{$target['scope']}'\n";
					yield 'patch' => arrayToDimensionObject($target);
			}
			// remove dimension from known dimensions so its easy to add any remaning after this loop
			unset($targetCDconfiguration[$targetCDIndex]);
		}		
	}
	// any non-removed target configurations left?
	foreach($targetCDconfiguration as $target){
		$target = arrayToDimensionObject($target);
		echo "Create as new CD {$target->getIndex()}\n";
		echo "Name '{$target->getName()}\n";
		echo "Scope '{$target->getScope()}\n";

		yield 'insert' => $target;
	}
}
