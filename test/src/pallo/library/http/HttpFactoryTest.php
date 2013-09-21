<?php

namespace pallo\library\http;

use \PHPUnit_Framework_TestCase;

class HttpFactoryTest extends PHPUnit_Framework_TestCase {

	protected $httpFactory;

	protected function setUp() {
		$this->httpFactory = new HttpFactory();
	}

	public function testSetRequestClass() {
		$class = 'pallo\\library\\http\\TestRequest';

		$this->httpFactory->setRequestClass($class);

		$this->assertEquals($class, $this->httpFactory->getRequestClass());
	}

	/**
	 * @dataProvider providerSetRequestClassThrowsExceptionWhenInvalidClassProvided
	 * @expectedException pallo\library\http\exception\HttpException
	 */
	public function testSetRequestClassThrowsExceptionWhenInvalidClassProvided($class) {
		$this->httpFactory->setRequestClass($class);
	}

	public function providerSetRequestClassThrowsExceptionWhenInvalidClassProvided() {
		return array(
			array($this),
			array('UnexistantClass'),
			array('pallo\\library\\http\\Header'),
		);
	}

	public function testSetResponseClass() {
		$class = 'pallo\\library\\http\\TestResponse';

		$this->httpFactory->setResponseClass($class);

		$this->assertEquals($class, $this->httpFactory->getResponseClass());
	}

	/**
	 * @dataProvider providerSetResponseClassThrowsExceptionWhenInvalidClassProvided
	 * @expectedException pallo\library\http\exception\HttpException
	 */
	public function testSetResponseClassThrowsExceptionWhenInvalidClassProvided($class) {
		$this->httpFactory->setResponseClass($class);
	}

	public function providerSetResponseClassThrowsExceptionWhenInvalidClassProvided() {
		return array(
			array($this),
			array('UnexistantClass'),
			array('pallo\\library\\http\\Header'),
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
		$_SERVER = array();
		$_POST = array();
		$headers = new HeaderContainer();
		$headers->addHeader('Host', 'localhost');
		$request = new Request('/', 'GET', 'HTTP/1.0', $headers);

		$result = $this->httpFactory->createRequestFromServer();
		$this->assertEquals($request, $result);

		$_SERVER = array(
			'SCRIPT_NAME' => 'index.php',
		);
		$request = new Request('/index.php', 'GET', 'HTTP/1.0', $headers);

		$result = $this->httpFactory->createRequestFromServer();
		$this->assertEquals($request, $result);

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
		$headers->setHeader('Host', 'server');
		$headers->addHeader('Connection', 'keep-alive');

		$request = new Request('/path/to?action=content', 'POST', 'HTTP/1.1', $headers, $_POST);
		$request->setIsSecure(true);

		$result = $this->httpFactory->createRequestFromServer();

		$this->assertEquals($request, $result);
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
	 * @expectedException pallo\library\http\exception\HttpException
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