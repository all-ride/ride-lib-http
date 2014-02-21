<?php

namespace ride\library\http;

use \PHPUnit_Framework_TestCase;

class CookieTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $time = time();
        $cookie = new Cookie('name');

        $this->assertEquals('name', $cookie->getName());
        $this->assertEquals(null, $cookie->getValue());
        $this->assertEquals($time - 7777777, $cookie->getExpires());
        $this->assertEquals(null, $cookie->getDomain());
        $this->assertEquals(null, $cookie->getPath());
        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());

        $string = 'name=DELETED; Expires=' . gmdate('D, d-M-Y H:i:s T', $time - 7777777) . '; HttpOnly';
        $this->assertEquals($string, (string) $cookie);

        $cookie = new Cookie('name', 'value', 0, 'foo-domain');

        $this->assertEquals('value', $cookie->getValue());
        $this->assertEquals(0, $cookie->getExpires());
        $this->assertEquals('foo-domain', $cookie->getDomain());

        $string = 'name=value; Domain=foo-domain; Path=/; HttpOnly';
        $this->assertEquals($string, (string) $cookie);

        $cookie = new Cookie('name', 'value', 0, 'foo-domain', 'bar-path', true, false);

        $this->assertEquals('bar-path', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());

        $string = 'name=value; Domain=foo-domain; Path=bar-path; Secure';
        $this->assertEquals($string, (string) $cookie);
    }

    /**
     * @dataProvider providerConstructThrowsExceptionWhenInvalidArgumentsProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testConstructThrowsExceptionWhenInvalidArgumentsProvided($name, $value, $expires, $domain, $path, $isSecure, $isHttpOnly) {
        new Cookie($name, $value, $expires, $domain, $path, $isSecure, $isHttpOnly);
    }

    public function providerConstructThrowsExceptionWhenInvalidArgumentsProvided() {
        return array(
            array(null, 'value', 0, 'domain', 'path', true, true),
            array($this, 'value', 0, 'domain', 'path', true, true),
            array(array(), 'value', 0, 'domain', 'path', true, true),
            array('name', array(), 0, 'domain', 'path', true, true),
            array('name', $this, 0, 'domain', 'path', true, true),
            array('name', 'value', array(), 'domain', 'path', true, true),
            array('name', 'value', $this, 'domain', 'path', true, true),
            array('name', 'value', 'string', 'domain', 'path', true, true),
            array('name', 'value', -500, 'domain', 'path', true, true),
            array('name', 'value', 0, array(), 'path', true, true),
            array('name', 'value', 0, $this, 'path', true, true),
            array('name', 'value', 0, 'domain', array(), true, true),
            array('name', 'value', 0, 'domain', $this, true, true),
            array('name', 'value', 0, 'domain', 'path', array(), true),
            array('name', 'value', 0, 'domain', 'path', $this, true),
            array('name', 'value', 0, 'domain', 'path', true, array()),
            array('name', 'value', 0, 'domain', 'path', true, $this),
        );
    }

}