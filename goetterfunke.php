<?php
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}else{
    echo "hi\n";
}

require 'vendor/autoload.php';
require_once 'getClient.php';
require_once 'list.php';
require_once 'customDimensions.php';

$client = getClient('client_credentials.json');
$analyticsService = new Google_Service_Analytics($client);

list($file,$cliAction,$account,$property,$cliParam) = $argv;
echo "hello {$cliAction}\n";
switch($cliAction){
    case 'getCDsJSON':
        $customDimensions = getCustomDimensions($analyticsService,$account,$property);
        print json_encode($customDimensions,JSON_PRETTY_PRINT);
    break;
    case 'setCDs': 
        //get a delta
        $currentCDconfiguration = getCustomDimensions($analyticsService,$account,$property);
        $targetCDconfiguration = json_decode(file_get_contents(__DIR__ . '/' . $cliParam),true);

        $change = configDiffGenerator($currentCDconfiguration,$targetCDconfiguration);
        var_dump($change);
        $client->setUseBatch(true);
        $maxBatchSize = 5;

        while($change->valid()){

            $requests = [];
            for ($i=0; $i < $maxBatchSize and $change->valid(); $i++) { 
                $cd = $change->current();
                if($change->key() == 'patch'){
                    
                    $requests[$cd->getIndex()] = $analyticsService->management_customDimensions->patch($account, $property, ('ga:dimension' . $cd->getIndex()),$cd);
                }else{
                    $requests[$cd->getIndex()] = $analyticsService->management_customDimensions->insert($account, $property, $cd);
                }
                $change->next();
            }

            do{
                echo "create new batch \n";
                $batch = $analyticsService->createBatch();
                foreach($requests as $index => $request){
                    $batch->add($request, $index);
                }
                $results = $batch->execute();
                foreach ($results as $result => $obj) {
                    $index = substr($result, 9);
                    if(get_class($obj) !== "Google_Service_Exception" ){
                        unset($requests[$index]);
                        echo "CD $index ok\n";
                        var_dump($obj);
                    }else{
                        $msg = json_decode($obj->getMessage());
                        echo "Retry CD $index:{$obj->getMessage()}\n";
                        sleep(3);
                    }
                }
            }while(count($requests)>0);
            echo "Section completed Successfully.\n\n";
        }
    break;
    case 'listProperties':
        listProperties($analyticsService);
    break;
    default:
        echo "unknown Action {$cliAction}\n";
        echo "use\n";
        echo "listProperties\n";
        echo "getCDsJSON <account> <property>\n";
        echo "setCDs <account> <property> <config.json>\n";
}

