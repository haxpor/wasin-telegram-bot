#!/usr/bin/env php
<?php

if (php_sapi_name() == 'cli') {
	
	include('config.php');
	include('functions.php');
	include('configapikey.php');

	if (PRODUCTION)
	{
		// the php script to link to is api.php so you don't need to change this part
		define('WEBHOOK_URL', 'https://your/production/webhook/api.php');
	}
	else
	{
		// the php script to link to is api.php so you don't need to change this part
		define('WEBHOOK_URL', 'https://your/development/webhook/api.php');
	}

	// if run from console, set or delete webhook
	apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
	exit;
}
?>