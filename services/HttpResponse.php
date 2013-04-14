<?php

namespace beaba\core\services;

use \beaba\core;

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class HttpResponse extends core\Service implements core\IResponse
{

    public function setCode($code, $message)
    {
        header('HTTP/1.0 ' . $code . ' ' . $message);
        header('Status: ' . $code . ' ' . $message);
        return $this;
    }

    /**
     * Sets the selected language
     */
    public function setLang( $lang ) {
        defined('LC_MESSAGES') or define('LC_MESSAGES', 6);
        $locale = explode('_', $lang, 2);
        if (empty($locale[1])) $locale[1] = strtoupper($locale[0]);
        $lang = implode('_', $locale).'.UTF-8';
        putenv('LC_MESSAGES=' . $lang);
        setlocale(LC_MESSAGES, $lang);
        bindtextdomain('core', BEABA_PATH . '/locale' );
        bindtextdomain('app', APP_PATH . '/locale' );
        bind_textdomain_codeset( 'app', 'UTF-8');
        bind_textdomain_codeset( 'core', 'UTF-8');
        return $this->setHeader('Content-Language', $locale[0]);
    }

    /**
     * Sets the response header
     * @param string|array $attribute
     * @param string $value
     * @return IResponse
     */
    public function setHeader($attribute, $value = null)
    {
        if ( is_array($attribute) ) {
            foreach($attribute as $name => $value ) {
                header( $name . ': ' . $value );
            }
        } else {
            header( $attribute . ': ' . $value );
        }
        return $this;
    }

    public function writeLine($message)
    {
        if ($this->_app->getRequest()->getResponseType() === 'html') {
            echo $message . '<br />' . "\n";
        } else {
            echo $message . "\n";
        }
        return $this;
    }

    public function write($message)
    {
        echo $message;
        return $this;
    }

}
