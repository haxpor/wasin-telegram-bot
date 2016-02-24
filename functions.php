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
	Send photo via url format.
*/
function apiRequestSendPhoto($chat_id, $parameters)
{
	if (!$parameters)
	{
		$parameters = array();
	}
	else if (!is_array($parameters))
	{
		error_log("Parameters must be an array\n");
		return false;
	}

	$handle = curl_init();
	curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data"));
	curl_setopt($handle, CURLOPT_URL, API_URL."sendPhoto?chat_id=" . $chat_id);
	curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
	
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
            sendTypingAction($chat_id);

            $parameters = array("chat_id" => $chat_id,
                                "text" => "Greeting to you!.\n\nGet to know Wasin Thonkaew about his basic contact information, freelancing rate, and discuss about business with him 24/7.\n\n/help for list of available commands.");
            apiRequestJson("sendMessage", $parameters);
        } 
        // help
        else if (strpos($text, "/help") === 0)
        {
            sendTypingAction($chat_id);

            // get the latest help text from gist
            $helpText = file_get_contents('https://gist.githubusercontent.com/haxpor/9a9dbe1a38782b792ca1/raw/4529125b6a5c587dd29fae689dc8d36cd33453da/wasinbot-commands.txt');

            $parameters = array("chat_id" => $chat_id,
                                "text" => $helpText);
            apiRequestJson("sendMessage", $parameters);
        }
        // stop
        else if (strpos($text, "/stop") === 0)
        {
            sendTypingAction($chat_id);

            $parameters = array("chat_id" => $chat_id,
                                "text" => "Good bye for now. Come back whenever you want. I'm always here :)");
            apiRequestJson("sendMessage", $parameters);
        }
        // getname
        else if (strpos($text, "/getname") === 0)
        {
            sendTypingAction($chat_id);

            $parameters = array("chat_id" => $chat_id,
                                "text"  =>  "Wasin Thonkaew");
            apiRequestJson("sendMessage", $parameters);
        }
		// getnickname
        else if (strpos($text, "/getnickname") === 0)
		{
            sendTypingAction($chat_id);

			$parameters = array("chat_id" => $chat_id,
								"text"	=>	"Best");
			apiRequestJson("sendMessage", $parameters);
        }
		// getsocial
		else if (strpos($text, "/getsocial") === 0)
		{
            sendTypingAction($chat_id);

			$parameters = array("chat_id" => $chat_id,
								"text" => "Twitter: [@haxpor](https://twitter.com/haxpor)\nFacebook: [Wasin Thonkaew](https://www.facebook.com/wasin.thonkaew)\nInstagram: [haxpor](https://www.instagram.com/haxpor/)\nWebsite: [https://wasin.io](https://wasin.io)",
								"parse_mode" => "Markdown",
								"disable_web_page_preview" => true);
			apiRequestJson("sendMessage", $parameters);
		}
		// getfreelancingrate
		else if (strpos($text, "/getfreelancingrate") === 0)
		{
            sendTypingAction($chat_id);

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
		// getproductsmade
		else if (strpos($text, "/getproductsmade") === 0)
		{
            sendTypingAction($chat_id);
            
			$parameters = array("chat_id" => $chat_id,
								"text" => "Zombie Hero : Revenge of Kiki\n - Website: [http://zombie-hero.com](http://zombie-hero.com)\n - App Store: [Download](https://itunes.apple.com/app/zombie-hero-revenge-of-kiki/id904184868?mt=8)\n\nIndiedevBkk - [Website](http://indiedevbkk.tk)",
								"parse_mode" => "Markdown",
								"disable_web_page_preview" => true);
			apiRequestJson("sendMessage", $parameters);
		}
		// getlistofclients
		else if (strpos($text, "/getlistofclients") === 0)
		{
			// aerothai
			{
                // send upload photo action
                sendUploadPhotoAction($chat_id);

                {
                    // get the realpath of image file to serve to user
                    if (PRODUCTION)
                    {
                        $realpath = realpath("/var/www/wasin.io/projs/wasin-telegram-bot/resources/aerothai-logo.png");
                    }
                    else
                    {
                        $realpath = realpath("/Users/haxpor/Sites/wasinbot-res/aerothai-logo.png");
                    }

				    $parameters = array("chat_id" => $chat_id,
								"photo" => new CURLFile($realpath),
								"caption" => "Aeronautical Radio of Thailand LTD");
				    apiRequestSendPhoto($chat_id, $parameters);
                }
			}

			// playbasis
			{
                // send upload photo action
                sendUploadPhotoAction($chat_id);

                {
                    // get the realpath of image file to serve to user
                    if (PRODUCTION)
                    {
                        $realpath = realpath("/var/www/wasin.io/projs/wasin-telegram-bot/resources/playbasis-logo.png");
                    }
                    else
                    {
                        $realpath = realpath("/Users/haxpor/Sites/wasinbot-res/playbasis-logo.png");
                    }

				    $parameters = array("chat_id" => $chat_id,
                                "photo" => new CURLFile($realpath),
                                "caption" => "Playbasis");
                    apiRequestSendPhoto($chat_id, $parameters);
                }
			}
		}
    }
    else
    {
        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
    }
}

/*
    Send upload_photo action to user.
    $chat_id - chat id to send action to
*/
function sendUploadPhotoAction($chat_id)
{
    $parameters = array("chat_id" => $chat_id,
                        "action" => "upload_photo");
    apiRequestJson("sendChatAction", $parameters);
}

/*
    Send typing action to user.
    $chat_id - chat id to send action to
*/
function sendTypingAction($chat_id)
{
    $parameters = array("chat_id" => $chat_id,
                        "action" => "typing");
    apiRequestJson("sendChatAction", $parameters);
}

?>
