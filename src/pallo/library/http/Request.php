<?php

namespace pallo\library\http;

use pallo\library\http\exception\HttpException;
use pallo\library\http\session\Session;

/**
 * Data container for a HTTP request
 */
class Request {

    /**
     * The HEAD method
     * @var string
     */
    const METHOD_HEAD = 'HEAD';

    /**
     * The GET method
     * @var string
     */
    const METHOD_GET = 'GET';

    /**
     * The POST method
     * @var string
     */
    const METHOD_POST = 'POST';

    /**
     * The PUT method
     * @var string
     */
    const METHOD_PUT = 'PUT';

    /**
     * The DELETE method
     * @var string
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * Value of the request header when the request is a XML HTTP request
     * @var string
     */
    const XML_HTTP_REQUEST = 'XMLHttpRequest';

    /**
     * Flag to see if HTTPS is used
     * @var boolean
     */
    protected $isSecure;

    /**
     * The method of the request
     * @var string
     */
    protected $method;

    /**
     * Protocol of the request
     * @var string
     */
    protected $protocol;

    /**
     * Requested path
     * @var string
     */
    protected $path;

    /**
     * Query string (eg ?var1=value&var2=value)
     * @var string
     */
    protected $query;

    /**
     * Parameters of the query string
     * @var array
     */
    protected $queryParameters;

    /**
     * Container with the request headers
     * @var pallo\library\http\HeaderContainer
     */
    protected $headers;

    /**
     * Array with the name as key and the cookie as value
     * @var array
     */
    protected $cookies;

    /**
     * Body of the request
     * @var string
     */
    protected $body;

    /**
     * Parameters in the body of the request
     * @var array
     */
    protected $bodyParameters;

    /**
     * Instance of the session
     * @var pallo\library\http\session\Session
     */
    protected $session;

    /**
     * Constructs a new request
     * @param string $method The method of the request
     * @param string $path The requested path with query string
     * @param string $protocol The HTTP protocol version
     * @param HeaderContainer $headers A container with the request headers
     * @param string|array $body The body of the request, this can be $_POST
     * @return null
     */
    public function __construct($path, $method = self::METHOD_GET, $protocol = 'HTTP/1.1', HeaderContainer $headers = null, $body = null) {
        if ($headers) {
            $this->headers = $headers;
        } else {
            $this->headers = new HeaderContainer();
        }

        $this->setMethod($method);
        $this->setProtocol($protocol);
        $this->setPath($path);
        $this->setBody($body);

        $this->setCookies();

        $this->isSecure = false;
    }

    /**
     * Gets a string representation of this request
     * @return string
     */
    public function __toString() {
        $request = $this->method . ' ' . $this->path . ' ' . $this->protocol . "\r\n";

        foreach ($this->headers as $header) {
            $request .= (string) $header . "\r\n";
        }

        if ($this->getBody()) {
            $request .= "\r\n" . $this->getBody() . "\r\n";
        }

        return $request;
    }

    /**
     * Sets whether this is a HTTPS request
     * @param boolean $isSecure
     * @return null
     */
    public function setIsSecure($isSecure) {
        $this->isSecure = $isSecure;
    }

    /**
     * Gets whether this is a HTTPS request
     * @return boolean
     */
    public function isSecure() {
        return $this->isSecure;
    }

    /**
     * Sets the method of this request
     * @param string $method The method
     * @throws pallo\library\http\exception\HttpException when an invalid
     * method is provided
     */
    protected function setMethod($method) {
        if (!is_string($method) || !$method) {
            throw new HttpException('Provided method is empty or not a string');
        }

        $this->method = strtoupper($method);
    }

    /**
     * Gets the method of this HTTP request (GET, POST, ...)
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Checks if this is a HEAD request
     * @return boolean
     */
    public function isHead() {
        return $this->method == self::METHOD_HEAD;
    }

    /**
     * Checks if this is a GET request
     * @return boolean
     */
    public function isGet() {
        return $this->method == self::METHOD_GET;
    }

    /**
     * Checks if this is a POST request
     * @return boolean
     */
    public function isPost() {
        return $this->method == self::METHOD_POST;
    }

    /**
     * Checks if this is a PUT request
     * @return boolean
     */
    public function isPut() {
        return $this->method == self::METHOD_PUT;
    }

