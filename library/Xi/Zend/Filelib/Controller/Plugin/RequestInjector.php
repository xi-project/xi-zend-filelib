<?php

namespace Xi\Zend\Filelib\Controller\Plugin;

class RequestInjector extends \Zend_Controller_Plugin_Abstract
{
    
     public function routeShutdown(\Zend_Controller_Request_Abstract $request)
     {
         $renderer = \Zend_Registry::get('Xi_Filelib_Renderer');
         $renderer->setRequest($request);
     }
    
    
    
    
}