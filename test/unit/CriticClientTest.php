<?php

if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

/**
 * Unit tests for CriticClient
 */
class CriticClientTest extends PHPUnit_Framework_TestCase
{
    private $criticBaseUrl;
    private $criticClient;
    private $personaConfig;
    private $postFields;

    protected function setUp()
    {
        $this->criticBaseUrl = 'http://listreviews.talis.com/test/reviews';
        $this->criticClient = new \Critic\Client($this->criticBaseUrl);
        $this->personaConfig = array(
            'userAgent' => 'userAgentVal',
            'persona_host' => 'persona_host_val',
            'persona_oauth_route' => 'persona_oauth_route_val',
        );
        $this->postFields = array('listUri' => 'http://somelist');
    }

    /**
     * Exception thrown when response code is 200
     *
     * @expectedException \Critic\Exceptions\ReviewException
     */
    function testCreateReviewException()
    {
        $_COOKIE['access_token'] = json_encode(array('access_token' => 'some_token'));
        $this->setUp();

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(200, null, json_encode(array())));
        $client = new Guzzle\Http\Client();
        $client->addSubscriber($plugin);

        /** @var \Critic\Client | PHPUnit_Framework_MockObject_MockObject $criticClient */
        $criticClient = $this->getMock('\Critic\Client', array('getHTTPClient'), array($this->criticBaseUrl));
        $criticClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($client));
        $criticClient->setPersonaConnectValues($this->personaConfig);

        $criticClient->createReview($this->postFields, '', '');
    }

    /**
     * 401 response triggers UnauthorisedAccessException
     *
     * @expectedException \Critic\Exceptions\UnauthorisedAccessException
     */
    function testCreateReviewGuzzleException()
    {
        $_COOKIE['access_token'] = json_encode(array('access_token' => 'some_token'));
        $this->setUp();

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(401, null, json_encode(array())));
        $client = new Guzzle\Http\Client();
        $client->addSubscriber($plugin);

        /** @var \Critic\Client | PHPUnit_Framework_MockObject_MockObject $criticClient */
        $criticClient = $this->getMock('\Critic\Client', array('getHTTPClient'), array($this->criticBaseUrl));
        $criticClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($client));
        $criticClient->setPersonaConnectValues($this->personaConfig);

        $criticClient->createReview($this->postFields, '', '');
    }

    /**
     * No exception thrown when access token found in cookies
     */
    function testCreateReviewWithCookieSuccess()
    {
        $_COOKIE['access_token'] = json_encode(array('access_token' => 'some_token'));
        $this->setUp();

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(201, null, json_encode(array('id'=>'someId'))));
        $client = new Guzzle\Http\Client();
        $client->addSubscriber($plugin);

        /** @var \Critic\Client | PHPUnit_Framework_MockObject_MockObject $criticClient */
        $criticClient = $this->getMock('\Critic\Client', array('getHTTPClient'), array($this->criticBaseUrl));
        $criticClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($client));
        $criticClient->setPersonaConnectValues($this->personaConfig);

        $id = $criticClient->createReview($this->postFields, '', '');
        $this->assertEquals('someId', $id);
    }

    /**
     * Access token in cookies but not used when useCookies is false
     *
     * @expectedException \Exception
     * @expectedExceptionMessage You must specify clientId, and clientSecret to obtain a new token
     */
    function testCreateReviewWithUseCookiesFalseFailure()
    {
        $_COOKIE['access_token'] = json_encode(array('access_token' => 'some_token'));
        $this->setUp();

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(201, null, json_encode(array())));
        $client = new Guzzle\Http\Client();
        $client->addSubscriber($plugin);

        /** @var \Critic\Client | PHPUnit_Framework_MockObject_MockObject $criticClient */
        $criticClient = $this->getMock('\Critic\Client', array('getHTTPClient'), array($this->criticBaseUrl));
        $criticClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($client));
        $criticClient->setPersonaConnectValues($this->personaConfig);

        $criticClient->createReview($this->postFields, '', '', array('useCookies'=>false));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Did not retrieve successful response code from persona: -1
     */
    function testCreateReviewWithInvalidPersonaConfigFails()
    {
        $_COOKIE['access_token'] = json_encode(array('access_token' => 'some_token'));
        $this->setUp();

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(201, null, json_encode(array())));
        $client = new Guzzle\Http\Client();
        $client->addSubscriber($plugin);

        /** @var \Critic\Client | PHPUnit_Framework_MockObject_MockObject $criticClient */
        $criticClient = $this->getMock('\Critic\Client', array('getHTTPClient'), array($this->criticBaseUrl));
        $criticClient->expects($this->once())->method('getHTTPClient')->will($this->returnValue($client));
        $criticClient->setPersonaConnectValues($this->personaConfig);

        $criticClient->createReview($this->postFields, 'someClientId', 'someClientSecret', array('useCookies'=>false));
    }
}
