<?php

namespace beaba\core;
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */

/**
 * The application structure class
 */
abstract class Application
{

    /**
     * @var array List of services configuration
     */
    protected $services;

    /**
     * @var array List of services instances
     */
    protected $instances = array();

    /**
     * The current configuration instance
     * @var Configuration
     */
    public $config;

    /**
     * Initialize the application
     */
    public function __construct(array $config = null)
    {
        // initialize the configuration layer
        $this->config = new Configuration($this, $config);
    }

    /**
     * Gets a service instance
     * @ProfileGroup("service")
     * @ProfileCaption("#1")
     * @param string $name
     * @return IService
     */
    public function getService($name)
    {
        if (!isset($this->instances[$name])) {
            if (!$this->services) {
                $this->services = $this->config->getConfig('services');
            }
            if (!isset($this->services[$name])) {
                throw new \Exception(
                    'Undefined service : ' . $name
                );
            }
            $service = $this->services[$name];
            $options = array();
            if ( is_string( $service ) ) {
                $class = $service;
            } elseif ( is_array( $service ) ) {
                if ( empty($service['class']) ) {
                    throw new \Exception(
                        'Undefined service class offset : ' . $name
                    );
                }
                $class = $service['class'];
                if ( isset($service['options']) ) {
                    $options = $service['options'];
                }
            } else {
                throw new \Exception(
                    'Bad configuration entry for service : ' . $name
                );
            }
            $this->instances[$name] = new $class(
                $this, $options
            );
        }
        return $this->instances[$name];
    }


    /**
     * Gets the informations layer
     * @return IInfos
     */
    public function getInfos()
    {
        return $this->getService('infos');
    }

    /**
     * Gets the asset manager
     * @return IAssets
     */
    public function getAssets()
    {
        return $this->getService('assets');
    }

    /**
     * Gets the response handler
     * @return IResponse
     */
    public function getResponse()
    {
        return $this->getService('response');
    }

    /**
     * Gets the view manager
     * @return IView
     */
    public function getView()
    {
        return $this->getService('view');
    }

    /**
     * Gets the current request
     * @return IRequest
     */
    public function getRequest()
    {
        return $this->getService('request');
    }

    /**
     * Gets the session instance
     * @return ISession
     */
    public function getSession()
    {
        return $this->getService('session');
    }
    /**
     * Gets a url from the specified route
     * @param string $route
     * @param array $args
     */
    public function getUrl($route, array $args = null)
    {
        return
            $this->getRequest()->getBaseDir() .
            $this->getService('router')->getUrl($route, $args)
        ;
    }

    /**
     * Execute the specified action controller
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return string
     */
    public function execute($controller, $action, $params)
    {
        $instance = new $controller($this);
        return $instance->execute($action, $params);
    }

    /**
     * Retrieve a list of current request parameters
     * @param array $params
     * @return array
     */
    protected function _loadParameters(array $params = null)
    {
        return $params ?
            merge_array(
                $this->getRequest()->getParameters(), $params
            ) :
            $this->getRequest()->getParameters()
        ;
    }
    /**
     * Dispatching the specified request
     * @ProfileGroup("dispatch")
     * @param string $url
     * @param array $params
     * @throws \Exception
     */
    public function dispatch($method = null, $url = null, array $params = null)
    {
        if (!is_callable($url)) {
            // initialize parameters
            if (!is_null($url)) {
                $this->getRequest()->setLocation($url);
            } else {
                $url = $this->getRequest()->getLocation();
            }
            $route = $this->getService('router')->getRoute($url);
        } else {
            $route = $url;
        }
        if ($route === false) {
            throw new Exception('No route found', 404);
        } else {
            if ( is_array($route)) {
                if ( !empty($route['params']) ) {
                    $params = merge_array( $params, $route['params']);
                }
                if ( !empty($route['route']) ) {
                    $route = $route['route'];
                }
            }
            if (is_string($route)) {
                // execute a controller
                $parts = explode('::', $route, 2);
                if (empty($parts[1]))
                    $parts[1] = 'index';
                return $this->execute($parts[0], $parts[1], $params);
            } elseif( is_callable($route)) {
                // use the route as a callback
                return call_user_func_array($route, array($this, $params));
            } else {
                throw new Exception('Bad route format', 500);
            }
        }
    }

}

