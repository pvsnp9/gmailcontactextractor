<?php

/** I hope you are here by creating google web application, obtaining clientID and client secrete
 * if not, got to Google developer console and create app under an API Manager menu
 *
 */
session_start();

//include google api library

include'vendor/autoload.php'; //or wherever autoload.php is located

$google_client_id = 'your Application client ID'; 
$google_client_secret = 'your client secrete key';
$google_redirect_uri = 'uri to process response form google';// either self or other php file eg. http://localhost/appname/output.php -- for local machine


//setup new google client
$client = new Google_Client();
$client -> setApplicationName('your application name');
$client -> setClientid($google_client_id);
$client -> setClientSecret($google_client_secret);
$client -> setRedirectUri($google_redirect_uri);
$client -> setAccessType('online');

$client -> setScopes('https://www.google.com/m8/feeds');

$googleImportUrl = $client -> createAuthUrl();

// creating a curl function 
function curl($url, $post = "") {
	$curl = curl_init();
	$userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
	curl_setopt($curl, CURLOPT_URL, $url);
	//The URL to fetch. This can also be set when initializing a session with curl_init().
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	//TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
	//The number of seconds to wait while trying to connect.
	if ($post != "") {
		curl_setopt($curl, CURLOPT_POST, 5);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
	//The contents of the "User-Agent: " header to be used in a HTTP request.
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	//To follow any "Location: " header that the server sends as part of the HTTP header.
	curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
	//To automatically set the Referer: field in requests where it follows a Location: redirect.
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	//The maximum number of seconds to allow cURL functions to execute.
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	//To stop cURL from verifying the peer's certificate.
	$contents = curl_exec($curl);
	curl_close($curl);
	return $contents;
}
//google response with contact. We set a session and redirect back
if (isset($_GET['code'])) {
    $auth_code = $_GET["code"];
    $_SESSION['google_code'] = $auth_code;

    //header('Location: ' . $google_redirect_uri);
}

/// processing json or wahat ever
if(isset($_SESSION['google_code'])) {
    $auth_code = $_SESSION['google_code'];
    $max_results = 200;
    $fields=array(
        'code'=>  urlencode($auth_code),
        'client_id'=>  urlencode($google_client_id),
        'client_secret'=>  urlencode($google_client_secret),
        'redirect_uri'=>  urlencode($google_redirect_uri),
        'grant_type'=>  urlencode('authorization_code')
    );
    $post = '';
    foreach($fields as $key=>$value)
    {
        $post .= $key.'='.$value.'&';
    }
    $post = rtrim($post,'&');
    $result = curl('https://accounts.google.com/o/oauth2/token',$post);
    $response =  json_decode($result);
    $accesstoken = $response->access_token;
    $url = 'https://www.google.com/m8/feeds/contacts/default/full?max-results='.$max_results.'&alt=json&v=3.0&oauth_token='.$accesstoken;
    $xmlresponse =  curl($url);
    $contacts = json_decode($xmlresponse,true);

    $return = array();
    if (!empty($contacts['feed']['entry'])) {
        foreach($contacts['feed']['entry'] as $contact) {
            //retrieve Name and email address
            $return[] = array (
                'name'=> $contact['title']['$t'],
                'email' => $contact['gd$email'][0]['address'],
            );
        }
    }

    $google_contacts = $return;

    foreach($google_contacts as $e_contacts){
        echo "Name:".$e_contacts['name']."<br>";
        echo "Email:".$e_contacts['email']."<br>";

    }
    unset($_SESSION['google_code']);

}

?>
<a href="<?php echo $googleImportUrl; ?>">Give Me google Contacts</a>