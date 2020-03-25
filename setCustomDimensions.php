<?php

require_once './goetterfunke.php';

if(count($argv)<3){
	die('usage: ' .  __FILE__ . ' <acount id (123)> <property id (ua-123-4)> <debug>');
}

//Debug via Charles?
if(array_key_exists(3, $argv)){
	$httpClient = new GuzzleHttp\Client([
    	'proxy' => 'localhost:8888', // by default, Charles runs on localhost port 8888
    	'verify' => false, // otherwise HTTPS requests will fail.
	]);
	$client->setHttpClient($httpClient);
}

//Load "truth"
$dimensionsSetup = json_decode(file_get_contents("./configuration/customDimensions.json"),true);

//Target Property
$account = $argv[1];
$property = $argv[2];


//Load current Properties
$customDimensions = $analytics->management_customDimensions->listManagementCustomDimensions($account, $property);

var_dump($customDimensions);
die();

function fullName($name,$index,$scope){
	return $name . ' - CD' . $index . lcfirst($scope)[0];
}

$client->setUseBatch(true);
$batch = new Google_Http_Batch($client);

foreach($customDimensions->getItems() as $cd){
	$update = false;
	$index = $cd->getIndex();
	$changeset = [];

	//Do we have a definition for this Dimension? deactive if not
	if(!array_key_exists($index-1, $dimensionsSetup)){
		if($cd->getActive()){
			$cd->setActive(false);
			$update = true;
			$changeset[] = 'status (inactive)';
		}
	}else{
		//check if active, name and scope are set as they should be
		$should = $dimensionsSetup[$index-1];

		if(!$cd->getActive()){
			$cd->setActive(true);
			$update = true;
			$changeset[] = 'status (active)';
		}

		$fullName = fullName($should['name'], $index, $should['scope']);
		if($cd->getName() != $fullName){
			$cd->setName($fullName);
			$update = true;
			$changeset[] = "name ($fullName)";
		}

		if($cd->getScope() != $should['scope']){
			$cd->setScope($should['scope']);
			$update = true;
			$changeset[] = "scope ({$should['scope']})";
		}
	}

	if($update){
		echo 'updating ' . implode(', ', $changeset) . " of ga:dimension$index\n";
	 $batch->add($analytics->management_customDimensions->patch($account, $property, ('ga:dimension' . $index), $cd),$index);
	}else{
		echo "ga:dimension$index ok\n";
	}

	// remove dimension from known dimensions so its easy to add any remaning after this loop
	unset($dimensionsSetup[$index-1]);
}


//add any remaning custom dimensions
foreach($dimensionsSetup AS $index=>$should){
	$cd = new Google_Service_Analytics_CustomDimension();
	$cd->setActive(true);
	$cd->setName(fullName($should['name'], $index, $should['scope']));
	$cd->setScope($should['scope']);

  $batch->add($analytics->management_customDimensions->insert($account, $property, $cd),$index);
}

try {
	print "Sending Changes…\n";
	$results = $batch->execute();
	print count($results) . " Changes Successfull \n";
} catch (apiServiceException $e) {
	print 'There was an Analytics API service error '
			. $e->getCode() . ':' . $e->getMessage();

} catch (apiException $e) {
	print 'There was a general API error '
			. $e->getCode() . ':' . $e->getMessage();
}
