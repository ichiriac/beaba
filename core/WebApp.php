<?php

namespace beaba\core;

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class WebApp extends Application
{
    /**
     * Prepare the view for rendering the specified error
     * @return IView
     */
    public function renderError( \Exception $error ){
        if ($error instanceof Exception) {
            $code = $error->getCode();
            $title = $error->getHttpMessage();
        } else {
            $code = 500;
            $title = 'Internal Error';
        }
        $this->getResponse()->setCode(
            $code, $title
        );
        switch($code) {
            case 401: $target = 'errors/unauth'; break;
            case 404: $target = 'errors/not-found'; break;
            default: $target = 'errors/internal'; break;
        }
        return $this->getView()
            ->setTitle($code . ' - ' . $title)
            ->setLayout('layouts/center')
            ->push(
                'content',
                $target,
                $error
            )
        ;
    }
    /**
     * Renders the HTML response
     * @param mixed $response
     * @return string
     */
    public function renderHtml($response)
    {
        $this->getResponse()->setHeader(
            'Content-Type', 'text/html'
        );
        if (is_null($response))
            $response = $this->getView();
        if ($response instanceof IView) {
            return $response->renderTemplate();
        } elseif (is_string($response)) {
            return $response;
        } elseif (
            isset($response['view'])
        ) {
            if (isset($response['template'])) {
                $this->getView()->setTemplate($response['template']);
            }
            $this->getView()->setLayout($response['view']);
            if (isset($response['placeholders'])) {
                $this->getView()->initLayout();
                foreach ($response['placeholders'] as $target => $widgets) {
                    foreach ($widgets as $id => $widget) {
                        if (is_numeric($id)) {
                            $this->getView()->push(
                                $target, empty($widget['render']) ?
                                    null : $widget['render'], isset($widget['data']) ?
                                    $widget['data'] : null
                            );
                        } else {
                            $this->getView()->attach(
                                $target, $id, empty($widget['render']) ?
                                    null : $widget['render'], isset($widget['data']) ?
                                    $widget['data'] : null
                            );
                        }
                    }
                }
            }
            return $this->getView()->renderTemplate();
        } else {
            throw new \Exception(
                'Unsupported response type', 400
            );
        }
    }

    /**
     * Serialize the result as a json
     * @param mixed $response
     * @return string
     */
    public function renderJson($response)
    {
        define('PROFILE_DISABLED', true);
        if ( !empty($_SERVER['HTTP_ORIGIN']) ) {
            // ALLOW CROSS DOMAIN JSON REQUESTS
            $this->getResponse()->setHeader(array(
                'Access-Control-Allow-Origin'   => '*',
                'Access-Control-Allow-Methods'  => 'POST, GET, OPTIONS',
                'Access-Control-Allow-Headers'  => '*',
                'Access-Control-Max-Age'        => 1000
            ));
        }
        if ( $this->getRequest()->hasParameter('callback') ) {
            // RESPOND IN JSONP
            $this->getResponse()->setHeader(array(
                'Content-Type'  => 'application/script',
                'Pragma'        => 'no-cache',
                'Cache-Control' => 'no-cache, must-revalidate'
            ));
            return
                 $this->getRequest()->getParameter('callback')
                 . '('
                 . json_encode($response)
                 . ');'
            ;
        } else {
            // CLASSIC RESPONSE
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            return json_encode($response);
        }
    }

    /**
     * Serialize the result as a xml
     * @param mixed $response
     * @return string
     */
    public function renderXml($response)
    {
        define('PROFILE_DISABLED', true);
        $this->getResponse()->setHeader(
            'Content-Type', 'text/xml'
        );
        if (is_string($response)) {
            return '<response><![CDATA[' . $response . ']]></response>';
        } elseif (is_array($response)) {
            $return = '<response>';
            foreach ($response as $key => $value) {
                $return .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            return $return . '</response>';
        } else {
            return '<response />';
        }
    }

    /**
     * Executes the response callbacks and returns it's result
     * @param string $response
     * @param string $method
     * @param string $format
     * @param array $args
     * @return mixed
     */
    public function processResponse($response, $method, &$format, array $args = null)
    {
        // EXECUTING THE RESPONSE
        if (!is_string($response)) {
            if (is_array($response)) {
                // execute the rest method
                if (isset($response[$method])) {
                    $out = $response[$method];
                } elseif (isset($response['*'])) {
                    $out = $response['*'];
                } else {
                    $format = array_shift($format);
                    throw new http\BadMethod(
                        $this, array_keys($response)
                    );
                }
                // handle a callback
                if (is_callable($out)) {
                    $out = $out($this, $args);
                }
                if (is_array($out) && !isset($out['view'])) {
                    if ( !is_array($format) ) $format = array($format);
                    $outFormats = array_keys($out);
                    // handle the response type
                    foreach( $format as $type ) {
                        if ( $type === '*' ) {
                            $format = $outFormats[0];
                            $out = $out[$format];
                            break;
                        } elseif(isset($out[$type]) ) {
                            $format = $type;
                            $out = $out[$format];
                            break;
                        }
                    }
                    // matching not found
                    if ( is_array($format)) {
                        $format = array_shift($format);
                        throw new http\BadFormat(
                            $this, $outFormats
                        );
                    }
                } else {
                    $format = array_shift($format);
                }
                if (is_callable($out)) {
                    $response = $out($this, $args);
                } else {
                    $response = $out;
                }
            } else {
                $format = array_shift($format);
            }
        } else {
            $format = array_shift($format);
        }
        return $response;
    }

    /**
     * Comparing format types
     */
    public function isFormat( $format, $compare ) {
        $compare = strtolower($compare);
        $format = strtolower($format);
        if ( $format === $compare ) return true;
        if ( substr($format, -strlen($compare)) === $compare) return true;
        if ( $compare === 'rss' ) {
            return (
                $format === 'application/rss+xml'
                || $format === 'application/rdf+xml'
                || $format === 'application/atom+xml'
            );
        }
        return false;
    }
    /**
     * Renders the response to the specified format
     * @param mixed $response
     * @param string $format
     * @return string
     */
    public function renderResponse($response, $format)
    {
        // renders the template
        switch ($format) {
            case 'html':
            case 'text/html':
                defined('PROFILE_ENABLED') or define('PROFILE_ENABLED', true);
                return $this->renderHtml($response);
            case 'json':
            case 'application/json':
                return $this->renderJson($response);
            case 'xml':
            case 'text/xml':
            case 'application/xml':
                return $this->renderXml($response);
            case 'rss':
            case 'application/rss':
            case 'application/rss+xml':
            case 'application/rdf+xml':
            case 'application/atom+xml':
                return $this->renderRss($response);
            default:
                if ( strpos( $format, '/' ) === false ) {
                    $formats = $this->getRequest()->getResponseType();
                    if ( $offset = array_search($format, $formats) ) {
                        $format = $formats[ $offset - 1];
                    }
                }
                if ( strpos( $format, '/' ) !== false ) {
                    $this->getResponse()->setHeader(
                        'Content-Type', $format
                    );
                    return $response;
                }
                throw new http\BadFormat(
                    $this, array('html', 'json', 'xml', 'rss')
                );
        }
    }

    /**
     * Dispatching the specified request
     * @param string $url
     * @param array $params
     */
    public function dispatch(
        $method = null, $url = null,
        array $params = null, $format = null
    )
    {
        $params = $this->_loadParameters($params);
        if (is_null($method))
            $method = $this->getRequest()->getMethod();
        if (is_null($format))
            $format = $this->getRequest()->getResponseType();
        try {
            $response = parent::dispatch($method, $url, $params);
            $response = $this->renderResponse(
                $this->processResponse($response, $method, $format, $params)
                , $format
            );
        } catch (\Exception $ex) {
            if ( is_array($format) ) {
                $format = array_shift($format);
            }
            $this->getResponse()->setHeader(
                'X-Format', $format
            );
            // general exception catch
            if ($ex instanceof Exception && !$ex->isHttpError()) {
                // @todo decide what to show ?
                $response = null;
                $this->getResponse()->setCode(
                    $ex->getCode(), $ex->getHttpMessage()
                );
                if ($ex instanceof http\Redirect) {
                    if ( $this->isFormat($format, 'json') ) {
                        $this->getResponse()->setCode(
                            200, 'OK'
                        );
                        $response = $this->renderResponse(
                            array(
                            'redirect' => $ex->getUrl()
                            ), $format
                        );
                    } else {
                        $this->getResponse()->setHeader(
                            'Location', $ex->getUrl()
                        );
                    }
                }
            } else {
                trigger_error(
                    $ex->getMessage() . "\n" .
                    $ex->getFile() . ':' . $ex->getLine(),
                    E_USER_WARNING
                );
                header('X-Reason: ' . $ex->getMessage() );
                $response = $this->renderResponse(
                    $this->renderError($ex), $format
                );
            }
        }
        // flush the view instance
        return $response;
    }

}
