<?php

namespace ride\library\http;

use \PHPUnit_Framework_TestCase;

class ResponseTest extends PHPUnit_Framework_TestCase {

    /**
     * @var ride\library\http\Response
     */
    protected $response;

    protected function setUp() {
        parent::setUp();

        $this->response = new Response();
    }

    public function testConstruct() {
        $time = time();
        $response = new Response();

        $this->assertEquals(Response::STATUS_CODE_OK, $response->getStatusCode());
        $this->assertNull($response->getBody());
    }

    public function testToString() {
        $statusCode = $this->response->getStatusCode();

        $this->response->setCookie(new Cookie('name', 'value'));
        $this->response->setBody('<html></html>');

        $string = $statusCode . ' ' . Response::getStatusPhrase($statusCode) . "\r\n";
        $string .= "Date: " . $this->response->getHeader(Header::HEADER_DATE) . "\r\n";
        $string .= "Set-Cookie: name=value; HttpOnly\r\n";
        $string .= "\r\n";
        $string .= "<html></html>";

        $this->assertEquals($string, (string) $this->response);
    }

    /**
     * @dataProvider providerSetStatusCodeThrowsExceptionWhenInvalidStatusCodeProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetStatusCodeThrowsExceptionWhenInvalidStatusCodeProvided($statusCode) {
        $this->response->setStatusCode($statusCode);
    }

    public function providerSetStatusCodeThrowsExceptionWhenInvalidStatusCodeProvided() {
        return array(
            array(array()),
            array($this),
            array('value'),
            array(50),
            array(600),
        );
    }

    public function testHeaders() {
        $this->assertFalse($this->response->hasHeader('name'));

        $this->response->addHeader('name', 'value');

        $this->assertTrue($this->response->hasHeader('name'));
        $this->assertEquals('value', $this->response->getHeader('name'));
        $this->assertEquals('default', $this->response->getHeader('notset', 'default'));

        $this->response->addHeader('name', 'value2');

        $this->assertTrue($this->response->hasHeader('name'));
        $this->assertEquals(array('value', 'value2'), $this->response->getHeader('name'));

        $this->response->removeHeader('name');

        $this->assertFalse($this->response->hasHeader('name'));

        $this->assertTrue($this->response->getHeaders() instanceof HeaderContainer);
    }

    public function testCookies() {
        $cookie = new Cookie('var', 'value');

        $this->assertEmpty($this->response->getCookies());

        $this->response->setCookie($cookie);

        $this->assertNull($this->response->getCookie('test'));
        $this->assertEquals($cookie, $this->response->getCookie('var'));
    }

    public function testRedirect() {
        $this->assertEquals(Response::STATUS_CODE_OK, $this->response->getStatusCode());
        $this->assertFalse($this->response->willRedirect());

        $this->response->clearRedirect();

        $this->assertEquals(Response::STATUS_CODE_OK, $this->response->getStatusCode());
        $this->assertFalse($this->response->willRedirect());

        $this->response->setRedirect('http://server');

        $this->assertEquals(Response::STATUS_CODE_FOUND, $this->response->getStatusCode());
        $this->assertTrue($this->response->willRedirect());

        $this->response->clearRedirect();

        $this->assertEquals(Response::STATUS_CODE_OK, $this->response->getStatusCode());
        $this->assertFalse($this->response->hasHeader('location'));
        $this->assertFalse($this->response->willRedirect());

        $this->response->setRedirect('http://server', Response::STATUS_CODE_MOVED_PERMANENTLY);
        $this->assertEquals(Response::STATUS_CODE_MOVED_PERMANENTLY, $this->response->getStatusCode());
        $this->assertEquals('http://server', $this->response->getHeader('location'));
        $this->assertTrue($this->response->willRedirect());
    }

    /**
     * @dataProvider providerSetRedirectThrowsExceptionWhenInvalidArgumentProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetRedirectThrowsExceptionWhenInvalidArgumentProvided($url, $statusCode) {
        $this->response->setRedirect($url, $statusCode);
    }

    public function providerSetRedirectThrowsExceptionWhenInvalidArgumentProvided() {
        return array(
            array(array(), 301),
            array($this, 301),
            array('value', 'value'),
            array('value', array()),
            array('value', $this),
            array('value', 200),
            array('value', 404),
        );
    }

    public function testExpires() {
        $this->assertNull($this->response->getExpires());

        $time = time();

        $this->response->setExpires($time);

        $this->assertEquals($time, $this->response->getExpires());
        $this->assertEquals(Header::parseTime($time), $this->response->getHeader(Header::HEADER_EXPIRES));

        $this->response->setExpires();

        $this->assertNull($this->response->getExpires());
        $this->assertFalse($this->response->hasHeader(Header::HEADER_EXPIRES));
    }

    /**
     * @dataProvider providerSetExpiresThrowsExceptionWhenInvalidArgumentProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetExpiresThrowsExceptionWhenInvalidArgumentProvided($time) {
        $this->response->setExpires($time);
    }

    public function providerSetExpiresThrowsExceptionWhenInvalidArgumentProvided() {
        return array(
            array(-500),
            array('value'),
            array(array()),
            array($this),
        );
    }

    public function testPublicPrivate() {
        $this->assertNull($this->response->isPublic());
        $this->assertNull($this->response->isPrivate());

        $this->response->setIsPublic(true);

        $this->assertTrue($this->response->isPublic());
        $this->assertNull($this->response->isPrivate());

        $this->response->setIsPublic(false);

        $this->assertNull($this->response->isPublic());
        $this->assertNull($this->response->isPrivate());

        $this->response->setIsPrivate(true);

        $this->assertNull($this->response->isPublic());
        $this->assertTrue($this->response->isPrivate());

        $this->response->setIsPrivate(false);

        $this->assertNull($this->response->isPublic());
        $this->assertNull($this->response->isPrivate());
    }

    public function testAge() {
        $this->assertNull($this->response->getMaxAge());
        $this->assertNull($this->response->getSharedMaxAge());

        $this->response->setMaxAge(60);
        $this->response->setSharedMaxAge(120);

        $this->assertEquals(60, $this->response->getMaxAge());
        $this->assertEquals(120, $this->response->getSharedMaxAge());

        $this->response->setMaxAge();
        $this->response->setSharedMaxAge();

        $this->assertNull($this->response->getMaxAge());
        $this->assertNull($this->response->getSharedMaxAge());
    }

    /**
     * @dataProvider providerTestAgeThrowsException
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetMaxAgeThrowsExceptionWhenInvalidArgumentProvided($age) {
        $this->response->setMaxAge($age);
    }

    /**
     * @dataProvider providerTestAgeThrowsException
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetSharedMaxAgeThrowsExceptionWhenInvalidArgumentProvided($age) {
        $this->response->setSharedMaxAge($age);
    }

    public function testLastModified() {
        $time = time();

        $this->assertNull($this->response->getLastModified());

        $this->response->setLastModified($time);

        $this->assertEquals($time, $this->response->getLastModified());
        $this->assertEquals(Header::parseTime($time), $this->response->getHeader(Header::HEADER_LAST_MODIFIED));

        $this->response->setLastModified();

        $this->assertNull($this->response->getLastModified());
        $this->assertFalse($this->response->hasHeader(Header::HEADER_LAST_MODIFIED));
    }

    /**
     * @dataProvider providerTestAgeThrowsException
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetLastModifiedThrowsExceptionWhenInvalidArgumentProvided($time) {
        $this->response->setLastModified($time);
    }

    public function providerTestAgeThrowsException() {
        return array(
            array('value'),
            array(array()),
            array($this),
            array(-1500),
        );
    }

    public function testEtag() {
        $this->response = new Response();

        $etag = 'abc';

        $this->assertNull($this->response->getETag());
        $this->assertFalse($this->response->hasHeader(Header::HEADER_ETAG));

        $this->response->setETag($etag);

        $this->assertEquals($etag, $this->response->getETag());
        $this->assertEquals($etag, $this->response->getHeader(Header::HEADER_ETAG));

        $this->response->setETag();

        $this->assertNull($this->response->getETag());
        $this->assertFalse($this->response->hasHeader(Header::HEADER_ETAG));
    }

    public function testIsNotModified() {
        $etag = 'abc';
        $time = time() - 60000;

        $headers = new HeaderContainer();
        $request = new Request('/', 'GET', 'protocol', $headers);

        $this->assertFalse($this->response->isNotModified($request));

        $this->response->setETag($etag);

        $headers->setHeader(Header::HEADER_IF_NONE_MATCH, '*');
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertTrue($this->response->isNotModified($request));

        $headers->setHeader(Header::HEADER_IF_NONE_MATCH, 'def');
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertFalse($this->response->isNotModified($request));

        $headers->setHeader(Header::HEADER_IF_NONE_MATCH, $etag);
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertTrue($this->response->isNotModified($request));


        $this->response->setLastModified($time);

        $headers->setHeader(Header::HEADER_IF_NONE_MATCH, '*');
        $headers->setHeader(Header::HEADER_IF_MODIFIED_SINCE, Header::parseTime($time));
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertTrue($this->response->isNotModified($request));

        $headers->setHeader(Header::HEADER_IF_NONE_MATCH, 'def');
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertFalse($this->response->isNotModified($request));

        $headers->setHeader(Header::HEADER_IF_NONE_MATCH, $etag);
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertTrue($this->response->isNotModified($request));

        $this->response->setETag();
        $headers->removeHeader(Header::HEADER_IF_NONE_MATCH);
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertTrue($this->response->isNotModified($request));

        $headers->removeHeader(Header::HEADER_IF_MODIFIED_SINCE);
        $request = new Request('/', 'GET', 'protocol', $headers);
        $this->assertFalse($this->response->isNotModified($request));
    }

    public function testSetNotModified() {
        $this->response = new Response();
        $this->response->setHeader(Header::HEADER_ALLOW, 'value');
        $this->response->setHeader(Header::HEADER_CONTENT_ENCODING, 'value');
        $this->response->setHeader(Header::HEADER_CONTENT_LANGUAGE, 'value');
        $this->response->setHeader(Header::HEADER_CONTENT_LENGTH, 'value');
        $this->response->setHeader(Header::HEADER_CONTENT_MD5, 'value');
        $this->response->setHeader(Header::HEADER_CONTENT_TYPE, 'value');
        $this->response->setHeader(Header::HEADER_LAST_MODIFIED, 'value');
        $this->response->setBody('body');

        $this->assertEquals(Response::STATUS_CODE_OK, $this->response->getStatusCode());
        $this->assertTrue($this->response->hasHeader(Header::HEADER_ALLOW));
        $this->assertTrue($this->response->hasHeader(Header::HEADER_CONTENT_ENCODING));
        $this->assertTrue($this->response->hasHeader(Header::HEADER_CONTENT_LANGUAGE));
        $this->assertTrue($this->response->hasHeader(Header::HEADER_CONTENT_LENGTH));
        $this->assertTrue($this->response->hasHeader(Header::HEADER_CONTENT_MD5));
        $this->assertTrue($this->response->hasHeader(Header::HEADER_CONTENT_TYPE));
        $this->assertTrue($this->response->hasHeader(Header::HEADER_LAST_MODIFIED));
        $this->assertNotNull($this->response->getBody());

        $this->response->setNotModified();

        $this->assertEquals(Response::STATUS_CODE_NOT_MODIFIED, $this->response->getStatusCode());
        $this->assertFalse($this->response->hasHeader(Header::HEADER_ALLOW));
        $this->assertFalse($this->response->hasHeader(Header::HEADER_CONTENT_ENCODING));
        $this->assertFalse($this->response->hasHeader(Header::HEADER_CONTENT_LANGUAGE));
        $this->assertFalse($this->response->hasHeader(Header::HEADER_CONTENT_LENGTH));
        $this->assertFalse($this->response->hasHeader(Header::HEADER_CONTENT_MD5));
        $this->assertFalse($this->response->hasHeader(Header::HEADER_CONTENT_TYPE));
        $this->assertFalse($this->response->hasHeader(Header::HEADER_LAST_MODIFIED));
        $this->assertNull($this->response->getBody());
    }

    public function testSend() {
        $this->expectOutputString('body');

        $request = new Request('/');
        $this->response->removeHeader(Header::HEADER_DATE);
        $this->response->setBody('body');

        $this->response->send($request);
    }

}
