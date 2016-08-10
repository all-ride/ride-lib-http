<?php

namespace ride\library\http;

use \PHPUnit_Framework_TestCase;

class HttpFactoryTest extends PHPUnit_Framework_TestCase {

    protected $httpFactory;

    protected function setUp() {
        $this->httpFactory = new HttpFactory();
    }

    public function testSetRequestClass() {
        $class = 'ride\\library\\http\\TestRequest';

        $this->httpFactory->setRequestClass($class);

        $this->assertEquals($class, $this->httpFactory->getRequestClass());
    }

    /**
     * @dataProvider providerSetRequestClassThrowsExceptionWhenInvalidClassProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetRequestClassThrowsExceptionWhenInvalidClassProvided($class) {
        $this->httpFactory->setRequestClass($class);
    }

    public function providerSetRequestClassThrowsExceptionWhenInvalidClassProvided() {
        return array(
            array($this),
            array('UnexistantClass'),
            array('ride\\library\\http\\Header'),
        );
    }

    public function testSetResponseClass() {
        $class = 'ride\\library\\http\\TestResponse';

        $this->httpFactory->setResponseClass($class);

        $this->assertEquals($class, $this->httpFactory->getResponseClass());
    }

    /**
     * @dataProvider providerSetResponseClassThrowsExceptionWhenInvalidClassProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetResponseClassThrowsExceptionWhenInvalidClassProvided($class) {
        $this->httpFactory->setResponseClass($class);
    }

    public function providerSetResponseClassThrowsExceptionWhenInvalidClassProvided() {
        return array(
            array($this),
            array('UnexistantClass'),
            array('ride\\library\\http\\Header'),
        );
    }

    public function testSetServerUrlWithSimpleUrl() {
        $_SERVER = array();

        $result = $this->httpFactory->setServerUrl('http://www.example.com');

        $this->assertTrue($result);
        $this->assertTrue(isset($_SERVER['HTTP_HOST']));
        $this->assertEquals('www.example.com', $_SERVER['HTTP_HOST']);
        $this->assertTrue(isset($_SERVER['REQUEST_URI']));
        $this->assertEquals('/', $_SERVER['REQUEST_URI']);
        $this->assertFalse(isset($_SERVER['HTTPS']));
    }

    public function testSetServerUrlWithAdvancedUrl() {
        $_SERVER = array();

        $result = $this->httpFactory->setServerUrl('https://www.example.com:8080/path/to/action?var=value');

        $this->assertTrue($result);
        $this->assertTrue(isset($_SERVER['HTTP_HOST']));
        $this->assertEquals('www.example.com:8080', $_SERVER['HTTP_HOST']);
        $this->assertTrue(isset($_SERVER['REQUEST_URI']));
        $this->assertEquals('/path/to/action?var=value', $_SERVER['REQUEST_URI']);
        $this->assertTrue(isset($_SERVER['HTTPS']));
        $this->assertEquals('on', $_SERVER['HTTPS']);
    }

    public function testSetServerUrlDoesNothingWhenAlreadySet() {
        $_SERVER = array(
            'HTTP_HOST' => 'server',
            'HTTP_CONNECTION' => 'keep-alive',
            'REQUEST_METHOD' => 'POST',
            'PATH' => '/usr/local/bin:/usr/bin:/bin',
            'SERVER_NAME' => 'localhost',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_URI' => '/path/to?action=content',
        );

        $result = $this->httpFactory->setServerUrl('https://www.example.com');

        $this->assertFalse($result);
        $this->assertEquals('server', $_SERVER['HTTP_HOST']);
    }

    /**
     * @dataProvider providerSetServerUrlThrowsExceptionWhenInvalidUrlProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetServerUrlThrowsExceptionWhenInvalidUrlProvided($url) {
        $this->httpFactory->setServerUrl($url);
    }

    public function providerSetServerUrlThrowsExceptionWhenInvalidUrlProvided() {
        return array(
            array(null),
            array(false),
            array('server'),
            array('a simple sentence'),
            array(array()),
            array($this),
        );
    }

    /**
     * @dataProvider providerCreateCookieFromString
     */
    public function testCreateCookieFromString($expected, $string) {
        $cookie = $this->httpFactory->createCookieFromString($string);

        $this->assertEquals($expected, $cookie);
    }

    public function providerCreateCookieFromString() {
        return array(
            array(new Cookie('name', 'value', 0, null, null, false, false), "name=value"),
            array(new Cookie('name', 'value', 0, null, null, true, true), "name=value; Secure; HttpOnly"),
            array(new Cookie('name', 'value2', 1241307505, 'www.example.org', '/blog', true, true), "name=value2;domain=www.example.org; Expires=Sat, 02-May-2009 23:38:25 GMT; path=/blog; Secure; HttpOnly"),
        );
    }

