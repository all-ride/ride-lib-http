<?php

namespace ride\library\http;

use ride\library\http\exception\HttpException;

/**
 * Data container for a HTTP response
 */
class Response {

    /**
     * HTTP status code for a continue status
     * @var int
     */
    const STATUS_CODE_CONTINUE = 100;

    /**
     * HTTP status code for a switching protocols status
     * @var int
     */
    const STATUS_CODE_SWITCHING_PROTOCOLS = 101;

    /**
     * HTTP status code for a ok status
     * @var int
     */
    const STATUS_CODE_OK = 200;

    /**
     * HTTP status code for a created status
     * @var int
     */
    const STATUS_CODE_CREATED = 201;

    /**
     * HTTP status code for a accepted status
     * @var int
     */
    const STATUS_CODE_ACCEPTED = 202;

    /**
     * HTTP status code for a non-authoritative information status
     * @var int
     */
    const STATUS_CODE_NON_AUTHORITATIVE_INFORMATION = 203;

    /**
     * HTTP status code for a no content status
     * @var int
     */
    const STATUS_CODE_NO_CONTENT = 204;

    /**
     * HTTP status code for a reset content status
     * @var int
     */
    const STATUS_CODE_RESET_CONTENT = 205;

    /**
     * HTTP status code for a partial content status
     * @var int
     */
    const STATUS_CODE_PARTIAL_CONTENT = 206;

    /**
     * HTTP status code for a multiple choices status
     * @var int
     */
    const STATUS_CODE_MULTIPLE_CHOICES = 300;

    /**
     * HTTP status code for a moved permanently status
     * @var int
     */
    const STATUS_CODE_MOVED_PERMANENTLY = 301;

    /**
     * HTTP status code for a found status
     * @var int
     */
    const STATUS_CODE_FOUND = 302;

    /**
     * HTTP status code for a see other status
     * @var int
     */
    const STATUS_CODE_SEE_OTHER = 303;

    /**
     * HTTP status code for a not modified status
     * @var int
     */
    const STATUS_CODE_NOT_MODIFIED = 304;

    /**
     * HTTP status code for a use proxy status
     * @var int
     */
    const STATUS_CODE_USE_PROXY = 305;

    /**
     * HTTP status code for a temporary redirect status
     * @var int
     */
    const STATUS_CODE_TEMPORARY_REDIRECT = 307;

    /**
     * HTTP status code for a bad request status
     * @var int
     */
    const STATUS_CODE_BAD_REQUEST = 400;

    /**
     * HTTP status code for a unauthorized status
     * @var int
     */
    const STATUS_CODE_UNAUTHORIZED = 401;

    /**
     * HTTP status code for a payment required status
     * @var int
     */
    const STATUS_CODE_PAYMENT_REQUIRED = 402;

    /**
     * HTTP status code for a forbidden status
     * @var int
     */
    const STATUS_CODE_FORBIDDEN = 403;

    /**
     * HTTP status code for a not found status
     * @var int
     */
    const STATUS_CODE_NOT_FOUND = 404;

    /**
     * HTTP status code for a method not allowed status
     * @var int
     */
    const STATUS_CODE_METHOD_NOT_ALLOWED = 405;

    /**
     * HTTP status code for a conflict status
     * @var int
     */
    const STATUS_CODE_CONFLICT = 409;

    /**
     * HTTP status code for a gone status
     * @var int
     */
    const STATUS_CODE_GONE = 410;

    /**
     * HTTP status code for a unsupported media type status
     * @var int
     */
    const STATUS_CODE_UNSUPPORTED_MEDIA_TYPE = 415;

    /**
     * HTTP status code for a unprocessable entitiy status
     * @var int
     */
    const STATUS_CODE_UNPROCESSABLE_ENTITY = 422;

    /**
     * HTTP status code for a update required status
     * @var int
     */
    const STATUS_CODE_UPDATE_REQUIRED = 426;

    /**
     * HTTP status code for a too many requests status
     * @var int
     */
    const STATUS_CODE_TOO_MANY_REQUESTS = 429;

    /**
     * HTTP status code for a server error status
     * @var int
     */
    const STATUS_CODE_SERVER_ERROR = 500;

    /**
     * HTTP status code for a unimplemented status
     * @var int
     */
    const STATUS_CODE_NOT_IMPLEMENTED = 501;

    /**
     * HTTP status code for a unavailable service status
     * @var int
     */
    const STATUS_CODE_SERVICE_UNAVAILABLE = 503;

