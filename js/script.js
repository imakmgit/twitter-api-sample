(function () {
    var next_results = false,
        refresh_url = false,
        last_search_text = '',
        request = false,
        showMessage = function (type, text) {

            $('.message').remove();
            var element = $('<div class="message ' + type + '">' + text + '<div>');
            $('body').append(element);
            setTimeout(function () { element.fadeOut(1000); }, 6000);
        },
        renderTweets = function (tweets) {
            var container = $('.tweets');

            if (next_results == null) {
                $('.tweet').removeClass('prepended');
            }

            if ($('.tweet').length === 0) {
                showMessage('success', 'Displaying ' + tweets.length + ' tweets for text "' + last_search_text +
                                        '" which have been retweeted atleast once. Click "Load more" button' +
                                        '(available at bottom of the page also) to load more tweets.');
            } else {
                showMessage('success', 'Fetched ' + tweets.length + ' more tweets. Showing ' +
                                        (tweets.length + $('.tweet').length) + ' tweets in total.');
            }

            for(i = 0 ; i < tweets.length; i++) {
                if(tweets[i].original_tweet != false) {
                    var original_tweet = '<div class="retweet-info">Retwitted on following status (so Twitter returned above also in response)</div>' +
                            '<div class="tweet retwitted">' +
                                '<div class="user-image">' +
                                    '<img src="' + tweets[i].original_tweet.user.profile_image_url + '" title="' + tweets[i].original_tweet.user.screen_name + '"/>' +
                                '</div>' +
                                '<div class="tweet-info">' +
                                    '<div class="user-name" title="' + tweets[i].original_tweet.user.description + '">' +
                                        '<a href="http://twitter.com/' + tweets[i].original_tweet.user.screen_name + '">' + tweets[i].original_tweet.user.name + '</a>' +
                                        '<span class="screen-name">(@' + tweets[i].original_tweet.user.screen_name + ')</span>' +
                                    '</div>' +
                                    '<div class="tweet-text">' + tweets[i].original_tweet.text + '</div>' +
                                    '<div class="date"></div>' +
                                '</div>' +
                                '<div class="clear"></div>' +
                            '</div>';
                } else {
                    var original_tweet = '';
                }

                var tweet =  '<div class="tweet' + (next_results == null ? ' prepended' : '') + '">' +
                                '<div class="user-image">' +
                                    '<img src="' + tweets[i].user.profile_image_url + '" title="' + tweets[i].user.screen_name + '"/>' +
                                '</div>' +
                                '<div class="tweet-info">' +
                                    '<div class="user-name" title="' + tweets[i].user.description + '">' +
                                        '<a href="http://twitter.com/' + tweets[i].user.screen_name + '">' + tweets[i].user.name + '</a>' +
                                        '<span class="screen-name">(@' + tweets[i].user.screen_name + ')</span>' +
                                    '</div>' +
                                    '<div class="tweet-text">' + tweets[i].text + '</div>' +
                                    '<div class="date"></div>' +
                                    '<div class="retweet-count"> <b>Retweet Count :</b> ' + tweets[i].retweet_count +  '</div>' +
                                    original_tweet +
                                '</div>' +
                                '<div class="clear"></div>' +
                            '</div>';

                if (next_results == null) {
                    if ($('.prepended').length > 0) {
                        $(tweet).insertAfter($('.prepended:last'));
                    } else {
                        container.prepend(tweet);
                    }
                } else {
                    container.append(tweet);
                }
            }
        },
        handleDocumentHeight = function () {

            if ($(document).height() > $(window).height()) {
                $('footer').css('position', 'relative');

                if ($('.scroll-top').length === 0) {
                    $('body').append($('<div></div>').addClass('scroll-top hide').text('Scroll Top'));
                    $('.scroll-top').click(function () {
                        $(window.opera ? 'html' : 'html, body').animate({
                            scrollTop: 0
                        }, 400);
                        $('.scroll-top').addClass('hide');
                    });
                }
            } else {
                $('footer').css('position', 'absolute');
            }
        },
        reset = function (search_text) {

            last_search_text = search_text;
            next_results = false;
            refresh_url = false;
            $('.tweets').html('');

            if ($(document).height() > $(window).height()) {
               $('footer').css('position', 'relative');
            } else {
                $('footer').css('position', 'absolute');
            }
        },
        sendRequest = function () {
            var query_string = '';

            if (next_results !== false) {
                query_string = (next_results == null ? refresh_url : next_results);
            } else {
                query_string = '?q=' + encodeURIComponent(last_search_text) + '&count=50&include_entities=1';
            }

            $.ajax({
                url: '/search.php' + query_string,
                type: 'get',
                success: function (data) {

                    $('.search-submit-btn button').removeAttr('disabled').removeClass('hide');
                    $('.search-submit-btn img').addClass('hide');
                    $('.load-more').removeAttr('disabled').text('Load more');

                    var response = $.parseJSON(data);
                    if (response.error) {
                        showMessage('error', response.message);
                        $('.load-more').addClass('hide').attr('disabled');
                        return;
                    }

                    if (typeof response.tweets != 'undefined') {
                        renderTweets(response.tweets);
                        $('.load-more').removeClass('hide');
                    } else {
                        if (next_results === false && response.next_results == null) {
                            $('.load-more').addClass('hide').attr('disabled');
                            showMessage('info', 'No tweet found for text "' + last_search_text + '"');
                        } else {
                            if (next_results == null) {
                                showMessage('info', 'No new tweet found for text "' + last_search_text + '"');
                            } else {
                                showMessage('info', 'All Tweets from the past have been loaded. Clicking on "Load more" ' +
                                                    'button will fetch latest tweets for text "' + last_search_text + '"');
                            }
                        }
                        if ($('.tweet').length === 0) {
                            $('.load-more').addClass('hide').attr('disabled');
                        }
                    }
                    next_results = response.next_results;
                    refresh_url = response.refresh_url;
                    if (next_results == null) {
                        $('.search-submit-btn button').text('Refresh');
                    } else {
                        $('.search-submit-btn button').text('Load more');
                    }
                    handleDocumentHeight();
                },
                error: function () {
                    $('.search-submit-btn button').removeAttr('disabled').removeClass('hide');
                    $('.search-submit-btn img').addClass('hide');
                    $('.load-more').removeAttr('disabled').text('Load more');
                    showMessage('error', 'Oops! Something seems to be broken. Please try again later. ' +
                                        'If you see this repeatedly, please contact our support team.');
                }
            })
        },
        loadTweets = function () {

            var search_text = $('.search-box input').val();

            if($.trim(search_text).length == 0) {
                showMessage('error', 'Provide a # tag to search in twitter feeds.');
                $('.search-box input').focus();
                return;
            }

            $('.search-submit-btn button').attr('disabled', 'disabled').addClass('hide');
            $('.search-submit-btn img').removeClass('hide');
            $('.load-more').attr('disabled', 'disabled').text('Loading...');

            if (search_text.substr(0, 1) !== '#') {
                search_text = '#' + search_text;
            }

            if (last_search_text != search_text) {
                reset(search_text);
            }
            $('.message').remove();
            sendRequest();
        };

        $(document).ready(function () {
            $('.search-submit-btn button, .load-more').click(function () {
                loadTweets();
            });
            $(window).scroll(function () {
                if ($(window).scrollTop() > 0) {
                    $('.scroll-top').removeClass('hide');
                } else {
                    $('.scroll-top').addClass('hide');
                }
            });
            $('.search-box input').change(function () {
                $('.search-submit-btn button').text('Search');
                $('.load-more').addClass('hide');
            });
        });
})();
