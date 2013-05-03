<?php
namespace beaba\core;
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class Exception extends \Exception
{
    /**
     * List of common http codes
     */
    public static $codes = array(
        200 => 'OK',
        201 => 'CREATED',
        202 => 'Accepted',
        204 => 'No Response',
        301 => 'Moved',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        412 => 'Precondition failed',
        500 => 'Internal Error',
        501 => 'Not implemented'
    );
    /**
     * Check if the current http code is an error
     * @return boolean
     */
    public function isHttpError()
    {
        return (
            $this->getCode() == 0 || $this->getCode() > 399
        );
    }
    /**
     * Gets the http error message
     * @return string
     */
    public function getHttpMessage()
    {
        if ( isset( static::$codes[ $this->getCode() ] ) ) {
            return static::$codes[ $this->getCode() ];
        } else {
            static::$codes[ 500 ];
        }
    }
}
