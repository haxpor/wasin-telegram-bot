<?php
	// get bot token from environment variable
	define('BOT_TOKEN', getenv('WASIN_TELEGRAM_BOT_TOKEN'));

	define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
?>