    /**
     * Checks if this is a DELETE request
     * @return boolean
     */
    public function isDelete() {
        return $this->method == self::METHOD_DELETE;
    }

    /**
     * Sets the protocol version of the request
     * @param string $protocol The protocol
     * @throws pallo\library\http\exception\HttpException when an invalid
     * protocol is provided
     */
    protected function setProtocol($protocol) {
        if (!is_string($protocol) || !$protocol) {
            throw new HttpException('Provided protocol is empty or not a string');
        }

        $this->protocol = $protocol;
    }

    /**
     * Gets the HTTP protocol version
     * @return string
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * Sets the requested path
     * @param string $path The requested path
     * @throws pallo\library\http\exception\HttpException when an invalid path
     * is provided
     */
    protected function setPath($path) {
        if (!is_string($path) || !$path) {
            throw new HttpException('Provided path is empty or not a string');
        }

        $this->path = $path;

        // set the query parameters
        $positionQuery = strpos($this->path, '?');
        if ($positionQuery !== false) {
            $this->query = substr($this->path, $positionQuery);
        }
    }

    /**
     * Gets the full path of the HTTP request
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Gets the URL to the server
     * @return string
     * @todo check for secure requests
     */
    public function getServerUrl() {
        $host = $this->getHeader(Header::HEADER_HOST);
        if (!$host) {
            $host = 'localhost';
        }

        if ($this->isSecure) {
            return 'https://' . $host;
        }

        return 'http://' . $host;
    }

    /**
     * Gets the full requested URL
     * @return string
     */
    public function getUrl() {
        return $this->getServerUrl() . $this->path;
    }

    /**
     * Gets the query string of the path
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * Gets a query parameter by name
     * @param string $name The name of the parameter
     * @param mixed $default Default value for the parameter
     * @return mixed The value of the query parameter if set, the provided
     * default otherwise
     */
    public function getQueryParameter($name, $default = null) {
        $this->getQueryParameters();

        return $this->getParameterByName($this->queryParameters, $name, $default);
    }

    /**
     * Gets all the query parameters
     * @return array
     */
    public function getQueryParameters() {
        if ($this->queryParameters === null) {
            if ($this->query) {
                $this->queryParameters = $this->parseQueryString($this->query);
            } else {
                $this->queryParameters = array();
            }
        }

        return $this->queryParameters;
    }

    /**
     * Gets the query parameters for the action as a string. Useful to recreate
     * the URL of this request. The question mark is not included.
     * @return string
    */
    public function getQueryParametersAsString() {
        return $this->queryParameters ? http_build_query($this->getQueryParameters()) : null;
    }

    /**
     * Sets the body of this request
     * @param string|array $body
     * @return null
     * @throws pallo\library\http\exception\HttpException when a invalid body
     * has been provided
     */
    public function setBody($body) {
        if (is_array($body)) {
            $this->bodyParameters = $body;
            $this->body = null;
        } elseif (is_scalar($body) || $body === null) {
//             $this->bodyParameters = array();
            $this->body = $body;
        } else {
            throw new HttpException('Could not set the body of this request: no array or scalar value provided');
        }
    }

    /**
     * Gets the body of the request
     * @return string
     */
    public function getBody() {
        if (!$this->body && $this->bodyParameters) {
            $contentType = $this->getHeader(Header::HEADER_CONTENT_TYPE);

            if (strpos($contentType, ';') !== false) {
                list($contentType, $attributes) = explode(';', $contentType);
            }

            if ($contentType == 'application/json') {
                $this->body = json_encode($this->bodyParameters);
            } elseif ($contentType == 'application/x-www-form-urlencoded') {
                $this->body = $this->getBodyParametersAsString();
            }
        }

        return $this->body;
    }

    /**
     * Gets a body parameter by name
     * @param string $name The name of the parameter
     * @param mixed $default Default value for the parameter
     * @return mixed The value of the query parameter if set, the provided
     * default otherwise
     */
    public function getBodyParameter($name, $default = null) {
        $this->getBodyParameters();

        return $this->getParameterByName($this->bodyParameters, $name, $default);
    }

