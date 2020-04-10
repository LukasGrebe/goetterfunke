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
        
        $change = configDiffGenerator($currentCDconfiguration,$targetCDconfiguration);

        while($change->valid()){
            $target = $change->current();
            echo "… ";
            try{
                if($change->key() == 'patch'){
                    $result = $analyticsService->management_customDimensions->patch($account, $property, ('ga:dimension' . $target->getIndex()),$target);
                }else{
                    $result = $analyticsService->management_customDimensions->insert($account, $property, $target);
                }
                //var_dump($result);
            } catch(Google_Service_Exception $e){
                echo ">>> \033[31mfailed\033[0m ({$e->getCode()})\n{$e->getMessage()}\n\n\n";                
                $change->next();
                continue;
            }

            echo "> double checking (index {$result->getIndex()}) > ";
            if( $result->getActive() == $target->getActive() and
				$result->getName()   == $target->getName() and
				$result->getScope()  == strtoupper($target->getScope())){
                echo "\033[32mok\033[0m\n";
                $change->next();
            }else{
                echo "\033[33mretry\033[0m ";
            }
            //sleep(1);
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

