<?php

use Xi\Filelib\FileLibrary;
use Xi\Filelib\Renderer\ZendRenderer;
use Xi\Filelib\File\File;

class Xi_Zend_Filelib_View_Helper_Filelib extends Zend_View_Helper_Abstract
{

    protected $defaultOptions = array(
        'version' => 'original',
        'download' => false,
    );
    
    
    /**
     * Returns filelib
     * 
     * @return FileLibrary
     */
    protected function getFilelib()
    {
        return Zend_Registry::get('Xi_Filelib');
    }

    
     /**
     * Returns filelib
     * 
     * @return ZendRenderer
     */
    protected function getRenderer()
    {
        return Zend_Registry::get('Xi_Filelib_Renderer');
    }

    
    /**
     * @return Zend_Controller_Router_Abstract
     */
    protected function getRouter()
    {
        return Zend_Controller_Front::getInstance()->getRouter();
    }
    
    
    protected function assertFile($file)
    {
        if (!$file instanceof File) {
            $file = $this->getFilelib()->getFileOperator()->find($file);
        }
        
        if (!$file) {
            throw new Zend_Exception('File not found');
        }

        return $file;
        
    }
    
    
    public function filelib()
    {
        return $this;
    }
    
    
    public function file($file, $options = array())
    {
        $file = $this->assertFile($file);
        
        $acl = $this->getFilelib()->getAcl();
        if ($acl->isFileReadableByAnonymous($file)) {
            return $this->url($file, $options);
        }
        
        return $this->render($file, $options);
        
    }
    
    public function url($file, $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);
        $file = $this->assertFile($file);
        return $this->getRenderer()->getUrl($file, $options);
    }
    
    
    public function render($file, $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);
        $file = $this->assertFile($file);
        
        $router = $this->getRouter();
        
        $options['id'] = $file->getId();
        
        return $router->assemble($options, 'xi_filelib_render', true, false);

    }
    
    
    
    
}
