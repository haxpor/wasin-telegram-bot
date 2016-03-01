#!/usr/bin/env php
<?php

if (php_sapi_name() == 'cli') {
	
	include('functions.php');
	$configs = include 'config.php';

	if (PRODUCTION)
	{
		define('WEBHOOK_URL', 'https://wasin.io/projs/wasin-telegram-bot/api.php');
	}
	else
	{
		define('WEBHOOK_URL', 'https://c71b79e5.ngrok.io/~haxpor/wasin-telegram-bot/api.php');
	}

	// if run from console, set or delete webhook
	apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
	exit;
}
?>