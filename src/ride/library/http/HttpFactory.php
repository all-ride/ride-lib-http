<?php

namespace ride\library\http;

use ride\library\http\exception\HttpException;

use \DateTime;
use \ReflectionClass;
use \ReflectionException;

/**
 * Factory for HTTP objects
 */
class HttpFactory {

    /**
     * Full class name of the request object
     * @var string
     */
    protected $requestClass;

    /**
     * Full class name of the response object
     * @var string
     */
    protected $responseClass;

    /**
     * Sets the class name of the request object
     * @param string $requestClass Full class name of the request
     * @return null
     * @throws \ride\library\http\exception\HttpException when the provided
     * class is invalid or not a subclass of the Request class
     */
    public function setRequestClass($requestClass) {
        if (!is_string($requestClass) || !$requestClass) {
            throw new HttpException('Could not set the request class: provided class is not a string or empty');
        }

        try {
            $reflection = new ReflectionClass($requestClass);
            if (!$reflection->isSubclassOf('ride\\library\\http\\Request')) {
                throw new HttpException('Could not set the request class: ' . $requestClass . ' is not a subclass of ride\\library\\http\\Request');
            }
        } catch (ReflectionException $exception) {
            throw new HttpException('Could not set the request class: provided class is invalid', 0, $exception);
        }

        $this->requestClass = $requestClass;
    }

    /**
     * Gets the class name of the request object
     * @return string
     */
    public function getRequestClass() {
        return $this->requestClass ? $this->requestClass : 'ride\\library\\http\\Request';
    }

    /**
     * Sets the class name of the response object
     * @param string $responseClass Full class name of the response
     * @return null
     * @throws \ride\library\http\exception\HttpException when the provided
     * class is invalid or not a subclass of the Response class
     */
    public function setResponseClass($responseClass) {
        if (!is_string($responseClass) || !$responseClass) {
            throw new HttpException('Could not set the response class: provided class is not a string or empty');
        }

        try {
            $reflection = new ReflectionClass($responseClass);
            if (!$reflection->isSubclassOf('ride\\library\\http\\Response')) {
                throw new HttpException('Could not set the response class: ' . $responseClass . ' is not a subclass of ride\\library\\http\\Response');
            }
        } catch (ReflectionException $exception) {
            throw new HttpException('Could not set the response class: provided class is invalid', 0, $exception);
        }

        $this->responseClass = $responseClass;
    }

    /**
     * Gets the class name of the response object
     * @return string
     */
    public function getResponseClass() {
        return $this->responseClass ? $this->responseClass : 'ride\\library\\http\\Response';
    }

    /**
     * Sets the provided URL to the $_SERVER variable
     * @param string $url URL to translate into $_SERVER elements
     * @return boolean True when the URL has been set, false if the $_SERVER
     * elements are already set
     */
    public function setServerUrl($url) {
        if (!is_string($url)) {
            throw new HttpException('Could not set the server URL: provided URL is not a string');
        } elseif (isset($_SERVER['HTTP_HOST']) || isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $urlTokens = parse_url($url);
        if ($urlTokens === false || !isset($urlTokens['host'])) {
            throw new HttpException('Could not set the server URL: provided URL could not be parsed');
        }

        $_SERVER['HTTP_HOST'] = $urlTokens['host'] . (isset($urlTokens['port']) ? ':' . $urlTokens['port'] : '');
        $_SERVER['REQUEST_URI'] = isset($urlTokens['path']) ? $urlTokens['path'] . (isset($urlTokens['query']) ? '?' . $urlTokens['query'] : '') : '/';

        if (isset($urlTokens['scheme']) && $urlTokens['scheme'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
        }

        return true;
    }

    /**
     * Creates a cookie instance
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
     * @throws \ride\library\http\exception\HttpException when a invalid value
     * has been provided
     */
    public function createCookie($name, $value = null, $expires = 0, $domain = null, $path = null, $isSecure = false, $isHttpOnly = true) {
        return new Cookie($name, $value, $expires, $domain, $path, $isSecure, $isHttpOnly);
    }

    /**
     * Creates a cookie instance from a set-cookie header value
     * @param string $string Cookie string
     * @param string $domain Default domain
     * @return Cookie
     * @throws \ride\library\http\exception\HttpException when an invalid string
     * is provided
     */
    public function createCookieFromString($string, $domain = null) {
        $name = null;
        $value = null;
        $expires = 0;
        $path = null;
        $isSecure = false;
        $isHttpOnly = false;

        $tokens = explode(';', $string);
        foreach ($tokens as $token) {
            $token = trim($token);
            if (!$token) {
                continue;
            }

            $hasEquals = strpos($token, '=');

            if ($name === null && !$hasEquals) {
                throw new HttpException('Could not parse cookie: ' . $token . ' is not a valid attribute');
            } elseif (!$hasEquals) {
                $token = strtolower($token);
                switch ($token) {
                    case 'secure':
                        $isSecure = true;

                        break;
                    case 'httponly':
                        $isHttpOnly = true;

                        break;
                }
            } else {
                list($key, $val) = explode('=', $token, 2);
                if ($name === null) {
                    $name = $key;
                    $value = $val;

                    continue;
                }

                $key = strtolower($key);
                switch ($key) {
                    case 'expires':
                        $date = DateTime::createFromFormat('D, d-M-Y H:i:s T', $val);
                        if ($date) {
                            $expires = $date->getTimestamp();
                        }

                        break;
                    case 'domain':
                        $domain = $val;

                        break;
                    case 'path':
                        $path = $val;

                        break;
                }
            }
        }

        return $this->createCookie($name, $value, $expires, $domain, $path, $isSecure, $isHttpOnly);
    }

    /**
     * Creates a header container from the $_SERVER variable
     * @return HeaderContainer
     */
    public function createHeaderContainerFromServer() {
        $headers = new HeaderContainer();

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers->setHeader(Header::HEADER_CONTENT_TYPE, $_SERVER['CONTENT_TYPE']);
        }

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) != 'HTTP_' || !$value) {
                continue;
            }

