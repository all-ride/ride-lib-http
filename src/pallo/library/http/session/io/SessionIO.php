<?php

namespace pallo\library\http\session\io;

/**
 * Interface for the session input/output to storage
 */
interface SessionIO {

    /**
     * Cleans up the sessions which are invalidated
     * @param boolean $force Set to true to clear all sessions
     * @return null
     */
    public function clean($force = false);

    /**
     * Reads the session data for the provided id
     * @param string $id Id of the session
     * @return array Array with the session data
     */
    public function read($id);

    /**
     * Writes the session data to storage
     * @param string $id Id of the session
     * @param array $data Session data
     * @return null
     */
    public function write($id, array $data);

}