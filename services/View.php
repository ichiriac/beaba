<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
namespace beaba\services;

use \beaba\core\Service;
use \beaba\core\IView;

class View extends Service implements IView
{

    protected $_defaults;
    protected $_template;
    protected $_layout;
    protected $_placeholders = array();
    protected $_renderers = array();
    protected $_flagInit = false;

    /**
     * Handle the assets loading
     */
    protected function onStart()
    {
        parent::onStart();
        foreach ($this->app->getInfos()->getConfig('assets') as $asset) {
            $this->app->getAssets()->attach($asset);
        }
    }

    /**
     * Sets the main layout
     */
    public function setLayout($file)
    {
        $this->_flagInit = false;
        $this->_layout = $file;
        return $this;
    }

    /**
     * Sets the templating file
     */
    public function setTemplate($file)
    {
        $this->_flagInit = false;
        $this->_template = $file;
        return $this;
    }

    /**
     * Adds the specified data to the end of the specified
     * zone (using the specified file for the rendering)
     */
    public function push($zone, $file, $datasource = null)
    {
        if (!isset($this->_placeholders[$zone])) {
            $this->_placeholders[$zone] = array();
        }
        $this->_placeholders[$zone][] = array(
            $file, &$datasource
        );
        return $this;
    }

    /**
     * Components helper
     */
    public function __call( $function, $args )
    {
        if ( substr($function, 0, 3) === 'add' ) {
            return $this->push(
                !empty($args[1]) ? $args[1] : 'top',
                'components/' . strtolower(substr(strtr($function, '_', '/'), 3)),
                !empty($args[0]) ? $args[0]: array()
            );
        } elseif ( substr($function, 0, 4) === 'push' ) {
            return $this->push(
                !empty($args[1]) ? $args[1] : 'top',
                'components/' . strtolower(substr(strtr($function, '_', '/'), 4)),
                !empty($args[0]) ? $args[0]: array()
            );
        } elseif ( substr($function, 0, 6) === 'insert' ) {
            return $this->insert(
                !empty($args[1]) ? $args[1] : 'top',
                'components/' . strtolower(substr(strtr($function, '_', '/'), 6)),
                !empty($args[0]) ? $args[0]: array()
            );
        } elseif ( substr($function, 0, 6) === 'render' ) {
            return $this->render(
                'components/' . strtolower(substr(strtr($function, '_', '/'), 6)),
                !empty($args[0]) ? $args[0]: array()
            );
        } else {
            throw new \BadMethodCallException(
                'Undefined method : ' . $function
            );
        }
    }

    /**
     * Adds the specified data to the top of the specified
     * zone (using the specified file for the rendering)
     */
    public function insert($zone, $file, $datasource = null)
    {
        if (!isset($this->_placeholders[$zone])) {
            $this->_placeholders[$zone] = array();
        }
        array_unshift(
            $this->_placeholders[$zone], array($file, $datasource)
        );
        return $this;
    }

    /**
     * Converts the current datasource to an array
     * @return array
     */
    protected function getDatasource($datasource = null)
    {
        if (!$datasource || is_array($datasource)) {
            return $datasource;
        }
        if (is_callable($datasource)) {
            return $datasource($this->app);
        } else {
            return $datasource;
        }
    }

    /**
     * Renders the specified file
     * @return string
     */
    public function render($file, $datasource = null)
    {
        // check for a callback
        if ( !is_string( $file ) && is_callable($file)) {
            $key = spl_object_hash($file);
            $this->_renderers[$key] = $file;
            $file = $key;
        }
        if (!isset($this->_renderers[$file])) {
            $callback = strtr('view_'.$file, '/.', '__');
            if (function_exists($callback)) {
                $this->_renderers[$file] = $callback;
            }
        }
        // already buffered
        if (isset($this->_renderers[$file])) {
            ob_start();
            $this->_renderers[$file](
                $this->app, $this->getDatasource($datasource)
            );
            return $this->debugStart('From closure : ' . $file, 'view')
                . ob_get_clean()
                . $this->debugEnd('view')
            ;
        }
        // check for a file include
        $app = $this->_app;
        $data = $this->getDatasource($datasource);
        $view = $this;
        if (!file_exists($target = 'views/' . $file . '.phtml')) {
            if (
                !file_exists(
                    $target = APP_PATH . '/views/'
                            . $file . '.phtml'
                )
                && !file_exists(
                    $target = BEABA_PATH . '/views/'
                            . $file . '.phtml'
                )
            ) {
                if (
                    isset( $this->_defaults[ $file ] )
                    && is_callable( $this->_defaults[ $file ] )
                )
                {
                    $this->_renderers[$file] = $this->_defaults[ $file ];
                    ob_start();
                    $this->_renderers[$file](
                        $this->app,
                        $data
                    );
                    return
                        $this->debugStart('From default : ' . $file, 'view')
                        . ob_get_clean()
                        . $this->debugEnd('view')
                    ;
                } else {
                    trigger_error(
                         'Unable to locate the view : ' . $file
                         , E_USER_WARNING
                    );
                    return '';
                }
            }
        }
        ob_start();
        include $target;
        return $this->debugStart('From include : ' . $target, 'view')
            . ob_get_clean()
            . $this->debugEnd('view');
    }

