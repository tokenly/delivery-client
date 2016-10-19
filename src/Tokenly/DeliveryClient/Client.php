<?php 

namespace Tokenly\DeliveryClient;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Tokenly\CurrencyLib\Quantity;
use Tokenly\HmacAuth\Generator;
use Tokenly\DeliveryClient\Exception\DeliveryException;

/**
* Token Delivery Service Client
*/
class Client
{
    
    function __construct($api_url, $api_token, $api_secret_key)
    {
        $this->api_url     = $api_url;
        $this->api_token      = $api_token;
        $this->api_secret_key = $api_secret_key;
    }
    
    /** Source Address Methods **/

    public function newSourceAddress($label, $type, $webhook = null, $join_callback = null, $auto_fulfill = true)
    {
        $data = array();
        $data['label'] = $label;
        $data['type'] = $type;
        $data['webhook'] = $webhook;
        $data['join_callback'] = $join_callback;
        $data['auto_fulfill'] = $auto_fulfill;
        return $this->newAPIRequest('POST', '/source', $data);
    }
    
    public function getSourceAddressList()
    {
        return $this->newAPIRequest('GET', '/source');
    }
    
    public function getSourceAddress($uuid)
    {
        return $this->newAPIRequest('GET', '/source/'.$uuid);
    }
    
    public function updateSourceAddress($uuid, $data)
    {
        $use_data = array();
        $fields =array('webhook', 'label', 'join_callback', 'active', 'auto_fulfill');
        foreach($fields as $f){
            if(isset($data[$f])){
                $use_data[$f] = $data[$f];
            }
        }
        if(count($use_data) == 0){
            throw new Exception('No data to update for delivery source address');
        }
        return $this->newAPIRequest('PATCH', '/source/'.$uuid, $use_data);
    }
    
    public function shutdownSourceAddress($uuid, $sweep_address)
    {
        $data = array('sweep_address' => $sweep_address);
        return $this->newAPIRequest('DELETE', '/source/'.$uuid, $data);
    }
    
    
    /** Token Delivery Methods **/
    
    public function newDelivery($source, $destination, $token, $quantity, $delivery_date = null, $extra_opts = array())
    {
        $data = array();
        $data['source'] = $source;
        $data['destination'] = $destination;
        $data['token'] = $token;
        $data['quantity'] = $quantity;
        $data['delivery_date'] = $delivery_date;
        if(isset($extra_opts['pseudo']) AND $extra_opts['pseudo']){
            $data['pseudo'] = true;
        }
        if(isset($extra_opts['hold_promise']) AND $extra_opts['hold_promise']){
            $data['hold_promise'] = true;
        }
        if(isset($extra_opts['ref_data'])){
            $data['ref_data'] = $extra_opts['ref_data'];
        }
        $delivery = false;
        try{
            $delivery = $this->newAPIRequest('POST', '/delivery', $data);
        }
        catch(DeliveryException $e){
            throw new Exception('Error creating delivery: '.$e->getMessage());
        }
        if(!isset($delivery['result'])){
            throw new Exception('Unknown error creating token delivery');
        }
        return $delivery['result'];
    }
    
    public function getDelivery($uuid)
    {
        $delivery = false;
        try{
            $delivery = $this->newAPIRequest('GET', '/delivery/'.$uuid);
        }
        catch(DeliveryException $e){
            throw new Exception('Error getting details: '.$e->getMessage());
        }
        if(!isset($delivery['result'])){
            throw new Exception('Unknown error getting delivery details');
        }
        return $delivery['result'];
    }
    
    public function getDeliveryList($filters = array())
    {
        $use_filters = array();
        $valid_filters = array('source', 'token', 'destination', 'show_complete');
        foreach($valid_filters as $f){
            if(isset($filters[$f])){
                $use_filters[$f] = $filters[$f];
            }
        }
        $output = false;
        try{
            $output = $this->newAPIRequest('GET', '/delivery', $use_filters);
        }
        catch(DeliveryException $e){
            throw new Exception($e->getMessage());
        }
        return $output;
    }
    
