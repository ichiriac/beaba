<?php
namespace beaba\core;
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class Controller
{

    const GET       = 'GET';
    const POST      = 'POST';
    const DELETE    = 'DELETE';
    const PUT       = 'PUT';
    const ALL       = '*';
    const HTML      = 'html';
    const JSON      = 'json';
    const XML       = 'xml';
    const RSS       = 'rss';

    protected $app;
    protected $debug = true;
    /**
     * Initialize a new controller with the specified app
     * @param Application $app
     */
    public function __construct( Application $app )
    {
        $this->app = $app;
    }

    /**
     * Gets the view response
     * @return IView
     */
    public function getView()
    {
        return $this->app->getView();
    }

    /**
     * Retrieves a specific model mapper
     * @param string $name The model to be retrieved
     * @return IModel
     */
    public function getModel( $name )
    {
        return $this->app->getModel($name);
    }

    /**
     * Helper for getting a service
     * @param string $name The service name to retrieve
     * @return IService
     */
    public function getService( $name )
    {
        return $this->app->getService($name);
    }

    /**
     * Hook : before executing any action
     */
    protected function preAction( &$action, &$args ) { }
    /**
     * Hook : after executing the action
     */
    protected function postAction( $action, $args, &$result ) { }
    /**
     * Gets a configuration entry
     */
    public function getConfig( $key )
    {
        return $this->app->config->getConfig($key);
    }
    /**
     * Executes the specified action
     * @param string $action
     * @param array $params
     */
    public function execute( $action, $params )
    {
        $action = strtr(sanitize($action, true, true), '-', '_').'_action';
        if ( !is_callable( array( $this, $action ) ) ) {
            throw new Exception(
                'Undefined action : ' . $action, 501
            );
        }
        if ( $this->debug ) {
            $this->getService('logger')->debug(
                get_class($this).'::'.$action
                . ' -> '
                . print_r( $params, true )
            );
        }
        $this->preAction($action, $params);
        $result = $this->$action( $params );
        $this->postAction($action, $params, $result);
        return $result;
    }
}
