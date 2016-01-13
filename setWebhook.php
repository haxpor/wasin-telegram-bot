#!/usr/bin/env php
<?php

define('WEBHOOK_URL', 'https://wasin.io/projs/wasin-telegram-bot/api.php');

if (php_sapi_name() == 'cli') {
	
  include('functions.php');
  $configs = include 'config.php';

  // if run from console, set or delete webhook
  apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
  exit;
}
?>