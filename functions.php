<?php

include('state.php');

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
function processMessage($message, $mongodb) {
    // process incoming message
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];

    // get state of current user
    $doc = $mongodb->getDocInStateCollection($chat_id);
    $state_id = State::Normal;

    if (empty($doc))
    {
        // save to db
        $mongodb->insertDocToStateCollection($chat_id, State::Normal);
    }
    else 
    {
        $state_id = $mongodb->getStateIdFromDoc($doc);
    }

    if (isset($message['text']))
    {
        // incoming text message
        $text = $message['text'];

        // stop will not up to the current state
        if (strpos($text, "/startover") === 0)
        {
            sendTypingAction($chat_id);

            $parameters = array("chat_id" => $chat_id,
                                "text" => "Good bye for now. Come back whenever you want. I'm always here :)");
            apiRequestJson("sendMessage", $parameters);

            // start it over
            $state_id = State::Normal;
            $mongodb->updateDocToStateCollection($chat_id, State::Normal);
            $text = "/start";
        }
        // need to have this in case of restart the bot after deleting
        else if (strpos($text, "/start") === 0)
        {
            // start it over
            $state_id = State::Normal;
            $mongodb->updateDocToStateCollection($chat_id, State::Normal);
        }
        // jump start over to the first choice screen
        else if (strpos($text, "/bypassstart") === 0)
        {
            // start it over
            $state_id = State::Normal;
            $mongodb->updateDocToStateCollection($chat_id, State::Normal);
        }
        // stop
        else if (strpos($text, "/stop") === 0)
        {
            sendTypingAction($chat_id);

            $parameters = array("chat_id" => $chat_id,
                                "text" => "Thanks for chatting with me ✌️😀✌️");
            apiRequestJson("sendMessage", $parameters);

            // start it over
            $state_id = State::Normal;
            $mongodb->updateDocToStateCollection($chat_id, State::Normal);
            $text = "/bypassstart";
        }

        PC:

        if ($state_id == State::Normal)
        {
            // start
            if (strpos($text, "/start") === 0) 
            {
                // change state
                $mongodb->updateDocToStateCollection($chat_id, State::Start_Answer);

                // msg chunk 1
                sendTypingAction($chat_id);
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "Greeting to you!");
                apiRequestJson("sendMessage", $parameters);

                // msg chunk 2
                sendTypingAction($chat_id);
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "I will help you get down to do business with Wasin, or even be friend with him 24/7.");
                apiRequestJson("sendMessage", $parameters);

                // msg chunk 3
                sendTypingAction($chat_id);
                $replyMarkup = array("keyboard" => array(array("Business 💵"), array("Personal 😀")));
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "What can I help you?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);
            } 
            // bypass start (cut out greetings, and un-neccessary text)
            else if (strpos($text, "/bypassstart") === 0)
            {
                // change state
                $mongodb->updateDocToStateCollection($chat_id, State::Start_Answer);

                // send msg
                sendTypingAction($chat_id);
                $replyMarkup = array("keyboard" => array(array("Business 💵"), array("Personal 😀")));
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "What can I help you?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);
            }
            // help
            else if (strpos($text, "/help") === 0)
            {
                sendTypingAction($chat_id);

                // get the latest help text from gist
                $helpText = file_get_contents('https://gist.githubusercontent.com/haxpor/9a9dbe1a38782b792ca1/raw/565d9dd73473fc020b0f7b97b705e198b8bebfa6/wasinbot-commands.txt');


                $parameters = array("chat_id" => $chat_id,
                                    "text" => $helpText);
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
                sendFindingLocationAction($chat_id);

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
        else if ($state_id == State::Start_Answer)
        {
            if (strpos($text, "Business 💵") === 0)
            {
                sendTypingAction($chat_id);

                // create reply markup
                $replyMarkup = array("keyboard" => array(array("👍")));

                $parameters = array("chat_id" => $chat_id,
                                    "text"  =>  "You wanna talk business huh?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);

                // send user back to business state 1
                $mongodb->updateDocToStateCollection($chat_id, State::Business_1);
            }
            else if (strpos($text, "Personal 😀") === 0)
            {
                sendTypingAction($chat_id);

                // create reply markup
                $replyMarkup = array("keyboard" => array(array("Absolutely! 😉"), array("Nahh, maybe... I just want to know you more 😬")));

                $parameters = array("chat_id" => $chat_id,
                                    "text"  =>  "Wanna feel comfortable with me first before getting down to biz 😁?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);

                // send user back to business state 1
                $mongodb->updateDocToStateCollection($chat_id, State::Personal_1);
            }
            else
            {
                // repeat the question again
                $state_id = State::Normal;
                $text = "/bypassstart";
                $mongodb->updateDocToStateCollection($chat_id, State::Normal);

                goto PC;
            }
        }
        else if ($state_id == State::Business_1)
        {
            if (strpos($text, "👍") === 0)
            {
                sendTypingAction($chat_id);

                // create reply markup
                $replyMarkup = array("keyboard" => array(array("Business opportunity"), array("Freelance work")));

                $parameters = array("chat_id" => $chat_id,
                                    "text" => "Which one best describe your proposal?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Business_2);
            }
        }
        else if ($state_id == State::Business_2)
        {
            if (strpos($text, "Business opportunity") === 0)
            {
                sendTypingAction($chat_id);

                // create a reply markup
                $replyMarkup = array("keyboard" => array(array("1. Tech startup"), array("2. Game development"), array("3. Blended of 1 and 2"), array("4. Others")));

                $parameters = array("chat_id" => $chat_id,
                                    "text" => "What is it about?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Business_Opportunity_1);
            }
            else if (strpos($text, "Freelance work") === 0)
            {
                
            }
            else
            {
                // repeat the question again
                $state_id = State::Business_1;
                $text = "👍";
                $mongodb->updateDocToStateCollection($chat_id, State::Business_1);

                goto PC;
            }
        }

        else if ($state_id == State::Business_Opportunity_1)
        {
            $isOk = false;

            if (strpos($text, "1. Tech startup") === 0)
            {
                // save information for later submission
                $mongodb->updateBusinessMsgWithTypeId($chat_id, 1);

                $isOk = true;
            }
            else if (strpos($text, "2. Game development") === 0)
            {
                // save information for later submission
                $mongodb->updateBusinessMsgWithTypeId($chat_id, 2);

                $isOk = true;
            }
            else if (strpos($text, "3. Blended of 1 and 2") === 0)
            {
                // save information for later submission
                $mongodb->updateBusinessMsgWithTypeId($chat_id, 3);

                $isOk = true;
            }
            else if (strpos($text, "4. Others") === 0)
            {
                // save information for later submission
                $mongodb->updateBusinessMsgWithTypeId($chat_id, 4);

                $isOk = true;
            }

            if ($isOk)
            {
                // proceed to next question
                sendTypingAction($chat_id);
                $replyKeyboardHide = array("hide_keyboard" => true);
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "Tell me briefly about it",
                                    "reply_markup" => $replyKeyboardHide);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Business_Opportunity_2);
            }
            else
            {
                // repeat the question again
                $state_id = State::Business_2;
                $text = "Business opportunity";
                $mongodb->updateDocToStateCollection($chat_id, State::Business_2);

                goto PC;
            }
        }
        else if ($state_id == State::Business_Opportunity_2)
        {
            // anything can go here as it's open-ended answer
            // save the answer
            $mongodb->updateBusinessMsgWithProductDescriptionText($chat_id, $text);

            // proceed to next question
            sendTypingAction($chat_id);
            $replyKeyboardHide = array("hide_keyboard" => true);
            $parameters = array("chat_id" => $chat_id,
                                "text" => "What's your offer?",
                                "reply_markup" => $replyKeyboardHide);
            apiRequestJson("sendMessage", $parameters);

            // save the state
            $mongodb->updateDocToStateCollection($chat_id, State::Business_Opportunity_3);
        }
        else if ($state_id == State::Business_Opportunity_3)
        {
            // anything can go here as it's open-ended answer
            // save the answer
            $mongodb->updateBusinessMsgWithOfferText($chat_id, $text);
            
            // proceed to next question
            sendTypingAction($chat_id);
            $replyKeyboardHide = array("hide_keyboard" => true);
            $parameters = array("chat_id" => $chat_id,
                                "text" => "What's your e-mail that I can reach you back?",
                                "reply_markup" => $replyKeyboardHide);
            apiRequestJson("sendMessage", $parameters);

            // save the state
            $mongodb->updateDocToStateCollection($chat_id, State::Business_Opportunity_4);
        }
        else if ($state_id == State::Business_Opportunity_4)
        {
            // anything can go here as it's open-ended answer
            // save the answer
            $mongodb->updateBusinessMsgWithProposerEmail($chat_id, $text);
            
            // proceed to next question
            sendTypingAction($chat_id);
            $replyKeyboardHide = array("hide_keyboard" => true);
            $parameters = array("chat_id" => $chat_id,
                                "text" => "What's your first name?",
                                "reply_markup" => $replyKeyboardHide);
            apiRequestJson("sendMessage", $parameters);

            // save the state
            $mongodb->updateDocToStateCollection($chat_id, State::Business_Opportunity_5);
        }
        else if ($state_id == State::Business_Opportunity_5)
        {
            // anything can go here as it's open-ended answer
            // save the answer
            $mongodb->updateBusinessMsgWithProposerFirstName($chat_id, $text);

            // time to send e-mail including all informatio of request back to Wasin :)
            // check the current config as we don't have e-mail system readily configured on development system, we only send e-mail back when it's in PRODUCTION
            if (PRODUCTION)
            {
                // get document via chat_id
                $doc = $mongodb->getDocInBusinessMsgCollection($chat_id);

                if (!empty($doc))
                {
                    // proposer email is the most important field we need to have 
                    if (isset($doc["proposerEmail"]))
                    {
                        // send email
                        $result = sendEmailOfProposedBusinessOpportunity($doc["type_id"], $doc["productDescriptionText"], $doc["offerText"], $doc["proposerEmail"], $doc["proposerFirstName"]);

                        // update status if it's sent successfully
                        if ($result)
                        {
                            $mongodb->updateBusinessMsgWithStatus(1);
                        }
                    }
                }
            }
            
            // send notifying msg
            // regardless of result here, if something wrong happened, I'll check it and manually send it myself
            sendTypingAction($chat_id);
            $parameters = array("chat_id" => $chat_id,
                                "text" => "Your proposal information has been sent to me! Hoorayy!");
            apiRequestJson("sendMessage", $parameters);

            // send reply keyboard
            sendTypingAction($chat_id);
            $replyMarkup = array("keyboard" => array(array("👍")));
            $parameters = array("chat_id" => $chat_id,
                                "text" => "I will reach you back when I have time to carefully read and consider your request. Thank you so much!",
                                "reply_markup" => $replyMarkup);
            apiRequestJson("sendMessage", $parameters);

            // save the state
            $mongodb->updateDocToStateCollection($chat_id, State::Business_Opportunity_6);
        }
        else if ($state_id == State::Business_Opportunity_6)
        {
            if (strpos($text, "👍") === 0)
            {
                // start it over while bypassing the greetings text
                $state_id = State::Normal;
                $mongodb->updateDocToStateCollection($chat_id, State::Normal);
                $text = "/bypassstart";

                goto PC;
            }
        }

        else if ($state_id == State::Personal_1)
        {

        }
    }
    else
    {
        apiRequest("sendMessage", array("chat_id" => $chat_id, "text" => 'I understand only text messages'));
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

/*
    Send find_location action to user.
    $chat_id - chat id to send action to
*/
function sendFindingLocationAction($chat_id)
{
    $parameters = array("chat_id" => $chat_id,
                        "action" => "find_location");
    apiRequestJson("sendChatAction", $parameters);
}

/*
    Send email with all gathered information from proposed business opportunity.

    @return True if sending email is successful, otherwise return false.
*/
function sendEmailOfProposedBusinessOpportunity($type_id, $productDescriptionText, $offerText, $proposerEmail, $proposerFirstName)
{
    $typeText = getTextFromBusinessOpportunityTypeId($type_id);

    $to = 'haxpor@gmail.com';
    $subject = 'Business Opportunity from ' . $proposerFirstName;
    $headers = 'From: wasin@wasin.io' . "\r\n" .
            'Reply-To: ' . $proposerEmail . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

    $msg = "Product description:\r\n" . $productDescriptionText . "\r\n\r\n";
    $msg .= "Offer:\r\n" . $offerText . "\r\n";

    $result = mail($to, $subject, $msg, $headers);
    return $result;
}

/*
    Get text description of the input business opportunity's type id.

    @return Text description according to input type_id
*/
function getTextFromBusinessOpportunityTypeId($type_id)
{
    if ($type_id == 1)
    {
        return "Tech startup";
    }
    else if ($type_id == 2)
    {
        return "Game development";
    }
    else if ($type_id == 3)
    {
        return "Blended of 1, and 2.";
    }
    else if ($type_id == 4)
    {
        return "Others";
    }
    else
    {
        // should not happen
        return "Unknown";
    }
}

?>
