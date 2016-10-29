#!/usr/bin/env php
<?php 

use Tokenly\DeliveryClient\Client as DeliveryClient;

require __DIR__.'/../vendor/autoload.php';

try {
    // env vars
    $api_url        = getenv('TOKENDELIVERY_CONNECTION_URL'); if ($api_url === false) { $api_url = 'https://deliver.tokenly.com'; }
    $api_token      = getenv('TOKENDELIVERY_API_TOKEN');      if ($api_token === false) { throw new Exception("TOKENDELIVERY_API_TOKEN environment var must be defined", 1); }
    $api_secret_key = getenv('TOKENDELIVERY_API_KEY');        if ($api_secret_key === false) { throw new Exception("TOKENDELIVERY_API_KEY environment var must be defined", 1); }

    // arguments
    $filters    = isset($argv[1]) ? json_decode($argv[1], true) : [];
} catch (Exception $e) {
    echo $e->getMessage()."\n";
    echo "Usage: ".basename(__FILE__)." [<filters_json>]\n";
    exit(1);
}

// init the client
$api = new DeliveryClient($api_url, $api_token, $api_secret_key);

// run and show the results
$api_result = $api->getDeliveryList($filters);
echo json_encode($api_result, 192)."\n";
