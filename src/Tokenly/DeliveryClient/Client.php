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
                if ($json and isset($json['message'])) {
                    // throw an DeliveryException with the errorName
                    if (isset($json['errorName'])) {
                        $delivery_exception = new DeliveryException($json['message'], $code);
                        $delivery_exception->setErrorName($json['errorName']);
                        throw $delivery_exception;
                    }

                    // generic exception
                    throw new Exception($json['message'], $code);
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
