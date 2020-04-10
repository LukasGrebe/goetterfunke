<?php
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require 'vendor/autoload.php';
require_once 'getClient.php';
require_once 'list.php';
require_once 'customDimensions.php';

$client = getClient('client_credentials.json','https://lukas.grebe.me/goetterfunke/auth/');
$analyticsService = new Google_Service_Analytics($client);

error_reporting(E_ALL & ~E_NOTICE);
list($file,$cliAction,$account,$property,$cliParam) = $argv;
error_reporting(E_ALL);

switch($cliAction){
    case 'getCDsJSON':
        $customDimensions = getCustomDimensions($analyticsService,$account,$property);
        print json_encode($customDimensions,JSON_PRETTY_PRINT);
    break;
    case 'setCDs': 
        echo "\n#######\nWorking with $account $property\n";
        $currentCDconfiguration = getCustomDimensions($analyticsService,$account,$property);
        $targetCDconfiguration = json_decode(file_get_contents(__DIR__ . '/' . $cliParam),true);

        $client->setUseBatch(true);
        $batchSize = 4;

        $change = configDiffGenerator($currentCDconfiguration,$targetCDconfiguration);

        while($change->valid()){
            $batch = $analyticsService->createBatch();
            for ($i=0; $i < $batchSize and $change->valid(); $i++) { 
                $target = $change->current();
                
                if($change->key() == 'patch'){
                    $batch->add($analyticsService->management_customDimensions->patch($account, $property, ('ga:dimension' . $target->getIndex()),$target),$target->getIndex());
                }else{
                    $batch->add($analyticsService->management_customDimensions->insert($account, $property, $target),$target->getIndex());
                }
                echo "-> \033[33mbatched\033[0m\n";
                $change->next();
            }
            echo "\nsending batch: ";
            $results = $batch->execute();
            foreach ($results as $index => $result){
                if(get_class($result) === 'Google_Service_Analytics_CustomDimension'){
                    echo "CD$index: \033[32mok\033[0m ";
                }else{
                    //assuming Error Result
                    $err = $result->getErrors()[0];
                    switch($result->getCode()){
                        case 400:
                            echo "\nCD$index: \033[31mfailed\033[0m ({$result->getCode()} {$err['reason']}): {$err['message']}\n";
                        break;
                        case 403:
                            echo "\nCD$index: \033[31mfailed\033[0m ({$result->getCode()} {$err['reason']}): {$err['message']}\n";
                            die();

                    }
                }
            }
            sleep(1); //1.5 Queries per second max
        }
        echo "\nDone.\n";
    break;
    case 'listProperties':
        listProperties($analyticsService);
    break;
    default:
        echo "unknown Action {$cliAction}\n";
        echo "use\n";
        echo "listProperties\n";
        echo "getCDsJSON <account id> <property id>\n";
        echo "setCDs <account id> <property id> <config.json>\n";
}

