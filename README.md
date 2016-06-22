# Ride: HTTP Library

HTTP library of the PHP Ride framework.

This library helps you when working with the HTTP protocol.
It contains classes to work with requests, responses, headers, cookies, sessions and data URI's.

## Code Sample

Check this code sample to see the possibilities of this library:

```php
<?php

use ride\library\http\session\Session;
use ride\library\http\HttpFactory;
    
$httpFactory = new HttpFactory();
    
$request = $httpFactory->createRequestFromServer();
$response = $httpFactory->createResponse();

// basic request
$request->getMethod();
$request->isGet();
$request->isPost();
$request->getQueryParameter('variable'); 
$request->getBodyParameter('variable'); 
$request->getCookie('variable');
$request->getHeader('variable');

// request headers
$request->getUserAgent();
$request->getAccept();
$request->getAcceptCharset();
$request->getAcceptEncoding();
$request->getAcceptLanguage();
$request->getIfNoneMatch();
$request->getIfModifiedSince();
$request->isXmlHttpRequest();
$request->isNoCache();

// sessions
$request->setSession(new Session());

if ($request->hasSession()) {
    $session = $request->getSession();
    $session->get('variable');
}

// basic response
$response->setBody('{"variable":"value"}');
$response->setHeader('content-type', 'application/json');
$response->setCookie($httpFactory->createCookie('myCookie', 'value'));

// redirection
$response->setRedirect('http://server');
if ($response->willRedirect()) {
    $response->getLocation();
    $response->clearRedirect();
}

// handle caching
$response->setIsPrivate();
$response->setIsPublic();
$response->setNoCache();
$response->setNoStore();
$response->setExpires(time() + 50);
$response->setMaxAge(60);
$response->setSharedMaxAge(3600);

// handle not modified
$response->setLastModified(time());
$response->setETag('abc');

if ($response->isNotModified($request)) {
    $response->setNotModified();
}

// send a response
$response->send($request);

// working with data URI's
$dataUri = $httpFactory->createDataUri('Hello, World!', 'text/plain', null, true);
$dataUri->encode(); // 'data:text/plain;base64,SGVsbG8sIFdvcmxkIQ%3D%3D'

$dataUri = $httpFactory->createDataUriFromString($dataUri->encode());
$dataUri->getValue(); // Hello, World!
```