/**
 * The service interface
 */
interface IService
{

    /**
     * Gets the current application
     * @return Application
     */
    function getApplication();
}


/**
 * Inner service class (automatically loaded)
 */
class Service implements IService
{

    /**
     * The current application instance
     * @var Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $options;
    /**
     * The current application instance
     * @param Application $app
     * @param array $options
     */
    final public function __construct(Application $app, array $options = null)
    {
        $this->app = $app;
        $this->options = is_null($options) ? array() : $options;
        $this->onStart();
    }
    /**
     * Gets the current application
     * @return Application
     */
    final public function getApplication()
    {
        return $this->app;
    }
    /**
     * On Start hook
     */
    protected function onStart() { }
}
/**
 * The session service (avoid using $_SESSION to really have a portable way ta
 * manage sessions - without necessarily using the PHP internals)
 *
 * Options :
 *  - name      : The session name
 *  - driver    : The cache driver name
 */
interface ISession extends IService
{
    /**
     * Check if the request contains a session data
     * @return boolean
     */
    function isDefined();
    /**
     * Check if the session is actually started
     * @return boolean
     */
    function isStarted();
    /**
     * Retrieves an item value from its name. If no session is defined, this
     * function will simply return *NULL*, without starting a session. If the
     * session is not started, it will be started automatically
     * @param string $name
     * @return mixed
     * @throws OutOfBoundsException Occurs if the item is not defined
     */
    function getItem($name);
    /**
     * Stores a value attached to the specified item. If no session is defined,
     * will create a session. If the session is not started, it will be started
     * automatically
     * @param string $name
     * @param mixed $value
     * @return ISession
     */
    function setItem($name, $value);
    /**
     * Check if the specified item is defined or not. If no session is defined,
     * this function will simply return *FALSE*, without starting a session
     * @param string $name The item name to be verified
     * @return boolean
     */
    function hasItem($name);
    /**
     * Removes the specified item
     * @param string $name The item to remove
     * @return ISession
     * @throws OutOfBoundsException Occurs if the item is not defined
     * @throws LogicException Occurs if no session was defined
     */
    function removeItem($name);
}

/**
 * Defines the requesting service
 */
interface IRequest extends IService
{

    /**
     * Gets the requested method type
     * @see GET, POST, PUT, DELETE ...
     * @return string
     */
    public function getMethod();

    /**
     * Gets the requested unique ressource location
     * @see /index.html
     * @return string
     */
    public function getLocation();

    /**
     * Gets the request base dir (to build requests)
     * @return string
     */
    public function getBaseDir();

    /**
     * Sets the requested unique ressource location
     * @params string $url
     * @return IRequest
     */
    public function setLocation($url);

    /**
     * Gets the response type : html, xml, json ...
     * @return string
     */
    public function getResponseType();

    /**
     * Gets the list of requested parameters
     * @return array
     */
    public function getParameters();

    /**
     * Gets the specified parameter
     * @return mixed
     */
    public function getParameter($name);

    /**
     * Check if the specified parameter is defined
     * @return boolean
     */
    public function hasParameter($name);
}

/**
 * The informations layer
 */
interface IInfos extends IService
{

    /**
     * Check if the specified configuration key is defined
     * @param string $key
     * @return boolean
     */
    public function hasConfig($key);

    /**
     * Gets the configuration entry
     * @param string $key
     * @return mixed
     */
    public function getConfig($key);

    /**
     * Sets the specified configuration entry
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setConfig($key, $value);

    /**
     * Gets the application name
     * @return string
     */
    public function getName();

    /**
     * Sets the current application name
     * @param string $value
     */
    public function setName($value);

    /**
     * Gets the page title
     * @return string
     */
    public function getTitle();