    /**
     * The HTTP response status code
     * @var int
     */
    protected $statusCode;

    /**
     * Container of the headers assigned to this response
     * @var \ride\library\http\HeaderContainer
     */
    protected $headers;

    /**
     * Array with Cookie objects
     * @var array
     */
    protected $cookies;

    /**
     * The timestamp of the last modified date of the content
     * @var integer
     */
    protected $dateLastModified;

   /**
     * The body of this response
     * @var string
     */
    protected $body;

    /**
     * Construct a new response
     * @return null
     */
    public function __construct() {
        $this->statusCode = self::STATUS_CODE_OK;

        $this->dateLastModified = null;

        $this->headers = new HeaderContainer();
        $this->headers->setHeader(Header::HEADER_DATE, Header::parseTime(time()));

        $this->cookies = array();

        $this->body = null;
    }

    /**
     * Gets a string representation of this response
     * @return string
     */
    public function __toString() {
        $response = $this->statusCode;

        $statusPhrase = self::getStatusPhrase($this->statusCode, null);
        if ($statusPhrase) {
            $response .= ' ' . $statusPhrase;
        }

        $response .= "\r\n";

        foreach ($this->headers as $header) {
            $response .= (string) $header . "\r\n";
        }

        foreach ($this->cookies as $cookie) {
            $response .= Header::HEADER_SET_COOKIE . ': ' . $cookie . "\r\n";
        }

        if ($this->body) {
            $response .= "\r\n" . $this->body;
        }

        return $response;
    }

    /**
     * Sets the HTTP status code. At Wikipedia you can find a
     * {@link http://en.wikipedia.org/wiki/List_of_HTTP_status_codes list of HTTP status codes}.
     * @param integer $statusCode The HTTP status code
     * @return null
     * @see STATUS_CODE_OK, STATUS_CODE_MOVED_PERMANENTLY, STATUS_CODE_FOUND,
     * STATUS_CODE_NOT_MODIFIED, STATUS_CODE_NOT_FOUND
     * @throws Exception when the provided response code is not a
     * valid reponse code
     */
    public function setStatusCode($statusCode) {
        if (!is_integer($statusCode) || $statusCode < 100 || $statusCode > 599) {
            throw new HttpException('Could not set status code: provided code is an invalid status code');
        }

        $this->statusCode = $statusCode;
    }

    /**
     * Returns the current HTTP status code.
     * @return integer
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * Sets the status code to 200 Ok
     * @return null
     */
    public function setOk() {
        $this->statusCode = self::STATUS_CODE_OK;
    }

    /**
     * Gets whether the status code is 200 Ok
     * @return boolean
     */
    public function isOk() {
        return $this->statusCode == self::STATUS_CODE_OK;
    }

    /**
     * Sets the status code to 400 Bad Request
     * @return null
     */
    public function setBadRequest() {
        $this->statusCode = self::STATUS_CODE_BAD_REQUEST;
    }

    /**
     * Gets whether the status code is 400 Bad Request
     * @return null
     */
    public function isBadRequest() {
        return $this->statusCode == self::STATUS_CODE_BAD_REQUEST;
    }

    /**
     * Sets whether the status code is 403 Forbidden
     * @return boolean
     */
    public function setForbidden() {
        $this->statusCode = self::STATUS_CODE_FORBIDDEN;
    }

    /**
     * Gets whether the status code is 403 Forbidden
     * @return boolean
     */
    public function isForbidden() {
        return $this->statusCode == self::STATUS_CODE_FORBIDDEN;
    }

    /**
     * Sets the status code to 404 Not Found
     * @return null
     */
    public function setNotFound() {
        $this->statusCode = self::STATUS_CODE_NOT_FOUND;
    }

    /**
     * Gets whether the status code is 404 Not Found
     * @return boolean
     */
    public function isNotFound() {
        return $this->statusCode == self::STATUS_CODE_NOT_FOUND;
    }

    /**
     * Sets the status code to 405 Method Not Allowed
     * @return null
     */
    public function setMethodNotAllowed() {
        $this->statusCode = self::STATUS_CODE_METHOD_NOT_ALLOWED;
    }

    /**
     * Gets whether the status code is 405 Method Not Allowed
     * @return boolean
     */
    public function isMethodNotAllowed() {
        return $this->statusCode == self::STATUS_CODE_METHOD_NOT_ALLOWED;
    }

