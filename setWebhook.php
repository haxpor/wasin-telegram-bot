#!/usr/bin/env php
<?php

if (php_sapi_name() == 'cli') {
	
	include('functions.php');
	include('config.php');
	include('configapikey.php');

	if (PRODUCTION)
	{
		define('WEBHOOK_URL', 'https://your/production/webhook/url.php');
	}
	else
	{
		define('WEBHOOK_URL', 'https://your/development/webhook/url.php');
	}

	// if run from console, set or delete webhook
	apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
	exit;
}
?>