    /**
     * Sets the current page title
     * @param string $value
     */
    public function setTitle($value);

    /**
     * Gets the page description
     * @return string
     */
    public function getDescription();

    /**
     * Sets the current page description
     * @param string $value
     */
    public function setDescription($value);

    /**
     * Gets the page template
     * @return string
     */
    public function getTemplate();

    /**
     * Sets the current page template
     * @param string $value
     */
    public function setTemplate($value);

    /**
     * Gets the page layout
     * @return string
     */
    public function getLayout();

    /**
     * Sets the current page layout
     * @param string $value
     */
    public function setLayout($value);
}

/**
 * The router interface
 */
interface IRouter extends IService
{

    /**
     * Retrieves a list of routes from the configuration
     * @return array
     */
    public function getRoutes();

    /**
     * Gets the requested route
     * @param string $url
     * @return string
     */
    public function getRoute($url);

    /**
     * Gets a url from the specified route
     * @param string $route
     * @param array $args
     */
    public function getUrl($route, array $args = null);
}

/**
 * The view interface
 */
interface IView extends IService
{
    /**
     * Sets the view (page) title
     * @return IView
     */
    public function setTitle($title);
    /**
     * Gets the view (page) title
     * @return string
     */
    public function getTitle();
    /**
     * Sets the main layout
     * @return IView
     */
    public function setLayout($file);

    /**
     * Initialize the layout data
     * @return IView
     */
    public function initLayout();

    /**
     * Sets the templating file
     * @return IView
     */
    public function setTemplate($file);

    /**
     * Adds the specified data to the end of the specified
     * zone (using the specified file for the rendering)
     * @return IView
     */
    public function push($zone, $file, $datasource = null);

    /**
     * Attaching a widget data
     * @param string $zone
     * @param string $widget
     * @param string|callback $render
     * @param array|callback $datasource
     * @return IView
     */
    public function attach(
    $zone, $widget, $render = null, $datasource = null
    );

    /**
     * Adds the specified data to the top of the specified
     * zone (using the specified file for the rendering)
     * @return IView
     */
    public function insert($zone, $file, $datasource = null);

    /**
     * Renders the specified file
     * @return string
     */
    public function render($file, $datasource = null);

    /**
     * Renders the current template
     * @return string
     */
    public function renderTemplate();

    /**
     * Renders the current layout
     * @return string
     */
    public function renderLayout();

    /**
     * Renders the current layout
     * @return string
     */
    public function renderPlaceholder($zone);
}

/**
 * The assets manager structure
 */
interface IAssets extends IService
{

    /**
     * Check if the specified package is defined
     * @param string $package
     * @return boolean
     */
    public function hasConfig($package);

    /**
     * Gets the specified package configuration
     * @param string $package
     * @return array
     * @throws Exception
     */
    public function getConfig($package);

    /**
     * Attach a package to the current app
     * @param string $package
     * @return void
     */
    public function attach($package);

    /**
     * Remove the package usage
     * @param string $package
     * @return void
     */
    public function detach($package);

    /**
     * Add the specified external js file
     * @param string $file
     * @return IAssets
     */
    public function useJs( $file );

    /**
     * Adds the specified css file
     * @param string $file
     * @return IAssets
     */
    public function useCss( $file );

    /**
     * Retrieves the list of css includes
     * @return array
     */
    public function getCss();

    /**
     * Gets a list of JS links
     * @return array
     */
    public function getJs();
}

/**
 * Services interfaces
 */
interface IResponse extends IService
{

    /**
     * Sets the response code
     * @param string $code
     * @param string $message
     * @return IResponse
     */
    public function setCode($code, $message);

    /**
     * Sets the response header
     * @param string|array $attribute
     * @param string $value
     * @return IResponse
     */
    public function setHeader($attribute, $value = null);

    /**
     * Write a new line with the specified message
     * @param string $message
     * @return IResponse
     */
    public function writeLine($message);

    /**
     * Outputs the specified contents
     * @param string $message
     * @return IResponse
     */
    public function write($message);
}