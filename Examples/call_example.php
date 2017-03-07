<?php

require __DIR__ . '/../vendor/autoload.php';

$wamp = new \Rx\Thruway\Client('wss://localhost:9090', 'realm1');

$wamp->call('slack.channels.join', [], ['name' => 'user-1'])
    ->pluck(0, 0, 'channel', 'id')
    ->flatMap(function ($channel) use ($wamp) {
        return $wamp->call('slack.chat.postMessage', [], [
            'channel'  => $channel,
            'text'     => 'first message!',
            'username' => 'user-1',
            'as_user'  => false
        ]);
    })
    ->subscribe(
        function ($res) {
            print_r($res[0]);
        },
        function (Throwable $e) {
            echo $e->getMessage();
        },
        function () {
            echo 'complete', PHP_EOL;
        }
    );
