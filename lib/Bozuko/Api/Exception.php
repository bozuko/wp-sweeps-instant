<?php

class Bozuko_Api_Exception extends Exception
{
    protected $request;
    
    public function __construct( $request )
    {
        $this->request = $request;
        parent::__construct( $this->getBozukoMessage() );
    }
    
    protected function getBozukoMessage()
    {
        return 'Bozuko Api Error: '.print_r(array(
            'path'      => $this->request->getPath(),
            'params'    => $this->request->getParams(),
            'method'    => $this->request->getMethod(),
            'result'    => $this->request->getResponse()->getResult(),
            'info'      => $this->request->getResponse()->getInfo(),
        ), 1);
    }
    
    
}
