<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
namespace beaba\services;
use \beaba\core\Service;
use \beaba\core\IInfos;

class Infos  extends Service implements IInfos
{
    protected $_config;
    /**
     * Check if the specified configuration key is defined
     * @param string $key
     * @return boolean
     */
    public function hasConfig( $key )
    {
        if ( !$this->_config ) {
            $this->_config =  $this->app->config->getConfig( 'infos' );
        }
        return isset( $this->_config[ $key ] );
    }
    /**
     * Requires the specified configuration key
     * @param string $key
     * @throws \OutOfRangeException
     */
    protected function requireConfig( $key )
    {
        if ( !$this->hasConfig( $key ) ) {
            throw new \OutOfRangeException(
              'Undefined infos : ' . $key
            );
        }
    }
    /**
     * Gets the configuration entry
     * @param string $key
     * @return mixed
     */
    public function getConfig( $key )
    {
        $this->requireConfig($key);
        return $this->_config[ $key ];
    }
    /**
     * Sets the specified configuration entry
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setConfig( $key, $value )
    {
        if ( !$this->_config ) {
            $this->_config = $this->app->config->getConfig( 'infos' ) ;
        }
        if (
           is_array($value)
           && isset($this->_config[$key])
           && is_array($this->_config[$key])
        ) {
            $this->_config[ $key ] = merge_array($this->_config[ $key ], $value);
        } else {
            $this->_config[$key] = $value;
        }
    }
    /**
     * Gets the application name
     * @return string
     */
    public function getName()
    {
        return $this->getConfig( 'name' );
    }
    /**
     * Sets the current application name
     * @param string $value
     */
    public function setName( $value )
    {
        $this->setConfig('name', $value);
    }
    /**
     * Gets the page title
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfig( 'title' );
    }
    /**
     * Sets the current page title
     * @param string $value
     */
    public function setTitle( $value )
    {
        $this->setConfig('title', $value);
    }
    /**
     * Gets the page description
     * @return string
     */
    public function getDescription()
    {
        return $this->getConfig( 'description' );
    }
    /**
     * Sets the current page description
     * @param string $value
     */
    public function setDescription( $value )
    {
        $this->setConfig('description', $value);
    }
    /**
     * Gets the page template
     * @return string
     */
    public function getTemplate()
    {
        return $this->getConfig( 'template' );
    }
    /**
     * Sets the current page template
     * @param string $value
     */
    public function setTemplate( $value )
    {
        $this->setConfig('template', $value);
    }
    /**
     * Gets the page layout
     * @return string
     */
    public function getLayout()
    {
        return $this->getConfig( 'layout' );
    }
    /**
     * Sets the current page layout
     * @param string $value
     */
    public function setLayout( $value )
    {
        $this->setConfig('layout', $value);
    }
}
