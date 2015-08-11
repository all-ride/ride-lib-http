<?php

namespace ride\library\http;

use \PHPUnit_Framework_TestCase;

class HeaderContainerTest extends PHPUnit_Framework_TestCase {

    private $hc;

    public function setUp() {
        $this->hc = new HeaderContainer();
    }

    public function testToString() {
        $this->hc->addHeader('content-type', 'application/json');
        $this->hc->addHeader('content-length', 25);

        $expected = "Content-Type: application/json\r\nContent-Length: 25\r\n";

        $this->assertEquals($expected, (string) $this->hc);
    }

    public function testAddHeaderWithHeaderInstance() {
        $name = 'Name';
        $header = new Header($name, 'value');

        $this->assertEquals(array(), $this->hc->getHeaders());
        $this->assertFalse($this->hc->hasHeaders());

        $this->hc->addHeader($header);

        $this->assertEquals(array($header), $this->hc->getHeaders());
        $this->assertTrue($this->hc->hasHeaders());
    }

    public function testAddHeaderMultipleTimes() {
        $name = 'Name';
        $header = new Header($name, 'value');
        $header2 = new Header($name, 'value2');

        $this->assertEquals(array(), $this->hc->getHeaders());

        $this->hc->addHeader($header);
        $this->hc->addHeader($header);

        $this->assertEquals(array($header), $this->hc->getHeaders());

        $this->hc->addHeader($header);

        $this->assertEquals(array($header), $this->hc->getHeaders());

        $this->hc->addHeader($header2);

        $this->assertEquals(array($header, $header2), $this->hc->getHeaders());
    }

    public function testAddHeaderWithNameAndValue() {
        $name = 'Name';
        $value = 'value';

        $this->assertEquals(array(), $this->hc->getHeaders());

        $this->hc->addHeader($name, $value);

        $this->assertEquals(array(new Header($name, $value)), $this->hc->getHeaders());
    }

    /**
     * @dataProvider providerAddHeaderWithInvalidValuesThrowsException
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testAddHeaderWithInvalidValuesThrowsException($header, $value = null) {
        $this->hc->addHeader($header, $value);
    }

    public function providerAddHeaderWithInvalidValuesThrowsException() {
        return array(
            array(null),
            array(array()),
            array($this),
            array('name', null),
            array('name', array()),
            array('name', $this),
        );
    }

    public function testSetHeaderWithHeaderInstance() {
        $name = 'Name';
        $name2 = 'Name2';
        $header = new Header($name, 'value');
        $header2 = new Header($name2, 'value');

        $this->hc->addHeader($name, 'old value');
        $this->hc->addHeader($header2);

        $this->assertEquals(array(new Header($name, 'old value'), $header2), $this->hc->getHeaders());

        $this->hc->setHeader($header);

        $this->assertEquals(array($header, $header2), $this->hc->getHeaders());
    }

    public function testSetHeaderWithNameAndValue() {
        $name = 'Name';
        $name2 = 'Name2';
        $name3 = 'Name3';
        $value = 'value';
        $header = new Header($name, $value);
        $header2 = new Header($name2, $value);
        $header3 = new Header($name3, $value);

        $this->hc->addHeader($header);
        $this->hc->addHeader($header);
        $this->hc->addHeader($header2);

        $this->assertEquals(array($header, $header2), $this->hc->getHeaders());

        $this->hc->setHeader($name3, $value, true);

        $this->assertEquals(array($header3, $header, $header2), $this->hc->getHeaders());
    }

    /**
     * @dataProvider providerAddHeaderWithInvalidValuesThrowsException
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testSetHeaderWithInvalidValuesThrowsException($header, $value = null) {
        $this->hc->setHeader($header, $value);
    }

    /**
     * @dataProvider providerHasHeader
     */
    public function testHasHeader($expected, $header) {
        $this->hc->addHeader('Content-Length', 123);
        $this->hc->addHeader('Accept-Language', 'en');

        $result = $this->hc->hasHeader($header);

        $this->assertEquals($expected, $result);
    }

    public function providerHasHeader() {
        return array(
            array(true, 'Content-Length'),
            array(true, 'content-length'),
            array(false, 'content'),
        );
    }

