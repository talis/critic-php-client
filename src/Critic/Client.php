<?php

namespace Critic;

require_once('critic-php-client/vendor/domnikl/statsd/lib/Domnikl/Statsd/Connection.php');
require_once('critic-php-client/vendor/domnikl/statsd/lib/Domnikl/Statsd/Connection/Blackhole.php');
require_once('critic-php-client/vendor/domnikl/statsd/lib/Domnikl/Statsd/Client.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/BasicClientInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/ClientInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientOptionsInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/OptionInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/AbstractOption.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Profile/ServerProfileInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/Processor/CommandProcessingInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/CommandInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/AbstractCommand.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/ConnectionSelect.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/PrefixableCommandInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/PrefixableCommand.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/StringGet.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/TransactionMulti.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/StringSet.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/KeyExpire.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Command/TransactionExec.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Profile/ServerProfile.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Profile/ServerVersion26.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientProfile.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/ConnectionFactoryInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/ConnectionParametersInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/ConnectionParameters.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/ConnectionInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/SingleConnectionInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/AbstractConnection.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/ResponseObjectInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/ResponseQueued.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/StreamConnection.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Connection/ConnectionFactory.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientConnectionFactory.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientCluster.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientReplication.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientPrefix.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientExceptions.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Option/ClientOptions.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/ExecutableContextInterface.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Transaction/MultiExecContext.php');
require_once('critic-php-client/vendor/predis/predis/lib/Predis/Client.php');
require_once('critic-php-client/vendor/talis/persona-php-client/src/personaclient/PersonaClient.php');

require_once('critic-php-client/src/Critic/Exceptions/ReviewException.php');
class Client {

    protected $clientId;
    protected $clientSecret;

    /**
     * @var \personaclient\PersonaClient
     */
    protected $personaClient;

    /**
     * @var array
     */
    protected $personaConnectValues = array();

    /**
     * @var string
     */
    protected $criticBaseUrl;

    /**
     * @var \Guzzle\Http\Client
     */
    protected $httpClient;

    /**
     * @param array $personaConnectValues
     */
    public function __construct($criticBaseUrl, $personaConnectValues = array())
    {
        $this->criticBaseUrl = $criticBaseUrl;
        $this->personaConnectValues = $personaConnectValues;
    }

    /**
     * @param array $personaConnectValues
     */
    public function setPersonaConnectValues($personaConnectValues)
    {
        $this->personaConnectValues = $personaConnectValues;
    }

    /**
     * For mocking
     * @return \personaclient\PersonaClient
     */
    protected function getPersonaClient()
    {
        if(!isset($this->personaClient))
        {
            $this->personaClient = new \personaclient\PersonaClient($this->personaConnectValues);
        }

        return $this->personaClient;
    }

    /**
     * Allows PersonaClient override, if PersonaClient has been initialized elsewhere
     * @param \personaclient\PersonaClient $personaClient
     */
    public function setPersonaClient(\personaclient\PersonaClient $personaClient)
    {
        $this->personaClient = $personaClient;
    }

    /**
     * For mocking
     * @return \Guzzle\Http\Client
     */
    protected function getHTTPClient()
    {
        if(!$this->httpClient)
        {
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/HasDispatcherInterface.php');
            require_once('critic-php-client/vendor/symfony/event-dispatcher/Symfony/Component/EventDispatcher/Event.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/ToArrayInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/Event.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/AbstractHasDispatcher.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/ClientInterface.php');
            require_once('critic-php-client/vendor/symfony/event-dispatcher/Symfony/Component/EventDispatcher/EventSubscriberInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/RedirectPlugin.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/ToArrayInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/Collection.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/RequestFactoryInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/MessageInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/Header/HeaderFactoryInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/Header/HeaderInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/Header.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/Header/HeaderFactory.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/Header/HeaderCollection.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/AbstractMessage.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/RequestInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Url.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/Exception/GuzzleException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/Exception/RuntimeException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Exception/HttpException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Exception/HttpException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Exception/RequestException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Exception/BadResponseException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Exception/ServerErrorResponseException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/Request.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/EntityEnclosingRequestInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/QueryAggregator/QueryAggregatorInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/QueryAggregator/PhpAggregator.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/QueryString.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/EntityEnclosingRequest.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/RequestFactory.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/Version.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Curl/CurlVersion.php');
            require_once('critic-php-client/vendor/symfony/event-dispatcher/Symfony/Component/EventDispatcher/EventDispatcherInterface.php');
            require_once('critic-php-client/vendor/symfony/event-dispatcher/Symfony/Component/EventDispatcher/EventDispatcher.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Parser/UriTemplate/UriTemplateInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Parser/UriTemplate/UriTemplate.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Parser/ParserRegistry.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Stream/StreamInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Stream/Stream.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/EntityBodyInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/EntityBody.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Message/Response.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Curl/RequestMediator.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Curl/CurlHandle.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Curl/CurlMultiInterface.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Common/Exception/ExceptionCollection.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Exception/MultiTransferException.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Curl/CurlMulti.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Curl/CurlMultiProxy.php');
            require_once('critic-php-client/vendor/guzzle/guzzle/src/Guzzle/Http/Client.php');
            $this->httpClient = new \Guzzle\Http\Client();
        }
        return $this->httpClient;
    }

    /**
     *
     *
     * @param string $clientId
     * @param string $clientSecret
     * @throws \Exception|\Guzzle\Http\Exception\ClientErrorResponseException
     * @throws Exceptions\UnauthorisedAccessException
     */
    public function createReview($postFields, $clientId, $clientSecret)
    {
        try
        {
            $client = $this->getHTTPClient();
            $headers = $this->getHeaders($clientId, $clientSecret);

            $request = $client->post($this->criticBaseUrl, $headers, $postFields);

            $response = $request->send();



            if($response->getStatusCode() == 201)
            {
                $body = json_decode($response->getBody(true));
                return $body->id;
            }
            else
            {
                throw new \Critic\Exceptions\ReviewException();
            }
        }
        /** @var \Guzzle\Http\Exception\ClientErrorResponseException $e */
        catch(\Guzzle\Http\Exception\ClientErrorResponseException $e)
        {
            $response = $e->getResponse();
            $error = $this->processErrorResponseBody($response->getBody(true));
            switch($response->getStatusCode())
            {
                case 403:
                case 401:
                    throw new \Critic\Exceptions\UnauthorisedAccessException($error['message'], $error['error_code'], $e);
                    break;
                default:
                    throw $e;
            }
        }

    }

    /**
     * Setup the header array for any request to Manifesto
     * @param string $clientId
     * @param string $clientSecret
     * @return array
     */
    protected function getHeaders($clientId, $clientSecret)
    {
        $arrPersonaToken = $this->getPersonaClient()->obtainNewToken($clientId, $clientSecret);
        $personaToken = $arrPersonaToken['access_token'];
        $headers = array(
            'Content-Type'=>'application/json',
            'Authorization'=>'Bearer '.$personaToken
        );
        return $headers;
    }

    protected function processErrorResponseBody($responseBody)
    {

        $error = array('error_code'=>null, 'message'=>null);
        $response = json_decode($responseBody, true);

        if(isset($response['error_code']))
        {
            $error['error_code'] = $response['error_code'];
        }

        if(isset($response['message']))
        {
            $error['message'] = $response['message'];
        }

        return $error;
    }
}