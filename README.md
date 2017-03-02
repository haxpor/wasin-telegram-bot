# wasin-telegram-bot
WasinBot, a bot interacting with Wasin on Telegram in friendly or for business purpose.

# How to
1. First you need to create a new telegram bot on Telegram by chatting with BotFather (@BotFather) on Telegram. It will lead you through all the steps of inputing bot name, info, and most importantly getting **bot token**.
2. Set bot token, and webhook url via environment variables as follows.
   * bot token - `export WASIN_TELEGRAM_BOT_TOKEN=...` replace `...` with bot token you got from step 1.
   * webhook url - `export WASIN_TELEGRAM_BOT_WEBHOOK_URL=https://.../api.php` replace `...` with your URL leading to serving file `api.php`.
3. Set webhook by executing `php setWebhook.php`. You should receive success message.
4. Done. Feel free to interact with the bot on telegram.

# License

[![Creative Commons License](https://i.creativecommons.org/l/by-nc-sa/4.0/88x31.png)](http://creativecommons.org/licenses/by-nc-sa/4.0/)  
This work is licensed under a [Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License](https://github.com/haxpor/wasin-telegram-bot/blob/master/LICENSE).