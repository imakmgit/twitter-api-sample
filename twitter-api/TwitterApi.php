<?php
/**
 * File contains class to search text in Twitter
 *
 * @category API
 * @package  Twitter_API_PHP
 * @author   Abani Meher <abanimeher@gmail.com>
 * @license  MIT License
 * @link
 */

/**
 * Twitter Feed Search PHP Client
 *
 * @extends TwitterConnector
 * @category API
 * @package  Twitter_Connector_PHP
 * @author   Abani Meher <abanimeher@gmail.com>
 * @license  MIT License
 * @link
 */
class TwitterApi extends TwitterConnector
{
    /**
     * Searchs in Twitter feeds for specified text
     *
     * @param string $text         - Text to be searched in feed
     * @param array  $extra_params - extra parameters to filter search result
     *
     * @return object
     */
    public function search($text, array $extra_params = array())
    {
        $url = self::API_BASE_URL . 'search/tweets.json';
        $request_data = array_merge(array('q' => $text), $extra_params);

        try {

            $this->setRequestInfo($request_data, $url, 'GET');
            $result = $this->sendRequest();
            $result->error = false;
        } catch(Exception $exception) {

            $result = new stdClass();
            $result->error = true;
            $result->message = 'Oops! There seems to be a problem.' .
                                'Our technical team has been informed about the error.' .
                                'They will get it fixed asap. We hope to see you soon again.';
             $this->logException($exception, $result);

        }

        return $result;
    }
}