    /**
     * Gets all the body parameters
     * @return array
     */
    public function getBodyParameters() {
        if ($this->bodyParameters === null && $this->body) {
            $contentType = $this->getHeader(Header::HEADER_CONTENT_TYPE);

            if (strpos($contentType, ';') !== false) {
                list($contentType, $attributes) = explode(';', $contentType);
            }

            if ($contentType == 'application/json') {
                $this->bodyParameters = json_decode($this->body, true);
            } elseif ($contentType == 'application/x-www-form-urlencoded') {
                $this->bodyParameters = $this->parseQueryString($this->body);
            } else {
                $this->bodyParameters = array();
            }
        }

        return $this->bodyParameters;
    }

    /**
     * Gets the query parameters for the action as a string. Useful to recreate
     * the URL of this request. The question mark is not included.
     * @return string
     */
    public function getBodyParametersAsString() {
        return $this->bodyParameters ? http_build_query($this->getBodyParameters()) : null;
    }

    /**
     * Gets a parameter by name
     * @param array $parameters The parameters
     * @param string $name The name of the parameter
     * @param mixed $default Default value for the parameter
     * @return mixed The value of the query parameter if set, the provided
     * default otherwise
     * @throws pallo\library\http\exception\HttpException when the provided
     * parameter name is empty or invalid
     */
    protected function getParameterByName(array &$parameters = null, $name, $default = null) {
        if (!is_string($name) || !$name) {
            throw new HttpException('Could not get parameter: name is empty or not a string');
        }

        if (!isset($parameters[$name])) {
            return $default;
        }

        return $parameters[$name];
    }

    /**
     * Sets the cookies from the headers in this request
     * @return null
     */
    protected function setCookies() {
        $this->cookies = array();

        $headers = $this->getHeader(Header::HEADER_COOKIE);
        if (!$headers) {
            return;
        }

        if (!is_array($headers)) {
            $headers = array($headers);
        }

        foreach ($headers as $header) {
            $cookies = explode(';', $header);
            foreach ($cookies as $cookie) {
                list($name, $value) = explode('=', trim($cookie), 2);

                $this->cookies[$name] = $value;
            }
        }
    }

    /**
     * Gets a cookie value
     * @param string $name Name of the cookie
     * @param mixed $default Default value for when the cookie is not set
     * @return mixed The value of the cookie if set, the provided default
     * value otherwise
     */
    public function getCookie($name, $default = null) {
        if (!isset($this->cookies[$name])) {
            return $default;
        }

        return $this->cookies[$name];
    }

    /**
     * Gets all the cookies of this request
     * @return array Array with
     */
    public function getCookies() {
        return $this->cookies;
    }

    /**
     * Gets a HTTP header value
     * @param string $name Name of the header
     * @return string|array|null The value of the header, an array of values if
     * the header is set multiple times, null if not set
     * @see pallo\library\http\Header
     */
    public function getHeader($name) {
        if (!$this->headers->hasHeader($name)) {
            return null;
        }

        $header = $this->headers->getHeader($name);

        if (!is_array($header)) {
            return $header->getValue();
        }

        $values = array();
        foreach ($header as $h) {
            $values[] = $h->getValue();
        }

        return $values;
    }

    /**
     * Returns the HTTP headers.
     * @return pallo\library\http\HeaderContainer The container of the HTTP
     * headers
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Gets a list of media types acceptable by the client browser.
     * @return array Array with the media type as key and the preferable order
     * as value
     */
    public function getAccept() {
        if (isset($this->accept)) {
            return $this->accept;
        }

        $header = $this->getHeader(Header::HEADER_ACCEPT);
        if (!$header) {
            return $this->accept = array();
        }

        return $this->accept = Header::parseAccept($header);
    }

    /**
     * Gets a list of charsets acceptable by the client browser.
     * @return array Array with the charset as key and the preferable order
     * as value
     */
    public function getAcceptCharset() {
        if (isset($this->acceptCharset)) {
            return $this->acceptCharset;
        }

        $header = $this->getHeader(Header::HEADER_ACCEPT_CHARSET);
        if (!$header) {
            return $this->acceptCharset = array();
        }

        return $this->acceptCharset = Header::parseAccept($header);
    }

    /**
     * Gets a list of encodings acceptable by the client browser.
     * @return array Array with the encoding as key and the preferable order
     * as value
     */
    public function getAcceptEncoding() {
        if (isset($this->acceptEncoding)) {
            return $this->acceptEncoding;
        }

        $header = $this->getHeader(Header::HEADER_ACCEPT_ENCODING);
        if (!$header) {
            return $this->acceptEncoding = array();
        }

        return $this->acceptEncoding = Header::parseAccept($header);
    }

