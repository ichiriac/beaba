# Beaba-light

```
Work in progress - actually this source does not run correctly - comming soon
```

This project is a light-weight version of the beaba framework - a PHP MVC based framework - written for 
helping you to increase your dev productivity without needing to use a big or hard to understnd framework.

Objectives are the following : 

* 5 minutes to understand main principles
* 5 minutes to configure your server

You're ready to start your dev

## What does this version

 - Boostrap a web environement
 - Routing
 - Light model layer (a bilbon ORM version)
 - Templating (template - layout - reusable views & components)
 - Application injection layer based on services

## List of built-in services

 - Configuration (extension handler)
 - Storage (based on PDO)
 - Model (based on a tiny Active Record Mapper)
 - Views (templating engine based on phtml)
 - Router (closures & controller/action compliant)
 - Request (HTTP request wrapper)
 - Response (Output handler)

## Extras

Beaba is totaly compliant with these great projects :

 * forp & forp-ui
 * bootstrap css (from twitter)

## Your application structure

```
  +- config*
  |
  +- controllers
  |
  +- model*
  |
  +- locale*
  |
  +- views
  |
  +- www
     |
     +- index.php (the application bootstrap)
```

* optionnal folders, but in major projets they should be used. In theory, beaba
only require the index.php but it's not a good thing to put all the application
into a single file.

## How to create an application

- Donwload and put beaba somewhere (ex: /etc/beaba/framework )

- Start to create your application folder (ex: /etc/beaba/apps/my-app)

- Create all application folders, including the index.php.
(copy/paste the squeleton from demo/default/)

The content of your index.php is :
```php
<?php
// defines bootstrap
define('BEABA_PATH', '/etc/beaba/framework');
define('APP_NAME', 'my-app');
require_once( BEABA_PATH . '/bootstrap.php' );
// initialize the app
$app = new beaba\core\WebApp(array(
    'infos' => array(
        'title' => 'Your website title'
    ),
    'routes' => array(
        // @see beaba documentation
    )
));
// execute and sends the response
$app->getResponse()
    ->setLang(
        $app->getRequest()->getLang()
    )->write(
        $app->dispatch()
    )
;
```

- Configure your webserver, beaba can be run on apache or nginx

## A controller sample

```php
<?php
namespace app\controller;
use \beaba\core\Controller;

class index extends Controller {
    public function index_action() {
        return 'Hello world';
    }
    public function with_template_action() {
        return $this->getView()->push(
            'index', array(
                'data' => 'sent to view'
            ) // views/index.phtml
        );
    }
    public function with_rest_action($args) {
        return array(
            'GET' => array(
                'html' => function() use($app, $args) {
                    return $app->getView()->push(
                        'index', $args
                    );
                },
                'json' => $args,
                '*' => function() { // this is a wildcard fallback
                    return null;
                }
            ),
            'PUT' => null
            // ... etc ...
        );
    }
}
```
