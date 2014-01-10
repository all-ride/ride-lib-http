<?php

namespace pallo\library\http;

use pallo\library\http\session\io\SessionIO;
use pallo\library\http\session\Session;

use \PHPUnit_Framework_TestCase;

class RequestTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $path = '/path';
        $method = 'POST';
        $protocol = 'protocol';
        $headers = new HeaderContainer();
        $headers->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $body = array('var' => 'value');

        $request = new Request($path, $method, $protocol, $headers, $body);

        $this->assertEquals($protocol, $request->getProtocol());
        $this->assertEquals($method, $request->getMethod());
        $this->assertFalse($request->isGet());
        $this->assertTrue($request->isPost());
        $this->assertFalse($request->isPut());
        $this->assertFalse($request->isDelete());
        $this->assertFalse($request->isHead());

        $this->assertEquals('http://localhost', $request->getServerUrl());
        $this->assertEquals($path, $request->getPath());
        $this->assertEquals('', $request->getQuery());
        $this->assertEquals('http://localhost' . $path, $request->getUrl());
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeader('content-type'));
        $this->assertEquals($request->getBody(), $request->getBodyParametersAsString());

        $string = $method . ' ' . $path . ' ' . $protocol . "\r\n" . $headers . "\r\n" . $request->getBody() . "\r\n";
        $this->assertEquals($string, (string) $request);

        // test security
        $this->assertFalse($request->isSecure());

        $request->setIsSecure(true);

        $this->assertTrue($request->isSecure());
        $this->assertEquals('https://localhost', $request->getServerUrl());
    }

    /**
     * @dataProvider providerConstructThrowsExceptionWhenInvalidArgumentsProvided
     * @expectedException pallo\library\http\exception\HttpException
     */
    public function testConstructThrowsExceptionWhenInvalidArgumentsProvided($path, $method, $protocol, $headers, $body) {
        new Request($path, $method, $protocol, $headers, $body);
    }

    public function providerConstructThrowsExceptionWhenInvalidArgumentsProvided() {
        return array(
            array(null, 'GET', 'protocol', null, null),
            array('', 'GET', 'protocol', null, null),
            array($this, 'GET', 'protocol', null, null),
            array(array(), 'GET', 'protocol', null, null),
            array('/', null, 'protocol', null, null),
            array('/', '', 'protocol', null, null),
            array('/', $this, 'protocol', null, null),
            array('/', array(),'protocol', null, null),
            array('/', 'GET', null, null, null),
            array('/', 'GET', '', null, null),
            array('/', 'GET', $this, null, null),
            array('/', 'GET', array(), null, null),
            array('/', 'GET', 'protocol', null, $this),
        );
    }

    public function testQuery() {
        $path = '/path?var1=value1&var2=value2';
        $request = new Request($path);

        $this->assertEquals('?var1=value1&var2=value2', $request->getQuery());
        $this->assertEquals('value1', $request->getQueryParameter('var1'));
        $this->assertEquals('value2', $request->getQueryParameter('var2'));
        $this->assertEquals('default', $request->getQueryParameter('var3', 'default'));
        $this->assertEquals(array('var1' => 'value1', 'var2' => 'value2'), $request->getQueryParameters());
        $this->assertEquals('var1=value1&var2=value2', $request->getQueryParametersAsString());

        $path = '/path?var1[subvar1]=value1&var1[subvar2]=value2&var2&var3[1][]=value3&var3[1][foo]=bar';
        $request = new Request($path);

        $vars = array('subvar1' => 'value1', 'subvar2' => 'value2');
        $this->assertEquals($vars, $request->getQueryParameter('var1'));
        $this->assertEquals(array('var1' => $vars, 'var2' => '', 'var3' => array(1 => array('value3', 'foo' => 'bar'))), $request->getQueryParameters());
    }

    /**
     * @dataProvider providerGetQueryParameterThrowsExceptionWhenInvalidNameProvided
     * @expectedException pallo\library\http\exception\HttpException
     */
    public function testGetQueryParameterThrowsExceptionWhenInvalidNameProvided($name) {
        $request = new Request('/');
        $request->getQueryParameter($name);
    }

    public function providerGetQueryParameterThrowsExceptionWhenInvalidNameProvided() {
        return array(
            array(null),
            array(''),
            array(array()),
            array($this),
        );
    }

    public function testBody() {
        $body = 'variable=value&variable2=value';

        $request = new Request('/path', 'GET', 'HTTP/1.1', null, $body);

        $this->assertEquals($body, $request->getBody());
        $this->assertEquals(array(), $request->getBodyParameters());
    }

    public function testDecodesUrlEncodedBody() {
        $headers = new HeaderContainer();
        $headers->setHeader(Header::HEADER_CONTENT_TYPE, 'application/x-www-form-urlencoded');
        $body = 'variable=value&variable2=value';

        $request = new Request('/path', 'GET', 'HTTP/1.1', $headers, $body);

        $this->assertEquals('value', $request->getBodyParameter('variable'));
        $this->assertEquals('value', $request->getBodyParameter('variable2'));
        $this->assertEquals(array('variable' => 'value', 'variable2' => 'value'), $request->getBodyParameters());
    }

    public function testDecodesJsonBody() {
        $headers = new HeaderContainer();
        $headers->setHeader(Header::HEADER_CONTENT_TYPE, 'application/json');
        $bodyJson = '{"variable":"value"}';

        $request = new Request('/path', 'GET', 'HTTP/1.1', $headers, $bodyJson);

        $this->assertEquals('value', $request->getBodyParameter('variable'));

        $bodyArray = array('variable' => 'value');

        $request = new Request('/path', 'GET', 'HTTP/1.1', $headers, $bodyArray);

        $this->assertEquals($bodyJson, $request->getBody());
    }

    public function testSession() {
        $sessionIo = $this->getMock('pallo\\library\\http\\session\\io\\SessionIO');
        $session = new Session($sessionIo);

        $request = new Request('/');

        $this->assertFalse($request->hasSession());
        $this->assertNull($request->getSession());

        $request->setSession($session);

        $this->assertTrue($request->hasSession());
        $this->assertEquals($session, $request->getSession());

        $request->setSession(null);

        $this->assertFalse($request->hasSession());
        $this->assertNull($request->getSession());
    }

    public function testHeaders() {
        $headers = new HeaderContainer();
        $headers->addHeader('Name', 'value');

        $request = new Request('/', 'GET', 'protocol', $headers);

        $this->assertEquals($headers, $request->getHeaders());
        $this->assertEquals('value', $request->getHeader('name'));
        $this->assertEquals(array(), $request->getAccept());
        $this->assertEquals(array(), $request->getAcceptCharset());
        $this->assertEquals(array(), $request->getAcceptEncoding());
        $this->assertEquals(array(), $request->getAcceptLanguage());
        $this->assertEquals(array(), $request->getIfNoneMatch());
        $this->assertNull($request->getIfModifiedSince());
        $this->assertNull($request->isNoCache());

        $headers->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        $headers->addHeader('Accept-Charset', '*/*');
        $headers->addHeader('Accept-Encoding', 'gzip, deflate');
        $headers->addHeader('Accept-Language', 'en,nl-be;q=0.7,nl;q=0.3');
        $headers->addHeader('Cache-Control', 'no-cache');
        $headers->addHeader('If-Modified-Since', 'Wed, 01 Dec 2010 16:00:00 GMT');
        $headers->addHeader('If-None-Match', '"def",W/"ghi"');

        $request = new Request('/', 'GET', 'protocol', $headers);

        $this->assertEquals(array('text/html' => 1, 'application/xhtml+xml' => 1, 'application/xml' => 0.9, '*/*' => 0.8), $request->getAccept());
        $this->assertEquals(array('text/html' => 1, 'application/xhtml+xml' => 1, 'application/xml' => 0.9, '*/*' => 0.8), $request->getAccept());
        $this->assertEquals(array('*/*' => 1), $request->getAcceptCharset());
        $this->assertEquals(array('*/*' => 1), $request->getAcceptCharset());
        $this->assertEquals(array('gzip' => 1, 'deflate' => 1), $request->getAcceptEncoding());
        $this->assertEquals(array('gzip' => 1, 'deflate' => 1), $request->getAcceptEncoding());
        $this->assertEquals(array('en' => 1, 'nl-be' => 0.7, 'nl' => 0.3), $request->getAcceptLanguage());
        $this->assertEquals(array('en' => 1, 'nl-be' => 0.7, 'nl' => 0.3), $request->getAcceptLanguage());
        $this->assertEquals(array('def' => false, 'ghi' => true), $request->getIfNoneMatch());
        $this->assertEquals(array('def' => false, 'ghi' => true), $request->getIfNoneMatch());
        $this->assertEquals(1291219200, $request->getIfModifiedSince());
        $this->assertEquals(1291219200, $request->getIfModifiedSince());
        $this->assertTrue($request->isNoCache());
    }

    public function testCookies() {
        $headers = new HeaderContainer();
        $headers->setHeader(Header::HEADER_COOKIE, 'cookie1=value1; cookie2=value2');

        $request = new Request('/path', 'GET', 'HTTP/1.1', $headers);

        $this->assertEquals(array('cookie1' => 'value1', 'cookie2' => 'value2'), $request->getCookies());
        $this->assertEquals('value2', $request->getCookie('cookie2'));
        $this->assertEquals('default', $request->getCookie('cookie4', 'default'));

        $headers->addHeader(Header::HEADER_COOKIE, 'cookie3=value3');
        $request = new Request('/path', 'GET', 'HTTP/1.1', $headers);

        $this->assertEquals(array('cookie1' => 'value1', 'cookie2' => 'value2', 'cookie3' => 'value3'), $request->getCookies());
    }

    public function testGetUserAgent() {
        $request = new Request('/');

        $this->assertNull($request->getUserAgent());

        $headers = new HeaderContainer();
        $headers->addHeader('User-Agent', 'Pallo');
        $request = new Request('/', 'GET', 'protocol', $headers);

        $this->assertEquals('Pallo', $request->getUserAgent());
    }

    public function testIsXmlRequest() {
        $request = new Request('/');

        $this->assertFalse($request->isXmlHttpRequest());

        $headers = new HeaderContainer();
        $headers->addHeader('X-Requested-With', 'XMLHttpRequest');

        $request = new Request('/', 'GET', 'protocol', $headers);

        $this->assertTrue($request->isXmlHttpRequest());
    }

}