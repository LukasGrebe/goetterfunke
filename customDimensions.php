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
	foreach ($currentCDconfiguration as $cd) {
		$index = $cd->getIndex();
		$targetCDIndex = $index-1; //because unlike Analytic's CDs, arrays are 0 based
		$changeset = [];
		echo "{$index}: ";
		if(!array_key_exists($targetCDIndex, $targetCDconfiguration)){
			//this CD should not be active
			if($cd->getActive()){
				$cd->setActive(false);
				$cd->setName('-not in use-');
				echo "set active: false; change name from '{$cd->getName()}' to '-not in use-'; ";
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
			$updateFlag = false;
			if( $cd->getActive() != $target['active'] ){
				$updateFlag = true;
				echo "set active " & $target['active']? 'true':'false' & "; ";
			}if( $cd->getName()   != $target['name'] ){
				$updateFlag = true;
				echo "change name from '{$cd->getName()}' to '{$target['name']}'; ";
			}if( $cd->getScope()  != $target['scope'] ){
				$updateFlag = true;
				echo "change scope from '{$cd->getScope()}' to '{$target['scope']}'; ";
			}
			if($updateFlag){
				yield 'patch' => arrayToDimensionObject($target);
			}else{
				echo "\033[32mok\033[0m ";
			}
			// remove dimension from known dimensions so its easy to add any remaning after this loop
			unset($targetCDconfiguration[$targetCDIndex]);
		}		
	}
	// any non-removed target configurations left?
	foreach($targetCDconfiguration as $target){
		$target = arrayToDimensionObject($target);
		echo "create with name '{$target->getName()}'; scope '{$target->getScope()}'; '";
		yield 'insert' => $target;
	}
}
