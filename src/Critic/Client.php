<?php

namespace Critic;

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
     * Setup the header array for any request to Critic
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