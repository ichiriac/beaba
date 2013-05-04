<?php
/**
 * @read-only true
 */
return array(
    'router'    => 'beaba\\services\\Router',
    'response'  => 'beaba\\services\\HttpResponse',
    'request'   => 'beaba\\services\\HttpRequest',
    'errors'    => 'beaba\\services\\ErrorHandler',
    'assets'    => 'beaba\\services\\Assets',
    'storage'   => 'beaba\\services\\Storage',
    'view'      => array(
        'class' => 'beaba\\services\\View',
        'options' => array(
            'debug' => array(
                'enabled' => false,
                'view' => array(
                    'enabled' => true,
                    'border' => 'solid 1px #666666;',
                    'text' => array(
                        'size' => '9px',
                        'color' => '#ffffff',
                        'background' => '#333333'
                    )
                ),
                'placeholder' => array(
                    'enabled' => true,
                    'border' => 'dotted 2px #663333;',
                    'text' => array(
                        'size' => '9px',
                        'color' => '#ffffff',
                        'background' => '#330000'
                    )
                )
            )
        )
    ),
    'session'   => 'beaba\\services\\Session',
    'infos'     => 'beaba\\services\\Infos',
);