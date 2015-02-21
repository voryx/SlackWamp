<?php


require '../vendor/autoload.php';

use Thruway\ClientSession;

$client = new \Thruway\Peer\Client("realm1");
$client->on('open', function (ClientSession $session) {
    $session->call("users.setpresence", [], ["presence" => "away"])->then(function ($res) {
        print_r($res[0]);
    });
}
);

$client->addTransportProvider(new \Thruway\Transport\PawlTransportProvider());
$client->start();