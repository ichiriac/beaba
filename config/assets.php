<?php
return array(
    'jquery' => array(
        'js' => array(
            'main' =>
            'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'
        )
    ),
    'jquery-ui' => array(
        'depends' => array('jquery'),
        'js' => array(
            'main' =>
            'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/jquery-ui.min.js'
        ),
        'css' => array(
            'main' =>
            'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css'
        )
    ),
    'swfobject' => array(
        'js' => array(
            'main' =>
            'https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js'
        )
    ),
    'dojo' => array(
        'js' => array(
            'main' =>
            'https://ajax.googleapis.com/ajax/libs/dojo/1.7.3/dojo/dojo.js'
        )
    ),
    'bootstrap' => array(
        'js' => array(
            '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/js/bootstrap.min.js'
        ),
        'css' => array(
            '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css'
        )
    )
);