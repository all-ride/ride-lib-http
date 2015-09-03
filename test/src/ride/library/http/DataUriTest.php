<?php

namespace ride\library\http;

use \PHPUnit_Framework_TestCase;

class DataUriTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $data = 'data';
        $mime = 'mime';
        $encoding = 'charset';
        $isBase64 = true;

        $dataUri = new DataUri($data, $mime, $encoding, $isBase64);

        $this->assertEquals($data, $dataUri->getData());
        $this->assertEquals($mime, $dataUri->getMimeType());
        $this->assertEquals($encoding, $dataUri->getEncoding());
        $this->assertEquals($isBase64, $dataUri->isBase64());
    }

    /**
     * @dataProvider providerTest
     */
    public function testDecode($instance, $string) {
        $result = DataUri::decode($string);

        $this->assertEquals($instance, $result);
    }

    /**
     * @dataProvider providerTest
     */
    public function testEncode($instance, $string) {
        $this->assertEquals($string, $instance->encode());
    }

    public function providerTest() {
        return array(
            array(new DataUri('Hello, World!', DataUri::DEFAULT_MIME_TYPE, DataUri::DEFAULT_ENCODING, false), 'data:,Hello%2C%20World%21'),
            array(new DataUri('Hello, World!', 'text/plain', null, true), 'data:text/plain;base64,SGVsbG8sIFdvcmxkIQ%3D%3D'),
            array(new DataUri('<h1>Hello, World!</h1>', 'text/html', null, false), 'data:text/html,%3Ch1%3EHello%2C%20World%21%3C%2Fh1%3E'),
        );
    }

}
