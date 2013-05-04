<?php
// defines bootstrap path from the configuration
define('BEABA_PATH', getenv('BEABA_PATH'));
// defines an unique application name (corresponding to the application path)
define('APP_NAME', 'default');
// if enabled on production, use a generated token varname instead 'debug'
define('CAN_DEBUG', isset($_GET['debug']));
// loads the bootstrap
require_once( BEABA_PATH . '/bootstrap.php' );
// initialize the app
$app = new beaba\core\WebApp(array(
    'infos' => array(
        'name'          => 'Your WebSite',
        'title'         => 'Untitled document',
        'description'   => 'Your default page description',
        'template'      => 'templates/default', // views/templates/default.phtml
        'layout'        => 'layouts/default', // views/templates/defauult.phtml
        'langs'         => 'en',
        'assets'        => array('bootstrap')
    ),
    // handling the views debugger
    'services' => array(
        'view' => array(
            'options' => array(
                'debug' => array(
                    'enabled' => CAN_DEBUG && isset($_GET['debug-view'])
                )
            )
        )
    ),
    // defines default placeholders contents
    'layouts' => array(
        'header' => array( // header placeholder :
            array(
                'render' => 'snippet/header' // see views/snippet/header.phtml
            )
        ),
        'footer' => array( // footer placeholder :
            array(
                'render' => 'snippet/footer' // see views/snippet/footer.phtml
            )
        )
    )
));
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