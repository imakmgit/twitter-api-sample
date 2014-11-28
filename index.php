<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Search Tweets</title>
        <meta name="description" content=""/>
        <meta name="viewport" content="width=1000, initial-scale=1.0, maximum-scale=1.0">
        <script type="text/javascript" src="js/jquery-2.1.1.min.js"></script>
        <script type="text/javascript" src="js/script.js"></script>
        <link rel="stylesheet" href="css/styles.css">
    </head>

    <body>
        <header></header>

        <div class="container">
            <div class="search-container">
                <h1>Search # tags in Twitter</h1>
                <div class="search-box-container">
                    <div class="search-box inline-block">
                        <input type="text" placeholder="" value="#custserv"/>
                    </div>
                    <div class="search-submit-btn inline-block">
                        <button type="button">Search</button>
                        <img src="img/loader.gif" class="hide"/>
                    </div>
                </div>
            </div>
            <div class="tweets"></div>
            <div class="load-container">
                <img src="img/loader.gif" class="hide"/>
                <button class="hide load-more">Load more</button>
            </div>
        </div>
        <footer></footer>
    </body>
</html>
