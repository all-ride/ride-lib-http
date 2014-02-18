<?php

namespace pallo\library\http;

use pallo\library\http\exception\HttpException;

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
     * @throws pallo\library\http\exception\HttpException when the provided
     * class is invalid or not a subclass of the Request class
     */
    public function setRequestClass($requestClass) {
        if (!is_string($requestClass) || !$requestClass) {
            throw new HttpException('Could not set the request class: provided class is not a string or empty');
        }

        try {
            $reflection = new ReflectionClass($requestClass);
            if (!$reflection->isSubclassOf('pallo\\library\\http\\Request')) {
                throw new HttpException('Could not set the request class: ' . $requestClass . ' is not a subclass of pallo\\library\\http\\Request');
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
        return $this->requestClass ? $this->requestClass : 'pallo\\library\\http\\Request';
    }

    /**
     * Sets the class name of the response object
     * @param string $responseClass Full class name of the response
     * @return null
     * @throws pallo\library\http\exception\HttpException when the provided
     * class is invalid or not a subclass of the Response class
     */
    public function setResponseClass($responseClass) {
        if (!is_string($responseClass) || !$responseClass) {
            throw new HttpException('Could not set the response class: provided class is not a string or empty');
        }

        try {
            $reflection = new ReflectionClass($responseClass);
            if (!$reflection->isSubclassOf('pallo\\library\\http\\Response')) {
                throw new HttpException('Could not set the response class: ' . $responseClass . ' is not a subclass of pallo\\library\\http\\Response');
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
        return $this->responseClass ? $this->responseClass : 'pallo\\library\\http\\Response';
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

        $class = $this->getRequestClass();

        return new $class($path, $method, $protocol, $headers, $body);
    }

    /**
     * Creates a request from the $_SERVER variable
     * @return Request
     */
    public function createRequestFromServer() {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        } else {
            $method = Request::METHOD_GET;
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $path = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['SCRIPT_NAME'])) {
            $path = '/' . $_SERVER['SCRIPT_NAME'];
        } else {
            $path = '/';
        }

        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        } else {
            $protocol = 'HTTP/1.0';
        }

        $headers = $this->createHeaderContainerFromServer();

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

        $class = $this->getRequestClass();

        $request = new $class($path, $method, $protocol, $headers, $body);

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $request->setIsSecure(true);
        }

        return $request;
    }

    /**
     * Merge and normalize the files array with the provided data
     * @param array $data Submitted form data
     * @param array $files File upload definitions ($_FILES)
     * @return array Provided data merged with the file uploads
     */
    protected function mergeFiles(array $data, array $files) {
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
            if (!isset($data[$key])) {
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
     * Creates a response
     * @return pallo\library\http\Response
     */
    public function createResponse() {
        $class = $this->getResponseClass();

        return new $class();
    }

    /**
     * Creates a object from a raw HTTP response
     * @param string $data Raw HTTP response
     * @param string $lineBreak Line break of the response
     * @return pallo\library\http\Response
     * @throws pallo\library\http\exception\HttpException when the raw HTTP
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

}