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

$client = getClient('client_credentials.json','https://lukas.grebe.me/goetterfunke/auth.php');
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
        //get a delta
        $currentCDconfiguration = getCustomDimensions($analyticsService,$account,$property);
        $targetCDconfiguration = json_decode(file_get_contents(__DIR__ . '/' . $cliParam),true);
        
        $change = configDiffGenerator($currentCDconfiguration,$targetCDconfiguration);

        while($change->valid()){
            $target = $change->current();
            echo ">--\nsending change\n";
            try{
                if($change->key() == 'patch'){
                    $result = $analyticsService->management_customDimensions->patch($account, $property, ('ga:dimension' . $target->getIndex()),$target);
                }else{
                    $result = $analyticsService->management_customDimensions->insert($account, $property, $target);
                }
                //var_dump($result);
            } catch (apiServiceException $e) {
                print 'There was an Analytics API service error '
                        . $e->getCode() . ':' . $e->getMessage();
            } catch (apiException $e) {
                print 'There was a general API error '
                        . $e->getCode() . ':' . $e->getMessage();
            }

            echo ">>- Double Checking ga:dimension{$target->getIndex()} - (response index {$result->getIndex()}):\n";
            echo "Active {$result->getActive()} == {$target->getActive()}\n";
            echo "Name '{$result->getName()}' == '{$target->getName()}'\n";
            echo "Scope '{$result->getScope()}' == '{$target->getScope()}'\n";
            if( $result->getActive() == $target->getActive() and
				$result->getName()   == $target->getName() and
				$result->getScope()  == $target->getScope()){
                echo ">>> update seems good. next! \n\n";
                $change->next();
            }else{
                echo ">>> update did not work. try again \n\n";
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

