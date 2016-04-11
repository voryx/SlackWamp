<?php

use SlackWamp\SlackClient;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

require __DIR__.'/../vendor/autoload.php';

$token = 'your_slack_token'; //your slack token https://my.slack.com/services/new/bot
$realm = 'realm1'; // WAMP Realm
$uri   = 'ws://127.0.0.1:9090/'; // WAMP Router URI

$router = new Router();
$router->addTransportProvider(new RatchetTransportProvider("127.0.0.1", 9090));

$client = new SlackClient($token, $realm, $router->getLoop());
$router->addInternalClient($client);

$router->start();
