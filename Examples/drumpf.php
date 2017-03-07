<?php

require __DIR__ . '/../vendor/autoload.php';

$wamp = new \Rx\Thruway\Client('wss://localhost:9090', 'realm1');

//Listen for the word 'trump' in any channel that this bot is in
$trumpMessage = $wamp->topic('slack.message')
    ->pluck(0, 0)
    ->filter(function ($message) {
        return isset($message->text) && $pos = strpos(strtolower($message->text), 'trump') !== false;
    });

//Replay with a `drumpf` message
$trumpMessage->flatMap(function ($message) use ($wamp) {
    return $wamp->call('slack.chat.postMessage', [], [
        'channel'  => $message->channel,
        'text'     => "I'm pretty sure you meant 'Drumpf'",
        'username' => 'Drumpf Man',
        'icon_url' => 'https://lh3.googleusercontent.com/gho88Sb4ofWFxIl7tRELks_EOr-BNSGKVMHOg5kw5NfqOLFLdgUw3w7PdPqnEmPxNvbgbFDp1Q=s128-h128-e365',
    ]);
})
    ->subscribe();
