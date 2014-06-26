<?php

namespace ride\library\http;

use ride\library\http\exception\HttpException;

use \Countable;
use \Iterator;

/**
 * Container of headers
 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 */
class HeaderContainer implements Iterator, Countable {

    /**
     * Name of the no cache control directive
     * @var string
     */
    const CACHE_CONTROL_NO_CACHE = 'no-cache';

    /**
     * Name of the private cache control directive
     * @var string
     */
    const CACHE_CONTROL_PRIVATE = 'private';

    /**
     * Name of the public cache control directive
     * @var string
     */
    const CACHE_CONTROL_PUBLIC = 'public';

    /**
     * Name of the max age cache control directive
     * @var string
     */
    const CACHE_CONTROL_MAX_AGE = 'max-age';

    /**
     * Name of the shared max age cache control directive
     * @var string
     */
    const CACHE_CONTROL_SHARED_MAX_AGE = 's-maxage';

    /**
     * The headers in this list
     * @var array
     */
    protected $headers;

    /**
     * The cache control directives
     * @var array
     */
    protected $cacheControl;

    /**
     * Creates a new header list
     * @return null
     */
    public function __construct() {
        $this->headers = array();
        $this->cacheControl = array();
    }

    /**
     * Gets a string representation of this header container
     * @return string
     */
    public function __toString() {
        $string = '';

        foreach ($this as $header) {
            $string .= $header . "\r\n";
        }

        return $string;
    }

    /**
     * Adds a header to this container
     * @param string|Header $header Name of the header or a Header instance
     * @param string|null $value The value of the header or null if a Header
     * instance is provided
     * @param boolean $prepend Set to true to add the header at the beginning,
     * only for new headers
     * @return null
     * @see setHeader
     */
    public function addHeader($header, $value = null, $prepend = false) {
        if (!$header instanceof Header) {
            $header = new Header($header, $value);
        }

        $headerName = $header->getName();

        if ($headerName == Header::HEADER_CACHE_CONTROL) {
            // make sure the cache control array is in sync with the header
            $this->cacheControl = $this->parseCacheControl($header->getValue());
            $this->headers[$headerName] = $header;
        } elseif (isset($this->headers[$headerName])) {
            if (is_array($this->headers[$headerName])) {
                // already some headers set with this name, just add it
                $found = false;
                foreach ($this->headers[$headerName] as $existingHeader) {
                    if ($existingHeader->getValue() !== $value) {
                        $found = true;

                        break;
                    }
                }

                if (!$found) {
                    $this->headers[$headerName][] = $header;
                }
            } elseif ($this->headers[$headerName]->getValue() !== $value) {
                // already a header set with this name, convert to array and
                // add it
                $this->headers[$headerName] = array(
                    $this->headers[$headerName],
                    $header,
                );
            }
        } else {
            // no header set with this name
            $this->headers[$headerName] = $header;

            if ($prepend) {
                $this->headers = array($headerName => $header) + $this->headers;
            }
        }
    }

    /**
     * Sets a header to this container, any existing headers with the same
     * name will be overwritten
     * @param string|Header $header Name of the header or a Header instance
     * @param string|null $value The value of the header or null if a Header
     * instance is provided
     * @param boolean $prepend Set to true to add the header at the beginning,
     * only for new headers
     * @return null
     * @see addHeader
     */
    public function setHeader($header, $value = null, $prepend = false) {
        if (!$header instanceof Header) {
            $header = new Header($header, $value);
        }

        if (isset($this->headers[$header->getName()])) {
            unset($this->headers[$header->getName()]);
        }

        $this->addHeader($header, null, $prepend);
    }

    /**
     * Checks if a header is set
     * @param string $name The name of the header
     * @return boolean True if the header is set, false otherwise
     */
    public function hasHeader($name) {
        $name = Header::parseName($name);

        return isset($this->headers[$name]);
    }

    /**
     * Gets wheter there are headers in this container
     * @return boolean True if there are, false otherwise
     */
    public function hasHeaders() {
        return $this->headers ? true : false;
    }

    /**
     * Gets the header(s) with the provided name
     * @param string $name Name of the header
     * @return Header|array|null An instance of Header of only 1 header set, an
     * array of Header objects if multiple values are set, null otherwise
     * @throws \ride\library\http\exception\HttpException when the provided name
     * is empty or invalid
     */
    public function getHeader($name) {
        $name = Header::parseName($name);

        if (!isset($this->headers[$name])) {
            return null;
        }

        return $this->headers[$name];
    }

    /**
     * Gets all the headers
     * @return array Array with Header objects
     */
    public function getHeaders() {
        $headers = array();
        foreach ($this as $header) {
            if (is_array($header)) {
                foreach ($header as $h) {
                    $headers[] = $h;
                }
            } else {
                $headers[] = $header;
            }
        }

        return $headers;
    }

