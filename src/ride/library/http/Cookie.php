<?php

namespace ride\library\http;

use ride\library\http\exception\HttpException;

/**
 * Data container for a cookie
 */
class Cookie {

    /**
     * Name of the cookie
     * @var string
     */
    protected $name;

    /**
     * Value of the cookie
     * @var string
     */
    protected $value;

    /**
     * UNIX timestamp when the cookie expires
     * @var integer
     */
    protected $expires;

    /**
     * Domain for the cookie
     * @var string
     */
    protected $domain;

    /**
     * Path for the cookie
     * @var string
     */
    protected $path;

    /**
     * Flag to see if the cookie is for secure connections
     * @var boolean
     */
    protected $isSecure;

    /**
     * Flag to see if the cookie is for HTTP connections
     * @var boolean
     */
    protected $isHttpOnly;

    /**
     * Constructs a new cookie
     * @param string $name Name of the cookie
     * @param string $value Value for the cookie, leave null to delete the
     * cookie
     * @param integer $expires UNIX timestamp of the expires date
     * @param string $domain Domain for the cookie
     * @param string $path Path for the cookie
     * @param boolean $isSecure Flag to use this cookie only on secure
     * connections
     * @param boolean $isHttp Flag to use this cookie only in HTTP requests,
     * not in javascript calls
     * @return null
     * @throws ride\library\http\exception\HttpException when a invalid value
     * has been provided
     */
    public function __construct($name, $value = null, $expires = 0, $domain = null, $path = null, $isSecure = false, $isHttpOnly = true) {
        if ($value === null && $expires === 0) {
            $this->value = null;
            $this->expires = time() - 7777777;
        } else {
            $this->setValue($value);
            $this->setExpires($expires);
        }

        $this->setName($name);
        $this->setDomain($domain);
        $this->setPath($path);
        $this->setIsSecure($isSecure);
        $this->setIsHttpOnly($isHttpOnly);
    }

    /**
     * Gets a string representation for this cookie, ready for the Set-Cookie
     * header
     * @return string
     */
    public function __toString() {
        $output = $this->name . '=';

        if ($this->value !== null) {
            $output .= urlencode($this->value);
        } else {
            $output .= 'DELETED';
        }

        if ($this->domain && !$this->path) {
            $this->path = '/';
        }

        if ($this->domain && $this->path) {
            $output .= '; Domain=' . $this->domain;
        }

        if ($this->path) {
            $output .= '; Path=' . $this->path;
        }

        if ($this->expires != 0) {
            $output .= '; Expires=' . gmdate('D, d-M-Y H:i:s T', $this->expires);
        }

        if ($this->isSecure) {
            $output .= '; Secure';
        }

        if ($this->isHttpOnly) {
            $output .= '; HttpOnly';
        }

        return $output;
    }

    /**
     * Sets the name of the cookie
     * @param string $name Name of the cookie
     * @throws ride\library\http\exception\HttpException when the name is
     * invalid or empty
     */
    protected function setName($name) {
        if (!is_string($name) || $name == '') {
            throw new HttpException('Could not set the cookie name: provided name is not a string or is empty');
        }

        $this->name = $name;
    }

    /**
     * Gets the name of the cookie
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the value for the cookie
     * @param mixed $value Value for the cookie
     * @throws ride\library\http\exception\HttpException when the value is not
     * a scalar value
     */
    protected function setValue($value) {
        if (!is_scalar($value)) {
            throw new HttpException('Could not set the cookie value: provided value is not a scalar value');
        }

        $this->value = $value;
    }

    /**
     * Gets the value of the cookie
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Sets the expires date for the cookie
     * @param integer $expires UNIX timestamp
     * @return null
     * @throws ride\library\http\exception\HttpException when the value is not
     * a valid timestamp
     */
    protected function setExpires($expires) {
        if (!is_integer($expires) || $expires < 0) {
            throw new HttpException('Could not set the cookie expire date: provided date is not a UNIX timestamp');
        }

        $this->expires = $expires;
    }

    /**
     * Gets the expires date
     * @return integer UNIX timestamp
     */
    public function getExpires() {
        return $this->expires;
    }

    /**
     * Sets the domain for the cookie
     * @param string $domain
     * @return null
     * @throws ride\library\http\exception\HttpException when the provided
     * domain is invalid or empty
     */
    protected function setDomain($domain) {
        if ($domain !== null && (!is_string($domain) || $domain == '')) {
            throw new HttpException('Could not set cookie domain: provided domain is not a string or is empty');
        }

        $this->domain = $domain;
    }

    /**
     * Gets the domain for the cookie
     * @return string
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Sets the path for the cookie
     * @param string $path
     * @return null
     * @throws ride\library\http\exception\HttpException when the provided path
     * is invalid or empty
     */
    protected function setPath($path) {
        if ($path !== null && (!is_string($path) || $path == '')) {
            throw new HttpException('Could not set cookie path: provided path is not a string or is empty');
        }

        $this->path = $path;
    }

    /**
     * Gets the path of the cookie
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Sets whether this cookie is only available in secure connections
     * @param boolean $flag
     * @return null
     * @throws ride\library\http\exception\HttpException when the provided flag
     * is not a boolean
     */
    protected function setIsSecure($flag) {
        if (!is_bool($flag)) {
            throw new HttpException('Could not set cookie secure flag: provided flag is not a boolean');
        }

        $this->isSecure = $flag;
    }

    /**
     * Gets whether this cookie is only available in secure connections
     * @return boolean
     */
    public function isSecure() {
        return $this->isSecure;
    }

    /**
     * Sets whether this cookie is only available in HTTP requests, no
     * javascript requests
     * @param boolean $flag
     * @return null
     * @throws ride\library\http\exception\HttpException when the provided flag
     * is not a boolean
     */
    protected function setIsHttpOnly($flag) {
        if (!is_bool($flag)) {
            throw new HttpException('Could not set cookie HttpOnly flag: provided flag is not a boolean');
        }

        $this->isHttpOnly = $flag;
    }

    /**
     * Gets whether this cookie is available only on secure connections
     * @return boolean
     */
    public function isHttpOnly() {
        return $this->isHttpOnly;
    }

}