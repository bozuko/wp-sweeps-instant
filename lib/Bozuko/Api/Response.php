<?php

class Bozuko_Api_Response
{
    protected $result;
    protected $info;
    protected $data;
    protected $error;
    
    public function __construct($result, $info)
    {
        $this->result = $result;
        $this->info = $info;
        $this->parseResponse();
    }
    
    protected function parseResponse()
    {
        if( $this->result ){
            $this->data = json_decode( $this->result );
        }
        if( $this->info['http_code'] != 200 ){
            $this->error = true;
        }
    }
    
    public function isError()
    {
        return $this->error;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getResult()
    {
        return $this->result;
    }
    
    public function getInfo()
    {
        return $this->info;
    }
}