<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
namespace beaba\services;

use \beaba\core\ICacheDriver;
use \beaba\core\ISession;
use \beaba\core\Service;

/**
 * Sets user-level session storage functions
 */
class Session extends Service implements ISession
{

    protected $savePath;
    protected $sessionName;
    protected $started;
    protected $sessionId;
    protected $storage;

    /**
     * Initialize the session handler (using PHP session handler)
     * @return void
     */
    protected function onStart()
    {
        parent::onStart();
        if ( !empty($this->options['name']) ) {
            $this->sessionName = $this->options['name'];
            session_name($this->options['name']);
        } else {
            $this->sessionName = session_name();
        }
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    /**
     * Gets the session storage driver
     * @return ICacheDriver
     */
    protected function getStorage()
    {
        if ( !$this->storage ) {
            $this->storage = $this->app->getService('cache')->get(
                empty($this->options['driver']) ?
                    null : $this->options['driver']
            );
        }
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function isDefined()
    {
        return !empty($_COOKIE[ $this->sessionName ]);
    }

    /**
     * @inheritdoc
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * @inheritdoc
     */
    public function getItem( $entry )
    {
        if ( !$this->isDefined()) {
            return null;
        } else {
            if ( !$this->started ) $this->started = session_start();
            return isset($_SESSION[$entry]) ? $_SESSION[$entry] : null;
        }
    }

    /**
     * @inheritdoc
     */
    public function hasItem( $entry )
    {
        if ( !$this->isDefined()) {
            return false;
        } else {
            if ( !$this->started ) $this->started = session_start();
            return isset($_SESSION[$entry]);
        }
    }

    /**
     * @inheritdoc
     */
    public function setItem( $entry, $value )
    {
        if ( !$this->started ) $this->started = session_start();
        $_SESSION[$entry] = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeItem( $entry )
    {
        if ( $this->isDefined() ) {
            if ( !$this->started ) $this->started = session_start();
            unset($_SESSION[$entry]);
        }
        return $this;
    }

    /**
     * The open callback works like a constructor in classes and is executed
     * when the session is being opened. It is the first callback function
     * executed when the session is started automatically or manually
     * with session_start().
     * @param string $savePath
     * @param string $sessionName
     * @return boolean
     */
    public function open($savePath, $sessionName)
    {
        $this->started = true;
        $this->savePath = $savePath;
        $this->sessionName = $sessionName;
        return true;
    }

    /**
     * The close callback works like a destructor in classes and is executed
     * after the session write callback has been called. It is also invoked
     * when session_write_close() is called.
     * @return boolean
     */
    public function close()
    {
        $this->started = false;
        return true;
    }

    /**
     * The read callback must always return a session encoded (serialized)
     * string, or an empty string if there is no data to read.
     *
     * This callback is called internally by PHP when the session starts
     * or when session_start() is called. Before this callback is invoked
     * PHP will invoke the open callback.
     *
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId)
    {
        $this->sessionId = $sessionId;
        return $this->getStorage()->getValue(
            $this->sessionName . ':' . $sessionId
        );
    }

    /**
     * The write callback is called when the session needs to be saved and
     * closed. This callback receives the current session ID a serialized
     * version the $_SESSION superglobal. The serialization method used
     * internally by PHP is specified in the session.serialize_handler
     * ini setting.
     *
     * The serialized session data passed to this callback should be stored
     * against the passed session ID. When retrieving this data, the read
     * callback must return the exact value that was originally passed to the
     * write callback.
     *
     * @param string $sessionId
     * @param string $data
     */
    public function write($sessionId, $data)
    {
        try {
            $this->getStorage()->setValue(
                $this->sessionName . ':' . $sessionId,
                $data
            );
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * This callback is executed when a session is destroyed with
     * session_destroy() or with session_regenerate_id() with the destroy
     * parameter set to TRUE.
     * @param string $sessionId
     * @return boolean
     */
    public function destroy($sessionId)
    {
        try {
            $this->getStorage()->unsetValue( $this->sessionName . ':' . $sessionId );
            return true;
        } catch(\Exception $ex) {
            return false;
        }
    }

    /**
     * The garbage collector callback is invoked internally by PHP periodically
     * in order to purge old session data. The frequency is controlled by
     * session.gc_probability and session.gc_divisor. The value of lifetime
     * which is passed to this callback can be set in session.gc_maxlifetime.
     * @param type $lifetime
     * @return boolean
     */
    public function gc($lifetime)
    {
        return true;
    }
}
