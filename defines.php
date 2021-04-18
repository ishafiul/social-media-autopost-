<?php
session_start();
//FACEBOOK
define( 'FACEBOOK_APP_ID', 'YOUR_APP_ID_HERE' );
define( 'FACEBOOK_APP_SECRET', 'APP_SECRET_HERE' );
define( 'FACEBOOK_REDIRECT_URI', 'https://movies.delux.icu/admin/autopost/index.php' );
define( 'ENDPOINT_BASE', 'https://graph.facebook.com/v10.0/' );

// FB page id
$pageId = 'PAGE_ID_HERE';

// instagram business account id
$instagramAccountId = 'IG_ID_HERE';


//twitter
$twitter_api_key ="TW_API_HERE";
$twitter_api_secret ="TW_API_SECRET_HERE";
$twitter_access_token ="TW_API_ACCESS_TOKEN_HERE";
$twitter_access_token_secret ="TW_API_ACCESS_TOKEN_SECRET_HERE";