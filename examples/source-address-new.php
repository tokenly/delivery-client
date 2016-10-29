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
    $label         = $argv[1];
    $type          = $argv[2];
    $webhook       = (isset($argv[3]) AND strlen($argv[3])) ? $argv[3] : null;
    $join_callback = (isset($argv[4]) AND strlen($argv[4])) ? $argv[4] : null;
    $auto_fulfill  = !!(isset($argv[5]) ? $argv[5] : true);
    if (!$label) { throw new Exception("label is required", 1); }
    if (!$type) { throw new Exception("type is required", 1); }
} catch (Exception $e) {
    echo $e->getMessage()."\n";
    echo "Usage: ".basename(__FILE__)." <label> <type:"2:2" or "2:3")> [<webhook>] [<join_callback>] [<auto_fulfill>]\n";
    exit(1);
}

// init the client
$api = new DeliveryClient($api_url, $api_token, $api_secret_key);

// run and show the results
$api_result = $api->newSourceAddress($label, $type, $webhook, $join_callback, $auto_fulfill);
echo json_encode($api_result, 192)."\n";
