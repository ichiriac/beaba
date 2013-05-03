<?php
// defines bootstrap path from the configuration
define('BEABA_PATH', getenv('BEABA_PATH'));
// defines an unique application name
define('APP_NAME', 'hello_world');
// enable the forp profiler
define('CAN_DEBUG', true);
// loads the bootstrap
require_once( BEABA_PATH . '/bootstrap.php' );

// initialize the app
$app = new beaba\core\WebApp(array(
    'routes' => array(
        /**
         * the index pattern is defined in beaba/config/routes.php :
         * 'check' => array(
         *      'equals', array(
         *          '/', '/index'
         *      )
         * ),
         */
        'index' => array(
            // overwrite the index route
            'callback' => function() {
                // outputs an hello world
                return 'hello world';
            }
        )
    )
));
// sending the output to the client
$app->getResponse()->write(
    // dispatching the current request and retrieves the response
    $app->dispatch()
);