<?php
namespace beaba\core;
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class Configuration
{

    /**
     * @var array List of merged configuration
     */
    protected $_config = array();

    /**
     * @var array Website injected configuration
     */
    protected $_local = array();

    /**
     * @var array The core configuration
     */
    protected $_core = array();

    /**
     * @var array The application configuration
     */
    protected $_app = array();

    /**
     * @var array Configuration from each plugin
     */
    protected $_plugins = array();

    /**
     * @var Application The parent application instance
     */
    protected $_parent;

    /**
     * Initialize a configuration layer with specified data
     * @param array $config
     */
    public function __construct( Application $parent, array $config = null)
    {
        $this->_parent = $parent;
        if (is_array($config)) {
            $this->_local = $config;
        }
    }

    /**
     * Gets the merged configuration from different sources
     * @param string $key
     * @param boolean $prepend
     * @param boolean $include_plugins
     * @return array
     * @ProfileGroup("config")
     * @ProfileCaption("#1")
     */
    public function getConfig($key, $prepend = false, $include_plugins = false)
    {
        if (!isset($this->_config[$key])) {
            $this->_config[$key] = merge_array(
                $include_plugins ?
                merge_array(
                    $this->getCoreConfig($key),
                    $this->getPluginsConfig($key),
                    $prepend
                ) :
                $this->getCoreConfig($key),
                merge_array(
                    $this->getAppConfig($key),
                    $this->getLocalConfig($key),
                    $prepend
                ),
                $prepend
            );
        }
        return $this->_config[$key];
    }

    /**
     * Reads a configuration from a callback function
     * @param string $prefix
     * @param string $key
     * @return array
     */
    protected function _readCallbackConf($prefix, $key)
    {
        $callback = 'config\\' . $prefix . '_' . strtr($key, '/.', '__');
        return function_exists($callback) ?
            $callback() : false
        ;
    }

    /**
     *
     * @param type $path
     * @param type $key
     * @return type
     */
    protected function _readFileConf($path, $key)
    {
        $target = $path . '/config/' . $key . '.php';
        return file_exists($target) ?
            include( $target ) : array()
        ;
    }

    /**
     * Gets the local website configuration
     * @param string $key
     * @return array
     */
    public function getLocalConfig($key)
    {
        if (!isset($this->_local[$key])) {
            $this->_local[$key] = (
                $data = $this->_readCallbackConf('local', $key)
            ) ? $data : $this->_readFileConf('.', $key);
        }
        return $this->_local[$key];
    }

    /**
     * Gets the core configuration
     * @param string $key
     * @return array
     */
    public function getCoreConfig($key)
    {
        if (!isset($this->_core[$key])) {
            $this->_core[$key] = (
                BEABA_BUILD_CORE &&
                $data = $this->_readCallbackConf('core', $key)
            ) ? $data : $this->_readFileConf(BEABA_PATH, $key);
        }
        return $this->_core[$key];
    }

    /**
     * Gets the plugins configuration (only enabled ones)
     * @param string $key
     * @return array
     */
    public function getPluginsConfig( $key ) {
        if (!isset($this->_plugins[$key])) {
            if (
                BEABA_BUILD_CORE &&
                $data = $this->_readCallbackConf('plugins', $key)
            ) {
                $this->_plugins[$key] = $data;
            } else {
                $data = array();
                $plugins = $this->getConfig('plugins');
                foreach( $plugins as $name => $conf ) {
                    if ( !empty($conf['enabled']) ) {
                        $data = merge_array(
                            $data,
                            $this->_readFileConf(
                                BEABA_PLUGIN . '/' . $name , $key
                            )
                        );
                    }
                }
                $this->_plugins[$key] = $data;
            }
        }
        return $this->_plugins[$key];
    }

    /**
     * Gets the application configuration
     * @param string $key
     * @return array
     */
    public function getAppConfig($key)
    {
        if (!isset($this->_app[$key])) {
            $this->_app[$key] = (
                BEABA_BUILD_APP &&
                $data = $this->_readCallbackConf('app_' . APP_NAME , $key)
            ) ? $data : $this->_readFileConf( APP_PATH , $key);
        }
        return $this->_app[$key];
    }
}