    /**
     * Adds a HTTP header.
     *
     * On Wikipedia you can find a {@link http://en.wikipedia.org/wiki/List_of_HTTP_headers list of HTTP headers}.
     * If a Locaton header is added, the status code will also be automatically
     * set to 302 Found if the current status code is 200 OK.
     * @param string $name the name of the header
     * @param string $value the value of the header
     * @return null
     * @throws Exception when the provided name is empty or invalid
     * @throws Exception when the provided value is empty or invalid
     * @see setHeader()
     */
    public function addHeader($name, $value) {
        $header = new Header($name, $value);

        $name = $header->getName();

        if (in_array($name, array(Header::HEADER_DATE, Header::HEADER_LOCATION))) {
            $this->headers->setHeader($header);
        } else {
            $this->headers->addHeader($header);
        }
    }

    /**
     * Sets a HTTP header, replacing any previously added HTTP headers with
     * the same name.
     * @param string $name the name of the header
     * @param string $value the value of the header
     * @return null
     * @throws Exception when the provided name is empty or invalid
     * @throws Exception when the provided value is empty or invalid
     * @see addHeader()
     */
    public function setHeader($name, $value) {
        $this->headers->removeHeader($name);

        $this->addHeader($name, $value);
    }

    /**
     * Checks if a header is set
     * @param string $name The name of the header
     * @return boolean True if the header is set, false otherwise
     */
    public function hasHeader($name) {
        return $this->headers->hasHeader($name);
    }

    /**
    * Gets a HTTP header value
    * @param string $name Name of the header
    * @param mixed $default Default value returned when the header is not set
    * @return string|array|null Value of the header, an array of values if the
    * header is set multiple times, provided default value if the header is not
    * set
    * @see \ride\library\http\Header
    */
    public function getHeader($name, $default = null) {
        $header = $this->headers->getHeader($name);
        if (!$header) {
            return $default;
        }

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
     * @return \ride\library\http\HeaderContainer The container of the HTTP headers
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Removes a HTTP header.
     * @param string $name the name of the header you want to remove
     * @return null
     * @throws Exception when the provided name is empty or invalid
     */
    public function removeHeader($name) {
        $this->headers->removeHeader($name);
    }

    /**
     * Sets a cookie
     * @param Cookie $cookie
     * @return null
     */
    public function setCookie(Cookie $cookie) {
        $this->cookies[$cookie->getName()] = $cookie;
    }

    /**
     * Gets a cookie by name
     * @param string $name Name of the cookie
     * @return Cookie|null Instance of the cookie or null if the cookie was not
     * set
     */
    public function getCookie($name) {
        if (!isset($this->cookies[$name])) {
            return null;
        }

        return $this->cookies[$name];
    }

    /**
     * Gets all the cookies
     * @return array Array with the name of the cookie as key and a instance of
     * Cookie as value
     * @see Cookie
     */
    public function getCookies() {
        return $this->cookies;
    }

    /**
     * Sets the HTTP headers required to make the browser redirect.
     * @param string $url The URL to redirect to
     * @param string $statusCode The status code for this redirect
     * @return null
     * @throws Exception when the provided status code is not a
     * valid redirect status code
     */
    public function setRedirect($url, $statusCode = null) {
        if ($statusCode === null) {
            $statusCode = self::STATUS_CODE_FOUND;
        }

        if (!is_integer($statusCode) || $statusCode < 300 || 400 <= $statusCode) {
            throw new HttpException('Could not set redirect: invalid status code provided');
        }

        $this->setStatusCode($statusCode);
        $this->setHeader(Header::HEADER_LOCATION, $url);
    }

    /**
     * Checks if the response will redirect
     * @return boolean True if the status code is a redirect code, false otherwise
     */
    public function willRedirect() {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Removes the HTTP headers that cause the browser to redirect.
     *
     * The HTTP status code of the response will be reset to 200 OK.
     * @return null
     */
    public function clearRedirect() {
        if (!$this->willRedirect()) {
            return;
        }

        $this->headers->removeHeader(Header::HEADER_LOCATION);

        $this->statusCode = self::STATUS_CODE_OK;
    }

    /**
     * Gets the location header
     * @return string|null
     */
    public function getLocation() {
        return $this->getHeader(Header::HEADER_LOCATION);
    }

    /**
     * Sets the date the content will become stale
     * @param integer $timestamp Timestamp of the date
     * @return null
     * @throws \ride\library\http\exception\HttpException when the provided
     * timestamp is invalid
     */
    public function setExpires($timestamp = null) {
        if ($timestamp === null) {
            $this->headers->removeHeader(Header::HEADER_EXPIRES);

            return;
        }

        if (!is_long($timestamp) || $timestamp < 0) {
            throw new HttpException('Could not set expires header: invalid timestamp provided');
        }

        $this->headers->setHeader(Header::HEADER_EXPIRES, Header::parseTime($timestamp));
    }

    /**
     * Gets the date the content was become stale
     * @return integer|null Timestamp of the date if set, null otherwise
     */
    public function getExpires() {
        $header = $this->headers->getHeader(Header::HEADER_EXPIRES);
        if (!$header) {
            return null;
        }

        return Header::parseTime($header->getValue());
    }

    /**
     * Sets or unsets the public cache control directive.
     *
     * When set to true, all caches may cache the response.
     * @param boolean $flag Set to false to unset the directive, true sets it
     * @return null
     */
    public function setIsPublic($flag = true) {
        $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PRIVATE);
        if ($flag) {
            $this->headers->addCacheControlDirective(HeaderContainer::CACHE_CONTROL_PUBLIC);
        } else {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PUBLIC);
        }
    }

