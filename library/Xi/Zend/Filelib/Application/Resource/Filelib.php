<?php

use Xi\Filelib\Configurator;

/**
 * Filelib initialization
 *
 * @author pekkis
 * @todo Some kind of initializer stuff for converting resources to init
 *
 */
class Xi_Zend_Filelib_Application_Resource_Filelib extends Zend_Application_Resource_ResourceAbstract
{

    /**
     * @var Xi\Filelib\FileLibrary
     */
    protected $_filelib;

    /**
     * Returns filelib
     *
     * @return Xi\Filelib\FileLibrary
     */
    public function getFilelib()
    {

        if (!$this->_filelib) {

            $options = $this->getOptions();

            // These are kludgings... rethink required

            if (isset($options['cache'])) {
                $this->getBootstrap()->bootstrap('cache');
                $cache = Zend_Registry::get('Emerald_CacheManager')->getCache($options['cache']);
                unset($options['cache']);
            } else {
                $cache = false;
            }

            // $aclOptions = $options['acl'];
            // unset($options['acl']);

            $storageOptions = $options['storage'];
            unset($options['storage']);

            $publisherOptions = $options['publisher'];
            unset($options['publisher']);

            if (!isset($publisherOptions['options'])) {
                $publisherOptions['options'] = array();
            }

            $backendOptions = $options['backend'];
            unset($options['backend']);

            $backendOptions = $this->_handleBackendOptions($backendOptions);

            $reflClass = new \ReflectionClass($backendOptions['type']);
            $backend = $reflClass->newInstanceArgs($backendOptions['arguments']);
            Configurator::setOptions($backend, $backendOptions['options']);

            $config = new Xi\Filelib\FileLibrary($options);
            if(isset($options['tempDir'])) {
                $config->setTempDir($options['tempDir']);
            }

            // $backend = new $backendOptions['type']($backendOptions['options']);
            $config->setBackend($backend);

            $storageOptions = $this->_handleStorageOptions($storageOptions);
            $storage = new $storageOptions['type']($storageOptions['options']);
            $config->setStorage($storage);

            $publisher = new $publisherOptions['type']($publisherOptions['options']);
            $config->setPublisher($publisher);

            if (!isset($options['profiles'])) {
                $options['profiles'] = array('default' => 'Default profile');
            }


            foreach ($options['profiles'] as $name => $poptions) {
                $linkerOptions = $poptions['linker'];
                unset($poptions['linker']);

                if ($linkerOptions['class'] == 'Xi\Filelib\Linker\BeautifurlLinker') {
                    $linker = new $linkerOptions['class']($config->getFolderOperator(), new Xi\Filelib\Tool\Slugifier\ZendSlugifier(new Xi\Filelib\Tool\Transliterator\IntlTransliterator()), $linkerOptions['options']);
                } else {
                    $linker = new $linkerOptions['class']($linkerOptions['options']);
                }

                $profile = new Xi\Filelib\File\FileProfile($poptions);
                $profile->setLinker($linker);

                $config->addProfile($profile);
            }

            if (isset($options['plugins'])) {
                foreach ($options['plugins'] as $plugin) {
                    // If no profiles are defined, use in all profiles.
                    if (!isset($plugin['profiles'])) {
                        $plugin['profiles'] = array_keys($config->file()->getProfiles());
                    }
                    $plugin = new $plugin['type']($plugin);
                    $config->addPlugin($plugin);
                }
            }

            /*
            if($cache) {
                $cacheAdapter = new \Emerald\Base\Cache\Adapter\ZendCacheAdapter();
                $cacheAdapter->setCache($cache);
                $config->setCache($cacheAdapter);
            }
            */

            $config->setAcl(new \Xi\Filelib\Acl\SimpleAcl());






            // $this->_filelib = new \Xi\Filelib\FileLibrary($config);

            $this->_filelib = $config;

        }

        return $this->_filelib;
    }

    /**
     * @return Xi\Filelib\FileLibrary
     */
    public function init()
    {
        $filelib = $this->getFilelib();
        $renderer = $this->getRenderer();

        Zend_Registry::set('Xi_Filelib', $filelib);
        Zend_Registry::set('Xi_Filelib_Renderer', $renderer);

        return $filelib;
    }


    public function getRenderer()
    {
        $renderer = new Xi\Filelib\Renderer\ZendRenderer($this->getFilelib());

        $options = $this->getOptions();
        $rendererOptions = $options['renderer'];

        $renderer->enableAcceleration((bool) $rendererOptions['enableAcceleration']);

        if (isset($rendererOptions['stripPrefixFromAcceleratedPath'])) {
            $renderer->setStripPrefixFromAcceleratedPath($rendererOptions['stripPrefixFromAcceleratedPath']);
        }

        if (isset($rendererOptions['addPrefixToAcceleratedPath'])) {
            $renderer->setAddPrefixToAcceleratedPath($rendererOptions['addPrefixToAcceleratedPath']);
        }

        return $renderer;

    }



    private function _handleStorageOptions($storageOptions)
    {
        if ($storageOptions['type'] == '\Xi\Filelib\Storage\GridfsStorage') {
            if (isset($storageOptions['options']['resource'])) {
                $storageOptions['options']['mongo'] = $this->getBootstrap()->bootstrap($storageOptions['options']['resource'])->getResource($storageOptions['options']['resource']);
                unset($storageOptions['resource']);
            }
        }

        if (isset($storageOptions['options']['directoryIdCalculator'])) {
            $directoryIdCalculator = new $storageOptions['options']['directoryIdCalculator']['type']($storageOptions['options']['directoryIdCalculator']['options']);
            $storageOptions['options']['directoryIdCalculator'] = $directoryIdCalculator;
        }

        return $storageOptions;


    }


    private function _handleBackendOptions($backendOptions)
    {
        if (!isset($backendOptions['arguments'])) {
            $backendOptions['arguments'] = array();
        }

        if (isset($backendOptions['options']['resource'])) {
            $backendOptions['arguments'][] = $this->getBootstrap()->bootstrap($backendOptions['options']['resource'])->getResource($backendOptions['options']['resource']);
            unset($backendOptions['options']['resource']);
        }

        return $backendOptions;
    }

}
