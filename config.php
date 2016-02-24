<?php
	define('BOT_TOKEN', '133907604:AAGgVK008Q8EXOLpkKlnBvhDkIBHgYGv4ak');
	define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

	// 0 for development environment, it will use ngrok
	// 1 for production environment, the script should be readily hosted on production server
	define('PRODUCTION', 0);
?>