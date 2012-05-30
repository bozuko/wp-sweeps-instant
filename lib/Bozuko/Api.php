<?php

require_once('Api/Request.php');
require_once('Api/Response.php');
require_once('Api/Exception.php');

class Bozuko_Api {
    
    protected $server;
    protected $token;
    protected $history;
    protected $api_key;
    protected $throwExceptions = true;
    protected $port;
    
    public function __construct( $server=null )
    {
        $this->setServer( $server );
    }
    
    public function setServer( $server )
    {
        $this->server = $server;
        return $this;
    }
    
    public function setApiKey( $api_key )
    {
        $this->api_key = $api_key;
        return $this;
    }
    
    public function setToken( $token )
    {
        $this->token = $token;
        return $this;
    }
    
    public function setThrowExceptions( $throw )
    {
        $this->throwExceptions = $throw;
    }
    
    public function call( $path, $method='GET', $params=array() )
    {
        $this->history[] = $request = new Bozuko_Api_Request(array(
            'server'        => $this->server,
            'token'         => $this->token,
            'apiKey'        => $this->api_key,
            'path'          => $path,
            'method'        => $method,
            'params'        => $params
        ));
        
        $response = $request->run();
        
        if( $this->throwExceptions && $response->isError() ){
            throw new Bozuko_Api_Exception( $response );
        }
        
        return $response->getData();
    }
    
}