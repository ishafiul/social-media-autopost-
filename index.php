<?php
include '../../settings/db.php';
include 'defines.php';

//db model request
$q = "SELECT MAX(id) as id FROM POST_TABLE_NAME";
$r = mysqli_fetch_assoc(mysqli_query(YOUR_DB_VARIABLE,$q));
$id = $r['id'];
$q2 = "SELECT * FROM POST_TABLE_NAME where id ='$id'";
$p = mysqli_fetch_assoc(mysqli_query(YOUR_DB_VARIABLE,$q2));
$movieLink = 'https://YOursite.com/postpage.php?id='.$p['id']; //link of your post
$message = 'Watch '. $p['title'].' full movie free';

//twitter
require 'twitter/autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth($twitter_api_key, $twitter_api_secret, $twitter_access_token, $twitter_access_token_secret);
$content = $connection->get("account/verify_credentials");

//tw
$status = 'Watch '.$p['title']. ' here: https://YOursite.com/postpage.php?id='.$p['id'];;
$post_tweets = $connection->post("statuses/update", ["status" => $status]);


// api call funtion for IG


//facebook

// load graph-sdk files
require_once __DIR__ . '/vendor/autoload.php';
function makeApiCall( $endpoint, $type, $params ) {
    $ch = curl_init();

    if ( 'POST' == $type ) {
        curl_setopt( $ch, CURLOPT_URL, $endpoint );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
        curl_setopt( $ch, CURLOPT_POST, 1 );
    } elseif ( 'GET' == $type ) {
        curl_setopt( $ch, CURLOPT_URL, $endpoint . '?' . http_build_query( $params ) );
    }

    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, true );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    $response = curl_exec( $ch );
    curl_close( $ch );

    return json_decode( $response, true );
}
function resize_image($file, $w, $h, $crop=FALSE) {
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width-($width*abs($r-$w/$h)));
        } else {
            $height = ceil($height-($height*abs($r-$w/$h)));
        }
        $newwidth = $w;
        $newheight = $h;
    } else {
        if ($w/$h > $r) {
            $newwidth = $h*$r;
            $newheight = $h;
        } else {
            $newheight = $w/$r;
            $newwidth = $w;
        }
    }
    $src = imagecreatefromjpeg($file);
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    return $dst;
}
// facebook credentials array
$creds = array(
    'app_id' => FACEBOOK_APP_ID,
    'app_secret' => FACEBOOK_APP_SECRET,
    'default_graph_version' => 'v10.0',
    'persistent_data_handler' => 'session'
);

// create facebook object
$facebook = new Facebook\Facebook( $creds );

// helper
$helper = $facebook->getRedirectLoginHelper();

