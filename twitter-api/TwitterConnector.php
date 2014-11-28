<?php
/**
 * File contains base class to generate required oauth data, authorization header
 * and make call to supplied Twitter API url
 *
 * @category API
 * @package  Twitter_API_PHP
 * @author   Abani Meher <abanimeher@gmail.com>
 * @license  MIT License
 * @link
 */

/**
 * Twitter API PHP Client : PHP wrapper for v1.1 of Twitter API
 *
 * @category API
 * @package  Twitter_Connector_PHP
 * @author   Abani Meher <abanimeher@gmail.com>
 * @license  MIT License
 * @link
 */
class TwitterConnector
{
    private $_oauth_token;
    private $_oauth_token_secret;
    private $_consumer_key;
    private $_consumer_secret;
    private $_request_data;
    private $_request_method;
    private $_oauth_data;
    private $_url;

    private $_error_message;
    private $_error_code;
    private $_response;

    private $_log_errors;
    private $_development_mode;

    const API_BASE_URL = 'https://api.twitter.com/1.1/';

    /**
     * Creates a Twitter API connector.
     * Requires php5-curl
     *
     * @param array $config - array of keys oauth_token, oauth_token_secret,
     *                        consumer_key, consumer_secret and all are required
     *
     * @throws Exception - when curl libray is missing or
     *                     any of the required config parameter is missing
     *
     * @return void
     */
    function __construct(array $config)
    {
        //curl is required to make calls to API url. If not loaded, throw exception
        if (!in_array('curl', get_loaded_extensions())) {

            throw new Exception('cURL is missing. Install cURL.');
        }

        //if any of the required config param is missing, throw exception
        if (!isset($config['oauth_token'])
            || !isset($config['oauth_token_secret'])
            || !isset($config['consumer_key'])
            || !isset($config['consumer_secret'])
        ) {
            $message = 'oauth_token, oauth_token_secret, consumer_key and ' .
                        'consumer_secret are required config parameters.';
            throw new Exception($message);
        }

        //set all config params
        $this->_oauth_token = $config['oauth_token'];
        $this->_oauth_token_secret = $config['oauth_token_secret'];
        $this->_consumer_key = $config['consumer_key'];
        $this->_consumer_secret = $config['consumer_secret'];

        $this->_log_errors = false;
        $this->_development_mode = false;

    }

    /**
     * Enables API error logging
     *
     * @return void
     */
    public function enableErrorLogging()
    {
        $this->_log_errors = true;
    }

    /**
     * Disables API error logging
     *
     * @return void
     */
    public function disableErrorLogging()
    {
        $this->_log_errors = false;
    }

    /**
     * Returns log error mode status
     *
     * @return boolean
     */
    public function isErrorLoggingEnabled()
    {
        return $this->_log_errors;
    }

    /**
     * Enables API development mode
     *
     * @return void
     */
    public function enableDevelopmentMode()
    {
        $this->_development_mode = true;
    }

    /**
     * Disables API development mode
     *
     * @return void
     */
    public function disableDevelopmentMode()
    {
        $this->_development_mode = false;
    }

    /**
     * Returns API development mode status
     *
     * @return boolean
     */
    public function isDevelopmentModeEnabled()
    {
        return $this->_development_mode;
    }

    /*
     * Returns json decode error message text by its error code
     *
     * @return string
     */
    private function _getJsonDecodeError($error_code)
    {
        $errors = array(
            JSON_ERROR_NONE             => 'No error has occurred',
            JSON_ERROR_DEPTH            => 'The maximum stack depth has been exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR        => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX           => 'Syntax error',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            JSON_ERROR_RECURSION        => 'One or more recursive references in the value to be encoded',
            JSON_ERROR_INF_OR_NAN       => 'One or more NAN or INF values in the value to be encoded',
            JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given',
        );

        return (isset($errors[$error_code]) ? $errors[$error_code] : 'Unknown error code :' . $error_code);
    }

    /**
     * creates base string for the signature
     *
     * @see https://dev.twitter.com/oauth/overview/creating-signatures
     * - Convert the HTTP Method to uppercase and set the output string equal to this value.
     * - Append the '&' character to the output string.
     * - Percent encode the URL and append it to the output string.
     * - Append the '&' character to the output string.
     * - Percent encode the parameter string and append it to the output string.
     *
     * @return string
     */
    private function _createSignatureBaseString()
    {
        $params = array();

        //sort array data based on key
        ksort($this->_oauth_data);

        foreach ($this->_oauth_data as $key => $value) {

            $params[] = $key . "=" . $value;
        }

        return $this->_request_method . "&" .
                rawurlencode($this->_url) . '&' .
                rawurlencode(implode('&', $params));
    }

    /**
     * Generates oAuth data for the current request
     *
     * @see https://dev.twitter.com/oauth/overview/creating-signatures
     *
     * @return void
     */
    private function _generateOAuthData()
    {
        //store all data in array
        $this->_oauth_data = array(
            'oauth_consumer_key' => $this->_consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->_oauth_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );

        //if request methiod is GET, include all get params also in oauth data array
        if ($this->_request_method == 'GET') {

            foreach ($this->_request_data as $key => $value) {

                $this->_oauth_data[$key] = rawurlencode($value);
            }
        }

        //create signature base string
        $signature_base_string = $this->_createSignatureBaseString();

        //create a signing key
        $signing_key = rawurlencode($this->_consumer_secret) . '&' .
                        rawurlencode($this->_oauth_token_secret);

        //generate a signature for the request
        $oauth_signature = base64_encode(hash_hmac('sha1', $signature_base_string, $signing_key, true));

        //set signature value for the request in oauth data
        $this->_oauth_data['oauth_signature'] = $oauth_signature;
    }

