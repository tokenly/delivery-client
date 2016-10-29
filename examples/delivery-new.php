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
    $source        = (isset($argv[1]) AND strlen($argv[1])) ? $argv[1] : null; if ($source === null) { throw new Exception("source is required", 1); }
    $destination   = (isset($argv[2]) AND strlen($argv[2])) ? $argv[2] : null; if ($destination === null) { throw new Exception("destination is required", 1); }
    $token         = (isset($argv[3]) AND strlen($argv[3])) ? $argv[3] : null; if ($token === null) { throw new Exception("token is required", 1); }
    $quantity      = (isset($argv[4]) AND strlen($argv[4])) ? $argv[4] : null; if ($quantity === null) { throw new Exception("quantity is required", 1); }
    $delivery_date = isset($argv[5]) ? $argv[5] : null;
    $extra_opts    = isset($argv[6]) ? json_decode($argv[6], true) : [];

} catch (Exception $e) {
    echo $e->getMessage()."\n";
    echo "Usage: ".basename(__FILE__)." <source> <destination> <token> <quantity> [<delivery_date>] [<extra_opts_json>]\n";
    exit(1);
}

// init the client
$api = new DeliveryClient($api_url, $api_token, $api_secret_key);

// run and show the results
$api_result = $api->newDelivery($source, $destination, $token, $quantity, $delivery_date, $extra_opts);
echo json_encode($api_result, 192)."\n";
