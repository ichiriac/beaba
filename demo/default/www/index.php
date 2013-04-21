<?php
// defines bootstrap path from the configuration
define('BEABA_PATH', getenv('BEABA_PATH'));
// defines an unique application name
define('APP_NAME', 'your-app-name');
// loads the bootstrap
require_once( BEABA_PATH . '/bootstrap.php' );
// initialize the app
$app = new beaba\core\WebApp();
$app->getResponse()
    // enables the application to run multi languages
    ->setLang(
        // gets the language requested from the client
        $app->getRequest()->getLang()
    )
    // sending the output to the client
    ->write(
        // dispatching the current request and retrieves the response
        $app->dispatch()
    )
;