    /**
     * creates HTTP header for OAuth Authorization
     *
     * @return string
     */
    private function _createAuthorizationHeader()
    {
        $data = array();

        //Percent encode all values
        foreach ($this->_oauth_data as $key => $value) {

            $data[] = $key . '="' . rawurlencode($value) . '"';
        }

        return 'Authorization: OAuth ' .  implode(', ', $data);
    }

    /**
     * checkes for errors in recieved response in curl request
     *
     * @param string $response        - response text received in curl request
     * @param object $request_handler - curl request handler object
     *
     * @return boolean
     */
    private function _hasErrorInResponse($response, $request_handler)
    {
        //if no response is received, check curl error and error number
        if ($response === false) {

            $this->_error_code = curl_errno($request_handler);
            $this->_error_message = array(
                'source' => 'curl',
                'text' => curl_error($request_handler)
            );

            return true;
        }

        //decode json data
        $this->_response = json_decode($response);

        //check if there is json decode error
        if (is_null($this->_response)) {

            $this->_error_code = json_last_error();
            $this->_error_message = array(
                'source' => 'json_decode',
                'text' => $this->_getJsonDecodeError($this->_error_code),
                'twitter_response' => base64_encode($response),
                'instruction' => 'use base64_decode to see original twitter_response'
            );
            return true;
        }

        //if twitter error is received, throw exception
        if (property_exists($this->_response, 'errors')) {

            $this->_error_code = 0;
            $this->_error_message = array(
                'source' => 'twitter',
                'text' => json_encode($this->_response->errors)
            );
            return true;
        }

        return false;
    }

    /**
     * Makes call to the provided API url
     *
     * @return object
     *
     * @throws Exception - if curl request fails or API returns error
     */
    public function sendRequest()
    {
        //generate oauth data for this request
        $this->_generateOAuthData();

        //get HTTP header authorization data
        $header = array($this->_createAuthorizationHeader(), 'Expect: ');

        //create request handler
        $request_handler = curl_init();

        //set request options
        curl_setopt($request_handler, CURLOPT_HTTPHEADER, $header);
        curl_setopt($request_handler, CURLOPT_HEADER, false);
        curl_setopt($request_handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request_handler, CURLOPT_URL, $this->_url);

        //set post fields if request type is post, else append as query string in url
        if ($this->_request_method == 'POST') {

            curl_setopt($request_handler, CURLOPT_POSTFIELDS, $this->_request_data);
        } else {

            $query_string = count($this->_request_data) > 0 ?
                            ('?' . http_build_query($this->_request_data)) : '';
            curl_setopt($request_handler, CURLOPT_URL, $this->_url . $query_string);
        }

        //execute curl
        $response = curl_exec($request_handler);

        //check if response has any error
        if($this->_hasErrorInResponse($response, $request_handler)) {

            throw new Exception(json_encode($this->_error_message), $this->_error_code);
        }

        //close request handler
        curl_close($request_handler);

        //return decoded response
        return $this->_response;
    }

    /**
     * Returns oAuth data created for the current request
     *
     * @return array
     */
    public function getOAuthData()
    {
        return $this->_oauth_data;
    }

    /**
     * sets request details like - request data, request url and request method
     *
     * @param array  $request_data   - request data to be sent in request
     * @param string $url            - url to which request is sent
     * @param string $request_method - request method of the request.
     *
     * @return void
     *
     * @throws Exception - if request_method is not GET or POST, throw exception
     */
    public function setRequestInfo(array $request_data, $url, $request_method)
    {
        if (!in_array(strtoupper($request_method), array('POST', 'GET'))) {

            throw new Exception('Allowed request methods are POST or GET.');
        }

        //set the request info
        $this->_request_data = $request_data;
        $this->_request_method = strtoupper($request_method);
        $this->_url = $url;
    }

    /**
     * Logs exception in file or in response that is sent to client as per the configuration
     *
     * @param object $exception - Error details recived
     * @param object $result    - result that will be sent to client side
     *
     * @return object
     */
    public function logException($exception, $result)
    {
        //send in response if development mode is enabled
        if ($this->isDevelopmentModeEnabled()) {

            $result->debug_message['message'] = $exception->getMessage();
            $result->debug_message['code'] = $exception->getCode();
            $result->debug_message['trace'] = $exception->getTraceAsString();
        } elseif(!$this->isDevelopmentModeEnabled() && $this->isErrorLoggingEnabled()) {

            //log in a file/db. For now log in error_log
            error_log('[TWITTER-API-ERROR-LOG]Request Data:- ' . json_encode($this->request_data));
            error_log('[TWITTER-API-ERROR-LOG]Message:- ' . $exception->getMessage());
            error_log('[TWITTER-API-ERROR-LOG]Code:- ' . $exception->getCode());
            error_log('[TWITTER-API-ERROR-LOG]Trace:- ' . $exception->getTraceAsString());
        }

        return $result;
    }
}