    public function updateDelivery($uuid, $data)
    {
        $use_data = array();
        $valid_fields = array('quantity', 'delivery_date', 'ref_data', 'ready');
        foreach($valid_fields as $f){
            if(isset($data[$f])){
                $use_data[$f] = $data[$f];
            }
        }
        $update = false;
        try{
            $update = $this->newAPIRequest('PATCH', '/delivery/'.$uuid, $use_data);
        }
        catch(DeliveryException $e){
            throw new Exception('Error updating delivery: '.$e->getMessage());
        }
        if(!$update){
            throw new Exception('Unknown error updating delivery');
        }
        return $output; 
    }
    
    public function markReady($uuid)
    {
        $use_data = array();
        $use_data['ready'] = true;
        $update = false;
        try{
            $update = $this->newAPIRequest('PATCH', '/delivery/'.$uuid, $use_data);
        }
        catch(DeliveryException $e){
            throw new Exception('Error marking delivery as ready to fulfill: '.$e->getMessage());
        }
        if(!$update){
            throw new Exception('Unknown error marking delivery ready to fulfill');
        }
        return $output; 
    }
    
    public function markUnready($uuid)
    {
        $use_data = array();
        $use_data['ready'] = false;
        $update = false;
        try{
            $update = $this->newAPIRequest('PATCH', '/delivery/'.$uuid, $use_data);
        }
        catch(DeliveryException $e){
            throw new Exception('Error marking delivery as no longer ready to fulfill: '.$e->getMessage());
        }
        if(!$update){
            throw new Exception('Unknown error marking delivery no longer ready to fulfill');
        }
        return $output; 
    }
    
    public function cancelDelivery($uuid)
    {
        $delete = false;
        try{
            $delete = $this->newAPIRequest('DELETE', '/delivery/'.$uuid);
        }
        catch(DeliveryException $e){
            throw new Exception('Error canceling delivery: '.$e->getMessage());
        }
        if(!$delete){
            throw new Exception('Unknown error canceling delivery');
        }
        return $output; 
    }


    ////////////////////////////////////////////////////////////////////////

    protected function newAPIRequest($method, $path, $data=[]) {
        $api_path = '/api/v1'.$path;

        $client = new GuzzleClient();

        if ($data AND ($method == 'POST' OR $method == 'PATCH')) {
            $body = \GuzzleHttp\Psr7\stream_for(json_encode($data));
            $headers = ['Content-Type' => 'application/json'];
            $request = new \GuzzleHttp\Psr7\Request($method, $this->api_url.$api_path, $headers, $body);
        } else if ($method == 'GET') {
            $request = new \GuzzleHttp\Psr7\Request($method, $this->api_url.$api_path);
            $request = \GuzzleHttp\Psr7\modify_request($request, ['query' => http_build_query($data, null, '&', PHP_QUERY_RFC3986)]);
        } else {
            $request = new \GuzzleHttp\Psr7\Request($method, $this->api_url.$api_path);
        }

        // add auth
        $request = $this->getAuthenticationGenerator()->addSignatureToGuzzle6Request($request, $this->api_token, $this->api_secret_key);
        
        // send request
        try {
            $response = $client->send($request);
        } catch (RequestException $e) {
            if ($response = $e->getResponse()) {
                // interpret the response and error message
                $code = $response->getStatusCode();
                try {
                    $json = json_decode($response->getBody(), true);
                } catch (Exception $parse_json_exception) {
                    // could not parse json
                    $json = null;
                }
                if ($json and isset($json['error'])) {
                    $delivery_exception = new DeliveryException($json['error'], $code);
                    throw $delivery_exception;
                }
                elseif($json AND isset($json['message'])){
                    $delivery_exception = new DeliveryException($json['message'], $code);
                    throw $delivery_exception;
                }
            }

            // if no response, then just throw the original exception
            throw $e;
        }

        $code = $response->getStatusCode();
        if ($code == 204) {
            // empty content
            return [];
        }

        $json = json_decode($response->getBody(), true);
        if (!is_array($json)) { throw new Exception("Unexpected response: ".$response->getBody(), 1); }

        return $json;
    }

    protected function getAuthenticationGenerator() {
        $generator = new Generator();
        return $generator;
    }

}
