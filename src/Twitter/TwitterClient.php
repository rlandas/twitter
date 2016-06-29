<?php

namespace Rlandas\Twitter;

class TwitterClient
{

    /**
     * Twitter config
     *
     * @var array
     */
    protected $config = [
        'oauth_access_token' => null,
        'oauth_access_token_secret' => null,
        'consumer_key' => null,
        'consumer_secret' => null,
        'oauth_nonce' => null,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => null,
        'oauth_version' => '1.0'
    ];

    /**
     * Twitter API URL
     *
     * @var string
     */
    protected $url = 'https://api.twitter.com/1.1';

    /**
     * Constructor
     *
     * @param string $oauthAccessToken
     * @param string $oauthAccessTokenSecret
     * @param string $consumerKey
     * @param string $consumerSecret
     */
    public function __construct ($oauthAccessToken, $oauthAccessTokenSecret, $consumerKey, $consumerSecret)
    {
        $this->config['oauth_access_token'] = $oauthAccessToken;
        $this->config['oauth_access_token_secret'] = $oauthAccessTokenSecret;
        $this->config['consumer_key'] = $consumerKey;
        $this->config['consumer_secret'] = $consumerSecret;
    }

    /**
     * Get the response using GET method
     *
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    public function get ($endpoint, array $params = [])
    {
        // define the cURL method to use
        $method = 'GET';

        // get the OAuth parameters
        $oauth = $this->getOauth();

        // break down the endpoint if necessary
        $endPointParts = parse_url($endpoint);
        isset($endPointParts['query']) ? parse_str($endPointParts['query'], $query) : $query = [];
        $query = array_merge($params, $query);

        $path = $endPointParts['path'];
        $baseUrl = $this->url . '/' . $path;
        $requestUrl = $this->url . '/' . $path . '?' . http_build_query($query);

        // build the cURL header
        $requestHeader = strtoupper($method) . '&' . rawurlencode($baseUrl) . '&' . $this->buildBaseString(array_merge($oauth, $query));

        // create the signing key and the oauth signature
        $signingKey = rawurlencode($this->config['consumer_secret']) . '&' . rawurlencode($this->config['oauth_access_token_secret']);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $requestHeader, $signingKey, true));

        // build the request header
        $header = [
            $this->buildAuthorizationHeader($oauth),
            'Expect:'
        ];

        // define the cURL options
        $curlOpts = [
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        // create a connection to the Twitter API
        $curl = curl_init();
        curl_setopt_array($curl, $curlOpts);
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

//         // create response headers
//         if (isset($info['content_type']) && isset($info['size_download'])) {
//             header('Content-Type: ' . $info['content_type']);
//             header('Content-Length: ' . $info['size_download']);
//         }

        return $response;
    }

    /**
     * Get the response using POST method
     *
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    public function post ($endpoint, array $params)
    {
        // define the cURL method to use
        $method = 'POST';

        // get the OAuth parameters
        $oauth = $this->getOauth();

        // break down the endpoint if necessary
        $endPointParts = parse_url($endpoint);
        isset($endPointParts['query']) ? parse_str($endPointParts['query'], $query) : $query = [];
        $query = array_merge($params, $query);

        $path = $endPointParts['path'];
        $baseUrl = $this->url . '/' . $path;
        $requestUrl = $this->url . '/' . $path . '?' . http_build_query($query);

        // build the cURL header
        $requestHeader = strtoupper($method) . '&' . rawurlencode($baseUrl) . '&' . $this->buildBaseString(array_merge($oauth, $query));

        // create the signing key and the oauth signature
        $signingKey = rawurlencode($this->config['consumer_secret']) . '&' . rawurlencode($this->config['oauth_access_token_secret']);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $requestHeader, $signingKey, true));

        // build the request header
        $header = [
            $this->buildAuthorizationHeader($oauth),
            'Expect:'
        ];

        // define the cURL options
        $curlOpts = [
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        // create a connection to the Twitter API
        $curl = curl_init();
        curl_setopt_array($curl, $curlOpts);
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        // create response headers
        if (isset($info['content_type']) && isset($info['size_download'])) {
            header('Content-Type: ' . $info['content_type']);
            header('Content-Length: ' . $info['size_download']);
        }

        return $response;
    }

    /**
     * Get the user timeline
     *
     * @param array $params
     * @return string
     */
    public function getUserTimeLine (array $params)
    {
        $endpoint = 'statuses/user_timeline.json?' . http_build_query($params);
        return $this->get($endpoint, $params);
    }

    /**
     * Post a status update
     *
     * @param array $params
     * @return string
     */
    public function postStatusUpdate (array $params)
    {
        $endpoint = 'statuses/update.json';
        return $this->post($endpoint, $params);
    }

    // @todo more to come

    /**
     * Get the OAuth parameters
     *
     * @return array
     */
    protected function getOauth ()
    {
        $oauth = [
            'oauth_consumer_key' => $this->config['consumer_key'],
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->config['oauth_access_token'],
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        ];

        return $oauth;
    }

    /**
     * Builds the URL params from array
     *
     * @param array $params
     * @return string
     */
    protected function buildBaseString (array $params)
    {
        ksort($params);

        $urlParams = [];
        foreach ($params as $key => $value) {
            $urlParams[] = $key . '=' . rawurlencode($value);
        }
        return rawurlencode(implode('&', $urlParams));
    }

    /**
     * Builds the cURL request header
     *
     * @param array $params
     * @return string
     */
    protected function buildAuthorizationHeader (array $params)
    {
        $header = [];
        foreach ($params as $key => $value) {
            $header[] = sprintf("%s=\"%s\"", $key, rawurlencode($value));
        }
        return 'Authorization: OAuth ' . implode(', ', $header);
    }

    /**
     * Get the $config
     *
     * @return multitype: $config
     */
    public function getConfig ()
    {
        return $this->config;
    }

    /**
     * Set the $config
     *
     * @param multitype: $config
     * @return self
     */
    public function setConfig ($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Get the $url
     *
     * @return string $url
     */
    public function getUrl ()
    {
        return $this->url;
    }

    /**
     * Set the $url
     *
     * @param string $url
     * @return self
     */
    public function setUrl ($url)
    {
        $this->url = $url;
        return $this;
    }
}