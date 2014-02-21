# Ride: HTTP Library

HTTP library of the PHP Ride framework.

## Code Sample

Check this code sample to see the possibilities of this library:

    <?php
    
    use ride\library\http\HttpFactory;
        
    $httpFactory = new HttpFactory();
        
    $request = $httpFactory->createRequestFromServer();
    $response = $httpFactory->createResponse();
    
    $request->getMethod();
    $request->isPost();
    $request->getQueryParameter('variable'); 
    $request->getBodyParameter('variable'); 
    $request->getCookie('variable');
    $request->getHeader('variable');
    $request->getUserAgent();
    $request->isXmlHttpRequest();
    
    $time = time();
    $etag = 'abc';
    $body = '{"variable":"value"}';
    
    $response->setRedirect('http://server');
    $response->willRedirect();
    $response->clearRedirect();
    
    $response->setLastModified($time);
    $response->setETag($etag);
    $response->setHeader('content-type', 'application/json');
    $response->setBody($body);
    
    if ($response->isNotModified($request)) {
        $response->setNotModified();
    }
    
    $response->send($request);
