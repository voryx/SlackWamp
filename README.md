SlackWamp
===========

SlackWamp is a [WAMP v2](http://wamp.ws/) (Web Application Messaging Protocol) Client that exposes the entire [Slack API](https://api.slack.com/) (Web API and Real Time Messaging API) as WAMP topics and RPC calls.


### Quick Start with Composer

Create a directory for the test project

      $ mkdir slackwamp

Switch to the new directory

      $ cd slackwamp

Download Composer [more info](https://getcomposer.org/doc/00-intro.md#downloading-the-composer-executable)

      $ curl -sS https://getcomposer.org/installer | php
      
Download SlackWamp and dependencies

      $ php composer.phar require "voryx/slackwamp":"dev-master"

If you need a WAMP router to test with, then start the sample with:

      $ php vendor/voryx/thruway/Examples/SimpleWsServer.php
    
Thruway is now running on 127.0.0.1 port 9090.

### PHP SlackWamp Client Usage

```php
<?php
require_once __DIR__ . "/vendor/autoload.php";

$token = 'your_slack_token'; //your slack token https://my.slack.com/services/new/bot
$realm = 'realm1'; // WAMP Realm
$uri   = 'ws://127.0.0.1:9090/'; // WAMP Router URI

$client = new \SlackWamp\SlackClient($token, $realm);
$client->addTransportProvider(new PawlTransportProvider($uri));
$client->start();
```

### Subscribing to messages

You'll be able to subscribe to any [Slack RTM Event](https://api.slack.com/rtm) from any WAMP client, with the same topic name.

The response includes the entire Slack event message.

### Making an RPC call

This client maps all of Slack's [Web API Methods](https://api.slack.com/methods) to WAMP RPCs.
 
For example, you if wanted to change your [presence](https://api.slack.com/methods/users.setPresence), the Web API call's name is `users.setPresence`.  The WAMP RPC uses the same name except that it's all lower case and the arguments are passed through argsKW.

ie:
```PHP

$session->call("users.setpresence", [], ["presence" => "away"])->then(function ($res) {
    print_r($res[0]);
});
    
```    

