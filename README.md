SlackWamp
===========

SlackWamp is a [WAMP v2](http://wamp.ws/) (Web Application Messaging Protocol) bridge that exposes the entire [Slack API](https://api.slack.com/) (Web API and Real Time Messaging API) as WAMP topics and RPC calls.

SlackWamp is written in PHP and uses the [Thruway](https://github.com/voryx/RxThruwayClient) WAMP client, but can work with any of the available [WAMP routers](http://wamp.ws/implementations/).


### Install with Composer
```bash
$ composer require "voryx/slack-wamp":"dev-master"
```
### PHP SlackWamp Bridge Usage

```php
<?php
require_once __DIR__ . "/vendor/autoload.php";

$token    = 'your_slack_token';
$botToken = 'your_slack_token_with_rtm:stream';
$wamp = new \Rx\Thruway\Client('wss://localhost:9090', 'realm1');

(new \SlackWamp\APIBridge($wamp, $token))->subscribe();
(new \SlackWamp\RealTimeBridge($wamp, $botToken))->subscribe();

```

### Subscribing to messages

You'll be able to subscribe to any [Slack RTM Event](https://api.slack.com/rtm) from any WAMP client, with the same topic name.

The response includes the entire Slack event message.

### Making an RPC call

This bridge maps all of Slack's [Web API Methods](https://api.slack.com/methods) to WAMP RPCs.
 
For example, you if wanted to change your [presence](https://api.slack.com/methods/users.setPresence), the Web API call's name is `users.setPresence`.  The WAMP RPC uses the same name except that it's all lower case and the arguments are passed through argsKW.

ie:
```PHP

$wamp->call("users.setpresence", [], ["presence" => "away"])->subscribe(function ($res) {
    print_r($res[0]);
});
    
```    