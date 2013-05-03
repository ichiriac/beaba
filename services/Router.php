<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
namespace beaba\services;

use \beaba\core\Service;
use \beaba\core\IRouter;

class Router extends Service implements IRouter
{

    /**
     * List of routing configuration
     * @var array
     */
    protected $_routes;

    /**
     * Retrieves a list of routes from the configuration
     * @return array
     */
    public function getRoutes()
    {
        if (!$this->_routes) {
            $this->_routes = $this->app->config->getConfig('routes', true);
        }
        return $this->_routes;
    }

    /**
     * Gets a url from the specified route
     * @param string $route
     * @param array $args
     * @todo finish to implement this function
     */
    public function getUrl($route, array $args = null)
    {
        $routes = $this->getRoutes();
        if (!isset($routes[$route])) {
            throw new \OutOfBoundsException(
                'Undefined route : ' . $route
            );
        }
        $check = $routes[$route]['check'];
        switch ($check[0]) {
            case 'equals':
                if (is_array($check[1])) {
                    return array_shift($check[1]);
                } else {
                    return $check[1];
                }
                break;
        }
    }

    /**
     * Gets the requested route
     * @param string $url
     * @return string
     */
    public function getRoute($url)
    {
        foreach ($this->getRoutes() as $route) {
            if ($this->_isMatch($url, $route['check'])) {
                if (!empty($route['callback'])) {
                    return $route['callback'];
                } else {
                    if (is_string($route['route'])) {
                        return $route['route'];
                    } else {
                        $route = $route['route']($url, $this->app);
                        if ($route !== false) {
                            return $route;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if the specified route match or not
     * @param string $url
     * @param mixed $check
     * @return boolean
     */
    protected function _isMatch($url, $check)
    {
        switch ($check[0]) {
            case 'equals':
                if (is_array($check[1])) {
                    return in_array($url, $check[1]);
                } else {
                    return ($url === $check[1]);
                }
                break;
            case 'ends':
                if ( is_array($check[1]) ) {
                    foreach($check[1] as $pattern) {
                        if ( substr($url, -strlen($pattern)) === $pattern) return true;
                    }
                    return false;
                } else {
                    return substr($url, -strlen($check[1])) === $check[1];
                }
                break;
            case 'path':
                $times = substr_count($url, '/');
                if (!empty($check[2])) {
                    return $times >= $check[1] && $times <= $check[2];
                } else {
                    if (is_array($check[1])) {
                        return in_array($times, $check[1]);
                    } else {
                        return $times === $check[1];
                    }
                }
                break;
            case 'starts':
                $len = strlen($check[1]);
                if ( $len > strlen($url) ) return false;
                return substr_compare($url, $check[1], 0, $len, false) === 0;
                break;
            default:
                throw new \Exception(
                    'Bad check method : ' . $check[0]
                );
        }
    }

}
