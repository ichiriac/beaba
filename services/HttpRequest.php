<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
namespace beaba\services;
use \beaba\core\Service;


class HttpRequest extends Service
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
            $langs = $this->app->config->getConfig('infos');
            $langs = explode(',', $langs['langs']);
            $url = $this->getLocation();
            // gets the host extension
            $ext = strtolower(substr($_SERVER['HTTP_HOST'], -2));
            // handle the language
            $redirect = false;
            if ( strlen($url) === 3 && substr($url, -1) !== '/') {
                $url .= '/';
            }
            if (
                substr($url, 3, 1) === '/'
                && in_array(substr($url, 1, 2), $langs)
            ) {
                $this->lang = substr($url, 1, 2);
            } elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $this->lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 2);
                $this->lang = explode(';', $this->lang[0]);
                $this->lang = strtolower(substr($this->lang[0], 0, 2));
            } else {
                $this->lang = in_array($ext, $langs) ? $ext : DEFAULT_LANG;
            }
            if (!in_array($this->lang, $langs) ) {
                $url = '/'; // undefined lang
                $this->lang = DEFAULT_LANG;
            }
            // check if need redirection
            if (
                $ext !== $this->lang &&
                substr($url, 0, 4) !== '/'.$this->lang.'/'
            ) {
                $redirect = empty($_POST) && in_array('html', $this->getResponseType());
                $url = '/'.$this->lang.'/' . ltrim($url, '/');
                // sends header just for information purpose
                if ( !$redirect ) {
                    header('X-Need-Redirect: '. $url);
                }
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
                header('Status: 301 Moved Permanently', true, 301);
                exit(0);
            }
            // remove the language from the path for routing purpose
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