    /**
     * Gets the public cache control directive
     * @return boolean|null True if set, null otherwise
     */
    public function isPublic() {
        return $this->headers->getCacheControlDirective(HeaderContainer::CACHE_CONTROL_PUBLIC);
    }

    /**
     * Sets or unsets the private cache control directive
     *
     * When set to true, a shared cache must not cache the response.
     * @param boolean $flag Set to false to unset the directive, true or any value sets it
     * @return null
     */
    public function setIsPrivate($flag = true) {
        $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PUBLIC);
        if ($flag !== false) {
            $this->headers->addCacheControlDirective(HeaderContainer::CACHE_CONTROL_PRIVATE, $flag);
        } else {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PRIVATE);
        }
    }

    /**
     * Gets the private cache control directive
     * @return boolean|string|null True or the field if set, null otherwise
     */
    public function isPrivate() {
        return $this->headers->getCacheControlDirective(HeaderContainer::CACHE_CONTROL_PRIVATE);
    }

    /**
     * Sets or unsets the no-cache cache control directive.
     *
     * When set to true, all caches cannot cache the response.
     * @param boolean $flag Set to false to unset the directive, true sets it
     * @return null
     */
    public function setIsNoCache($flag = true) {
        if ($flag) {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PUBLIC);
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PRIVATE);
            $this->headers->addCacheControlDirective(HeaderContainer::CACHE_CONTROL_NO_CACHE);
        } else {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_NO_CACHE);
        }
    }

    /**
     * Gets the no-cache cache control directive
     * @return boolean|null True if set, null otherwise
     */
    public function isNoCache() {
        return $this->headers->getCacheControlDirective(HeaderContainer::CACHE_CONTROL_NO_CACHE);
    }

    /**
     * Sets or unsets the no-store cache control directive.
     *
     * When set to true, all caches cannot cache the response.
     * @param boolean $flag Set to false to unset the directive, true sets it
     * @return null
     */
    public function setIsNoStore($flag = true) {
        if ($flag) {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PUBLIC);
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_PRIVATE);
            $this->headers->addCacheControlDirective(HeaderContainer::CACHE_CONTROL_NO_STORE);
        } else {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_NO_STORE);
        }
    }

    /**
     * Gets the no-store cache control directive
     * @return boolean|null True if set, null otherwise
     */
    public function isNoStore() {
        return $this->headers->getCacheControlDirective(HeaderContainer::CACHE_CONTROL_NO_STORE);
    }

    /**
     * Sets the max age cache control directive
     *
     * When set to true, a shared cache must not cache the response.
     * @param boolean $flag Set to false to unset the directive, true or any value sets it
     * @return null
     */
    public function setMaxAge($seconds = null) {
        if ($seconds === null) {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_MAX_AGE);

            return;
        }

        if (!is_long($seconds) || $seconds < 0) {
            throw new HttpException('Could not set the max age: value should be a unsigned long');
        }

        $this->headers->addCacheControlDirective(HeaderContainer::CACHE_CONTROL_MAX_AGE, $seconds);
    }

    /**
     * Gets the max age cache control directive
     * @return integer|null Seconds if set, null otherwise
     */
    public function getMaxAge() {
        return $this->headers->getCacheControlDirective(HeaderContainer::CACHE_CONTROL_MAX_AGE);
    }

    /**
     * Sets the shared max age cache control directive
     *
     * This will make your response public
     * @param boolean $flag Set to false to unset the directive, true or any value sets it
     * @return null
     * @see setIsPublic()
     */
    public function setSharedMaxAge($seconds = null) {
        if ($seconds === null) {
            $this->headers->removeCacheControlDirective(HeaderContainer::CACHE_CONTROL_SHARED_MAX_AGE);

            return;
        }

        if (!is_long($seconds) || $seconds < 0) {
            throw new HttpException('Could not set shared max age: value should be a unsigned long');
        }

        $this->headers->addCacheControlDirective(HeaderContainer::CACHE_CONTROL_SHARED_MAX_AGE, $seconds);
    }

    /**
     * Gets the shared max age cache control directive
     * @return integer|null Seconds if set, null otherwise
     */
    public function getSharedMaxAge() {
        return $this->headers->getCacheControlDirective(HeaderContainer::CACHE_CONTROL_SHARED_MAX_AGE);
    }

    /**
     * Sets the date the content was last modified
     * @param integer $timestamp Timestamp of the date
     * @return null
     */
    public function setLastModified($timestamp = null) {
        if ($timestamp === null) {
            $this->dateLastModified = null;
            $this->headers->removeHeader(Header::HEADER_LAST_MODIFIED);

            return;
        }

        if (!is_numeric($timestamp) || $timestamp <= 0) {
            throw new HttpException('Could not set last modified header: invalid timestamp provided');
        }

        $this->dateLastModified = $timestamp;

        $this->headers->setHeader(Header::HEADER_LAST_MODIFIED, Header::parseTime($timestamp));
    }

    /**
     * Gets the date the content was last modified
     * @return integer|null Timestamp of the date if set, null otherwise
     */
    public function getLastModified() {
        return $this->dateLastModified;
    }

    /**
     * Sets the ETag
     * @param string $eTag A unique identifier of the current version of
     * the content
     * @return null
     */
    public function setETag($eTag = null) {
        if ($eTag === null) {
            $this->headers->removeHeader(Header::HEADER_ETAG);
        } else {
            $this->headers->setHeader(Header::HEADER_ETAG, $eTag);
        }
    }

    /**
     * Gets the ETag
     * @return null|string A unique identifier of the current version of
     * the content if set, null otherwise
     */
    public function getETag() {
        $header = $this->headers->getHeader(Header::HEADER_ETAG);

        if (!$header) {
            return null;
        }

        return $header->getValue();
    }

    /**
     * Checks if the current status is not modified. If the status code is set
     * @param \ride\library\http\Request $request
     * @return boolean True if the content is not modified, false otherwise
     */
    public function isNotModified(Request $request) {
        $noneMatch = $request->getIfNoneMatch();
        $modifiedSince = $request->getIfModifiedSince();

        $eTag = $this->getETag();

        $isNoneMatch = !$noneMatch || isset($noneMatch['*']) || ($eTag && isset($noneMatch[$eTag]));
        $isModifiedSince = !$modifiedSince || $this->getLastModified() == $modifiedSince;

        $isNotModified = false;
        if ($noneMatch && $modifiedSince) {
            $isNotModified = $isNoneMatch && $isModifiedSince;
        } elseif ($noneMatch) {
            $isNotModified = $isNoneMatch;
        } elseif ($modifiedSince) {
            $isNotModified = $isModifiedSince;
        }

        return $isNotModified;
    }

    /**
     * Sets the response status code to not modified and removes illegal
     * headers for such a response code
     * @return null
     */
    public function setNotModified() {
        $this->setStatusCode(self::STATUS_CODE_NOT_MODIFIED);
        $this->setBody(null);

        $removeHeaders = array(
            Header::HEADER_ALLOW,
            Header::HEADER_CONTENT_ENCODING,
            Header::HEADER_CONTENT_LANGUAGE,
            Header::HEADER_CONTENT_LENGTH,
            Header::HEADER_CONTENT_MD5,
            Header::HEADER_CONTENT_TYPE,
            Header::HEADER_LAST_MODIFIED,
        );

        $this->headers->removeHeader($removeHeaders);
    }

    /**
     * Sets the body of this response.
     * @param string $body The body
     * @return null
     */
    public function setBody($body = null) {
        $this->body = $body;
    }

    /**
     * Returns the body of this response
     * @return string The body
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Sends the response to the client
     * @param Request $request Request to respond to
     * @return null
     */
    public function send(Request $request) {
        $this->sendHeaders($request->getProtocol());

        if ($this->willRedirect()) {
            return;
        }

        echo $this->body;
    }

    /**
     * Sets the status code and sends the headers to the client
     * @param string $protocol HTTP protocol to use
     * @return null
     * @throws Exception when the output already started
     * @see \ride\library\http\Header
     */
    protected function sendHeaders($protocol) {
        if (!$this->headers->hasHeaders() && $this->statusCode === Response::STATUS_CODE_OK) {
            return;
        }

        if (headers_sent($file, $line)) {
            throw new HttpException('Cannot send headers, output already started in ' . $file . ' on line ' . $line);
        }

        // set the status code
        header($protocol . ' ' . $this->statusCode . ' ' . self::getStatusPhrase($this->statusCode));

        // set the headers
        foreach ($this->headers as $header) {
            if ($header->getName() === Header::HEADER_LOCATION) {
                header((string) $header, true, $this->statusCode);
            } else {
                header((string) $header, false);
            }
        }

        // set the cookies
        foreach ($this->cookies as $cookie) {
            header(Header::HEADER_SET_COOKIE . ': ' . $cookie, false);
        }
    }

    /**
     * Gets the status phrase for the provided status code
     * @param integer $statusCode HTTP response status code
     * @param string $default Default phrase
     * @return string HTTP response status phrase
     */
    public static function getStatusPhrase($statusCode, $default = 'Unknown Status') {
        $statusPhrases = array(
            self::STATUS_CODE_CONTINUE => 'Continue', // 100
            self::STATUS_CODE_SWITCHING_PROTOCOLS => 'Switching Protocols', // 101
            self::STATUS_CODE_OK => 'OK', // 200
            self::STATUS_CODE_CREATED => 'Created', // 201
            self::STATUS_CODE_ACCEPTED => 'Accepted', // 202
            self::STATUS_CODE_NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information', // 203
            self::STATUS_CODE_NO_CONTENT => 'No Content', // 204
            self::STATUS_CODE_RESET_CONTENT => 'Reset Content', // 205
            self::STATUS_CODE_PARTIAL_CONTENT => 'Partial Content', // 206
            self::STATUS_CODE_MULTIPLE_CHOICES => 'Multiple Choices', // 300
            self::STATUS_CODE_MOVED_PERMANENTLY => 'Moved Permanently', // 301
            self::STATUS_CODE_FOUND => 'Found', // 302
            self::STATUS_CODE_SEE_OTHER => 'See Other', // 303
            self::STATUS_CODE_NOT_MODIFIED => 'Not Modified', // 304
            self::STATUS_CODE_USE_PROXY => 'Use Proxy', // 305
            self::STATUS_CODE_TEMPORARY_REDIRECT => 'Temporary Redirect', // 307
            self::STATUS_CODE_BAD_REQUEST => 'Bad Request', // 400
            self::STATUS_CODE_UNAUTHORIZED => 'Unauthorized', // 401
            self::STATUS_CODE_PAYMENT_REQUIRED => 'Payment Required', // 402
            self::STATUS_CODE_FORBIDDEN => 'Forbidden', // 403
            self::STATUS_CODE_NOT_FOUND => 'Not Found', // 404
            self::STATUS_CODE_METHOD_NOT_ALLOWED => 'Method Not Allowed', //405
            self::STATUS_CODE_CONFLICT => 'Conflict', //409
            self::STATUS_CODE_GONE => 'Gone', //410
            self::STATUS_CODE_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type', //415
            self::STATUS_CODE_UNPROCESSABLE_ENTITY => 'Unprocessable Entity', //422
            self::STATUS_CODE_UPDATE_REQUIRED => 'Update Required', //426
            self::STATUS_CODE_TOO_MANY_REQUESTS => 'Too Many Requests', //429
            self::STATUS_CODE_SERVER_ERROR => 'Internal Server Error', // 500
            self::STATUS_CODE_NOT_IMPLEMENTED => 'Not Implemented', //501
            self::STATUS_CODE_SERVICE_UNAVAILABLE => 'Service Unavailable', //503
        );

        if (!isset($statusPhrases[$statusCode])) {
            return $default;
        }

        return $statusPhrases[$statusCode];
    }

}
