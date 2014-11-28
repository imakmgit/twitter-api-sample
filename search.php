<?php
/**
 * File processes ajax search request and send response in json to client side
 *
 * @category API
 * @package  Twitter_HASTAG_SEARCH_APP
 * @author   Abani Meher <abanimeher@gmail.com>
 * @license  MIT License
 * @link
 */
require_once 'twitter-api/include.php';

//initialize reponse variable
$response = array();
$response['error'] = false;

//set Twitter API requires configs
$config = array(
    'oauth_token' => "190565997-XY5cH07tF2oQi5POg7AmeUcrkko6U4aqbIRAHIPq",
    'oauth_token_secret' => "BNnNyAeqQXvAmJ6yjJ6NoLK3f4AstmlU5sywO85esbs4D",
    'consumer_key' => "3HT8EjapNfWUoUkamZY3OdSnR",
    'consumer_secret' => "oQrB2IUOKcoT9rTIuAUdy6SbrW4EiQ2lI3SqjgtTmj8sSwYVv5"
);

//create api object
$twitter_api = new TwitterApi($config);

//enable development mode. All error details will be sent in response to client side
$twitter_api->enableDevelopmentMode();

//Disabled development mode.By default disabled. Error details will not be sent to client side
//$twitter_api->disableDevelopmentMode();

//enable error logging only. All errors will be logged to error log.
//This is ignored, if development mode is enabled.
//$twitter_api->enableErrorLogging();

//Disable error logging. Errors are not logged anywhere.
//This is ignored, if development mode is enabled.
//$twitter_api->enableErrorLogging();

//get search text from query string
if (empty($_GET['q']) || trim($_GET['q']) == '') {

    $response['error'] = true;
    $response['message'] = 'Provide a # tag to search in twitter feeds.';
    echo json_encode($response);
    exit;
}
$search_text = $_GET['q'];

//get other parameters to be used to filter search result
$extra_params['count'] = empty($_GET['count']) ? 50 : $_GET['count'];
if (isset($_GET['max_id'])) {

    $extra_params['max_id'] = $_GET['max_id'];
} elseif(isset($_GET['since_id'])) {

    $extra_params['since_id'] = $_GET['since_id'];
}

//call Twitter API
$result = $twitter_api->search($search_text, $extra_params);

//return response incase of error
if ($result->error) {

    echo json_encode($result);
    exit;
}

//if result has any status, filter data required and save in an array
if (count($result->statuses) > 0) {

    $tweets = $result->statuses;
    foreach ($tweets as $tweet) {

        //get tweets which has been retweeted atleat once
        if ($tweet->retweet_count > 0) {

            //if hash tag text is not found in tweet, search the tweet on which user has retwitted
            if (strpos(strtolower($tweet->text), strtolower(rawurldecode($search_text))) === false) {

                $retweeted_status = $tweet->retweeted_status;

                //save original tweet in an array
                $original_tweet = array(
                    'text' => $twitter_api->formatSpecialText($retweeted_status->text),
                    'user' => array(
                        'id' => $retweeted_status->user->id_str,
                        'name' => $retweeted_status->user->name,
                        'screen_name' => $retweeted_status->user->screen_name,
                        'description' => $retweeted_status->user->description,
                        'profile_image_url' => $retweeted_status->user->profile_image_url,
                    ),
                    'retweet_count' => $retweeted_status->retweet_count,
                );
            } else {
                $original_tweet = false;
            }

            $response['tweets'][] = array(
                'text' =>  $twitter_api->formatSpecialText($tweet->text),
                'user' => array(
                    'id' => $tweet->user->id_str,
                    'name' => $tweet->user->name,
                    'screen_name' => $tweet->user->screen_name,
                    'description' => $tweet->user->description,
                    'profile_image_url' => $tweet->user->profile_image_url,
                ),
                'retweet_count' => $tweet->retweet_count,
                'original_tweet' => $original_tweet,
            );
        }
    }
}

//save search meta details for next call for this search
if(property_exists($result->search_metadata, 'next_results')) {

    $response['next_results'] = $result->search_metadata->next_results;
} else {
    $response['next_results'] = null;
}
$response['refresh_url'] = $result->search_metadata->refresh_url;
echo json_encode($response);
exit;
