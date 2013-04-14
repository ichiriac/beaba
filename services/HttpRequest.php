<?php
namespace beaba\core\services;
use \beaba\core;

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class HttpRequest extends core\Service
{
    /**
     * @var string
     */
    protected $_location;

    protected $lang;

    protected $_response;

    /**
     * Gets the requested method type
     * @see GET, POST, PUT, DELETE ...
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Change the current url location
     * @param string $url
     */
    public function setLocation( $url )
    {
        $this->_location = $url;
        return $this;
    }

    /**
     * Gets the requested unique ressource location
     * @see /index.html
     * @return string
     */
    public function getLocation()
    {
        if ( !$this->_location ) {
            $base_dir = $this->getBaseDir();
            $query = strpos($_SERVER['REQUEST_URI'], '?');
            $this->_location = substr(
                $_SERVER['REQUEST_URI'], strlen($base_dir),
                $query !== false ?
                    $query - strlen($base_dir) : strlen($_SERVER['REQUEST_URI'])
            );
        }
        return $this->_location;
    }

    /**
     * Chose the language from the request
     */
    public function getLang( $default = DEFAULT_LANG ) {
        if ( !$this->lang ) {
            $langs = $this->_app->config->getConfig('infos');
            $langs = explode(',', $langs['langs']);
            $url = $this->getLocation();
            // handle the language
            if ( in_array('html', $this->getResponseType()) ) {
                $redirect = false;
                $host = explode('.', $_SERVER['HTTP_HOST']);
                $this->lang = array_pop($host);
                if ( strlen($this->lang) !== 2 ) {
                    if ( strlen($url) === 3 ) {
                        $url .= '/';
                    }
                    if ( substr($url, 3, 1) === '/' ) {
                        $this->lang = substr($url, 1, 2);
                    } else {
                        $this->lang = DEFAULT_LANG;
                    }
                }
                if (!in_array($this->lang, $langs) ) {
                    $url = '/'; // undefined lang
                    $this->lang = DEFAULT_LANG;
                }
                if ( substr($url, 0, 4) !== '/'.$this->lang.'/' ) {
                    $redirect = empty($_POST);
                    $url = '/'.$this->lang.'/' . ltrim($url, '/');
                }
                // remove the 'www.' from the host
                if ( $redirect || substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') {
                    if ( !empty($_GET) ) {
                        $url .= '?' . http_build_query($_GET);
                    }
                    header('HTTP/1.0 301 Moved Permanently', true, 301);
                    if ( substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.' ) {
                        header('Location: http://' . substr($_SERVER['HTTP_HOST'], 4) . $url);
                    } else {
                        header('Location: ' . $url);
                    }
                    header('Status: 301 Moved Permanently');
                    exit(0);
                }
            } else {
                $this->lang = DEFAULT_LANG;
            }
            if ( substr($url, 0, 4) === '/'.$this->lang.'/' ) {
                $url = substr($url, 3);
            }
            $this->_location = $url;
        }
        return $this->lang;
    }

    /**
     * Gets the request base dir (to build requests)
     * @return type
     */
    public function getBaseDir() {
        return substr(
            $_SERVER['SCRIPT_NAME'], 0,
            strrpos($_SERVER['SCRIPT_NAME'], '/')
        );
    }
    /**
     * Gets the response type : html, xml, json ...
     * @return array
     */
    public function getResponseType()
    {
        if ( !$this->_response ) {
            $this->_response = array();
            if ( empty($_SERVER['HTTP_ACCEPT']) ) $_SERVER['HTTP_ACCEPT'] = '*/*';
            $accept = explode(',', $_SERVER['HTTP_ACCEPT']);
            foreach( $accept as $format ) {
                $format = explode(';', $format, 2);
                $this->_response[] = strtolower($format[0]);
                $format = explode('/', $format[0], 2);
                if ( !empty($format[1]) ) {
                    $this->_response[] = strtolower($format[1]);
                } else {
                    $this->_response[] = strtolower($format[0]);
                }
            }
            if ( empty($this->_response) ) {
                $this->_response[] = '*';
            }
        }
        return $this->_response;
    }

    /**
     * Gets the list of requested parameters
     * @return array
     */
    public function getParameters()
    {
        return $_REQUEST;
    }

    /**
     * Gets the specified parameter
     * @return mixed
     */
    public function getParameter($name)
    {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        } else {
            return null;
        }
    }

    /**
     * Check if the specified parameter is defined
     * @return boolean
     */
    public function hasParameter($name)
    {
        return isset($_REQUEST[$name]);
    }

}
