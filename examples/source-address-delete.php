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
    $uuid          = $argv[1];
    $sweep_address = $argv[2];
    if (!$uuid) { throw new Exception("uuid is required", 1); }
    if (!$sweep_address) { throw new Exception("sweep_address is required", 1); }
} catch (Exception $e) {
    echo $e->getMessage()."\n";
    echo "Usage: ".basename(__FILE__)." <uuid> <sweep_address>\n";
    exit(1);
}

// init the client
$api = new DeliveryClient($api_url, $api_token, $api_secret_key);

// run and show the results
$api_result = $api->shutdownSourceAddress($uuid, $sweep_address);
echo json_encode($api_result, 192)."\n";