    /**
     * @dataProvider providerHasHeaderThrowsExceptionWhenInvalidNameProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testHasHeaderThrowsExceptionWhenInvalidNameProvided($name) {
        $this->hc->hasHeader($name);
    }

    public function providerHasHeaderThrowsExceptionWhenInvalidNameProvided() {
        return array(
            array(null),
            array(array()),
            array($this),
        );
    }

    public function testGetHeader() {
        $name = 'Name';
        $value = 'value';

        $this->hc->addHeader($name, $value);

        $header = $this->hc->getHeader($name);

        $this->assertEquals($name, $header->getName());
        $this->assertEquals($value, $header->getValue());

        $this->hc->addHeader($name, 'value2');

        $headers = $this->hc->getHeader($name);

        $this->assertTrue(is_array($headers));
        $this->assertEquals(array($header, new Header($name, 'value2')), $headers);

        $header = $this->hc->getHeader('Unexistant');

        $this->assertNull($header);
    }

    /**
     * @dataProvider providerGetHeaderThrowsExceptionWhenInvalidHeaderProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testGetHeaderThrowsExceptionWhenInvalidHeaderProvided($name) {
        $this->hc->getHeader($name);
    }

    public function providerGetHeaderThrowsExceptionWhenInvalidHeaderProvided() {
        return array(
            array(null),
            array(array()),
            array($this),
        );
    }

    public function testRemoveHeader() {
        $name = 'Name1';
        $name2 = 'Name2';
        $name3 = 'Name3';
        $header = new Header($name, 'value');
        $header2 = new Header($name2, 'value');
        $header3 = new Header($name3, 'value');

        $this->hc->addHeader($name, 'value');
        $this->hc->addHeader($header2);

        $this->assertEquals(array($header, $header2), $this->hc->getHeaders());

        $this->hc->removeHeader($name);

        $this->assertEquals(array($header2), $this->hc->getHeaders());

        $this->hc->addHeader($header);
        $this->hc->addHeader($header3);

        $this->assertEquals(array($header2, $header, $header3), $this->hc->getHeaders());

        $this->hc->removeHeader(array($name2, $name3));

        $this->assertEquals(array($header), $this->hc->getHeaders());
    }

    public function testAddCacheControlDirective() {
        $this->assertEquals(array(), $this->hc->getCacheControlDirectives());

        $this->hc->addCacheControlDirective('private');

        $this->assertEquals(array('private' => true), $this->hc->getCacheControlDirectives());
        $this->assertEquals('private', $this->hc->getHeader('Cache-Control')->getValue());

        $this->hc->addCacheControlDirective('max-age', 60);

        $this->assertEquals(array('private' => true, 'max-age' => 60), $this->hc->getCacheControlDirectives());
        $this->assertEquals('private, max-age=60', $this->hc->getHeader('Cache-Control')->getValue());
    }

    /**
     * @dataProvider providerAddCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testAddCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided($directive, $value) {
        $this->hc->addCacheControlDirective($directive, $value);
    }

    public function providerAddCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided() {
        return array(
            array($this, 'value'),
            array(array(), 'value'),
            array('name', $this),
            array('name', array()),
        );
    }

    public function testAddCacheControlHeaderWillParseDirectives() {
        $this->hc->addHeader('Cache-Control', 'private,max-age=60,test="test value"');

        $this->assertEquals(true, $this->hc->getCacheControlDirective('private'));
        $this->assertEquals(60, $this->hc->getCacheControlDirective('max-age'));
        $this->assertEquals('test value', $this->hc->getCacheControlDirective('test'));
    }

    public function testGetCacheControlDirective() {
        $this->hc->addCacheControlDirective('no-store');
        $this->hc->addCacheControlDirective('max-age', 60);
        $this->hc->addCacheControlDirective('private', 'Vary');

        $this->assertEquals(true, $this->hc->getCacheControlDirective('no-store'));
        $this->assertEquals(60, $this->hc->getCacheControlDirective('max-age'));
        $this->assertEquals('Vary', $this->hc->getCacheControlDirective('private'));
        $this->assertNull($this->hc->getCacheControlDirective('foo'));
    }

    /**
     * @dataProvider providerGetCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testGetCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided($directive) {
        $this->hc->getCacheControlDirective($directive);
    }

    public function providerGetCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided() {
        return array(
            array($this),
            array(array()),
        );
    }

    public function testRemoveCacheControlDirective() {
        $this->assertEquals(array(), $this->hc->getCacheControlDirectives());

        $this->hc->addCacheControlDirective('private');
        $this->hc->addCacheControlDirective('max-age', 60);
        $this->hc->addCacheControlDirective('test', "test value");

        $this->assertEquals(array('private' => true, 'max-age' => 60, 'test' => 'test value'), $this->hc->getCacheControlDirectives());

        $this->hc->removeCacheControlDirective('max-age');

        $this->assertEquals(array('private' => true, 'test' => 'test value'), $this->hc->getCacheControlDirectives());
        $this->assertEquals('private, test="test value"', $this->hc->getHeader('Cache-Control')->getValue());

        $this->hc->removeCacheControlDirective('private');
        $this->hc->removeCacheControlDirective('test');
        $this->hc->removeCacheControlDirective('unexistant');

        $this->assertEquals(array(), $this->hc->getCacheControlDirectives());
        $this->assertNull($this->hc->getHeader('Cache-Control'));
    }

    /**
     * @dataProvider providerRemoveCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testRemoveCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided($directive) {
        $this->hc->removeCacheControlDirective($directive);
    }

    public function providerRemoveCacheControlDirectiveThrowsExceptionWhenInvalidNameProvided() {
        return array(
            array($this),
            array(array()),
        );
    }

    public function testIterator() {
        $this->hc->addHeader('Content-Length', 123);
        $this->hc->addHeader('Set-Cookie', 'name=test');
        $this->hc->addHeader('Set-Cookie', 'flag=1');
        $this->hc->addHeader('Age', 100);

        $expected = "Content-Length: 123\nSet-Cookie: name=test\nSet-Cookie: flag=1\nAge: 100";

        $output = '';
        foreach($this->hc as $index => $header) {
            $output .= ($output ? "\n" : "") . ((string) $header);
        }

        $this->assertEquals($expected, $output);
    }

    public function testCount() {
        $this->hc->addHeader('name', 'value');
        $this->hc->addHeader('name', 'value2');
        $this->hc->addHeader('name2', 'value3');

        $this->assertEquals(3, $this->hc->count());
    }

}