    /**
     * Gets the debug configuration, if the debug is enabled
     */
    protected function getDebug( $target = 'view' )
    {
        if ( empty($_GET['debug']) ) return;
        if ( empty($this->options['debug']['enabled']) ) return;
        if ( empty($this->options['debug'][$target]) ) {
            throw new \Exception(
                'Undefined debug output mode : ' . $target
            );
        }
        $colors = $this->options['debug'][$target];
        if ( empty($colors['enabled']) ) return;
        return $colors;
    }
    /**
     * Starting a debug output
     */
    protected function debugStart( $text, $target = 'view' ) {
        $colors = $this->getDebug( $target );
        if ( !$colors ) return;
        return
            '<div style="margin: 1px; border: '.$colors['border'].';
                padding: 1px;">
                <div style="font-size: '.$colors['text']['size'].';
                padding: 4px; color: '.$colors['text']['color'].';
                font-weight: bold;
                background-color: '.$colors['text']['background'].';
                margin-bottom: 2px;">' . $text . '</div>'
        ;
    }
    /**
     * Closing the debug output
     */
    protected function debugEnd($target = 'view') {
        $colors = $this->getDebug( $target );
        if ( !$colors ) return;
        return '</div>';
    }
    /**
     * Renders the current template
     * @return string
     */
    public function renderTemplate()
    {
        if ($this->_template) {
            return $this->render($this->_template);
        } else {
            return $this->render(
                    $this->app->getInfos()->getTemplate()
            );
        }
    }

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
    )
    {
        if (!isset($this->_placeholders[$zone])) {
            $this->_placeholders[$zone] = array();
        }
        if ( !isset($this->_placeholders[$zone][$widget]) ) {
            $this->_placeholders[$zone][$widget] = array(
                $render, $datasource
            );
        } else {
            if ( !is_null($render) ) {
                $this->_placeholders[$zone][$widget][0] = $render;
            }
            if ( !is_null($datasource) ) {
                if (
                    is_array($datasource) &&
                    is_array($this->_placeholders[$zone][$widget][1])
                ) {
                    $this->_placeholders[$zone][$widget][1] = merge_array(
                        $this->_placeholders[$zone][$widget][1],
                        $datasource
                    );
                } else {
                    $this->_placeholders[$zone][$widget][1] = $datasource;
                }
            }
        }
        return $this;
    }

    /**
     * Initialize the layout data
     * @return IView
     */
    public function initLayout() {
        if ($this->_flagInit) return $this;
        $this->_flagInit = true;
        if (!$this->_layout)
            $this->_layout = $this->app->getInfos()->getLayout();
        // load the layout default configuration
        $this->_defaults = merge_array(
            $this->app->config->getConfig('layouts'),
            $this->app->config->getConfig('layouts/' . $this->_layout)
        );
        foreach ($this->_defaults as $zone => $widgets) {
            if ( is_array($widgets) ) {
                foreach ($widgets as $id => $widget) {
                    if (
                        !isset($widget['visible'])
                        || $widget['visible'] !== false
                    ) {
                        if ( is_numeric($id) ) {
                            $this->push(
                                $zone, $widget['render'],
                                empty($widget['data']) ?
                                array() : $widget['data']
                            );
                        } else {
                            $this->attach(
                                $zone, $id,
                                $widget['render'],
                                empty($widget['data']) ?
                                array() : $widget['data']
                            );
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Renders the current layout
     * @return string
     */
    public function renderLayout()
    {
        return $this->initLayout()->render($this->_layout);
    }

    /**
     * Renders the current layout
     * @return string
     */
    public function renderPlaceholder($zone)
    {
        if (isset($this->_placeholders[$zone])) {
            $result = $this->debugStart($zone, 'placeholder');
            foreach ($this->_placeholders[$zone] as $item) {
                $result .= $this->render($item[0], $item[1]);
            }
            return $result . $this->debugEnd('placeholder');
        } else {
            trigger_error(
                'Undefined placeholder : ' . $zone,
                E_USER_WARNING
            );
            return '';
        }
    }

    /**
     * Check if the specified placeholder is defined and contains data
     * @param string $name The placeholder name
     * @return boolean
     */
    public function isEmpty( $name ) {
        return empty($this->_placeholders[$name]);
    }

}