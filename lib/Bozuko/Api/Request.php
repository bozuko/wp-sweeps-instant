<?php

class Bozuko_Api_Request
{
    
    // request vars
    protected $path;
    protected $method;
    protected $url;
    protected $server;
    protected $port;
    protected $params = array();
    protected $apiKey;
    protected $token;
    
    protected $response;
    
    public function __construct( $config = array() )
    {
        foreach( array('path', 'method', 'params', 'token', 'apiKey', 'server', 'port') as $v ){
            if( isset( $config[$v] ) ) $this->$v = $config[$v];
        }
    }
    
    public function __call($name, $arguments)
    {
        foreach( array('path', 'method', 'params', 'token', 'apiKey', 'server', 'port') as $v ){
            $setMethod = 'set'.ucfirst($v);
            if( method_exists( $this, $setMethod ) ){
                call_user_func_array( array(&$this, $setMethod), $arguments );
                return $this;
            }
            if( $name == $setMethod ){
                $this->$v = $arguments[0];
                return $this;
            }
            
            $getMethod = 'get'.ucFirst($v);
            if( method_exists( $this, $setMethod ) ){
                return call_user_func_array( array(&$this, $setMethod), $arguments );
            }
            if( $name == $setMethod ){
                return $this->$v;
            }
            
        }
        throw new Exception('Method ['.$name.'] not found');
    }
    
    public function setServer( $server )
    {
        $server = preg_replace('/\/$/', '', trim( $server ) );
        if( strpos($server, 'http') !== 0 ) $server = 'https://'.$server;
        
        
        if( preg_match('/\:(\d+)$/', $server, $matches) ){
            $this->port = $matches[1];
            //$server = preg_replace('/\:\d+$/','',$server);
        }
        else{
            $this->port = false;
        }
        
        $this->server = $server;
        return $this;
    }
    
    public function run()
    {
        $url = $this->getUrl( $this->path );
        $params = $this->params;
        
        // populate our params with the token
        if( $this->token ){
            $params['token'] = $this->token;
        }
        if( $this->apiKey ){
            $params['api_key'] = $this->apiKey;
        }
        $params['mobile_version'] = 'html5-1.0';
        
        // check for get
        if( $this->method === 'GET' ){
            $url.=('?'.http_build_query($params));
        }
        
        $ch = curl_init();
        curl_setopt_array( $ch, array(
            CURLOPT_URL             => $url,
            CURLOPT_CUSTOMREQUEST   => $this->method,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 2
        ));
        
        if( $this->port ){
            curl_setopt( $ch, CURLOPT_PORT, $this->port );
        }
        
        if( $this->method !== 'GET' ){
            $json = json_encode( $params );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json))
            );
        }
        
        $result = curl_exec( $ch );
        $info = curl_getinfo($ch);
        
        curl_close( $ch );
        
        $this->response = new Bozuko_Api_Response($result, $info);
        return $this->response;
    }
    
    public function getResponse()
    {
        return $this->response;
    }
    
    public function getUrl( $path )
    {
        return $this->server.$path;
    }
    
}