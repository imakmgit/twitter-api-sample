<?php
/**
 * File contains class to search text in Twitter
 *
 * @category API
 * @package  Twitter_API_PHP
 * @author   Abani Meher <abanimeher@gmail.com>
 * @license  MIT License
 * @link     https://github.com/imakmgit/twitter-api-sample/blob/master/twitter-api/TwitterApi.php
 */

/**
 * Twitter Feed Search PHP Client
 *
 * @extends  TwitterConnector
 * @category API
 * @package  Twitter_Connector_PHP
 * @author   Abani Meher <abanimeher@gmail.com>
 * @license  MIT License
 * @link     https://github.com/imakmgit/twitter-api-sample/blob/master/twitter-api/TwitterApi.php
 */
class TwitterApi extends TwitterClient
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

    /**
     * convert special texts like url, hasg tags, mentioned users to anchors
     *
     * @param string $text - Text to filter and format
     *
     * @return string
     */
    public function formatSpecialText($text)
    {
        $url_search_pattern = '|([\w\d]*)\s?(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i';
        $url_replace_pattern = '$1 <a target="_blank" href="$2">$2</a>';

        $hash_tag_search_pattern = '/(^|\s)#(\w*[a-zA-Z_]+\w*)/';
        $hash_tag_replace_pattern = '\1<a target="_blank" href="http://twitter.com/search?q=%23\2">#<b>\2</b></a>';

        $screen_name_search_pattern = '/(^|\s)@(\w*[a-zA-Z_]+\w*)/';
        $screen_name_replace_pattern = '<a target="_blank" href="http://twitter.com/\2">@\2</a>';

        //convert urls and hashtags to anchor
        $text = preg_replace($url_search_pattern, $url_replace_pattern, $text);
        $text = preg_replace($hash_tag_search_pattern, $hash_tag_replace_pattern, $text);
        $text = preg_replace($screen_name_search_pattern, $screen_name_replace_pattern, $text);

        return $text;
    }
}
