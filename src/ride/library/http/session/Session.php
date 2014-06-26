<?php

namespace ride\library\http\session;

use ride\library\http\session\io\SessionIO;

/**
 * Session handler
 */
class Session {

    /**
     * Session input/output
     * @var \ride\library\http\session\io\SessionIO
     */
    protected $io;

    /**
     * Id of the session
     * @var string
     */
    protected $id;

    /**
     * Data of the session
     * @var array
     */
    protected $data;

    /**
     * Flag to see if the session is changed
     * @var boolean
     */
    protected $isChanged;

    /**
     * Constructs a new session handler
     * @param \ride\library\http\session\io\SessionIO $io Input/output handler
     * for the session data
     * @return null
     */
    public function __construct(SessionIO $io) {
        $this->io = $io;
        $this->id = null;
        $this->data = array();
        $this->isChanged = false;
    }

    /**
     * Gets the id of the session
     * @return string Id of the session
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Loads a previous session by it's id
     * @param string $id Id of a previous session
     * @return null
     */
    public function read($id) {
        $this->id = $id;
        $this->data = $this->io->read($this->id);
        $this->isChanged = false;
    }

    /**
     * Writes the session to the storage
     * @return null
     */
    public function write() {
        $this->io->write($this->id, $this->data);
    }

    /**
     * Get a value from the session
     * @param string $key key of the value
     * @param mixed $default default value for when the key is not set
     * @return mixed Stored session value if set, the provided default value
     * otherwise
     */
    public function get($key, $default = null) {
        if (!isset($this->data[$key])) {
            return $default;
        }

        return $this->data[$key];
    }

    /**
     * Gets all the session variables
     * @return array
     */
    public function getAll() {
        return $this->data;
    }

    /**
     * Sets a value to the session or clear a previously set key by passing a
     * null value
     * @param string $key Key of the value
     * @param mixed $value The value, null to clear
     * @return null
     */
    public function set($key, $value = null) {
        if ($value !== null) {
            $this->data[$key] = $value;
        } elseif (isset($this->data[$key])) {
            unset($this->data[$key]);
        }

        $this->isChanged = true;
    }

    /**
     * Clears all values in the session
     * @return null
     */
    public function reset() {
        $this->data = array();
        $this->isChanged = true;
    }

    /**
     * Gets whether this session has changed
     * @return boolean
     */
    public function isChanged() {
        return $this->isChanged;
    }

}
