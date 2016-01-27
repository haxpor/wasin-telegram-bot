<?php

/*
  Request webhook api.
*/
function apiRequestWebhook($method, $parameters) {
	if (!is_string($method))
	{
		error_log("Method name must be a string\n");
    	return false;
  	}

	if (!$parameters) 
	{
    	$parameters = array();
  	}
	else if (!is_array($parameters))
	{
    	error_log("Parameters must be an array\n");
    	return false;
  	}

  	$parameters["method"] = $method;

  	header("Content-Type: application/json");
  	echo json_encode($parameters);
 	return true;
}

/*
  Execute request via curl.
*/
function exec_curl_request($handle) {
	$response = curl_exec($handle);

  	if ($response === false)
	{
    	$errno = curl_errno($handle);
    	$error = curl_error($handle);
    	error_log("Curl returned error $errno: $error\n");
    	curl_close($handle);
    	return false;
 	}

  	$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  	curl_close($handle);

  	if ($http_code >= 500)
	{
    	// do not wat to DDOS server if something goes wrong
    	sleep(10);
    	return false;
  	}
	else if ($http_code != 200)
	{
    	$response = json_decode($response, true);
    	error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    	if ($http_code == 401)
		{
      		throw new Exception('Invalid access token provided');
    	}
    	return false;
  	}
	else 
	{
    	$response = json_decode($response, true);
    	if (isset($response['description']))
		{
      		error_log("Request was successfull: {$response['description']}\n");
    	}
    	$response = $response['result'];
 	}

  	return $response;
}

/*
  Send API request via URL query string.
*/
function apiRequest($method, $parameters) {
	if (!is_string($method))
	{
    	error_log("Method name must be a string\n");
    	return false;
  	}

  	if (!$parameters)
	{
    	$parameters = array();
  	}
	else if (!is_array($parameters))
	{
    	error_log("Parameters must be an array\n");
   	 	return false;
 	}

  	foreach ($parameters as $key => &$val)
	{
    	// encoding to JSON array parameters, for example reply_markup
    	if (!is_numeric($val) && !is_string($val))
		{
      		$val = json_encode($val);
    	}
  	}
  	$url = API_URL.$method.'?'.http_build_query($parameters);

  	$handle = curl_init($url);
  	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  	curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  	return exec_curl_request($handle);
}

/*
  Send API request via json format.
*/
function apiRequestJson($method, $parameters) 
{
	if (!is_string($method)) 
	{
    	error_log("Method name must be a string\n");
    	return false;
  	}

  	if (!$parameters) 
	{
    	$parameters = array();
  	}
	else if (!is_array($parameters)) 
	{
    	error_log("Parameters must be an array\n");
    	return false;
 	}

  	$parameters["method"] = $method;

  	$handle = curl_init(API_URL);
  	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  	curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  	curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  	curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  	return exec_curl_request($handle);
}

/*
  Process message.
*/
function processMessage($message) {
    // process incoming message
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    if (isset($message['text']))
    {
        // incoming text message
        $text = $message['text'];

        // start
        if (strpos($text, "/start") === 0) 
        {
            $parameters = array("chat_id" => $chat_id,
                                "text" => "Get to know Wasin Thonkaew about his basic contact information, freelancing rate, and discuss about business with him 24/7.\n\n/help for list of available commands.");
            apiRequestJson("sendMessage", $parameters);
        } 
        // help
        else if (strpos($text, "/help") === 0)
        {
            // get the latest help text from gist
            $helpText = file_get_contents('https://gist.githubusercontent.com/haxpor/9a9dbe1a38782b792ca1/raw/191f91fc34fcb974e2186a514308b94a7a15a975/wasinbot-commands.txt');

            $parameters = array("chat_id" => $chat_id,
                                "text" => $helpText);
            apiRequestJson("sendMessage", $parameters);
        }
        // stop
        else if (strpos($text, "/stop") === 0)
        {
            $parameters = array("chat_id" => $chat_id,
                                "text" => "Good bye for now. Come back whenever you want. I'm always here :)");
            apiRequestJson("sendMessage", $parameters);
        }
        // getname
        else if (strpos($text, "/getname") === 0)
        {
            $parameters = array("chat_id" => $chat_id,
                                "text"  =>  "Wasin Thonkaew");
            apiRequestJson("sendMessage", $parameters);
        }
		// getnickname
        else if (strpos($text, "/getnickname") === 0)
		{
			$parameters = array("chat_id" => $chat_id,
								"text"	=>	"Best");
			apiRequestJson("sendMessage", $parameters);
        }
		// getsocial
		else if (strpos($text, "/getsocial") === 0)
		{
			$parameters = array("chat_id" => $chat_id,
								"text" => "Twitter: [@haxpor](https://twitter.com/haxpor)\nFacebook: [Wasin Thonkaew](https://www.facebook.com/wasin.thonkaew)\nInstagram: [haxpor](https://www.instagram.com/haxpor/)\nWebsite: [https://wasin.io](https://wasin.io)",
								"parse_mode" => "Markdown",
								"disable_web_page_preview" => true);
			apiRequestJson("sendMessage", $parameters);
		}
		// getfreelancingrate
		else if (strpos($text, "/getfreelancingrate") === 0)
		{
			$parameters = array("chat_id" => $chat_id,
								"text"	=>	"28 USD / Hour");
			apiRequestJson("sendMessage", $parameters);
		}
		// getcurrentlocation
		else if (strpos($text, "/getcurrentlocation") === 0)
		{
			$parameters = array("chat_id" => $chat_id,
								"latitude" => 18.786497,
								"longitude" => 98.991522);
			apiRequestJson("sendLocation", $parameters);
		}
    }
    else
    {
        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
    }
}

?>