    /**
     * Removes a header with the provided name
     * @param string|array $name Name of the header or an array with names
     * @return null
     * @throws rideException when the provided name is empty or invalid
     */
    public function removeHeader($name) {
        if (!is_array($name)) {
            $name = array($name);
        }

        foreach ($name as $header) {
            $header = Header::parseName($header);

            if (isset($this->headers[$header])) {
                unset($this->headers[$header]);
            }
        }
    }

    /**
     * Adds a cache control directive
     * @param string $directive Name of the directive
     * @param string $value Value of the directive, true as flag, a value
     * otherwise
     * @return null
     * @throws \ride\library\http\HttpException when the directive is empty or
     * not a string
     */
    public function addCacheControlDirective($directive, $value = true) {
        if (!is_string($directive) || !$directive) {
            throw new HttpException('Could not add cache control directive: provided directive is empty or not a string');
        }

        if ($value !== true && (!is_scalar($value) || $value == '')) {
            throw new HttpException('Could not add cache control directive: provided value is empty or not a scalar value');
        }

        $this->cacheControl[$directive] = $value;

        $this->setCacheControlHeaderValue();
    }

    /**
     * Gets a cache control directive
     * @param string $directive Name of the directive
     * @return boolean|string|null The value of the directive if found, null
     * otherwise
     * @throws \ride\library\http\exception\HttpException when the directive is empty or
     * not a string
     */
    public function getCacheControlDirective($directive) {
        if (!is_string($directive) || !$directive) {
            throw new HttpException('Could not get cache control directory: provided directive is empty or not a string');
        }

        if (!isset($this->cacheControl[$directive])) {
            return null;
        }

        return $this->cacheControl[$directive];
    }

    /**
     * Gets the cache control directives
     * @return array
     */
    public function getCacheControlDirectives() {
        return $this->cacheControl;
    }

    /**
     * Removes a cache control directive
     * @param string $directive Name of the directive
     * @return null
     * @throws \ride\library\http\exception\HttpException when the directive
     * is empty or not a string
     */
    public function removeCacheControlDirective($directive) {
        if (!is_string($directive) || !$directive) {
            throw new HttpException('Could not remove cache control directory: provided directive is empty or not a string');
        }

        if (!isset($this->cacheControl[$directive])) {
            return;
        }

        unset($this->cacheControl[$directive]);

        $this->setCacheControlHeaderValue();
    }

    /**
     * Sets the cache control header value
     * @return null
     */
    protected function setCacheControlHeaderValue() {
        $directives = array();

        foreach ($this->cacheControl as $directive => $value) {
            if ($value === true) {
                $directives[] = $directive;
            } else {
                if (preg_match('#[^a-zA-Z0-9._-]#', $value)) {
                    $value = '"' . $value . '"';
                }

                $directives[] = $directive . '=' . $value;
            }
        }

        $value = implode(', ', $directives);

        if ($value) {
            $this->headers[Header::HEADER_CACHE_CONTROL] = new Header(Header::HEADER_CACHE_CONTROL, $value);
        } elseif (isset($this->headers[Header::HEADER_CACHE_CONTROL])) {
            unset($this->headers[Header::HEADER_CACHE_CONTROL]);
        }
    }

    /**
     * Parses a cache control header value into an array.
     *
     * Taken from Symfony
     * @param string $header The value of the Cache-Control HTTP header
     * @return array An array representing the cache control directives
     */
    protected function parseCacheControl($header) {
        $cacheControl = array();

        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $cacheControl[strtolower($match[1])] = isset($match[2]) && $match[2] ? $match[2] : (isset($match[3]) ? $match[3] : true);
        }

        return $cacheControl;
    }

    /**
     * Sorts the headers by name
     * @return null
     */
    public function sort() {
        ksort($this->headers);
    }

    /**
     * Sets the internal pointer of the iterator to its first element
     * @return null
     */
    public function rewind() {
        reset($this->headers);
        unset($this->iteratorChildren);
    }

    /**
     * Gets the current element in the current array
     * return mixed
     */
    public function current() {
        if (isset($this->iteratorChildren)) {
            return current($this->iteratorChildren);
        } else {
            return current($this->headers);
        }
    }

    /**
     * Gets the index element of the current array position in the current array.
     * @return mixed
     */
    public function key() {
        if (isset($this->iteratorChildren)) {
            return key($this->iteratorChildren);
        } else {
            return key($this->headers);
        }
    }

    /**
     * Gets the current element and advances the internal array pointer
     * @return mixed
     */
    public function next() {
        if (isset($this->iteratorChildren)) {
            $next = next($this->iteratorChildren);
            if ($next) {
                return $next;
            }

            unset($this->iteratorChildren);
        }

        $next = next($this->headers);
        if (is_array($next)) {
            $this->iteratorChildren =& $next;
        }

        return $next;
    }

    /**
     * Checks if the iterator current position is valid
     * @return boolean
     */
    public function valid() {
        return $this->current() !== false;
    }

    /**
     * Counts the number of headers
     * @return integer
     */
    public function count() {
        $total = 0;

        foreach ($this->headers as $header) {
            if (is_array($header)) {
                $total += count($header);
            } else {
                $total ++;
            }
        }

        return $total;
    }

}