<?php


use Thruway\Transport\PawlTransportProvider;

require '../vendor/autoload.php';

$token = 'your_slack_token'; //your slack token https://my.slack.com/services/new/bot
$realm = 'realm1'; // WAMP Realm
$uri   = 'ws://127.0.0.1:9090/'; // WAMP Router URI

$client = new \SlackWamp\SlackClient($token, $realm);
$client->addTransportProvider(new PawlTransportProvider($uri));
$client->start();