<?php
/**
 * @read-only true
 */
return array(
    'index' => array(
        'check' => array(
            'equals', array(
                '/', '/index'
            )
        ),
        'route' => 'app\\controllers\\index::index'
    ),
    'action' => array(
        'check' => array(
            'path', 1, 2
        ),
        'route' => function( $url ) {
            $parts = explode('/', trim($url, '/'), 2);
            if ( empty( $parts[0] ) ) $parts[0] = 'index';
            if ( empty( $parts[1] ) ) $parts[1] = 'index';
            $target = APP_PATH . '/controllers/' . strtolower($parts[0]) . '.php';
            $class = 'app\\controllers\\' . strtolower($parts[0]);
            if ( !class_exists( $class ) ) {
                throw new \beaba\core\Exception(
                    'Unable to find controller : ' . strtolower($parts[0]),
                    404
                );
            }
            return $class . '::' . strtolower($parts[1]);
        }
    )
);
