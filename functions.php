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

    $proxy = getenv('WASIN_TELEGRAM_BOT_PROXY');
    if ( isset($proxy) && $proxy != "" && $proxy != null) {
        curl_setopt($handle, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($handle, CURLOPT_PROXY, $proxy);
    }

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

    $proxy = getenv('WASIN_TELEGRAM_BOT_PROXY');
    if ( isset($proxy) && $proxy != "" && $proxy != null) {
        curl_setopt($handle, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($handle, CURLOPT_PROXY, $proxy);
    }

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

    $proxy = getenv('WASIN_TELEGRAM_BOT_PROXY');
    if ( isset($proxy) && $proxy != "" && $proxy != null) {
        curl_setopt($handle, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($handle, CURLOPT_PROXY, $proxy);
    }
	
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

        // need to have this in case of restart the bot after deleting
        if (strpos($text, "/start") === 0)
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
        else if (strpos($text, "/stop") === 0 ||
                 strpos($text, "/startover") === 0)
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
        else if (strpos($text, "/help") === 0)
        {
            sendTypingAction($chat_id);

            // show the message to user no matter where's user is in at the moment
            // so user may decide to continue the current flow, or start it over via /startover
            $helpText = "Use /startover to begin the conversation from the beginning again.\nUse this when you get lost.";

            $replyKeyboardHide = array("hide_keyboard" => true);
            $parameters = array("chat_id" => $chat_id,
                                "text" => $helpText,
                                "keyboard" => $replyKeyboardHide);
            apiRequestJson("sendMessage", $parameters);
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
                // note: in case of starting over, then hide keyboard for fresh start
                sendTypingAction($chat_id);
                $replyMarkup = array("hide_keyboard" => true);
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "Greeting to you!",
                                    "reply_markup" => $replyMarkup);
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
                $replyMarkup = array("keyboard" => array(array("Tech startup"), array("Game development"), array("Tech startup + Game"), array("Others")));

                $parameters = array("chat_id" => $chat_id,
                                    "text" => "What is it about?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Business_Opportunity_1);
            }
            else if (strpos($text, "Freelance work") === 0)
            {
                sendTypingAction($chat_id);

                // create a reply markup
                $replyMarkup = array("keyboard" => array(array("Mobile game", "PC game"), array("HTML5 game"), array("Fully cross-platform game"), array("iOS application"), array("Landing page")));

                $parameters = array("chat_id" => $chat_id,
                                    "text" => "What do you want to get it done?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Freelancework_1);
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

        // Business
        //  |_ Freelance Work
        else if ($state_id == State::Freelancework_1)
        {
            $isOk = false;

            if (strpos($text, "Mobile game") === 0)
            {
                // save information for later submission
                $mongodb->updateFreelanceworkMsgWithTypeId($chat_id, 1);

                $isOk = true;
            }
            else if (strpos($text, "PC game") === 0)
            {
                // save information for later submission
                $mongodb->updateFreelanceworkMsgWithTypeId($chat_id, 2);

                $isOk = true;
            }
            else if (strpos($text, "HTML5 game") === 0)
            {
                // save information for later submission
                $mongodb->updateFreelanceworkMsgWithTypeId($chat_id, 3);

                $isOk = true;
            }
            else if (strpos($text, "Fully cross-platform game") === 0)
            {
                // save information for later submission
                $mongodb->updateFreelanceworkMsgWithTypeId($chat_id, 4);

                $isOk = true;
            }
            else if (strpos($text, "iOS application") === 0)
            {
                // save information for later submission
                $mongodb->updateFreelanceworkMsgWithTypeId($chat_id, 5);

                $isOk = true;
            }
            else if (strpos($text, "Landing page") === 0)
            {
                // save information for later submission
                $mongodb->updateFreelanceworkMsgWithTypeId($chat_id, 6);

                $isOk = true;
            }

            if ($isOk)
            {
                // proceed to next question
                sendTypingAction($chat_id);
                $replyKeyboardHide = array("hide_keyboard" => true);
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "Tell me about the idea",
                                    "reply_markup" => $replyKeyboardHide);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Freelancework_2);
            }
            else
            {
                // repeat the question again
                $state_id = State::Business_2;
                $text = "Freelance work";
                $mongodb->updateDocToStateCollection($chat_id, State::Business_2);

                goto PC;
            }
        }
        else if ($state_id == State::Freelancework_2)
        {
            // anything can go here as it's open-ended answer
            // save the answer
            $mongodb->updateFreelanceworkMsgWithIdeaText($chat_id, $text);

            // proceed to next question
            sendTypingAction($chat_id);

            $replyMarkup = array("keyboard" => array(array("💵 < $3,000"), array("💵💵 $8,500"), array("💰 $15,000"), array("💰💰💰 $30,000")));

            $parameters = array("chat_id" => $chat_id,
                                "text" => "What's your budget?",
                                "reply_markup" => $replyMarkup);
            apiRequestJson("sendMessage", $parameters);

            // save the state
            $mongodb->updateDocToStateCollection($chat_id, State::Freelancework_3);
        }
        else if ($state_id == State::Freelancework_3)
        {
            $isOk = false;

            if (strpos($text, "💵 < $3,000") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithBudgetTypeId($chat_id, 1);
                $isOk = true;
            }
            else if (strpos($text, "💵💵 $8,500") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithBudgetTypeId($chat_id, 2);
                $isOk = true;
            }
            else if (strpos($text, "💰 $15,000") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithBudgetTypeId($chat_id, 3);
                $isOk = true;
            }
            else if (strpos($text, "💰💰💰 $30,000") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithBudgetTypeId($chat_id, 4);
                $isOk = true;
            }

            // if received input properly, then proceed to next question
            if ($isOk)
            {
                sendTypingAction($chat_id);
                $replyMarkup = array("keyboard" => array(array("ASAP! 🏇", "1 month"), array("2-3 months"), array("<= 6 months"), array("<= 1 year")));
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "How much time do I have for development?",
                                    "reply_markup" => $replyMarkup);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Freelancework_4);
            }
        }
        else if ($state_id == State::Freelancework_4)
        {
            $isOk = false;

            if (strpos($text, "ASAP! 🏇") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithTimeTypeId($chat_id, 1);
                $isOk = true;
            }
            else if (strpos($text, "1 month") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithTimeTypeId($chat_id, 2);
                $isOk = true;
            }
            else if (strpos($text, "2-3 months") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithTimeTypeId($chat_id, 3);
                $isOk = true;
            }
            else if (strpos($text, "<= 6 months") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithTimeTypeId($chat_id, 4);
                $isOk = true;
            }
            else if (strpos($text, "<= 1 year") === 0)
            {
                $mongodb->updateFreelanceworkMsgWithTimeTypeId($chat_id, 5);
                $isOk = true;
            }

            // if received input properly, then proceed to next question
            if ($isOk)
            {
                sendTypingAction($chat_id);
                $replyKeyboardHide = array("hide_keyboard" => true);
                $parameters = array("chat_id" => $chat_id,
                                    "text" => "What's your e-mail for me to reach you back?",
                                    "reply_markup" => $replyKeyboardHide);
                apiRequestJson("sendMessage", $parameters);

                // proceed to next state
                $mongodb->updateDocToStateCollection($chat_id, State::Freelancework_5);
            }
        }
        else if ($state_id == State::Freelancework_5)
        {
            // anything can go here as it's open-ended answer
            // save the answer
            $mongodb->updateFreelanceworkMsgWithProposerEmail($chat_id, $text);

            // proceed to next question
            sendTypingAction($chat_id);
            $replyKeyboardHide = array("hide_keyboard" => true);
            $parameters = array("chat_id" => $chat_id,
                                "text" => "What's your first name?",
                                "reply_markup" => $replyKeyboardHide);
            apiRequestJson("sendMessage", $parameters);

            // save the state
            $mongodb->updateDocToStateCollection($chat_id, State::Freelancework_6);
        }
        else if ($state_id == State::Freelancework_6)
        {
            // anything can go here as it's open-ended answer
            // save the answer
            $mongodb->updateFreelanceworkMsgWithProposerFirstName($chat_id, $text);

            // time to send e-mail including all information of request back to Wasin :)
            // get document via chat_id
            $doc = $mongodb->getDocInFreelanceworkMsgCollection($chat_id);

            if (!empty($doc))
            {
                // proposer email is the most important field we need to have 
                if (isset($doc["proposerEmail"]))
                {
                    // send email
                    $result = sendEmailOfProposedFreelancework($doc["type_id"], $doc["ideaText"], $doc["budgetTypeId"], $doc["timeTypeId"], $doc["proposerEmail"], $doc["proposerFirstName"]);

                    // update status if it's sent successfully
                    if ($result)
                    {
                        $mongodb->updateFreelanceworkMsgWithStatus($chat_id, 1);
                    }
                }
            }
            
            // send notifying msg
            // regardless of result here, if something wrong happened, I'll check it and manually send it myself
            sendTypingAction($chat_id);
            $parameters = array("chat_id" => $chat_id,
                                "text" => "Ok, got it! 😉");
            apiRequestJson("sendMessage", $parameters);

            // send reply keyboard
            sendTypingAction($chat_id);
            $replyMarkup = array("keyboard" => array(array("👍")));
            $parameters = array("chat_id" => $chat_id,
                                "text" => "I will reach you back very soon. Thank you so much!",
                                "reply_markup" => $replyMarkup);
            apiRequestJson("sendMessage", $parameters);

            // save the state
            $mongodb->updateDocToStateCollection($chat_id, State::Freelancework_7);
        }
        else if ($state_id == State::Freelancework_7)
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

        // Business
        //  |_ Business Opportunity
        else if ($state_id == State::Business_Opportunity_1)
        {
            $isOk = false;

            if (strpos($text, "Tech startup") === 0)
            {
                // save information for later submission
                $mongodb->updateBusinessMsgWithTypeId($chat_id, 1);

                $isOk = true;
            }
            else if (strpos($text, "Game development") === 0)
            {
                // save information for later submission
                $mongodb->updateBusinessMsgWithTypeId($chat_id, 2);

                $isOk = true;
            }
            else if (strpos($text, "Tech startup + Game") === 0)
            {
                // save information for later submission
                $mongodb->updateBusinessMsgWithTypeId($chat_id, 3);

                $isOk = true;
            }
            else if (strpos($text, "Others") === 0)
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

            // time to send e-mail including all information of request back to Wasin :)
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
                        $mongodb->updateBusinessMsgWithStatus($chat_id, 1);
                    }
                }
            }
            
            // send notifying msg
            // regardless of result here, if something wrong happened, I'll check it and manually send it myself
            sendTypingAction($chat_id);
            $parameters = array("chat_id" => $chat_id,
                                "text" => "Ok, got it! 😉");
            apiRequestJson("sendMessage", $parameters);

            // send reply keyboard
            sendTypingAction($chat_id);
            $replyMarkup = array("keyboard" => array(array("👍")));
            $parameters = array("chat_id" => $chat_id,
                                "text" => "I will reach you back very soon. Thank you so much!",
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

        // Personal
        else if ($state_id == State::Personal_1)
        {
            $textResponse = "Alright 😉";

            if (strpos($text, "Absolutely! 😉") === 0)
            {
                $textResponse = "Cool! What do you want to know more about me?";
            }
            else if (strpos($text, "Nahh, maybe... I just want to know you more 😬") === 0)
            {
                $textResponse = "Not an issue 😉\nWhat do you want to know more about me then?";
            }
            // special case, programmatically restart the choice option screen again
            // when personal path finishes individual request, then it should go back to State::Personal_1 state, alongwith the set of $text to be exactly "startagain".
            else if (strpos($text, "startagain") === 0)
            {
                $textResponse = "Anything else?";
            }

            sendTypingAction($chat_id);
            $replyMarkup = array("keyboard" => array(array("Name", "Nick name"), array("🐶 List of my social networks"), array("💵 Freelancing rate"), array("📍Current location"), array("📦 Notable things I made"), array("📘 List of clients"), array("OKAY, enough of it! 😂")));
            $parameters = array("chat_id" => $chat_id,
                                "text" => $textResponse,
                                "reply_markup" => $replyMarkup);
            apiRequestJson("sendMessage", $parameters);

            // proceed to next state
            $mongodb->updateDocToStateCollection($chat_id, State::Personal_2);
        }
        else if ($state_id == State::Personal_2)
        {
            if (strpos($text, "Name") === 0)
            {
                $text = "/getname";
            }
            else if (strpos($text, "Nick name") === 0)
            {
                $text = "/getnickname";
            }
            else if (strpos($text, "🐶 List of my social networks") === 0)
            {
                $text = "/getsocial";
            }
            else if (strpos($text, "💵 Freelancing rate") === 0)
            {
                $text = "/getfreelancingrate";
            }
            else if (strpos($text, "📍Current location") === 0)
            {
                $text = "/getcurrentlocation";
            }
            else if (strpos($text, "📦 Notable things I made") === 0)
            {
                $text = "/getproductsmade";
            }
            else if (strpos($text, "📘 List of clients") === 0)
            {
                $text = "/getlistofclients";
            }
            else if (strpos($text, "OKAY, enough of it! 😂") === 0)
            {
                // restart the flow again
                $text = "/startoverfrompersonal";
            }

            // execute according to the text operation
            // change $text to text operation that needs to be carried out according to the above statements

            $isExecutedCommand = false;
            // getname
            if (strpos($text, "/getname") === 0)
            {
                // send answer
                sendTypingAction($chat_id);
                $parameters = array("chat_id" => $chat_id,
                                    "text"  =>  "Wasin Thonkaew");
                apiRequestJson("sendMessage", $parameters);

                $isExecutedCommand = true;
            }
            // getnickname
            else if (strpos($text, "/getnickname") === 0)
            {
                sendTypingAction($chat_id);

                $parameters = array("chat_id" => $chat_id,
                                    "text"  =>  "Best");
                apiRequestJson("sendMessage", $parameters);

                $isExecutedCommand = true;
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

                $isExecutedCommand = true;
            }
            // getfreelancingrate
            else if (strpos($text, "/getfreelancingrate") === 0)
            {
                sendTypingAction($chat_id);

                $parameters = array("chat_id" => $chat_id,
                                    "text"  =>  "28 USD / Hour, or flat rate depending on project 👌");
                apiRequestJson("sendMessage", $parameters);

                $isExecutedCommand = true;
            }
            // getcurrentlocation
            else if (strpos($text, "/getcurrentlocation") === 0)
            {
                sendFindingLocationAction($chat_id);

                $parameters = array("chat_id" => $chat_id,
                                    "latitude" => 29.611306,
                                    "longitude" => 106.291662);
                apiRequestJson("sendLocation", $parameters);

                $isExecutedCommand = true;
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

                $isExecutedCommand = true;
            }
            // getlistofclients
            else if (strpos($text, "/getlistofclients") === 0)
            {
                // send msg first
                {
                    sendTypingAction($chat_id);

                    $parameters = array("chat_id" => $chat_id,
                                        "text"  =>  "Only some of selected clients here...");
                    apiRequestJson("sendMessage", $parameters);
                }

                // aerothai
                {
                    // send upload photo action
                    sendUploadPhotoAction($chat_id);

                    {
                        // get the realpath of image file to serve to user
                        $realpath = realpath("./resources/aerothai-logo.png");

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
                        $realpath = realpath("./resources/playbasis-logo.png");

                        $parameters = array("chat_id" => $chat_id,
                                    "photo" => new CURLFile($realpath),
                                    "caption" => "Playbasis");
                        apiRequestSendPhoto($chat_id, $parameters);
                    }
                }

                $isExecutedCommand = true;
            }
            else if (strpos($text, "/startoverfrompersonal") === 0)
            {
                sendTypingAction($chat_id);

                $parameters = array("chat_id" => $chat_id,
                                    "text" => "I believe you know more about me now ✌️😀✌️");
                apiRequestJson("sendMessage", $parameters);

                // start it over
                $state_id = State::Normal;
                $mongodb->updateDocToStateCollection($chat_id, State::Normal);
                $text = "/bypassstart";
                goto PC;
            }

            // if command is executed then take user back to main choice screen
            if ($isExecutedCommand)
            {
                // take user back main chioce screen
                $text = "startagain";
                $state_id = State::Personal_1;
                $mongodb->updateDocToStateCollection($chat_id, State::Personal_1);
                goto PC;
            }
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
    Send email with all gathered information from proposed freelancework.

    @return True if sending email is successful, otherwise return false.
*/
function sendEmailOfProposedFreelancework($type_id, $ideaText, $budgetTypeId, $timeTypeId, $proposerEmail, $proposerFirstName)
{
    // type text
    $typeText = getTextFromFreelancework_TypeId($type_id);
    // budget text
    $budgetText = getTextFromFreelancework_BudgetTypeId($budgetTypeId);
    // time text
    $timeText = getTextFromFreelancework_TimeTypeId($timeTypeId);

    $to = 'haxpor@gmail.com';
    $subject = 'Freelance work Proposal from ' . $proposerFirstName;
    $headers = 'From: wasin@wasin.io' . "\r\n" .
            'Reply-To: ' . $proposerEmail . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

    $msg = "Project type:\r\n" . $typeText . "\r\n\r\n";
    $msg .= "Idea:\r\n" . $ideaText . "\r\n\r\n";
    $msg .= "Budget:\r\n" . $budgetText . "\r\n\r\n";
    $msg .= "Development time:\r\n" . $timeText . "\r\n\r\n";

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

/**
    Get text description from freelancework's type_id.

    @return Text description according to input of type_id.
*/
function getTextFromFreelancework_TypeId($type_id)
{
    if ($type_id == 1)
    {
        return "Mobile game";
    }
    else if ($type_id == 2)
    {
        return "PC game";
    }
    else if ($type_id == 3)
    {
        return "HTML5 game";
    }
    else if ($type_id == 4)
    {
        return "Fully cross-platform game";
    }
    else if ($type_id == 5)
    {
        return "iOS application";
    }
    else if ($type_id == 6)
    {
        return "Landing page";
    }
    else
    {
        // should not happen
        return "Unknown";
    }
}

/**
    Get text description from budgetTypeId.

    @return Text description according to input of budgetTypeId.
*/
function getTextFromFreelancework_BudgetTypeId($budgetTypeId)
{
    if ($budgetTypeId == 1)
    {
        return "Less than $3,000";
    }
    else if ($budgetTypeId == 2)
    {
        return "$8,500";
    }
    else if ($budgetTypeId == 3)
    {
        return "$15,000";
    }
    else if ($budgetTypeId == 4)
    {
        return "$30,000";
    }
    else
    {
        // should not happen
        return "Unknown";
    }
}

/**
    Get text description from timeTypeId.

    @return Text description according to input of timeTypeId.
*/
function getTextFromFreelancework_TimeTypeId($timeTypeId)
{
    if ($timeTypeId == 1)
    {
        return "ASAP!";
    }
    else if ($timeTypeId == 2)
    {
        return "1 month";
    }
    else if ($timeTypeId == 3)
    {
        return "2-3 months";
    }
    else if ($timeTypeId == 4)
    {
        return "<= 6 months";
    }
    else if ($timeTypeId == 5)
    {
        return "<= 1 year";
    }
    else
    {
        // should not happen
        return "Unknown";
    }
}

?>
