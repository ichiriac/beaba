<?php

namespace beaba\services\cache;

use \beaba\core;

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
class File extends core\Service implements core\ICacheDriver
{

    private $tmp;

    /**
     * Converts a key to a file name
     * @param string $key
     * @return string
     */
    protected function getTemp( $key )
    {
        if ( !$this->tmp ) {
            $this->tmp = sys_get_temp_dir();
        }
        return $this->tmp . '/' . APP_NAME . '-' . md5($key) ;
    }

    /**
     * Gets the value from the specified key
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        $file = $this->getTemp($key);
        if ( file_exists($file) ) {
            return unserialize(file_get_contents($file));
        } else {
            return null;
        }
    }

    /**
     * Get values from the specified keys
     * @param array $keys
     * @return array
     */
    public function getValues(array $keys)
    {
        $result = array();
        foreach( $keys as $key ) {
            $result[ $key ] = $this->getValue($key);
        }
        return $result;
    }

    /**
     * Sets a value attached to the specified key
     * @param string $key
     * @param mixed $value
     * @return ICache
     */
    public function setValue($key, $value)
    {
        file_put_contents(
            $this->getTemp($key),
            serialize( $value )
        );
        return $this;
    }

    /**
     * Set values attaches to specified indexes (keys)
     * @param array $values
     * @return ICache
     */
    public function setValues($values)
    {
        foreach( $values as $key => $value ) $this->setValue($key, $value);
        return $this;
    }

    /**
     * Remove the specified key
     * @param string $key
     * @return ICache
     */
    public function unsetValue($key)
    {
        unlink( $this->getTemp($key) );
        return $this;
    }

    /**
     * Remove the specified keys
     * @param array $key
     * @return ICache
     */
    function unsetValues($keys)
    {
        foreach($this->keys as $key) $this->unsetValue($key);
        return $this;
    }

}
