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

    public function newSourceAddress($label, $type, $webhook = null, $join_callback = null, $auto_fulfill = true, $desired_utxo_primes = null, $assign_user = null, $assign_user_hash = null)
    {
        $data = array();
        $data['label'] = $label;
        $data['type'] = $type;
        $data['webhook'] = $webhook;
        $data['join_callback'] = $join_callback;
        $data['auto_fulfill'] = $auto_fulfill;
        if($desired_utxo_primes !== null){
            $data['desired_utxo_primes'] = $desired_utxo_primes;
        }
        if($assign_user !== null){
            $data['assign_user'] = $assign_user;
            $data['assign_user_hash'] = $assign_user_hash;
        }
        return $this->newAPIRequest('POST', '/source', $data);
    }
    
    /**
     * fetch existing source addresses
     * Can be filtered by tokenpass username with the assign_user parameter
     * @param  array $parameters filter the list.  Accepts (string) assign_user, (bool) archived
     * @return array API response
     */
    public function getSourceAddressList($parameters=null)
    {
        $result = false;
        try {
            $query_params = [];
            if ($parameters !== null) {
                $query_params = $parameters;
            }
            $result = $this->newAPIRequest('GET', '/source', $query_params);
        }
        catch(APIException $e) {
            throw new Exception('Error getting list of source addresses: '.$e->getMessage());
        }
        return $result;
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
        $fields =array('webhook', 'label', 'join_callback', 'active', 'auto_fulfill', 'desired_utxo_primes');
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
        return $delivery;
    }
    
    public function multipleNewDeliveries($deliveries)
    {
        try{
            $new_deliveries = $this->newAPIRequest('POST', '/delivery/multiple', $deliveries);
        }
        catch(APIException $e){
            throw new Exception('Error creating multiple deliveries: '.$e->getMessage());
        }
        return $new_deliveries;
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
        return $delivery;
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
        $valid_fields = array('quantity', 'delivery_date', 'ref_data', 'ready', 'destination');
        foreach($valid_fields as $f){
            if(isset($data[$f])){
                $use_data[$f] = $data[$f];
            }
        }
        $update_results = false;
        try{
            $update_results = $this->newAPIRequest('PATCH', '/delivery/'.$uuid, $use_data);
        }
        catch(APIException $e){
            throw new Exception('Error updating delivery: '.$e->getMessage());
        }
        if(!$update_results){
            throw new Exception('Unknown error updating delivery');
        }
        return $update_results; 
    }
    
    public function markReady($uuid)
    {
        $use_data = array();
        $use_data['ready'] = true;
        $update_results = false;
        try{
            $update_results = $this->newAPIRequest('PATCH', '/delivery/'.$uuid, $use_data);
        }
        catch(APIException $e){
            throw new Exception('Error marking delivery as ready to fulfill: '.$e->getMessage());
        }
        if(!$update_results){
            throw new Exception('Unknown error marking delivery ready to fulfill');
        }
        return $update_results; 
    }
    
    public function markUnready($uuid)
    {
        $use_data = array();
        $use_data['ready'] = false;
        $update_results = false;
        try{
            $update_results = $this->newAPIRequest('PATCH', '/delivery/'.$uuid, $use_data);
        }
        catch(APIException $e){
            throw new Exception('Error marking delivery as no longer ready to fulfill: '.$e->getMessage());
        }
        if(!$update_results){
            throw new Exception('Unknown error marking delivery no longer ready to fulfill');
        }
        return $update_results; 
    }
    
    public function cancelDelivery($uuid)
    {
        $delete_results = false;
        try{
            $delete_results = $this->newAPIRequest('DELETE', '/delivery/'.$uuid);
        }
        catch(APIException $e){
            throw new Exception('Error canceling delivery: '.$e->getMessage());
        }
        if(!$delete_results){
            throw new Exception('Unknown error canceling delivery');
        }
        return $delete_results; 
    }
    
    /** fulfillment methdods **/
    
    public function fulfillSingleDelivery($uuid)
    {
        return $this->newAPIRequest('POST', '/fulfillment/single/'.$uuid);
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
        return $this->newAPIRequest('POST', '/fulfillment/multiple/'.$source, $filter_params);
    }
    
    public function completeMultiple($source, $filters)
    {
        return $this->fulfillMultipleDeliveries($source, $filters);
    }

    function updateEmailTx($username, $email, $privilege_key) {
        $data = array(
            'username' => $username,
            'email'    => $email,
            'key' => $privilege_key
        );
        return $this->newAPIRequest('POST', '/email_deliveries/update', $data);
    }

    ////////////////////////////////////////////////////////////////////////


    /**
     * issue a new token from the given payment address
     * confirmed funds are sent first if they are available
     * @param  string $source             source address uuid or bitcoin address
     * @param  float  $quantity           quantity to issue
     * @param  string $asset              asset name to issue
     * @param  bool   $divisible          Whether the asset is divisible or not
     * @param  string $description        description attached to the issuance
     * @param  string $fee_rate           A fee rate to use. Accepts a pre-defined setting ("low","lowmed","medium","medhigh","high"), a number of blocks ("6 blocks"), or an exact number of satohis per byte ("75")
     * @param  string $fee_satoshis       An exact fee to use in satoshis
     * @return array                      An array with the issuance information, including `id`
     */
    public function createIssuance($source, $quantity, $asset, $divisible, $description='', $fee_rate=null, $fee_satoshis=null) {
        if ($fee_rate !== null AND $fee_satoshis !== null) {
            throw new Exception("Specify either fee_rate or fee_satoshis, but not both.", 1);
        }

        $body = [
            'quantity'    => $quantity,
            'asset'       => $asset,
            'divisible'   => $divisible,
            'description' => $description,
        ];
        if ($fee_rate !== null)     { $body['feeRate']     = $fee_rate; }
        if ($fee_satoshis !== null) { $body['feeSat']      = $fee_satoshis; }

        return $this->newAPIRequest('POST', '/issuance/'.$source, $body);
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