            $headers->setHeader(substr($key, 5), $value);
        }

        return $headers;
    }

    /**
     * Creates a request manually
     * @param string $path Path of the request
     * @param string $method Method of the request
     * @param string $protocol Protocol of the Request
     * @param \ride\library\http\HeaderContainer $headers
     * @param string|array $body
     * @param string $isSecure
     * @return Request
     */
    public function createRequest($path = '/', $method = null, $protocol = null, $headers = null, $body = null, $isSecure = null) {
        if (!$method) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                $method = $_SERVER['REQUEST_METHOD'];
            } else {
                $method = Request::METHOD_GET;
            }
        }

        if (!$protocol) {
            if (isset($_SERVER['SERVER_PROTOCOL'])) {
                $protocol = $_SERVER['SERVER_PROTOCOL'];
            } else {
                $protocol = 'HTTP/1.0';
            }
        }

        if (!$headers) {
            $headers = $this->createHeaderContainerFromServer();
        }

        $class = $this->getRequestClass();
        $request = new $class($path, $method, $protocol, $headers, $body, $isSecure);

        return $request;
    }

    /**
     * Creates a request from a raw request string
     * @param string $data Raw HTTP request
     * @return Request
     */
    public function createRequestFromString($data) {
        $data = explode("\n", $data);

        $command = array_shift($data);
        list($method, $path, $protocol) = explode(' ', $command);

        $protocol = trim($protocol);

        $headers = new HeaderContainer();
        do {
            $header = array_shift($data);
            $header = trim($header);
            if (!$header) {
                continue;
            }

            list($name, $value) = explode(': ', $header, 2);

            $headers->addHeader($name, $value);
        } while ($header !== '' && $header !== null);

        $body = implode("\n", $data);

        return $this->createRequest($path, $method, $protocol, $headers, $body);
    }

    /**
     * Creates a request from the $_SERVER variable
     * @return Request
     */
    public function createRequestFromServer() {
        if (isset($_SERVER['REQUEST_URI'])) {
            $path = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['SCRIPT_NAME'])) {
            $path = '/' . $_SERVER['SCRIPT_NAME'];
        } else {
            $path = '/';
        }

        if ($_POST) {
            $body = $_POST;
        } else {
            $body = file_get_contents('php://input');
        }

        if (!$body) {
            $body = array();
        }

        if ($_FILES && is_array($body)) {
            $body = $this->mergeFiles($body, $_FILES);
        }

        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || (isset($_SERVER['HTTP_SCHEME']) && $_SERVER['HTTP_SCHEME'] == 'https')) {
            $isSecure = true;
        } else {
            $isSecure = false;
        }

        return $this->createRequest($path, null, null, null, $body, $isSecure);
    }

    /**
     * Merge and normalize the files array with the provided data
     * @param array $data Submitted form data
     * @param array $files File upload definitions ($_FILES)
     * @return array Provided data merged with the file uploads
     */
    protected function mergeFiles(array $data, array $files) {
        $originalData = $data;

        foreach ($files as $index => $attributes) {
            if (!is_array(reset($attributes))) {
                $data[$index] = $attributes;

                continue;
            }

            foreach ($attributes as $attribute => $nestedAttributes) {
                if (!isset($data[$index]) || !is_array($data[$index])) {
                    $data[$index] = array();
                }

                $data[$index] = $this->normalizeFileAttributes($data[$index], $nestedAttributes, $attribute);
            }
        }

        $this->restoreNoUploadValues($data, $originalData);

        return $data;
    }

    /**
     * Adds the attributes to the data array and sets the actual value a level
     * deeper with the attribute as key
     * @param array $data
     * @param array $values
     * @param string $attribute
     * @return array Provided data with the values in the attribute key
     */
    protected function normalizeFileAttributes(array $data, array $values, $attribute) {
        foreach ($values as $key => $value) {
            if (!isset($data[$key]) || is_string($data[$key])) {
                $data[$key] = array();
            }

            if (is_array($value)) {
                $data[$key] = $this->normalizeFileAttributes($data[$key], $value, $attribute);
            } else {
                $data[$key][$attribute] = $value;
            }
        }

        return $data;
    }

    /**
     * Restore the no file uploads with posted value with the same name if
     * applicable
     * @param array $data Body with the files merged into
     * @param array $files File upload definitions ($_FILES)
     * @param $original Original submitted values ($_POST)
     * @return null
     */
    protected function restoreNoUploadValues(&$data, $original) {
        foreach ($data as $index => $values) {
            $issetOriginal = isset($original[$index]);

            if (isset($values['error']) && $values['error'] == UPLOAD_ERR_NO_FILE && $values['size'] === 0 && $issetOriginal) {
                $data[$index] = $original[$index];
            } elseif (is_array($values)) {
                $this->restoreNoUploadValues($data[$index], $issetOriginal ? $original[$index] : null);
            }
        }
    }

    /**
     * Creates a response
     * @return \ride\library\http\Response
     */
    public function createResponse() {
        $class = $this->getResponseClass();

        return new $class();
    }

    /**
     * Creates a object from a raw HTTP response
     * @param string $data Raw HTTP response
     * @param string $lineBreak Line break of the response
     * @return \ride\library\http\Response
     * @throws \ride\library\http\exception\HttpException when the raw HTTP
     * response is not valid
     */
    public function createResponseFromString($data, $lineBreak = "\r\n") {
        if (!is_string($data) || $data == '') {
            throw new HttpException('Could not parse the response: no HTTP response');
        }

        $response = $this->createResponse();

        $lines = explode($lineBreak, $data);

        // get the status code
        $status = array_shift($lines);

        preg_match('#^HTTP/.* ([0-9]{3,3})( (.*))?#i', $status, $matches);
        if (isset($matches[1])) {
            $response->setStatusCode((integer) $matches[1]);
        } else {
            throw new HttpException('Could not parse the response: no HTTP response');
        }

        // get the headers
        $emptyLine = false;
        while (!$emptyLine) {
            $line = array_shift($lines);
            $line = trim($line);

            if (!$line) {
                $emptyLine = true;

                continue;
            }

            $position = strpos($line, ':');
            if (!$position) {
                throw new HttpException('Could not parse the response: "' . $line . '" is not a valid header string');
            }

            list($name, $value) = explode(':', $line, 2);
            $name = trim($name);
            $value = trim($value);

            $response->addHeader($name, $value);
        }

        // get the content
        $body = '';
        while ($lines) {
            $line = array_shift($lines);
            $body .= $line . $lineBreak;
        }

        $body = substr($body, 0, strlen($lineBreak) * -1);

        $response->setBody($body);

        return $response;
    }

    /**
     * Creates a data URI instance
     * @param mixed $data Actual data
     * @param string|null $mime MIME type of the data
     * @param string|null $encoding Charset encoding of the data
     * @param boolean $isBase64 Flag to see if the data should be (or was)
     * base64 encoded
     * @return DataUri
     */
    public function createDataUri($data, $encoding = null, $mime = null, $isBase64 = false) {
        return new DataUri($data, $encoding, $mime, $isBase64);
    }

    /**
     * Create a data URI instance from a data URI string
     * @param string $data Data URI string
     * @return DataUri
     */
    public function createDataUriFromString($data) {
        return DataUri::decode($data);
    }

}
