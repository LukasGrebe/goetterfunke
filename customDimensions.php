<?php

function listCustomDimensionsAsJSON($service, $account, $property){
	$customDimensions = $service->management_customDimensions->listManagementCustomDimensions($account, $property);
	
	$json = [];
	foreach($customDimensions->getItems() as $cd){
		$json[] = array('name' => $cd->getName(),
			'scope' => $cd->getScope(),
			'active' => ($cd->getActive()),
			'index' => $cd->getIndex(),
			'status' => $cd->getActive());
	}
	echo json_encode($json,JSON_PRETTY_PRINT);
}

function setCustomDiemensionsFromJSON($service, $client, $account, $property, $cdSetup, $dryRun){
	//Load "truth"
	$dimensionsSetup = json_decode(file_get_contents(__DIR__ . '/' . $cdSetup),true);

	if(!$dimensionsSetup){
		die(json_last_error_msg());
	}

	$customDimensions = $service->management_customDimensions->listManagementCustomDimensions($account, $property);
	
	$client->setUseBatch(true);
	$batch = $service->createBatch();
	$batchCount = 0;

	foreach($customDimensions->getItems() as $cd){
		$update = false;
		$index = $cd->getIndex();
		$changeset = [];


	
		//Do we have a definition for this Dimension? deactive if not
		if(!array_key_exists($index-1, $dimensionsSetup)){
			if($cd->getActive()){
				$cd->setActive(false);
				$update = true;
				$changeset[] = 'status (set to inactive)';
			}
		}else{
			//check if active, name and scope are set as they should be
			$should = $dimensionsSetup[$index-1];

			if($should['index'] != $index){
				var_dump($should);
				throw new Exception("Configuration index {$should['index']} (see above) does not match array position {$index}");
			}

			$shouldBeActive = (stripos($should['status'],'inactive')===false);
			if(!$cd->getActive() == $shouldBeActive){
				$cd->setActive($shouldBeActive);
				$update = true;
				$changeset[] = 'status ('.($shouldBeActive?'t':'f').')';
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
			 $batch->add($service->management_customDimensions->patch($account, $property, ('ga:dimension' . $index), $cd),$index);
			 $batchCount ++;
			 if($batchCount > 9){
				 echo "batch of 10\n";
			 break;
			 }
		}else{
			echo "ga:dimension$index ok\n";
		}
	
		// remove dimension from known dimensions so its easy to add any remaning after this loop
		unset($dimensionsSetup[$index-1]);
	}
	
	
	//add any remaning custom dimensions
	if($batchCount < 10){
		foreach($dimensionsSetup AS $index=>$should){
			$cd = new Google_Service_Analytics_CustomDimension();
			$cd->setActive(true);
			$cd->setName($should['name']);
			$cd->setScope($should['scope']);
		
			$batch->add($service->management_customDimensions->insert($account, $property, $cd),$index);
			$batchCount ++;
			if($batchCount > 9){
			break;
			}
		}
	}
	
	if($dryRun){
		print "Dry run. no Changes sent";
	}elseif ($batchCount == 0){
		print "0 Changes";
	}else{
		try {
			print "$batchCount Changes to send…\n";
			$results = $batch->execute();
			foreach ($results as $result => $obj) {
				if(get_class($obj) === "Google_Service_Exception" ){
					echo "CD $result Error: {$obj->getMessage()}\n";
				}else{
					echo "CD $result \n";
					var_dump($obj);
				}
			}
			//print count($results) . " Changes Successfull \n";
		} catch (apiServiceException $e) {
			print 'There was an Analytics API service error '
					. $e->getCode() . ':' . $e->getMessage();
		
		} catch (apiException $e) {
			print 'There was a general API error '
					. $e->getCode() . ':' . $e->getMessage();
		}
	}
}
