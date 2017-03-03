# wasin-telegram-bot
WasinBot, a bot interacting with Wasin on Telegram in friendly or for business purpose.

# How to
1. First you need to create a new telegram bot on Telegram by chatting with BotFather (@BotFather) on Telegram. It will lead you through all the steps of inputing bot name, info, and most importantly getting **bot token**.
2. Set bot token, and webhook url via environment variables as follows.
   * bot token - `export WASIN_TELEGRAM_BOT_TOKEN=...` replace `...` with bot token you got from step 1.
   * webhook url - `export WASIN_TELEGRAM_BOT_WEBHOOK_URL=https://.../api.php` replace `...` with your URL leading to serving file `api.php`.
3. Set webhook by executing `php setWebhook.php`. You should receive success message.
4. Done. Feel free to interact with the bot on telegram.

# (Optional) Set Proxy

If you need to set proxy for the bot, you can do so by setting `WASIN_TELEGRAM_BOT_PROXY` as environment variable as follows

`export WASIN_TELEGRAM_BOT_PROXY=127.0.0.1:1087`

If it's set, then the bot will automatically make a request to such proxy IP address, and port through HTTP tunnel.

# Setting Environment Variables On macOS

It's very painful to set environment variables on macOS especially on Sierra (tested on 10.12.4) as all approaches on the Internet aren't working.  
The only way to make it work is to use `SetEnv` from `mod_env` of Apache. See its official doc [here](http://httpd.apache.org/docs/current/mod/mod_env.html).

Define it like this

```
<Directory "/Users/haxpor/Sites/">
    Options Indexes MultiViews FollowSymLinks
    SetEnv WASIN_TELEGRAM_BOT_TOKEN <your-bot-token-here>
    SetEnv WASIN_TELEGRAM_BOT_PROXY 127.0.0.1:1087
</Directory>
```

# Delete Webhook URL

You can delete webhook URL you've set with `php setWebhook.php delete`.

# License

[![Creative Commons License](https://i.creativecommons.org/l/by-nc-sa/4.0/88x31.png)](http://creativecommons.org/licenses/by-nc-sa/4.0/)  
This work is licensed under a [Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License](https://github.com/haxpor/wasin-telegram-bot/blob/master/LICENSE).