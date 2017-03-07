<?php

require __DIR__ . '/../vendor/autoload.php';

$token    = 'your_slack_token';
$botToken = 'your_slack_token_with_rtm:stream';
$wamp     = new \Rx\Thruway\Client('wss://localhost:9090', 'realm1');

(new \SlackWamp\APIBridge($wamp, $token))->subscribe(
    function ($c) use ($wamp) {
        echo json_encode($c), PHP_EOL;
    },
    function (Throwable $e) {
        echo $e->getMessage();
    },
    function () {
        echo 'complete', PHP_EOL;
    });

(new \SlackWamp\RealTimeBridge($wamp, $botToken))->subscribe(
    function ($c) use ($wamp) {
        echo json_encode($c), PHP_EOL;
    },
    function (Throwable $e) {
        echo $e->getMessage();
    },
    function () {
        echo 'complete', PHP_EOL;
    });