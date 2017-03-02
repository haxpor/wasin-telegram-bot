#!/usr/bin/env php
<?php

if (php_sapi_name() == 'cli') {
	
	include('functions.php');
	include('configapikey.php');

	// get webhook url from environment variable
	define('WEBHOOK_URL', getenv('WASIN_TELEGRAM_BOT_WEBHOOK_URL'));

	// if run from console, set or delete webhook
	apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
	exit;
}
?>