// oauth object
$oAuth2Client = $facebook->getOAuth2Client();
//echo $helper->getAccessToken();
if ( isset( $_GET['code'] ) ) { // get access token
    try {
        $accessToken = $helper->getAccessToken();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error for code: ' . $e->getMessage();
        exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error for code: ' . $e->getMessage();
        exit;
    }

    if (! isset($accessToken)) {
        if ($helper->getError()) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Error: " . $helper->getError() . "\n";
            echo "Error Code: " . $helper->getErrorCode() . "\n";
            echo "Error Reason: " . $helper->getErrorReason() . "\n";
            echo "Error Description: " . $helper->getErrorDescription() . "\n";
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo 'Bad request';
        }
        exit;
    }
  //echo $accessToken;
    $new_accessToken= str_replace(' ', '', $accessToken);
    $endpointFormat = ENDPOINT_BASE . '{page-id}?fields=access_token&access_token={access-token}';
    $fbAccountEndpoint = ENDPOINT_BASE . $pageId;

    // endpoint params
    $igParams = array(
        'fields' => 'access_token',
        'access_token' => $new_accessToken
    );

    // add params to endpoint
    $fbAccountEndpoint .= '?' . http_build_query( $igParams );

    // setup curl
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $fbAccountEndpoint );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    // make call and get response
    $response = curl_exec( $ch );
    curl_close( $ch );
    $responseArray = json_decode( $response, true );
    $page_access = $responseArray['access_token'];


    $data =[
        'message'=>$message,
        'link'=>$movieLink
    ];
    try
    {
        $response = $facebook->post('/me/feed', $data,$page_access);
    }
    catch(Facebook\Exceptions\FacebookResponseException $e)
    {
        echo 'Graph returned an error: '.$e->getMessage();
        exit;
    }
    catch(Facebook\Exceptions\FacebookSDKException $e)
    {
        echo 'Facebook SDK returned an Error for page: '.$e->getMessage();
        exit;
    }
    if($response){
        /***
         * IMAGE
         */


        $img_name = $p['thumbnail'];
        $copyFrom = '../../uploads/'.$img_name;
        copy($copyFrom, $img_name);
        $file_name = $img_name;
        function resize($target, $newcopy, $w, $h, $ext) {
            list($w_orig, $h_orig) = getimagesize($target);
            $scale_ratio = $w_orig / $h_orig;
            if (($w / $h) > $scale_ratio) {
                $w = $h * $scale_ratio;
            } else {
                $h = $w / $scale_ratio;
            }
            $img = "";
            $ext = strtolower($ext);
            if ($ext == "gif"){
                $img = imagecreatefromgif($target);
            } else if($ext =="png"){
                $img = imagecreatefrompng($target);
            } else {
                $img = imagecreatefromjpeg($target);
            }
            $tci = imagecreatetruecolor($w, $h);
            // imagecopyresampled(dst_img, src_img, dst_x, dst_y, src_x, src_y, dst_w, dst_h, src_w, src_h)
            imagecopyresampled($tci, $img, 0, 0, 0, 0, $w, $h, $w_orig, $h_orig);
            if ($ext == "gif"){
                imagegif($tci, $newcopy);
            } else if($ext =="png"){
                imagepng($tci, $newcopy);
            } else {
                imagejpeg($tci, $newcopy, 84);
            }
        }
        $ext = explode(".", $file_name);
        $newFile = '_'.$file_name;

        resize($file_name, $newFile, 350, 350, $ext);
        $link = 'https://movies.delux.icu/admin/autopost/'.$newFile;
        $imageMediaObjectEndpoint = ENDPOINT_BASE . $instagramAccountId . '/media';
        $imageMediaObjectEndpointParams = array( // POST
            'image_url' => $link,
            'caption' => $message.' '.$movieLink,
            'access_token' => $new_accessToken
        );
       $imageMediaObjectResponseArray = makeApiCall( $imageMediaObjectEndpoint, 'POST', $imageMediaObjectEndpointParams );

       //print_r($imageMediaObjectResponseArray);

        // set status to in progress
        $imageMediaObjectStatusCode = 'IN_PROGRESS';

        while( $imageMediaObjectStatusCode != 'FINISHED' ) { // keep checking media object until it is ready for publishing
            $imageMediaObjectStatusEndpoint = ENDPOINT_BASE . $imageMediaObjectResponseArray['id'];
            $imageMediaObjectStatusEndpointParams = array( // endpoint params
                'fields' => 'status_code',
                'access_token' => $new_accessToken
            );
            $imageMediaObjectResponseArray = makeApiCall( $imageMediaObjectStatusEndpoint, 'GET', $imageMediaObjectStatusEndpointParams );
            $imageMediaObjectStatusCode = $imageMediaObjectResponseArray['status_code'];
            sleep( 2 );
        }

        // publish image
        $imageMediaObjectId = $imageMediaObjectResponseArray['id'];
        echo $imageMediaObjectId;
        $publishImageEndpoint = ENDPOINT_BASE . $instagramAccountId . '/media_publish';
        $publishEndpointParams = array(
            'creation_id' => $imageMediaObjectId,
            'access_token' => $new_accessToken
        );
        $publishImageResponseArray = makeApiCall( $publishImageEndpoint, 'POST', $publishEndpointParams );
        /***
         * API LIMIT
         */
        // check user api limit
        $limitEndpoint = ENDPOINT_BASE . $instagramAccountId . '/content_publishing_limit';
        $limitEndpointParams = array( // get params
            'fields' => 'config,quota_usage',
            'access_token' => $accessToken
        );
        $limitResponseArray = makeApiCall( $limitEndpoint, 'GET', $limitEndpointParams );
        echo '<a href="https://YOursite.com">go back to movie manager</a>';
        unlink($newFile);
    }

} else { // display login url
    $permissions = [
        'public_profile',
        'instagram_basic',
        'pages_show_list',
        'instagram_manage_insights',
        'instagram_manage_comments',
        'ads_management',
        'business_management',
        'instagram_content_publish',
        'page_events',
        'pages_manage_posts',
        'pages_read_engagement'
    ];
    $loginUrl = $helper->getLoginUrl( FACEBOOK_REDIRECT_URI, $permissions );

    echo '<h1>Auto posted to twitter!</h1><br><a href="' . $loginUrl . '">
            Post FB AND IG
        </a>';
}
?>