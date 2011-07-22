<?php session_start(); ?>
<?php
// put your developer key and URL in config.php
include 'config.php';
$consumer_key = $developer_key; # from config.php
$oauth_callback = urlencode($callback_url); # from config.php
$server = $api_host; # from config.php

# Production code should pull these from https://api.familysearch.org/identity/v2/properties
$requestUrl = "/identity/v2/request_token"; 
$authorizeUrl = "/identity/v2/authorize";
$accessUrl = "/identity/v2/access_token";
$content = NULL;

/*-----------------------------------------------------------------------------------
 If this is the first time to this page get a request token and save secret to file
------------------------------------------------------------------------------------*/
if ($_GET["oauth_verifier"] == NULL) {
 $response = getRequestToken($server, $requestUrl, $oauth_callback, $consumer_key);
// print_r($response);
 $_SESSION['oauth_token_secret'] = $response['oauth_token_secret'];
 $content = '<span>You need to authenticate with FamilySearch to continue.<br/><br/><a href="'.$server.$authorizeUrl.'?oauth_token='.$response['oauth_token'].'"><button id="authlink" >Sign In to FamilySearch</button></a></span>';
}

/*-----------------------------------------
 Exchange oauth_verifier for access_token
------------------------------------------*/
if ($_GET["oauth_verifier"] != NULL) {
 $response = getAccessToken($server, $accessUrl, $consumer_key, $_GET["oauth_verifier"], $_GET["oauth_token"]);
 # Save the sessionId for all future FamilySearch API calls
 $sessionId = $response['oauth_token'];
 setcookie("fssessionid", $sessionId);

 $content = "<br />Authentication to FamilySearch successful.<br />";
 $content .= "<br/><a href='index.html'><button>Continue back to your application</button></a>";
 $debuginfo = "Your user info is:<br/>sessionId: ".$sessionId."<br/>";
 $debuginfo .= htmlentities(http($server."/familytree/v2/user/?dataFormat=application/json&sessionId=".$sessionId));

 
 session_destroy();
}


 /* Get a request_token */
 function getRequestToken($server, $requestUrl, $oauth_callback, $consumer_key) {
   $timeStamp = time();
   $url = $server.$requestUrl."?oauth_callback=".$oauth_callback."&oauth_consumer_key=";
   $url .= $consumer_key."&oauth_signature_method=PLAINTEXT&oauth_nonce=99806503068046&oauth_version=1.0&oauth_timestamp=";
   $url .= $timeStamp."&oauth_signature=%26".$oauth_token_secret;
   $r = http($url);
   return oAuthParseResponse($r);
 }

 /* Get an access_token */
 function getAccessToken($server, $requestUrl, $consumer_key, $oauth_verifier, $oauth_token) {
   $oauth_token_secret = $_SESSION['oauth_token_secret'];
   $timeStamp = time();
   $url = $server.$requestUrl."?oauth_consumer_key=".$consumer_key;
   $url .= "&oauth_signature_method=PLAINTEXT&oauth_nonce=99806503068046&oauth_version=1.0&oauth_timestamp=";
   $url .= $timeStamp."&oauth_verifier=".$oauth_verifier."&oauth_token=".$oauth_token."&oauth_signature=%26".$oauth_token_secret;
   $r = http($url);
   return oAuthParseResponse($r);
 }

 /* Parse a URL-encoded OAuth response */
 function oAuthParseResponse($responseString) {
   $r = array();
   foreach (explode('&', $responseString) as $param) {
     $pair = explode('=', $param, 2);
     if (count($pair) != 2) continue;
     $r[urldecode($pair[0])] = urldecode($pair[1]);
   }
   return $r;
 }

 /* Make an HTTP request */
 function http($url, $post_data = null) {
   $ch = curl_init();
   if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
   curl_setopt($ch, CURLOPT_TIMEOUT, 30);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_USERAGENT, "OAuthTestClient/1.0 (FamilySearch php5)");
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); # Set to 1 to verify FamilySearch's SSL Cert
   if (isset($post_data)) {
     curl_setopt($ch, CURLOPT_POST, 1);
     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
   }
   $response = curl_exec($ch);
   $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $last_api_call = $url;
   // print_r("<br />HTTP Response: ".$http_status." ".htmlentities($response));
   curl_close ($ch);
   return $response;
 }
?>

<html>
 <head>
   <title>FamilySearch OAuth Client</title>
   <link href="styles.css" rel="stylesheet" />
 </head>
 <body>
   <h1>OAuth Test client for FamilySearch</h1>

<div class="content">   
   <p class="message"><?php print_r($content); ?></p>
   <p class="error"><?php print_r($message); ?></p> 
   <hr style="margin-top:40px">  
   <p class="info"><?php print_r($response); ?></p>
   <p class="info"><?php print_r($debuginfo); ?></p>
</div> 
</body>
</html>