<?php 

namespace Tokenly\DeliveryClient;

use Exception;
use Tokenly\APIClient\Exception\APIException;
use Tokenly\APIClient\TokenlyAPI;
use Tokenly\CurrencyLib\Quantity;
use Tokenly\HmacAuth\Generator;

/**
* Token Delivery Service Client
*/
class Client extends TokenlyAPI
{
    
    function __construct($api_url, $api_token, $api_secret_key)
    {
        parent::__construct($api_url, $this->getAuthenticationGenerator(), $api_token, $api_secret_key);
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
        $get = false;
        try{
            $get = $this->newAPIRequest('GET', '/source');
        }
        catch(APIException $e){
            throw new Exception('Error getting list of source addresses: '.$e->getMessage());
        }
        return $get;
    }
    
    public function getSourceAddress($uuid)
    {
        $api_result = false;
        try{
            $api_result = $this->newAPIRequest('GET', '/source/'.$uuid);
        }
        catch(APIException $e){
            throw new Exception('Error getting source address: '.$e->getMessage());
        }
        if(!$api_result){
            throw new Exception('Unknown error getting source address');
        }
        return $api_result;
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
        try{
            $shutdown = $this->newAPIRequest('DELETE', '/source/'.$uuid, $data);
        }
        catch(APIException $e){
            throw new Exception('Error shutting down source address: '.$e->getMessage());
        }
        if(!isset($get['result'])){
            throw new Exception('Unknown error updating source address');
        }
        return $shutdown['result'];
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
        catch(APIException $e){
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
        catch(APIException $e){
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
        catch(APIException $e){
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
        catch(APIException $e){
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
        catch(APIException $e){
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
        catch(APIException $e){
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
        catch(APIException $e){
            throw new Exception('Error canceling delivery: '.$e->getMessage());
        }
        if(!$delete){
            throw new Exception('Unknown error canceling delivery');
        }
        return $output; 
    }
    
    /** fulfillment methdods **/
    
    public function fulfillSingleDelivery($uuid)
    {
        return $this->newAPIRequest('PATCH', '/fulfillment/single/'.$uuid);
    }
    
    public function completeDelivery($uuid)
    {
        return $this->fulfillSingleDelivery($uuid);
    }
    
    public function fulfillMultipleDeliveries($source, $filters = array())
    {
        $filter_params = array();
        if(isset($filters['tokens'])){
            $filter_params['tokens'] = $filters['tokens'];
        }
        return $this->newAPIRequest('PATCH', '/fulfillment/multiple/'.$source, $filter_params);
    }
    
    public function completeMultiple($source, $filters)
    {
        return $this->fulfillMultipleDeliveries($source, $filters);
    }

    ////////////////////////////////////////////////////////////////////////

    protected function newAPIRequest($method, $path, $parameters=[], $options=[]) {
        $api_path = '/api/v1'.$path;
        return $this->call($method, $api_path, $parameters, $options);
    }

    protected function getAuthenticationGenerator() {
        $generator = new Generator();
        return $generator;
    }

}
