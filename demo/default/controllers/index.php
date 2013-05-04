<?php
namespace app\controllers;
use \beaba\core\Controller;

class index extends Controller
{
    public function index_action( $args ) {
        return $this->getView()->push(
            'content', 'controllers/index', array(
                'text' => 'pass some dynamic data'
            )
        );
    }
}