    public function testCreateHeaderContainerFromServer() {
        $_SERVER = array(
            'HTTP_HOST' => 'server',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_USER_AGENT' => 'Mozilla...',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'PATH' => '/usr/local/bin:/usr/bin:/bin',
            'SERVER_NAME' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
        );

        $expected = array(
            new Header('Content-Type', $_SERVER['CONTENT_TYPE']),
            new Header('Host', $_SERVER['HTTP_HOST']),
            new Header('Connection', $_SERVER['HTTP_CONNECTION']),
            new Header('User-Agent', $_SERVER['HTTP_USER_AGENT']),
            new Header('Accept', $_SERVER['HTTP_ACCEPT']),
        );

        $headerContainer = $this->httpFactory->createHeaderContainerFromServer();

        $this->assertEquals($expected, $headerContainer->getHeaders());

        unset($_SERVER['HTTP_HOST']);
        $expected = array(
            new Header('Content-Type', $_SERVER['CONTENT_TYPE']),
            new Header('Connection', $_SERVER['HTTP_CONNECTION']),
            new Header('User-Agent', $_SERVER['HTTP_USER_AGENT']),
            new Header('Accept', $_SERVER['HTTP_ACCEPT']),
            new Header('Host', 'localhost'),
        );

        $headerContainer = $this->httpFactory->createHeaderContainerFromServer();

        $this->assertEquals($expected, $headerContainer->getHeaders());
    }

    public function testCreateRequestFromServer() {
        $_SERVER = array(
            'HTTP_HOST' => 'server',
            'HTTP_CONNECTION' => 'keep-alive',
            'REQUEST_METHOD' => 'POST',
            'PATH' => '/usr/local/bin:/usr/bin:/bin',
            'SERVER_NAME' => 'localhost',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_URI' => '/path/to?action=content',
            'HTTPS' => 'on',
        );
        $_POST = array(
            'var1' => 'value1',
        );

        $expectedHeaders = new HeaderContainer();
        $expectedHeaders->setHeader('Host', 'server');
        $expectedHeaders->addHeader('Connection', 'keep-alive');

        $expectedRequest = new Request('/path/to?action=content', 'POST', 'HTTP/1.1', $expectedHeaders, $_POST);
        $expectedRequest->setIsSecure(true);

        $result = $this->httpFactory->createRequestFromServer();

        $this->assertEquals($expectedRequest, $result);
    }

    public function testCreateRequestFromServerWithEmptyVariables() {
        $_SERVER = array();
        $_POST = array();

        $expectedHeaders = new HeaderContainer();
        $expectedHeaders->addHeader('Host', 'localhost');
        $expectedRequest = new Request('/', 'GET', 'HTTP/1.0', $expectedHeaders, array());

        $result = $this->httpFactory->createRequestFromServer();

        $this->assertEquals($expectedRequest, $result);
    }

    public function testCreateRequestFromServerWithScriptName() {
        $_SERVER = array(
            'SCRIPT_NAME' => 'index.php',
        );
        $_POST = array();

        $expectedHeaders = new HeaderContainer();
        $expectedHeaders->addHeader('Host', 'localhost');
        $expectedRequest = new Request('/index.php', 'GET', 'HTTP/1.0', $expectedHeaders, array());

        $result = $this->httpFactory->createRequestFromServer();

        $this->assertEquals($expectedRequest, $result);
    }

    public function testCreateRequestFromServerWithSetServerUrl() {
        $_SERVER = array();
        $_POST = array();

        $expectedHeaders = new HeaderContainer();
        $expectedHeaders->addHeader('Host', 'www.example.com:8080');
        $expectedRequest = new Request('/path/to/action?var=value', 'GET', 'HTTP/1.0', $expectedHeaders, array());
        $expectedRequest->setIsSecure(true);

        $this->httpFactory->setServerUrl('https://www.example.com:8080/path/to/action?var=value');
        $result = $this->httpFactory->createRequestFromServer();

        $this->assertEquals($expectedRequest, $result);
    }

    public function testCreateRequestFromString() {
        $string =
'GET /path/to?var1=value HTTP/1.1
Cache-Control: max-age=0
Connection: keep-alive
Host: localhost
';

        $headers = new HeaderContainer();
        $headers->addHeader('Cache-Control', 'max-age=0');
        $headers->addHeader('Connection', 'keep-alive');
        $headers->addHeader('Host', 'localhost');
        $request = new Request('/path/to?var1=value', 'GET', 'HTTP/1.1', $headers);

        $result = $this->httpFactory->createRequestFromString($string);

        $this->assertEquals($request, $result);
    }

    public function testCreateResponse() {
        $response = $this->httpFactory->createResponse();

        $this->assertTrue($response instanceof Response);
    }

    public function testCreateResponseFromString() {
        $string =
'HTTP/1.1 404 Not Found
Date: Sat, 21 Sep 2013 19:59:42 GMT
Content-Length: 7
Content-Type: text/plain

foo bar';

        $response = new Response();
        $response->setStatusCode(404);
        $response->setHeader('date', 'Sat, 21 Sep 2013 19:59:42 GMT');
        $response->setHeader('content-length', '7');
        $response->setHeader('content-type', 'text/plain');
        $response->setBody('foo bar');

        $result = $this->httpFactory->createResponseFromString($string, "\n");

        $this->assertEquals($response, $result);
    }

    /**
     * @dataProvider providerCreateResponseFromStringThrowsExceptionOnInvalidReponse
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testCreateResponseFromStringThrowsExceptionOnInvalidReponse($string) {
        $this->httpFactory->createResponseFromString($string, "\n");
    }

    public function providerCreateResponseFromStringThrowsExceptionOnInvalidReponse() {
        return array(
            array('HTTP/1.1 404 Not Found
invalid header'
            ),
            array('test'),
            array(null),
            array(array()),
            array($this),
        );
    }

}

class TestRequest extends Request {

}

class TestResponse extends Response {

}
