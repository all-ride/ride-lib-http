<?php

namespace ride\library\http;

use \PHPUnit_Framework_TestCase;

class HeaderTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $header = new Header('name', 'value');

        $this->assertEquals('Name', $header->getName());
        $this->assertEquals('value', $header->getValue());
    }

    public function testToString() {
        $header = new Header('Content-Type', 'application/json');

        $this->assertEquals('Content-Type: application/json', (string) $header);
    }

    /**
     * @dataProvider providerConstructWithInvalidValuesThrowsException
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testConstructWithInvalidValuesThrowsException($name, $value) {
        new Header($name, $value);
    }

    public function providerConstructWithInvalidValuesThrowsException() {
        return array(
            array('', 'value'),
            array(null, 'value'),
            array(array(), 'value'),
            array($this, 'value'),
            array('name', null),
            array('name', array()),
            array('name', $this),
        );
    }

    /**
     * @dataProvider providerParseName
     */
    public function testParseName($expected, $name) {
        $name = Header::parseName($name);

        $this->assertEquals($expected, $name);
    }

    public function providerParseName() {
        return array(
            array('Name', 'name'),
            array('Content-Length', 'content-length'),
            array('Content-Length', 'CONTENT_LENGTH'),
        );
    }

    /**
     * @dataProvider providerParseTime
     */
    public function testParseTime($expected, $value) {
        date_default_timezone_set('UTC');

        $time = Header::parseTime($value);

        $this->assertEquals($expected, $time);
    }

    public function providerParseTime() {
        return array(
            array('Wed, 01 Dec 2010 16:00:00 GMT', 1291219200),
            array(1291219200, 'Wed, 01 Dec 2010 16:00:00 GMT'),
        );
    }

    /**
     * @dataProvider providerParseTimeThrowsExceptionWhenInvalidValueProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testParseTimeThrowsExceptionWhenInvalidValueProvided($value) {
        Header::parseTime($value);
    }

    public function providerParseTimeThrowsExceptionWhenInvalidValueProvided() {
        return array(
            array(-2500),
            array($this),
            array('sme'),
        );
    }

    /**
     * @dataProvider providerParseAccept
     */
    public function testParseAccept($expected, $value) {
        $result = Header::parseAccept($value);

        $this->assertEquals($expected, $result);
    }

    public function providerParseAccept() {
        return array(
            array(
                array(
                    'text/x-c' => 1,
                    'text/html' => 1,
                    'text/x-dvi' => 0.8,
                    'text/plain' => 0.5,
                ),
                'text/plain; q=0.5, text/html, text/x-dvi; q=0.8, text/x-c',
            ),
            array(
                array(
                    'ISO-8859-1' => 1,
                    '*' => 0.7,
                    'utf-8' => 0.7,
                ),
                'ISO-8859-1,utf-8;q=0.7,*;q=0.7'
            ),
            array(
                array(
                    'gzip' => 1,
                    'identity' => 0.5,
                ),
                'gzip;q=1.0, identity; q=0.5, *;q=0'
            ),
            array(
                array(
                    'da' => 1,
                    'en-gb' => 0.8,
                    'en' => 0.7,
                ),
                'da, en-gb;q=0.8, en;q=0.7'
            ),
        );
    }

    /**
     * @dataProvider providerParseAcceptThrowsExceptionWhenInvalidValueProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testParseAcceptThrowsExceptionWhenInvalidValueProvided($value) {
        Header::parseAccept($value);
    }

    public function providerParseAcceptThrowsExceptionWhenInvalidValueProvided() {
        return array(
            array(null),
            array(''),
            array(false),
            array(true),
            array($this),
            array(array('test')),
        );
    }

    /**
     * @dataProvider providerParseIfMatch
     */
    public function testParseIfMatch($expected, $value) {
        $result = Header::parseIfMatch($value);

        $this->assertEquals($expected, $result);
    }

    public function providerParseIfMatch() {
        return array(
            array(array('abc' => false), 'abc'),
            array(array('abc' => false), '"abc"'),
            array(array('abc' => true), 'W/"abc"'),
            array(array('abc' => true, 'def' => false, 'ghi' => true), 'W/"abc", "def",W/"ghi"'),
        );
    }

    /**
     * @dataProvider providerParseIfMatchThrowsExceptionWhenInvalidValueProvided
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testParseIfMatchThrowsExceptionWhenInvalidValueProvided($value) {
        Header::parseIfMatch($value);
    }

    public function providerParseIfMatchThrowsExceptionWhenInvalidValueProvided() {
        return array(
            array(null),
            array(''),
            array(false),
            array(true),
            array($this),
            array(array('test')),
        );
    }

}