    /**
     * Gets a list of languages acceptable by the client browser.
     * @return array Array with the language as key and the preferable order
     * as value
     */
    public function getAcceptLanguage() {
        if (isset($this->acceptLanguage)) {
            return $this->acceptLanguage;
        }

        $header = $this->getHeader(Header::HEADER_ACCEPT_LANGUAGE);
        if (!$header) {
            return $this->acceptLanguage = array();
        }

        return $this->acceptLanguage = Header::parseAccept($header);
    }

    /**
     * Gets the user agent
     * @return string
     */
    public function getUserAgent() {
        return $this->getHeader(Header::HEADER_USER_AGENT);
    }

    /**
     * Gets the if none match header
     * @return array
     */
    public function getIfNoneMatch() {
        if (isset($this->ifNoneMatch)) {
            return $this->ifNoneMatch;
        }

        $header = $this->getHeader(Header::HEADER_IF_NONE_MATCH);
        if (!$header) {
            return $this->ifNoneMatch = array();
        }

        return $this->ifNoneMatch = Header::parseIfMatch($header);
    }

    /**
     * Gets the timestamp of the conditional modified since header
     * @return integer|null The timestamp if the header was set, null otherwise
     */
    public function getIfModifiedSince() {
        if (isset($this->ifModifiedSince)) {
            return $this->ifModifiedSince;
        }

        $header = $this->getHeader(Header::HEADER_IF_MODIFIED_SINCE);
        if (!$header) {
            return $this->ifModifiedSince = null;
        }

        return $this->ifModifiedSince = Header::parseTime($header);
    }

    /**
     * Checks if the no-cache header is requested
     * @return boolean
     */
    public function isNoCache() {
        return $this->headers->getCacheControlDirective(HeaderContainer::CACHE_CONTROL_NO_CACHE);
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     * Taken from the Zend framework
     * @return boolean
     */
    public function isXmlHttpRequest() {
        $header = $this->getHeader(Header::HEADER_REQUEST_WITH);
        if (!$header) {
            return false;
        }

        return $header == self::XML_HTTP_REQUEST;
    }

    /**
     * Sets the session container
     * @param pallo\library\http\session\Session $session
     * @return null
     */
    public function setSession(Session $session = null) {
        $this->session = $session;
    }

    /**
     * Checks if a session has been set
     * @return boolean
     */
    public function hasSession() {
        return !empty($this->session);
    }

    /**
     * Gets the session container
     * @return pallo\library\http\session\Session
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * Parses a query string into an array
     * @param string $string A query string (eg var1=value&var2=value)
     * @return array Hierarchic array with the variables of the query string
     */
    protected function parseQueryString($string) {
        $query = array();

        $string = ltrim($string, '?');
        $tokens = explode('&', $string);

        foreach ($tokens as $token) {
            $positionEquals = strpos($token, '=');
            if ($positionEquals !== false) {
                // = found, we have a key and a value
                $key = substr($token, 0, $positionEquals);
                $value = substr($token, $positionEquals + 1);
            } else {
                // empty value
                $key = $token;
                $value = '';
            }

            // decode the values
            $key = rawurldecode($key);
            $value = rawurldecode($value);

            $positionBracket = strpos($key, '[');
            if ($positionBracket === false || $key[strlen($key) - 1] != ']') {
                // not an array
                $query[$key] = urldecode($value);

                continue;
            }

            // all the keys of a potential hierarchic array
            $keys = explode('][', substr($key, $positionBracket + 1, -1));
            $key = substr($key, 0, $positionBracket);
            array_unshift($keys, $key);

            $queryValue = &$query;

            $numKeys = count($keys) - 1;
            for ($i = 0; $i <= $numKeys; $i++) {
                $key = $keys[$i];

//                 if (!is_array($queryValue)) {
//                     $queryValue = array();
//                 }

                if ($key == '') {
                    $key = count($queryValue);
                }

                if ($i == $numKeys) {
                    $queryValue[$key] = urldecode($value);
                } elseif (!isset($queryValue[$key])) {
                    $queryValue[$key] = array();
                }

                $queryValue = &$queryValue[$key];
            }
        }

        return $query;
    }

}