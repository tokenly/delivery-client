<?php

namespace Tokenly\DeliveryClient;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/*
* TokenDeliveryServiceProvider
*/
class TokenDeliveryServiceProvider extends ServiceProvider
{

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bindConfig();

        $this->app->bind('Tokenly\DeliveryClient\Client', function($app) {
            $xchain_client = new \Tokenly\DeliveryClient\Client(Config::get('tokendelivery.connection_url'), Config::get('tokendelivery.api_token'), Config::get('tokendelivery.api_key'));
            return $xchain_client;
        });

        $this->app->bind('Tokenly\DeliveryClient\WebHookReceiver', function($app) {
            $webhook_receiver = new \Tokenly\DeliveryClient\WebHookReceiver(Config::get('tokendelivery.api_token'), Config::get('tokendelivery.api_key'));
            return $webhook_receiver;
        });
    }

    protected function bindConfig()
    {
        // simple config
        $config = [
            'tokendelivery.connection_url' => env('TOKENDELIVERY_CONNECTION_URL', 'https://delivery.tokenly.com'),
            'tokendelivery.api_token'      => env('TOKENDELIVERY_API_TOKEN'     , null),
            'tokendelivery.api_key'        => env('TOKENDELIVERY_API_KEY'       , null),
        ];

        // set the laravel config
        Config::set($config);

        return $config;
    }

}

